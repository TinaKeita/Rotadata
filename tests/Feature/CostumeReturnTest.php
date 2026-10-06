<?php

namespace Tests\Feature;

use App\Models\Costume;
use App\Models\CostumeItem;
use App\Models\Group;
use App\Models\User;
use App\Notifications\StudentLeftGroupNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// tērpu atdošana (students pats vai skolotājs), grupas pamešana un piekļuve citas grupas tērpiem
class CostumeReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private Group $group;
    private Costume $costume;
    private CostumeItem $item;
    private User $marta;
    private User $roberts;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('admin');
        $this->group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $this->teacher->id]);

        $this->otherTeacher = User::factory()->create();
        $this->otherTeacher->assignRole('admin');
        Group::create(['name' => 'Cita grupa', 'admin_id' => $this->otherTeacher->id]);

        $this->costume = Costume::create(['name' => 'Krekls', 'quantity' => 0, 'group_id' => $this->group->id]);
        $this->costume->addItems(1);
        $this->item = $this->costume->items()->firstOrFail();

        foreach (['marta', 'roberts'] as $name) {
            $this->$name = User::factory()->create();
            $this->$name->assignRole('member');
            $this->group->members()->attach($this->$name->id);
        }

        $this->item->assignTo($this->marta, $this->teacher);
    }

    public function test_student_returns_their_own_item(): void
    {
        $this->actingAs($this->marta)->post(route('members.costumes.unassign', $this->item))
            ->assertSessionHas('success');

        $this->assertNull($this->item->fresh()->assigned_to);
        $this->assertSame('self', $this->item->assignments()->first()->return_note);
    }

    public function test_student_cannot_return_someone_elses_item(): void
    {
        $this->actingAs($this->roberts)->post(route('members.costumes.unassign', $this->item));

        $this->assertSame($this->marta->id, $this->item->fresh()->assigned_to);
    }

    public function test_teacher_takes_an_item_back(): void
    {
        $this->actingAs($this->teacher)->post(route('admin.costumes.items.unassign', $this->item));

        $this->assertNull($this->item->fresh()->assigned_to);
        $this->assertSame('admin', $this->item->assignments()->first()->return_note);
    }

    public function test_handed_out_item_cannot_be_deleted(): void
    {
        $this->actingAs($this->teacher)->delete(route('admin.costumes.items.destroy', $this->item))
            ->assertSessionHas('error');

        $this->assertModelExists($this->item);
    }

    // cits skolotājs nedrīkst ne skatīt, ne mainīt, ne dzēst šīs grupas tērpus
    public function test_another_teacher_cannot_touch_this_groups_costumes(): void
    {
        $this->actingAs($this->otherTeacher);

        $this->get(route('admin.costumes.show', $this->costume))->assertRedirect(route('dashboard'));
        $this->post(route('admin.costumes.items.unassign', $this->item))->assertRedirect(route('dashboard'));
        $this->delete(route('admin.costumes.destroy', $this->costume))->assertRedirect(route('dashboard'));

        $this->assertModelExists($this->costume);
        $this->assertSame($this->marta->id, $this->item->fresh()->assigned_to);
    }

    public function test_students_cannot_open_teacher_pages(): void
    {
        $this->actingAs($this->marta)->get(route('admin.dashboard'))->assertRedirect(route('dashboard'));
        $this->actingAs($this->marta)->get(route('admin.costumes.show', $this->costume))->assertRedirect(route('dashboard'));
    }

    // grupu var pamest tikai tad, kad visi tās tērpi atdoti; skolotājs saņem paziņojumu
    public function test_student_leaves_group_only_after_returning_items(): void
    {
        Notification::fake();

        $this->actingAs($this->marta)->post(route('members.costumes.leave', $this->group))->assertSessionHas('error');
        $this->assertTrue($this->marta->inGroup($this->group));

        $this->item->release($this->marta, 'self');

        $this->actingAs($this->marta)->post(route('members.costumes.leave', $this->group))->assertSessionHas('success');
        $this->assertFalse($this->marta->inGroup($this->group));
        Notification::assertSentTo($this->teacher, StudentLeftGroupNotification::class);
    }
}
