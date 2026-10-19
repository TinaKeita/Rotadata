<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// ikdienas fona darbi: dzēsto grupu un kontu galīga tīrīšana pēc 30 dienām un koncertu gatavības fiksēšana
class ScheduledTasksTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('admin');
        $this->group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $this->teacher->id]);
    }

    public function test_all_jobs_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('groups:purge')
            ->expectsOutputToContain('members:purge')
            ->expectsOutputToContain('events:snapshot')
            ->assertSuccessful();
    }

    public function test_deleted_group_is_purged_only_after_the_recovery_window(): void
    {
        $this->group->delete();

        $this->travel(Group::PURGE_AFTER_DAYS - 1)->days();
        $this->artisan('groups:purge')->assertSuccessful();
        $this->assertNotNull(Group::withTrashed()->find($this->group->id));

        $this->travel(2)->days();
        $this->artisan('groups:purge')->assertSuccessful();
        $this->assertNull(Group::withTrashed()->find($this->group->id));
    }

    public function test_self_deleted_account_is_purged_only_after_the_recovery_window(): void
    {
        $marta = User::factory()->create();
        $marta->assignRole('member');
        $this->group->members()->attach($marta->id);

        $this->actingAs($marta)->delete(route('profile.destroy'), ['password' => 'password']);
        $this->assertSoftDeleted($marta);

        $this->travel(Group::PURGE_AFTER_DAYS - 1)->days();
        $this->artisan('members:purge')->assertSuccessful();
        $this->assertNotNull(User::withTrashed()->find($marta->id));

        $this->travel(2)->days();
        $this->artisan('members:purge')->assertSuccessful();
        $this->assertNull(User::withTrashed()->find($marta->id));
    }

    // grupas dzēšanā deaktivizētos dalībniekus members:purge neaiztiek – tos iztīra groups:purge kopā ar grupu
    public function test_members_deactivated_with_a_group_wait_for_the_group_purge(): void
    {
        $marta = User::factory()->create();
        $marta->assignRole('member');
        $this->group->members()->attach($marta->id);

        $this->actingAs($this->teacher)->delete(route('admin.group.destroy'), [
            'name' => $this->group->name,
            'password' => 'password',
        ]);
        $this->assertSoftDeleted($marta);

        $this->travel(Group::PURGE_AFTER_DAYS + 1)->days();
        $this->artisan('members:purge')->assertSuccessful();
        $this->assertNotNull(User::withTrashed()->find($marta->id));
    }

    public function test_finished_concert_gets_a_readiness_snapshot(): void
    {
        $past = Event::create([
            'group_id' => $this->group->id,
            'title' => 'Rudens koncerts',
            'starts_at' => now()->addHour(),
            'created_by' => $this->teacher->id,
        ]);
        $upcoming = Event::create([
            'group_id' => $this->group->id,
            'title' => 'Ziemas koncerts',
            'starts_at' => now()->addMonth(),
            'created_by' => $this->teacher->id,
        ]);

        $this->travel(2)->hours();
        Event::snapshotFinished();

        $this->assertNotNull($past->fresh()->readiness_snapshot);
        $this->assertNull($upcoming->fresh()->readiness_snapshot);
    }
}
