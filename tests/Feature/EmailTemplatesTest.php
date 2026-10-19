<?php

namespace Tests\Feature;

use App\Mail\GroupDeletionMail;
use App\Mail\GroupInvitationMail;
use App\Mail\GroupTransferRequestMail;
use App\Mail\GroupTransferResultMail;
use App\Mail\ItemTakenOverMail;
use App\Mail\MemberRemovedMail;
use App\Mail\MemberWelcomeMail;
use App\Models\Costume;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupTransfer;
use App\Models\User;
use App\Notifications\ResetPasswordLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// citi testi e-pastus viltoti "nosūta", tāpēc veidnes netiek atvērtas – šeit katru e-pastu tiešām uzzīmē
// (HTML un teksta versiju), lai pazudusi vai salauzta veidne neizpaliktu nepamanīta
class EmailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['name' => 'Ilze']);
        $this->student = User::factory()->create(['name' => 'Marta']);
        $this->group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $this->teacher->id]);
    }

    // uzzīmē abas versijas un pārbauda, ka tajās ir gaidītais teksts
    private function assertRenders($mailable, string $expected): void
    {
        $html = $mailable->render();
        $this->assertStringContainsString($expected, $html);

        $mailable->assertSeeInText($expected);
    }

    public function test_member_emails_render(): void
    {
        $this->assertRenders(new MemberWelcomeMail($this->student, 'TempPass1234', 'Folkloras kopa'), 'Marta');
        $this->assertRenders(new MemberRemovedMail($this->student, 'Folkloras kopa', '09.11.2026'), 'Folkloras kopa');
        $this->assertRenders(new MemberRemovedMail($this->student, 'Folkloras kopa'), 'Folkloras kopa');

        $invitation = GroupInvitation::create([
            'group_id' => $this->group->id,
            'user_id' => $this->student->id,
            'invited_by' => $this->teacher->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
        $this->assertRenders(new GroupInvitationMail($this->student, $invitation), 'Folkloras kopa');
    }

    public function test_group_deletion_emails_render(): void
    {
        foreach ([GroupDeletionMail::DEACTIVATED, GroupDeletionMail::REMOVED, GroupDeletionMail::RESTORED] as $variant) {
            $this->assertRenders(new GroupDeletionMail($this->student, 'Folkloras kopa', $variant, '08.11.2026'), 'Marta');
        }
    }

    public function test_group_transfer_emails_render(): void
    {
        $recipient = User::factory()->create(['name' => 'Jānis']);
        $transfer = GroupTransfer::create([
            'group_id' => $this->group->id,
            'from_user_id' => $this->teacher->id,
            'to_user_id' => $recipient->id,
            'token' => Str::random(40),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
        $impact = ['students' => 3, 'costumes' => 2, 'items' => 10, 'itemsOut' => 1, 'upcoming' => 1];

        $this->assertRenders(new GroupTransferRequestMail($transfer, $impact), 'Folkloras kopa');
        $this->assertRenders(new GroupTransferResultMail($transfer, true), 'Folkloras kopa');
        $this->assertRenders(new GroupTransferResultMail($transfer, false), 'Folkloras kopa');
    }

    public function test_item_taken_over_email_renders(): void
    {
        $costume = Costume::create(['name' => 'Krekls', 'group_id' => $this->group->id]);
        $costume->addItems(1);
        $item = $costume->items()->firstOrFail();
        $taker = User::factory()->create(['name' => 'Roberts']);

        $this->assertRenders(new ItemTakenOverMail($this->student, $item, $taker), $item->code);
    }

    public function test_password_reset_email_renders(): void
    {
        $mail = (new ResetPasswordLinkNotification('test-token'))->toMail($this->student);

        $this->assertRenders($mail, 'test-token');
    }
}
