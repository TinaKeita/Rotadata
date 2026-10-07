<?php

namespace Tests\Feature;

use App\Models\Costume;
use App\Models\CostumeItem;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// QR koda skenēšana: kurš drīkst paņemt brīvu vienību un kurš drīkst pārņemt citam izsniegtu
class ScanFlowTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;
    private CostumeItem $item;
    private User $marta;
    private User $roberts;
    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $this->group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $teacher->id]);

        $costume = Costume::create(['name' => 'Krekls', 'group_id' => $this->group->id]);
        $costume->addItems(1);
        $this->item = $costume->items()->firstOrFail();

        $this->marta = $this->member('marta@example.com', $this->group);
        $this->roberts = $this->member('roberts@example.com', $this->group);

        // students no citas grupas
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('admin');
        $otherGroup = Group::create(['name' => 'Cita grupa', 'admin_id' => $otherTeacher->id]);
        $this->outsider = $this->member('outsider@example.com', $otherGroup);
    }

    private function member(string $email, Group $group): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('member');
        $group->members()->attach($user->id);

        return $user;
    }

    private function scanUrl(string $action = ''): string
    {
        return '/scan/'.$this->item->qr_code.$action;
    }

    public function test_unknown_code_shows_invalid_page(): void
    {
        $this->get('/scan/not-a-real-code')->assertNotFound()->assertViewIs('scan.invalid');
    }

    public function test_guest_is_asked_to_sign_in(): void
    {
        $this->get($this->scanUrl())->assertOk()->assertViewIs('scan.authenticate');
    }

    public function test_group_member_claims_a_free_item(): void
    {
        $this->actingAs($this->marta)->get($this->scanUrl())->assertViewIs('scan.confirm');

        $this->actingAs($this->marta)->post($this->scanUrl('/assign'))->assertViewIs('scan.success');

        $this->item->refresh();
        $this->assertSame($this->marta->id, $this->item->assigned_to);
        $this->assertSame(1, $this->item->assignments()->whereNull('returned_at')->count());
    }

    public function test_student_from_another_group_cannot_claim(): void
    {
        $this->actingAs($this->outsider)->get($this->scanUrl())->assertViewIs('scan.denied');

        $this->actingAs($this->outsider)->post($this->scanUrl('/assign'))->assertRedirect(route('dashboard'));

        $this->assertNull($this->item->fresh()->assigned_to);
    }

    // pieslēgšanās skenējot: svešas grupas students tiek uzreiz atslēgts
    public function test_signing_in_while_scanning_only_works_for_group_members(): void
    {
        $this->post($this->scanUrl('/authenticate'), ['email' => 'marta@example.com', 'password' => 'password'])
            ->assertRedirect($this->scanUrl());
        $this->assertAuthenticatedAs($this->marta);

        auth()->logout();

        $this->post($this->scanUrl('/authenticate'), ['email' => 'outsider@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // pēc 5 nepareizām parolēm arī pareizā parole uz laiku netiek pieņemta
    public function test_too_many_wrong_passwords_lock_the_scan_sign_in(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post($this->scanUrl('/authenticate'), ['email' => 'marta@example.com', 'password' => 'wrong']);
        }

        $this->post($this->scanUrl('/authenticate'), ['email' => 'marta@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // Roberts pārņem Martai izsniegtu vienību – vēsturē Martas ieraksts aizveras kā "transfer"
    public function test_group_member_takes_over_an_item_from_another_member(): void
    {
        $this->item->assignTo($this->marta, $this->marta);

        $this->actingAs($this->roberts)->post($this->scanUrl('/takeover'))->assertViewIs('scan.success');

        $this->item->refresh();
        $this->assertSame($this->roberts->id, $this->item->assigned_to);
        $this->assertSame('transfer', $this->item->assignments()->where('user_id', $this->marta->id)->value('return_note'));
        $this->assertSame(1, $this->item->assignments()->whereNull('returned_at')->count());
    }

    // pārņemšana nenotiek klusi: skolotājs redz to panelī, iepriekšējais turētājs saņem e-pastu
    public function test_takeover_notifies_the_teacher_and_the_previous_holder(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->item->assignTo($this->marta, $this->marta);

        $this->actingAs($this->roberts)->post($this->scanUrl('/takeover'))->assertViewIs('scan.success');

        $teacher = $this->group->admin;
        $notification = $teacher->unreadNotifications()->firstOrFail();
        $this->assertSame(\App\Notifications\ItemTakenOverNotification::class, $notification->type);
        $this->assertSame($this->marta->name, $notification->data['from_name']);
        $this->assertSame($this->roberts->name, $notification->data['to_name']);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ItemTakenOverMail::class,
            fn ($mail) => $mail->hasTo('marta@example.com') && $mail->takenBy->is($this->roberts));

        $this->actingAs($teacher)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee("took over {$this->item->code}")
            ->assertSee('View item');
    }

    public function test_outsider_cannot_take_over(): void
    {
        $this->item->assignTo($this->marta, $this->marta);

        $this->actingAs($this->outsider)->post($this->scanUrl('/takeover'))->assertRedirect(route('dashboard'));

        $this->assertSame($this->marta->id, $this->item->fresh()->assigned_to);
    }

    // publiskais QR neatklāj, kuram studentam vienība izsniegta – ne viesim, ne citas grupas dalībniekam
    public function test_scan_page_does_not_reveal_the_holder_to_outsiders(): void
    {
        $this->marta->update(['name' => 'Marta Kalniņa']);
        $this->item->assignTo($this->marta, $this->marta);

        $this->get($this->scanUrl())->assertOk()->assertDontSee('Marta Kalniņa');

        $this->actingAs($this->outsider)->get($this->scanUrl())->assertDontSee('Marta Kalniņa');
    }

    // QR koda PNG lejupielāde ar salasāmo kodu faila nosaukumā
    public function test_qr_code_downloads_as_png_named_after_the_item(): void
    {
        $this->get(route('qr.download', $this->item->qr_code))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'attachment; filename="qr-'.$this->item->code.'.png"');
    }

    // jauns QR kods aizstāj veco – vecā birka vairs nedarbojas
    public function test_regenerated_qr_makes_the_old_label_invalid(): void
    {
        $oldUrl = $this->scanUrl();

        $this->actingAs($this->group->admin)->post(route('admin.costumes.items.regenerate-qr', $this->item));

        $this->get($oldUrl)->assertNotFound();
        $this->get('/scan/'.$this->item->fresh()->qr_code)->assertOk();
    }
}
