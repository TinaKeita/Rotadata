<?php

namespace Tests\Feature;

use App\Mail\MemberAddedMail;
use App\Mail\MemberWelcomeMail;
use App\Models\Costume;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// skolotājs pievieno, izņem un atjauno studentus
class MemberManagementTest extends TestCase
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

    private function student(Group $group): User
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $group->members()->attach($user->id);

        return $user;
    }

    private function otherGroup(): Group
    {
        $other = User::factory()->create();
        $other->assignRole('admin');

        return Group::create(['name' => 'Cita grupa', 'admin_id' => $other->id]);
    }

    // jauns e-pasts – izveido kontu ar pagaidu paroli un nosūta uzaicinājumu
    public function test_teacher_adds_new_students(): void
    {
        $this->actingAs($this->teacher)->get(route('admin.members.create'))->assertOk();

        $this->actingAs($this->teacher)->post(route('admin.members.store'), [
            'members' => [
                ['name' => 'Marta', 'email' => 'marta@example.com'],
                ['name' => 'Roberts', 'email' => 'roberts@example.com'],
            ],
        ])->assertRedirect(route('admin.members.index'));

        $marta = User::where('email', 'marta@example.com')->firstOrFail();
        $this->assertTrue($marta->inGroup($this->group));
        $this->assertTrue($marta->hasRole('member'));
        $this->assertTrue($marta->must_change_password);
        $this->assertSame(2, $this->group->members()->count());
        Mail::assertSent(MemberWelcomeMail::class, 2);
    }

    // esošs e-pasts – otru kontu neveido, tikai pievieno grupai
    public function test_existing_account_is_added_not_duplicated(): void
    {
        $existing = $this->student($this->otherGroup());

        $this->actingAs($this->teacher)->post(route('admin.members.store'), [
            'members' => [['name' => 'Cits vārds', 'email' => $existing->email]],
        ]);

        $this->assertSame(1, User::where('email', $existing->email)->count());
        $this->assertTrue($existing->inGroup($this->group));
        Mail::assertSent(MemberAddedMail::class);
        Mail::assertNotSent(MemberWelcomeMail::class);
    }

    // students tikai šajā grupā – konts tiek deaktivizēts, tērpi atbrīvoti, un to var atjaunot
    public function test_removing_a_student_deactivates_and_restore_brings_them_back(): void
    {
        $marta = $this->student($this->group);
        $costume = Costume::create(['name' => 'Krekls', 'quantity' => 0, 'group_id' => $this->group->id]);
        $costume->addItems(1);
        $item = $costume->items()->firstOrFail();
        $item->assignTo($marta, $this->teacher);

        $this->actingAs($this->teacher)->delete(route('admin.members.destroy', $marta));

        $this->assertSoftDeleted($marta);
        $this->assertNull($item->fresh()->assigned_to);

        $this->actingAs($this->teacher)->post(route('admin.members.restore', $marta));

        $this->assertNotSoftDeleted($marta);
        $this->assertTrue($marta->fresh()->inGroup($this->group));
    }

    // students arī citā grupā – tikai atsaista no šīs grupas, konts paliek
    public function test_removing_a_student_who_is_in_another_group_keeps_the_account(): void
    {
        $other = $this->otherGroup();
        $marta = $this->student($this->group);
        $other->members()->attach($marta->id);

        $this->actingAs($this->teacher)->delete(route('admin.members.destroy', $marta));

        $this->assertNotSoftDeleted($marta);
        $this->assertFalse($marta->inGroup($this->group));
        $this->assertTrue($marta->inGroup($other));
    }

    // cits skolotājs nedrīkst skatīt vai izņemt šīs grupas studentu
    public function test_another_teacher_cannot_see_or_remove_my_student(): void
    {
        $marta = $this->student($this->group);
        $otherTeacher = $this->otherGroup()->admin;

        $this->actingAs($otherTeacher)->get(route('admin.members.show', $marta))->assertRedirect(route('dashboard'));
        $this->actingAs($otherTeacher)->delete(route('admin.members.destroy', $marta))->assertRedirect(route('dashboard'));

        $this->assertNotSoftDeleted($marta);
        $this->assertTrue($marta->inGroup($this->group));
    }
}
