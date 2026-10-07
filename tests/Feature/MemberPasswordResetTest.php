<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Notifications\ResetPasswordLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// paroli maina tikai pats konta īpašnieks ar e-pasta saiti – arī studenti; skolotājs paroli neredz un nemaina
class MemberPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $ilze;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->ilze = User::factory()->create();
        $this->ilze->assignRole('admin');
        $group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $this->ilze->id]);

        $this->student = User::factory()->create(['email' => 'anna@example.com', 'must_change_password' => true]);
        $this->student->assignRole('member');
        $group->members()->attach($this->student->id);
    }

    // students pats pieprasa saiti un ar to izvēlas jaunu paroli
    public function test_student_can_reset_password_with_email_link(): void
    {
        $this->post(route('password.email'), ['email' => 'anna@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($this->student, ResetPasswordLinkNotification::class, function ($notification) {
            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => 'anna@example.com',
                'password' => 'Jauna-parole-123',
                'password_confirmation' => 'Jauna-parole-123',
            ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

            return true;
        });

        $this->student->refresh();
        $this->assertTrue(Hash::check('Jauna-parole-123', $this->student->password));
        $this->assertFalse($this->student->must_change_password);
    }

    // pagaidu parole der līdz termiņam; pēc tam pieslēgties ar to vairs nevar ne pieslēgšanās lapā, ne skenējot
    public function test_temporary_password_stops_working_after_it_expires(): void
    {
        $this->student->update(['temporary_password_expires_at' => now()->addDay()]);

        $this->post(route('login'), ['email' => 'anna@example.com', 'password' => 'password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($this->student);
        $this->post(route('logout'));

        $this->travel(2)->days();

        $this->post(route('login'), ['email' => 'anna@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => User::TEMPORARY_PASSWORD_EXPIRED_MESSAGE]);
        $this->assertGuest();

        $costume = \App\Models\Costume::create(['name' => 'Krekls', 'group_id' => $this->student->memberGroups()->first()->id]);
        $costume->addItems(1);
        $item = $costume->items()->first();

        $this->flushSession();

        $this->post(route('scan.authenticate', $item->qr_code), ['email' => 'anna@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => User::TEMPORARY_PASSWORD_EXPIRED_MESSAGE]);
        $this->assertGuest();
    }

    // jaunā skolotāja izveidotā kontā ir termiņš; pēc savas paroles izvēles termiņš vairs neattiecas
    public function test_choosing_own_password_clears_the_expiry(): void
    {
        $this->actingAs($this->ilze)->post(route('admin.members.store'), [
            'members' => [['name' => 'Jānis', 'email' => 'janis@example.com']],
        ]);

        $janis = User::where('email', 'janis@example.com')->firstOrFail();
        $this->assertTrue($janis->temporary_password_expires_at->between(now()->addDays(6), now()->addDays(8)));

        $this->actingAs($janis)->put(route('password.change.update'), [
            'name' => 'Jānis',
            'password' => 'Jauna-parole-123',
            'password_confirmation' => 'Jauna-parole-123',
        ]);

        $janis->refresh();
        $this->assertFalse($janis->must_change_password);
        $this->assertNull($janis->temporary_password_expires_at);
        $this->assertFalse($janis->temporaryPasswordExpired());
    }

    // lapa neatklāj, vai šāds konts eksistē – atbilde ir tāda pati kā esošam kontam
    public function test_unknown_email_gets_the_same_answer(): void
    {
        $known = $this->post(route('password.email'), ['email' => 'anna@example.com']);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $unknown->assertSessionHasNoErrors();
        $this->assertSame(session('status'), $known->getSession()->get('status'));
    }

    // "Resend invite" nosūta saiti studentam, bet skolotājs paroli nemaina un neredz
    public function test_resend_invite_sends_link_without_changing_password(): void
    {
        $this->actingAs($this->ilze)
            ->post(route('admin.members.resend-invite', $this->student))
            ->assertSessionHas('success');

        Notification::assertSentTo($this->student, ResetPasswordLinkNotification::class);
        $this->assertTrue(Hash::check('password', $this->student->fresh()->password));
    }

    // skolotājs, kurš ir arī citas grupas dalībnieks, savu paroli atjauno pats ar e-pasta saiti
    public function test_teacher_who_is_a_member_elsewhere_can_reset_own_password(): void
    {
        $janis = User::factory()->create(['email' => 'janis@example.com']);
        $janis->assignRole(['admin', 'member']);
        Group::create(['name' => 'Jāņa grupa', 'admin_id' => $janis->id]);
        $this->ilze->adminGroups()->first()->members()->attach($janis->id);

        $this->post(route('password.email'), ['email' => 'janis@example.com']);

        Notification::assertSentTo($janis, ResetPasswordLinkNotification::class);
    }

    // students pameta savu pēdējo grupu – paroli joprojām var atjaunot pats
    public function test_student_without_a_group_can_reset_password(): void
    {
        $this->ilze->adminGroups()->first()->members()->detach($this->student->id);

        $this->post(route('password.email'), ['email' => 'anna@example.com']);

        Notification::assertSentTo($this->student, ResetPasswordLinkNotification::class);
    }
}
