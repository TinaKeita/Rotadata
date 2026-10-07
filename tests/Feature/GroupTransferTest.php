<?php

namespace Tests\Feature;

use App\Mail\GroupTransferRequestMail;
use App\Mail\GroupTransferResultMail;
use App\Models\Costume;
use App\Models\Group;
use App\Models\GroupTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// grupas nodošana citam skolotājam: meklēšana, paroles apstiprinājums, pieņemšana un noraidīšana
class GroupTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $ilze;
    private User $janis;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->ilze = User::factory()->create(['name' => 'Ilze Bērziņa']);
        $this->ilze->assignRole('admin');
        $this->group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $this->ilze->id]);
        $costume = Costume::create(['name' => 'Krekls', 'quantity' => 0, 'group_id' => $this->group->id]);
        $costume->addItems(2);

        // Jānis reģistrējās – viņam ir tukša grupa no reģistrācijas
        $this->janis = User::factory()->create(['name' => 'Jānis Ozols', 'email' => 'janis@example.com']);
        $this->janis->assignRole('admin');
        Group::create(['name' => 'Jāņa grupa', 'admin_id' => $this->janis->id]);
    }

    private function sendRequest(): GroupTransfer
    {
        $this->actingAs($this->ilze)->post(route('admin.group.transfer.store'), [
            'to_user_id' => $this->janis->id,
            'password' => 'password',
        ])->assertRedirect(route('admin.group.settings'));

        return GroupTransfer::firstOrFail();
    }

    // meklēšana iesaka skolotājus (arī tos, kam jau ir sava grupa), bet ne pašu un ne studentus
    public function test_search_suggests_teachers(): void
    {
        $other = User::factory()->create(['name' => 'Jana Kalna']);
        $other->assignRole('admin');
        Group::create(['name' => 'Cita', 'admin_id' => $other->id]);
        $student = User::factory()->create(['name' => 'Janka Students']);
        $student->assignRole('member');

        $names = collect($this->actingAs($this->ilze)
            ->getJson(route('admin.group.transfer.teachers', ['q' => 'ja']))
            ->assertOk()
            ->json())->pluck('name')->all();

        $this->assertContains('Jānis Ozols', $names);
        $this->assertContains('Jana Kalna', $names);
        $this->assertNotContains('Janka Students', $names);
        $this->assertNotContains('Ilze Bērziņa', $names);
    }

    public function test_request_needs_correct_password(): void
    {
        $this->actingAs($this->ilze)->post(route('admin.group.transfer.store'), [
            'to_user_id' => $this->janis->id,
            'password' => 'wrong',
        ])->assertSessionHasErrors('password', null, 'transfer');

        $this->assertSame(0, GroupTransfer::count());
    }

    #[\PHPUnit\Framework\Attributes\Group('core')]
    public function test_recipient_accepts_and_takes_over_the_group(): void
    {
        Mail::fake();
        $transfer = $this->sendRequest();

        Mail::assertSent(GroupTransferRequestMail::class, fn ($m) => $m->hasTo('janis@example.com'));

        $this->actingAs($this->janis)->get(route('admin.group.transfer.show', $transfer->token))
            ->assertOk()->assertSee('Accept and take over');

        $this->actingAs($this->janis)->post(route('admin.group.transfer.accept', $transfer->token))
            ->assertRedirect(route('admin.dashboard'));

        // pārņemtā grupa nāk klāt Jāņa paša grupai un kļūst par atvērto
        $this->assertSame($this->janis->id, $this->group->fresh()->admin_id);
        $this->assertSame(['Folkloras kopa', 'Jāņa grupa'], $this->janis->adminGroups()->orderBy('name')->pluck('name')->all());
        $this->assertSame($this->group->id, session(User::CURRENT_GROUP_KEY));
        $this->assertSame(0, $this->ilze->adminGroups()->count());
        $this->assertSame('accepted', $transfer->fresh()->status);
        Mail::assertSent(GroupTransferResultMail::class, fn ($m) => $m->hasTo($this->ilze->email) && $m->accepted);
    }

    public function test_recipient_declines_and_nothing_changes(): void
    {
        Mail::fake();
        $transfer = $this->sendRequest();

        $this->actingAs($this->janis)->post(route('admin.group.transfer.decline', $transfer->token))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($this->ilze->id, $this->group->fresh()->admin_id);
        $this->assertSame('declined', $transfer->fresh()->status);
        Mail::assertSent(GroupTransferResultMail::class, fn ($m) => ! $m->accepted);
    }

    public function test_only_the_recipient_can_open_or_accept(): void
    {
        Mail::fake();
        $transfer = $this->sendRequest();

        $stranger = User::factory()->create();
        $stranger->assignRole('admin');

        $this->actingAs($stranger)->get(route('admin.group.transfer.show', $transfer->token))->assertRedirect(route('dashboard'));
        $this->actingAs($stranger)->post(route('admin.group.transfer.accept', $transfer->token))->assertRedirect(route('dashboard'));
        $this->assertSame($this->ilze->id, $this->group->fresh()->admin_id);
    }

    public function test_cancelled_request_can_no_longer_be_accepted(): void
    {
        Mail::fake();
        $transfer = $this->sendRequest();

        $this->actingAs($this->ilze)->delete(route('admin.group.transfer.cancel'));

        $this->actingAs($this->janis)->post(route('admin.group.transfer.accept', $transfer->token))
            ->assertRedirect(route('dashboard'))->assertSessionHas('error');
        $this->assertSame($this->ilze->id, $this->group->fresh()->admin_id);
    }

    public function test_settings_and_dashboard_show_the_request(): void
    {
        Mail::fake();
        $this->sendRequest();

        $this->actingAs($this->ilze)->get(route('admin.group.settings'))->assertOk()->assertSee('Waiting for');
        $this->actingAs($this->janis)->get(route('admin.dashboard'))->assertOk()->assertSee('wants to hand you');
    }

    // skolotājs var izveidot vēl vienu grupu un pārslēgties starp savām grupām
    public function test_teacher_can_add_a_group_and_switch_between_groups(): void
    {
        $this->actingAs($this->ilze)->post(route('admin.groups.store'), ['group_name' => 'Deju kopa'])
            ->assertRedirect(route('admin.group.settings'));

        $new = Group::where('name', 'Deju kopa')->firstOrFail();
        $this->assertSame($this->ilze->id, $new->admin_id);
        $this->assertSame($new->id, session(User::CURRENT_GROUP_KEY));
        $this->actingAs($this->ilze)->get(route('admin.costumes.index'))->assertSee('Deju kopa')->assertDontSee('Krekls');

        $this->actingAs($this->ilze)->get(route('admin.groups.open', $this->group))->assertRedirect(route('admin.group.settings'));
        $this->actingAs($this->ilze)->get(route('admin.costumes.index'))->assertSee('Krekls');

        // svešu grupu atvērt nevar – pāradresē uz paneli
        $jana = Group::where('name', 'Jāņa grupa')->first();
        $this->actingAs($this->ilze)->get(route('admin.groups.open', $jana))->assertRedirect(route('dashboard'));
    }

    // nosūtītājam paliek viņa citas grupas
    public function test_sender_keeps_other_groups_after_handover(): void
    {
        Mail::fake();
        Group::create(['name' => 'Otrā grupa', 'admin_id' => $this->ilze->id]);
        $transfer = $this->sendRequest();

        $this->actingAs($this->janis)->post(route('admin.group.transfer.accept', $transfer->token));

        $this->assertSame(['Otrā grupa'], $this->ilze->adminGroups()->pluck('name')->all());
    }

    // nosūtītājs pēc pieprasījuma izdzēš grupu – saņēmēja panelis joprojām atveras un grupu pieņemt nevar
    public function test_deleting_the_group_does_not_break_the_recipients_dashboard(): void
    {
        Mail::fake();
        $transfer = $this->sendRequest();

        $this->actingAs($this->ilze)->delete(route('admin.group.destroy'), [
            'name' => $this->group->name,
            'password' => 'password',
        ]);
        $this->assertSoftDeleted($this->group);

        $this->actingAs($this->janis)->get(route('admin.dashboard'))->assertOk();

        $this->actingAs($this->janis)->post(route('admin.group.transfer.accept', $transfer->token));
        $this->assertSame($this->ilze->id, Group::withTrashed()->find($this->group->id)->admin_id);
    }

    // % un _ meklēšanā ir parasti simboli, nevis SQL aizstājējzīmes – ar tiem nevar izvilkt visus skolotājus
    public function test_search_treats_wildcards_as_plain_text(): void
    {
        foreach (['%%', '__', '%_'] as $q) {
            $this->actingAs($this->ilze)
                ->getJson(route('admin.group.transfer.teachers', ['q' => $q]))
                ->assertOk()
                ->assertExactJson([]);
        }
    }
}
