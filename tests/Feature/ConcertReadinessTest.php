<?php

namespace Tests\Feature;

use App\Models\Costume;
use App\Models\CostumeSet;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Mail\MemberRemovedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// koncerta gatavība pa studentiem ar komplektiem un neapmeklētājiem, un jaunā paneļa darbs
class ConcertReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Group $group;
    private CostumeSet $girls;
    private CostumeSet $boys;
    private array $costumes = [];
    private array $students = [];
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->teacher = User::factory()->create(['name' => 'Ilze Bērziņa']);
        $this->teacher->assignRole('admin');
        $this->group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $this->teacher->id]);

        // iebūvētie komplekti rodas automātiski kopā ar grupu
        $this->girls = $this->group->costumeSets()->where('name', 'Girls')->firstOrFail();
        $this->boys = $this->group->costumeSets()->where('name', 'Boys')->firstOrFail();

        // Krekls ir kopīgs, Vainags tikai meitenēm, Veste tikai puišiem
        foreach (['Krekls' => null, 'Vainags' => $this->girls->id, 'Veste' => $this->boys->id] as $name => $setId) {
            $costume = Costume::create(['name' => $name, 'quantity' => 0, 'group_id' => $this->group->id, 'costume_set_id' => $setId]);
            $costume->addItems(3);
            $this->costumes[$name] = $costume;
        }

        foreach (['Marta' => $this->girls->id, 'Roberts' => $this->boys->id, 'Anna' => $this->girls->id] as $name => $setId) {
            $student = User::factory()->create(['name' => $name]);
            $student->assignRole('member');
            $this->group->members()->attach($student->id, ['costume_set_id' => $setId]);
            $this->students[$name] = $student;
        }

        $this->event = Event::create([
            'group_id' => $this->group->id,
            'title' => 'Rudens koncerts',
            'starts_at' => now()->addDays(4),
            'created_by' => $this->teacher->id,
        ]);
        $this->event->costumes()->sync(collect($this->costumes)->pluck('id')->all());
    }

    private function give(string $student, string $costume): void
    {
        $this->costumes[$costume]->items()->whereNull('assigned_to')->first()
            ->assignTo($this->students[$student], $this->teacher);
    }

    private function freshEvent(): Event
    {
        return Event::with('costumes')->find($this->event->id);
    }

    public function test_student_is_ready_only_with_every_piece_of_their_set(): void
    {
        $this->give('Marta', 'Krekls');
        $this->give('Marta', 'Vainags');
        $this->give('Roberts', 'Krekls');
        $this->event->absentees()->attach($this->students['Anna']->id);

        $readiness = $this->freshEvent()->studentReadiness();

        $this->assertSame(2, $readiness['total']);
        $this->assertSame(1, $readiness['ready']);
        $this->assertSame(1, $readiness['absentCount']);

        $roberts = $readiness['students']->firstWhere('student.id', $this->students['Roberts']->id);
        $this->assertFalse($roberts['ready']);
        $this->assertSame(['Veste'], $roberts['missing']->pluck('name')->all());
    }

    public function test_student_without_set_is_not_counted_as_ready(): void
    {
        $this->group->members()->updateExistingPivot($this->students['Marta']->id, ['costume_set_id' => null]);
        $this->give('Marta', 'Krekls');

        $marta = $this->freshEvent()->studentReadiness()['students']
            ->firstWhere('student.id', $this->students['Marta']->id);

        $this->assertTrue($marta['no_set']);
        $this->assertFalse($marta['ready']);
    }

    public function test_costume_target_counts_only_performing_students_who_need_it(): void
    {
        $this->event->absentees()->attach($this->students['Anna']->id);

        $rows = $this->freshEvent()->costumeReadiness()->keyBy(fn ($r) => $r['costume']->name);

        $this->assertSame(2, $rows['Krekls']['target']);
        $this->assertSame(1, $rows['Vainags']['target']);
        $this->assertSame(1, $rows['Veste']['target']);
    }

    public function test_dashboard_lists_what_needs_the_teacher(): void
    {
        $this->give('Roberts', 'Krekls');

        $this->actingAs($this->teacher)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Needs you')
            ->assertSee('Rudens koncerts')
            ->assertSee('is missing Veste')
            ->assertSee('0 of 3 students ready');
    }

    // koncerts tālāk par divām nedēļām vēl neparādās "Needs you", bet gatavības kartītē joprojām redzams
    public function test_concert_further_than_two_weeks_adds_no_rows(): void
    {
        $this->event->update(['starts_at' => now()->addDays(20)]);

        $this->actingAs($this->teacher)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Nothing needs you today.')
            ->assertDontSee('is missing Veste')
            ->assertSee('0 of 3 students ready');
    }

    public function test_activity_page_keeps_the_old_statistics(): void
    {
        $this->give('Marta', 'Krekls');

        $this->actingAs($this->teacher)->get(route('admin.activity'))->assertRedirect(route('admin.group.settings').'#activity');

        $this->actingAs($this->teacher)
            ->get(route('admin.group.settings'))
            ->assertOk()
            ->assertSee('Recent activity')
            ->assertSee('Activity — last 6 weeks')
            ->assertSee('Holding the most');
    }

    public function test_unticked_students_are_saved_as_not_performing(): void
    {
        $this->actingAs($this->teacher)->put(route('admin.events.update', $this->event), [
            'title' => 'Rudens koncerts',
            'starts_at' => $this->event->starts_at->format('Y-m-d H:i'),
            'location' => null,
            'notes' => null,
            'costume_ids' => collect($this->costumes)->pluck('id')->all(),
            'attendance_sent' => 1,
            'attending_ids' => [$this->students['Marta']->id, $this->students['Roberts']->id],
        ])->assertRedirect(route('admin.events.index'));

        $this->assertSame([$this->students['Anna']->id], $this->event->absentees()->pluck('users.id')->all());
    }

    // visas lapas, kurās parādījās komplektu vai apmeklējuma lauki, joprojām atveras
    public function test_pages_with_set_and_attendance_controls_render(): void
    {
        $this->actingAs($this->teacher);

        foreach ([
            route('admin.members.index'),
            route('admin.members.create'),
            route('admin.members.show', $this->students['Marta']),
            route('admin.costumes.index'),
            route('admin.costumes.create'),
            route('admin.costumes.edit', $this->costumes['Vainags']),
            route('admin.events.index'),
            route('admin.events.create'),
            route('admin.events.edit', $this->event),
            route('admin.group.settings'),
            route('admin.group.delete'),
            route('admin.season-report.show'),
            route('admin.costumes.show', $this->costumes['Vainags']),
            route('admin.costumes.labels', $this->costumes['Vainags']),
            route('profile.edit'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    // studenta lapas un QR skenēšana jaunajā izkārtojumā
    public function test_student_and_scan_pages_render(): void
    {
        $this->give('Marta', 'Krekls');
        $free = $this->costumes['Vainags']->items()->whereNull('assigned_to')->first();
        $held = $this->costumes['Krekls']->items()->whereNotNull('assigned_to')->first();

        $this->get(route('scan.show', $free->qr_code))->assertOk()->assertSee('Confirm it');
        $this->get(route('scan.show', $held->qr_code))->assertOk()->assertSee('Assigned');
        $this->get(route('scan.show', 'nav-tada-koda'))->assertNotFound()->assertSee('recognised', false);
        $this->get(route('password.request'))->assertOk();

        $this->actingAs($this->students['Marta']);
        $this->get(route('dashboard'))->assertOk()->assertSee('Your groups')->assertSee('Rudens koncerts');
        $this->get(route('members.costumes.index', $this->group))->assertOk()->assertSee('With you now')->assertSee('Krekls');
        $this->get(route('scan.show', $free->qr_code))->assertOk()->assertSee('Assign to me');
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_teacher_can_move_students_to_a_set(): void
    {
        $this->actingAs($this->teacher)->patch(route('admin.members.set'), [
            'user_ids' => [$this->students['Marta']->id, $this->students['Anna']->id],
            'costume_set_id' => $this->boys->id,
        ])->assertSessionHas('success');

        $this->assertSame(
            2,
            $this->group->members()->wherePivot('costume_set_id', $this->boys->id)->count() - 1 // Roberts jau bija puišos
        );
    }

    // students redz to pašu gatavību, ko skolotājs: savus tērpus, kas trūkst, un savu komplektu
    public function test_student_sees_own_readiness_and_set(): void
    {
        $this->give('Roberts', 'Krekls');

        $this->actingAs($this->students['Roberts'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Before your next concert')
            ->assertSee('You still need:')
            ->assertSee('Veste')
            ->assertSee('Boys')
            ->assertSee('KRE-01');
    }

    public function test_absent_student_is_told_they_are_not_performing(): void
    {
        $this->event->absentees()->attach($this->students['Anna']->id);

        $this->actingAs($this->students['Anna'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Before your next concert')
            ->assertSee('Not performing')
            ->assertSee("You're not performing in this concert", false);
    }

    public function test_removed_student_gets_an_email(): void
    {
        Mail::fake();

        $this->actingAs($this->teacher)
            ->delete(route('admin.members.destroy', $this->students['Anna']))
            ->assertRedirect(route('admin.members.index'));

        Mail::assertSent(MemberRemovedMail::class, fn ($mail) => $mail->hasTo($this->students['Anna']->email) && $mail->restoreUntil !== null);
    }

    // skolotājs izsniedz konkrētu vienību no tērpa lapas
    public function test_teacher_can_assign_an_item_to_a_student(): void
    {
        $item = $this->costumes['Veste']->items()->orderBy('code')->first();

        $this->actingAs($this->teacher)
            ->post(route('admin.costumes.items.assign', $item), ['user_id' => $this->students['Roberts']->id])
            ->assertSessionHas('success');

        $this->assertSame($this->students['Roberts']->id, $item->fresh()->assigned_to);
        $this->assertSame($this->teacher->id, $item->assignments()->first()->assigned_by);
    }

    public function test_teacher_cannot_assign_to_someone_outside_the_group(): void
    {
        $outsider = User::factory()->create();
        $item = $this->costumes['Veste']->items()->first();

        $this->actingAs($this->teacher)
            ->post(route('admin.costumes.items.assign', $item), ['user_id' => $outsider->id])
            ->assertStatus(422);

        $this->assertNull($item->fresh()->assigned_to);
    }

    // skolotājs izsniedz no studenta lapas konkrēto vienību pēc koda; aizņemtu vienību izsniegt nevar
    public function test_teacher_hands_out_the_exact_item_from_member_page(): void
    {
        $vai03 = $this->costumes['Vainags']->items()->where('code', 'VAI-03')->first();

        $this->actingAs($this->teacher)
            ->post(route('admin.members.hand-out', $this->students['Anna']), ['item_id' => $vai03->id])
            ->assertSessionHas('success');

        $this->assertSame($this->students['Anna']->id, $vai03->fresh()->assigned_to);

        $this->actingAs($this->teacher)
            ->post(route('admin.members.hand-out', $this->students['Marta']), ['item_id' => $vai03->id])
            ->assertSessionHas('error');

        $this->assertSame($this->students['Anna']->id, $vai03->fresh()->assigned_to);
    }

    // solistam papildu tērps: gatavs tikai tad, kad ir arī tas; vajadzīgo skaitu nosaka automātiski
    public function test_extra_costume_for_a_soloist_counts_in_readiness(): void
    {
        $this->event->studentCostumes()->create([
            'user_id' => $this->students['Marta']->id,
            'costume_id' => $this->costumes['Veste']->id,
            'quantity' => 1,
        ]);
        $this->give('Marta', 'Krekls');
        $this->give('Marta', 'Vainags');

        $event = $this->freshEvent();
        $marta = $event->readinessFor($this->students['Marta']);

        $this->assertFalse($marta['ready']);
        $this->assertSame('Veste', $marta['missingText']);

        // Veste: Roberts (puiši) + Marta (papildu) = 2
        $rows = $event->costumeReadiness()->keyBy(fn ($r) => $r['costume']->name);
        $this->assertSame(2, $rows['Veste']['target']);

        $this->give('Marta', 'Veste');
        $this->assertTrue($this->freshEvent()->readinessFor($this->students['Marta'])['ready']);
    }

    // papildu skaits jau vajadzīgam tērpam: 2 vainagi solistei
    public function test_extra_quantity_raises_how_many_are_needed(): void
    {
        $this->event->studentCostumes()->create([
            'user_id' => $this->students['Marta']->id,
            'costume_id' => $this->costumes['Vainags']->id,
            'quantity' => 1,
        ]);
        $this->give('Marta', 'Krekls');
        $this->give('Marta', 'Vainags');

        $marta = $this->freshEvent()->readinessFor($this->students['Marta']);
        $this->assertFalse($marta['ready']);
        $this->assertSame('Vainags', $marta['missingText']);

        $this->give('Marta', 'Vainags');
        $this->assertTrue($this->freshEvent()->readinessFor($this->students['Marta'])['ready']);
    }

    public function test_concert_form_saves_extra_costumes(): void
    {
        $this->actingAs($this->teacher)->put(route('admin.events.update', $this->event), [
            'title' => 'Rudens koncerts',
            'starts_at' => $this->event->starts_at->format('Y-m-d H:i'),
            'location' => null,
            'notes' => null,
            'costume_ids' => collect($this->costumes)->pluck('id')->all(),
            'attendance_sent' => 1,
            'attending_ids' => collect($this->students)->pluck('id')->all(),
            'extras' => [
                ['user_id' => $this->students['Marta']->id, 'costume_id' => $this->costumes['Veste']->id, 'quantity' => 1],
            ],
        ])->assertRedirect(route('admin.events.index'));

        $this->assertSame(1, $this->event->studentCostumes()->count());
        $this->assertNull($this->event->costumes()->first()->pivot->target_count);
    }

    // sezonas atskaite: gatavība koncerta dienā tiek atjaunota no vēstures, nevis no šodienas stāvokļa
    public function test_season_report_rebuilds_readiness_on_concert_day(): void
    {
        $past = Event::create([
            'group_id' => $this->group->id,
            'title' => 'Vasaras koncerts',
            'starts_at' => now()->subDays(2),
            'created_by' => $this->teacher->id,
        ]);
        $past->costumes()->sync([$this->costumes['Krekls']->id]);

        // Marta Kreklu paņēma pirms koncerta, Roberts – tikai pēc tā
        $this->give('Marta', 'Krekls');
        $this->students['Marta']->costumeAssignments()->update(['assigned_at' => now()->subDays(5)]);
        $this->give('Roberts', 'Krekls');

        $day = Event::with('costumes')->find($past->id)->asOf($past->starts_at)->studentReadiness();
        $this->assertSame(1, $day['ready']);

        $this->actingAs($this->teacher)
            ->get(route('admin.season-report.show'))
            ->assertOk()
            ->assertSee('Vasaras koncerts')
            ->assertSee('Still to collect')
            ->assertSee('Inventory')
            ->assertSee('Marta')
            ->assertDontSee('Notes for next season');
    }

    // iebūvētos komplektus nevar pārsaukt vai dzēst, bet savus var pievienot
    public function test_built_in_sets_are_protected(): void
    {
        $this->assertSame(['Girls', 'Boys'], $this->group->costumeSets()->pluck('name')->all());

        $this->actingAs($this->teacher);
        $this->delete(route('admin.costume-sets.destroy', $this->girls))->assertRedirect(route('dashboard'));
        $this->patch(route('admin.costume-sets.update', $this->boys), ['set_name' => 'Puiši'])->assertRedirect(route('dashboard'));

        $this->post(route('admin.costume-sets.store'), ['set_name' => 'Musicians'])->assertSessionHas('success');
        $this->assertSame(['Girls', 'Boys', 'Musicians'], $this->group->costumeSets()->pluck('name')->all());
    }

    // meklēšana: vienība pēc koda (arī bez domuzīmes) rāda, pie kā tā ir, un vēsturi; students pēc vārda
    public function test_teacher_search_finds_items_and_students(): void
    {
        $this->give('Marta', 'Vainags');

        $this->actingAs($this->teacher);

        $this->get(route('admin.search', ['q' => 'vai01']))
            ->assertOk()
            ->assertSee('VAI-01')
            ->assertSee('Taken')
            ->assertSee('Marta')
            ->assertSee('History (1)');

        $this->get(route('admin.search', ['q' => 'Robert']))
            ->assertOk()
            ->assertSee('Students · 1')
            ->assertSee('holds nothing');

        $this->get(route('admin.search', ['q' => 'zzz']))->assertOk()->assertSee('Nothing found');

        // students meklēšanai netiek klāt
        $this->actingAs($this->students['Marta'])->get(route('admin.search', ['q' => 'VAI']))->assertRedirect(route('dashboard'));
    }

    // paroles maiņā students redz skolotāja ievadīto vārdu un var to atstāt vai izlabot
    public function test_student_can_keep_or_fix_name_when_setting_password(): void
    {
        $anna = $this->students['Anna'];
        $anna->update(['must_change_password' => true]);

        $this->actingAs($anna)->get(route('password.change'))
            ->assertOk()
            ->assertSee('value="Anna"', false);

        $this->actingAs($anna)->put(route('password.change.update'), [
            'name' => 'Anna Kalniņa',
            'password' => 'Jauna-Parole-123',
            'password_confirmation' => 'Jauna-Parole-123',
        ])->assertRedirect(route('dashboard'));

        $anna->refresh();
        $this->assertSame('Anna Kalniņa', $anna->name);
        $this->assertFalse($anna->must_change_password);
    }

    // jaunai parolei vajag lielo un mazo burtu, ciparu un speciālo zīmi
    public function test_weak_new_passwords_are_rejected(): void
    {
        $anna = $this->students['Anna'];
        $anna->update(['must_change_password' => true]);

        foreach (['aaaaaaaa', '12345678', 'Parole123', 'parole-123', 'Pa-1'] as $weak) {
            $this->actingAs($anna)->put(route('password.change.update'), [
                'name' => 'Anna',
                'password' => $weak,
                'password_confirmation' => $weak,
            ])->assertSessionHasErrors('password');
        }

        $this->assertTrue($anna->fresh()->must_change_password);
    }
}
