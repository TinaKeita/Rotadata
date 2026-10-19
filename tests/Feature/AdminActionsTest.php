<?php

namespace Tests\Feature;

use App\Models\Costume;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Notifications\StudentLeftGroupNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// skolotāja ikdienas darbības: tērpi un to izsniegšana, komplekti, meklēšana, koncerti, grupa un paziņojumi
class AdminActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Group $group;
    private Costume $costume;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('admin');
        $this->group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $this->teacher->id]);

        $this->costume = Costume::create(['name' => 'Krekls', 'group_id' => $this->group->id]);
        $this->costume->addItems(2);
    }

    // noteikts e-pasts – nejaušs varētu saturēt citu vārdu (piem. "robert") un sajaukt meklēšanas testu
    private function student(string $name): User
    {
        $student = User::factory()->create(['name' => $name, 'email' => strtolower($name).'@example.com']);
        $student->assignRole('member');
        $this->group->members()->attach($student->id);

        return $student;
    }

    private function otherTeacher(): User
    {
        $other = User::factory()->create();
        $other->assignRole('admin');
        Group::create(['name' => 'Cita grupa', 'admin_id' => $other->id]);

        return $other;
    }

    public function test_teacher_renames_a_costume_and_picks_a_set(): void
    {
        $set = $this->group->costumeSets()->create(['name' => 'Meitenes']);

        $this->actingAs($this->teacher)
            ->put(route('admin.costumes.update', $this->costume), ['name' => 'Linu krekls', 'costume_set_id' => $set->id])
            ->assertRedirect(route('admin.costumes.show', $this->costume));

        $this->costume->refresh();
        $this->assertSame('Linu krekls', $this->costume->name);
        $this->assertSame($set->id, $this->costume->costume_set_id);
    }

    public function test_teacher_adds_items_to_a_costume(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('admin.costumes.items.add', $this->costume), ['count' => 3])
            ->assertSessionHas('success');

        $this->assertSame(5, $this->costume->items()->count());

        // cits skolotājs šai grupai vienības pievienot nevar
        $this->actingAs($this->otherTeacher())->post(route('admin.costumes.items.add', $this->costume), ['count' => 1]);
        $this->assertSame(5, $this->costume->items()->count());
    }

    // jauns tērps uzreiz saņem vienības ar secīgiem kodiem un katrai savu QR kodu, kas ved uz skenēšanas lapu
    public function test_teacher_creates_a_costume_with_qr_codes(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('admin.costumes.store'), ['name' => 'Brunči', 'quantity' => 3])
            ->assertRedirect(route('admin.costumes.index'))
            ->assertSessionHasNoErrors();

        $costume = Costume::where('name', 'Brunči')->firstOrFail();
        $items = $costume->items()->orderBy('code')->get();

        $this->assertSame($this->group->id, $costume->group_id);
        $this->assertSame(['BRU-01', 'BRU-02', 'BRU-03'], $items->pluck('code')->all());
        $this->assertCount(3, $items->pluck('qr_code')->filter()->unique());
        $this->assertTrue($items->every(fn ($item) => $item->assigned_to === null));

        // grupas dalībnieks, noskenējot jauno QR kodu, var vienību paņemt
        $this->actingAs($this->student('Marta'))
            ->get(route('scan.show', $items->first()->qr_code))
            ->assertOk()
            ->assertViewIs('scan.confirm');
    }

    // nepildītie lauki (vieta, piezīmes) var nebūt pieprasījumā vispār
    // skolotājs izsniedz konkrētu vienību no tērpa lapas
    #[\PHPUnit\Framework\Attributes\Group('core')]
    public function test_teacher_can_assign_an_item_to_a_student(): void
    {
        $roberts = $this->student('Roberts');
        $item = $this->costume->items()->orderBy('code')->first();

        $this->actingAs($this->teacher)
            ->post(route('admin.costumes.items.assign', $item), ['user_id' => $roberts->id])
            ->assertSessionHas('success');

        $this->assertSame($roberts->id, $item->fresh()->assigned_to);
        $this->assertSame($this->teacher->id, $item->assignments()->first()->assigned_by);
    }

    public function test_teacher_cannot_assign_to_someone_outside_the_group(): void
    {
        $outsider = User::factory()->create();
        $item = $this->costume->items()->first();

        $this->actingAs($this->teacher)
            ->post(route('admin.costumes.items.assign', $item), ['user_id' => $outsider->id])
            ->assertStatus(422);

        $this->assertNull($item->fresh()->assigned_to);
    }

    // skolotājs izsniedz no studenta lapas konkrēto vienību pēc koda; aizņemtu vienību izsniegt nevar
    public function test_teacher_hands_out_the_exact_item_from_member_page(): void
    {
        $anna = $this->student('Anna');
        $marta = $this->student('Marta');
        $kre02 = $this->costume->items()->where('code', 'KRE-02')->first();

        $this->actingAs($this->teacher)
            ->post(route('admin.members.hand-out', $anna), ['item_id' => $kre02->id])
            ->assertSessionHas('success');

        $this->assertSame($anna->id, $kre02->fresh()->assigned_to);

        $this->actingAs($this->teacher)
            ->post(route('admin.members.hand-out', $marta), ['item_id' => $kre02->id])
            ->assertSessionHas('error');

        $this->assertSame($anna->id, $kre02->fresh()->assigned_to);
    }

    // meklēšana: vienība pēc koda (arī bez domuzīmes) rāda, pie kā tā ir, un vēsturi; students pēc vārda
    #[\PHPUnit\Framework\Attributes\Group('core')]
    public function test_teacher_search_finds_items_and_students(): void
    {
        $marta = $this->student('Marta');
        $this->student('Roberts');
        $this->costume->items()->orderBy('code')->first()->assignTo($marta, $this->teacher);

        $this->actingAs($this->teacher);

        $this->get(route('admin.search', ['q' => 'kre01']))
            ->assertOk()
            ->assertSee('KRE-01')
            ->assertSee('Taken')
            ->assertSee('Marta')
            ->assertSee('History (1)');

        $this->get(route('admin.search', ['q' => 'Robert']))
            ->assertOk()
            ->assertSee('Students · 1')
            ->assertSee('holds nothing');

        $this->get(route('admin.search', ['q' => 'zzz']))->assertOk()->assertSee('Nothing found');

        // students meklēšanai netiek klāt
        $this->actingAs($marta)->get(route('admin.search', ['q' => 'KRE']))->assertRedirect(route('dashboard'));
    }

    // iebūvētos komplektus nevar pārsaukt vai dzēst, bet savus var pievienot
    public function test_built_in_sets_are_protected(): void
    {
        $this->assertSame(['Girls', 'Boys'], $this->group->costumeSets()->pluck('name')->all());

        $girls = $this->group->costumeSets()->where('name', 'Girls')->firstOrFail();
        $boys = $this->group->costumeSets()->where('name', 'Boys')->firstOrFail();

        $this->actingAs($this->teacher);
        $this->delete(route('admin.costume-sets.destroy', $girls))
            ->assertRedirect(route('dashboard'))->assertSessionHas('error', 'Built-in sets cannot be deleted.');
        $this->patch(route('admin.costume-sets.update', $boys), ['set_name' => 'Puiši'])
            ->assertRedirect(route('dashboard'))->assertSessionHas('error', 'Built-in sets cannot be renamed.');

        $this->post(route('admin.costume-sets.store'), ['set_name' => 'Musicians'])->assertSessionHas('success');
        $this->assertSame(['Girls', 'Boys', 'Musicians'], $this->group->costumeSets()->pluck('name')->all());
    }

    // nepildītie lauki (vieta, piezīmes) var nebūt pieprasījumā vispār
    public function test_teacher_creates_a_concert_with_costumes(): void
    {
        $this->actingAs($this->teacher)->post(route('admin.events.store'), [
            'title' => 'Ziemassvētku koncerts',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i'),
            'costume_ids' => [$this->costume->id],
        ])->assertRedirect(route('admin.events.index'));

        $event = Event::where('title', 'Ziemassvētku koncerts')->firstOrFail();
        $this->assertSame($this->group->id, $event->group_id);
        $this->assertSame([$this->costume->id], $event->costumes()->pluck('costumes.id')->all());
    }

    public function test_concert_cannot_be_created_in_the_past(): void
    {
        $this->actingAs($this->teacher)->post(route('admin.events.store'), [
            'title' => 'Vakardienas koncerts',
            'starts_at' => now()->subDay()->format('Y-m-d H:i'),
        ])->assertSessionHasErrors('starts_at');

        $this->assertSame(0, Event::count());
    }

    // notikušu koncertu nevar rediģēt vai dzēst – ne pogās, ne pa tiešo saiti
    public function test_past_concert_cannot_be_edited_or_deleted(): void
    {
        $past = Event::create([
            'group_id' => $this->group->id,
            'title' => 'Pavasara koncerts',
            'starts_at' => now()->subDays(3),
            'created_by' => $this->teacher->id,
        ]);
        $upcoming = Event::create([
            'group_id' => $this->group->id,
            'title' => 'Rudens koncerts',
            'starts_at' => now()->addDays(4),
            'created_by' => $this->teacher->id,
        ]);

        $this->actingAs($this->teacher);

        $this->get(route('admin.events.index'))->assertOk()
            ->assertSee('Took place')
            ->assertDontSee(route('admin.events.edit', $past));

        $this->get(route('admin.events.edit', $past))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', '“Pavasara koncerts” has already taken place, so it can\'t be edited.');

        $this->delete(route('admin.events.destroy', $past))->assertRedirect(route('dashboard'));
        $this->assertNotNull($past->fresh());

        // gaidāmo koncertu joprojām var rediģēt
        $this->get(route('admin.events.edit', $upcoming))->assertOk();
    }

    public function test_teacher_renames_the_group(): void
    {
        $this->actingAs($this->teacher)
            ->patch(route('admin.group.update'), ['name' => 'Deju kopa'])
            ->assertRedirect(route('admin.group.settings'));

        $this->assertSame('Deju kopa', $this->group->fresh()->name);
    }

    // dzēstu grupu var galīgi izdzēst uzreiz, negaidot 30 dienas – tikai ar pareizu paroli
    public function test_deleted_group_can_be_purged_right_away(): void
    {
        $this->actingAs($this->teacher)->delete(route('admin.group.destroy'), [
            'name' => 'Folkloras kopa',
            'password' => 'password',
        ]);
        $this->assertSoftDeleted($this->group);

        $this->actingAs($this->teacher)
            ->delete(route('admin.group.force-destroy'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');
        $this->assertNotNull(Group::withTrashed()->find($this->group->id));

        $this->actingAs($this->teacher)
            ->delete(route('admin.group.force-destroy'), ['password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertNull(Group::withTrashed()->find($this->group->id));
        $this->assertSame(0, Costume::where('group_id', $this->group->id)->count());
    }

    public function test_teacher_dismisses_a_failed_invite_warning(): void
    {
        $student = User::factory()->create(['invite_email_failed_at' => now()]);
        $student->assignRole('member');
        $this->group->members()->attach($student->id);

        // cits skolotājs brīdinājumu par manu studentu noņemt nevar
        $this->actingAs($this->otherTeacher())->post(route('admin.members.dismiss-invite', $student));
        $this->assertNotNull($student->fresh()->invite_email_failed_at);

        $this->actingAs($this->teacher)
            ->post(route('admin.members.dismiss-invite', $student))
            ->assertSessionHas('success');

        $this->assertNull($student->fresh()->invite_email_failed_at);
    }

    // skolotājs var aizvērt tikai savus paziņojumus
    public function test_teacher_dismisses_only_their_own_notification(): void
    {
        $this->teacher->notify(new StudentLeftGroupNotification('Marta', 'Folkloras kopa'));
        $notification = $this->teacher->notifications()->firstOrFail();

        $this->actingAs($this->otherTeacher())->post(route('admin.notifications.dismiss', $notification->id));
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($this->teacher)->post(route('admin.notifications.dismiss', $notification->id));
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
