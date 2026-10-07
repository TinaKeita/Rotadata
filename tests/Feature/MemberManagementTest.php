<?php

namespace Tests\Feature;

use App\Mail\MemberAddedMail;
use App\Mail\MemberWelcomeMail;
use App\Models\Costume;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
        $costume = Costume::create(['name' => 'Krekls', 'group_id' => $this->group->id]);
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

    // students pats izdzēsa savu kontu – skolotājs to nedrīkst ne atjaunot, ne neatgriezeniski izdzēst
    public function test_teacher_cannot_restore_or_purge_a_self_deleted_account(): void
    {
        $marta = $this->student($this->group);

        $this->actingAs($marta)->delete(route('profile.destroy'), ['password' => 'password']);
        $this->assertSoftDeleted($marta);

        $this->actingAs($this->teacher)->post(route('admin.members.restore', $marta));
        $this->assertSoftDeleted($marta);

        $this->actingAs($this->teacher)->delete(route('admin.members.force-destroy', $marta), ['password' => 'password']);
        $this->assertSoftDeleted($marta);
    }

    // grupas atjaunošana atdzīvina tikai tos, kurus deaktivizēja šīs grupas dzēšana
    public function test_restoring_a_group_does_not_bring_back_self_deleted_accounts(): void
    {
        $marta = $this->student($this->group);
        $roberts = $this->student($this->group);

        $this->actingAs($marta)->delete(route('profile.destroy'), ['password' => 'password']);

        $this->actingAs($this->teacher)->delete(route('admin.group.destroy'), [
            'name' => $this->group->name,
            'password' => 'password',
        ]);
        $this->assertSoftDeleted($roberts);

        $this->actingAs($this->teacher)->post(route('admin.group.restore'));

        $this->assertNotSoftDeleted($roberts);
        $this->assertSoftDeleted($marta);
    }

    // students ir grupās A un B; A tiek dzēsta, pēc tam B skolotājs studentu izņem –
    // A atjaunošana nedrīkst atcelt B skolotāja lēmumu, un A skolotājs viņu atjaunot nevar
    public function test_restoring_a_group_does_not_undo_another_teachers_removal(): void
    {
        $groupB = $this->otherGroup();
        $marta = $this->student($this->group);
        $groupB->members()->attach($marta->id);

        $this->actingAs($this->teacher)->delete(route('admin.group.destroy'), [
            'name' => $this->group->name,
            'password' => 'password',
        ]);
        $this->assertNotSoftDeleted($marta);

        $teacherB = $groupB->admin;
        $this->actingAs($teacherB)->delete(route('admin.members.destroy', $marta));
        $this->assertSoftDeleted($marta);
        $this->assertSame($groupB->id, $marta->fresh()->deactivated_with_group_id);

        $this->actingAs($this->teacher)->post(route('admin.group.restore'));
        $this->assertSoftDeleted($marta);

        $this->actingAs($this->teacher)->post(route('admin.members.restore', $marta));
        $this->assertSoftDeleted($marta);
    }

    // skolotājam ir divas grupas; atvērta grupa B, students ir tikai grupā A – grupas B tērpu viņam izsniegt nevar
    public function test_teacher_cannot_hand_out_to_someone_outside_the_open_group(): void
    {
        $groupB = Group::create(['name' => 'Deju kopa', 'admin_id' => $this->teacher->id]);
        $costume = Costume::create(['name' => 'Svārki', 'group_id' => $groupB->id]);
        $costume->addItems(1);
        $item = $costume->items()->firstOrFail();

        $marta = $this->student($this->group);

        $this->actingAs($this->teacher)
            ->withSession([User::CURRENT_GROUP_KEY => $groupB->id])
            ->post(route('admin.members.hand-out', $marta), ['item_id' => $item->id]);

        $this->assertNull($item->fresh()->assigned_to);
    }

    // viens un tas pats cilvēks grupā var būt tikai vienreiz – to garantē datubāze, ne tikai kods
    public function test_group_membership_cannot_be_duplicated(): void
    {
        $marta = $this->student($this->group);

        $this->expectException(QueryException::class);
        $this->group->members()->attach($marta->id);
    }

    // e-pastu salīdzina bez lielo/mazo burtu atšķirības – esošais konts tiek pievienots, nevis dublēts
    public function test_email_case_does_not_create_a_duplicate_account(): void
    {
        $marta = User::factory()->create(['email' => 'marta@example.com']);
        $marta->assignRole('member');

        $this->actingAs($this->teacher)->post(route('admin.members.store'), [
            'members' => [['name' => 'Marta', 'email' => 'Marta@Example.COM']],
        ]);

        $this->assertSame(1, User::whereRaw('lower(email) = ?', ['marta@example.com'])->count());
        $this->assertTrue($marta->fresh()->inGroup($this->group));
    }

    // ja uzaicinājuma e-pastu neizdevās nosūtīt, pagaidu parole netiek ielikta sesijā
    public function test_temporary_password_is_not_stored_in_the_session(): void
    {
        Str::createRandomStringsUsing(fn () => 'TempPass1234');
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Mail server is down'));

        try {
            $this->actingAs($this->teacher)->post(route('admin.members.store'), [
                'members' => [['name' => 'Marta', 'email' => 'marta@example.com']],
            ]);

            $this->assertNotNull(User::where('email', 'marta@example.com')->value('invite_email_failed_at'));
            // _token arī veidojas ar Str::random, tāpēc to neskatāmies
            $this->assertStringNotContainsString('TempPass1234', json_encode(Arr::except(session()->all(), '_token')));
        } finally {
            Str::createRandomStringsNormally();
        }
    }
}
