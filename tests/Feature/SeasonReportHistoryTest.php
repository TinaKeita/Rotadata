<?php

namespace Tests\Feature;

use App\Models\Costume;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// notikuša koncerta gatavība sezonas atskaitē paliek tāda, kāda tā bija koncerta dienā –
// to nedrīkst mainīt vēlākas izmaiņas dalībniekos, komplektos vai inventārā
class SeasonReportHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Group $group;
    private array $costumes = [];
    private User $marta;
    private User $roberts;
    private Event $concert;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        // viss sagatavots pirms koncerta: Krekls visiem, Vainags tikai meitenēm
        $this->travelTo(now()->subDays(10));

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('admin');
        $this->group = Group::create(['name' => 'Folkloras kopa', 'admin_id' => $this->teacher->id]);
        $girls = $this->group->costumeSets()->where('name', 'Girls')->firstOrFail();
        $boys = $this->group->costumeSets()->where('name', 'Boys')->firstOrFail();

        foreach (['Krekls' => null, 'Vainags' => $girls->id] as $name => $setId) {
            $costume = Costume::create(['name' => $name, 'quantity' => 0, 'group_id' => $this->group->id, 'costume_set_id' => $setId]);
            $costume->addItems(3);
            $this->costumes[$name] = $costume;
        }

        $this->marta = $this->student('Marta', $girls->id);
        $this->roberts = $this->student('Roberts', $boys->id);

        $this->give($this->marta, 'Krekls');
        $this->give($this->marta, 'Vainags');
        $this->give($this->roberts, 'Krekls');

        $this->concert = Event::create([
            'group_id' => $this->group->id,
            'title' => 'Vasaras koncerts',
            'starts_at' => now()->addDays(8),
            'created_by' => $this->teacher->id,
        ]);
        $this->concert->costumes()->sync(collect($this->costumes)->pluck('id')->all());

        // koncerts ir pagājis pirms 2 dienām
        $this->travelBack();

        $this->assertConcertDay(ready: 2, total: 2);
    }

    private function student(string $name, int $setId): User
    {
        $student = User::factory()->create(['name' => $name]);
        $student->assignRole('member');
        $this->group->members()->attach($student->id, ['costume_set_id' => $setId]);

        return $student;
    }

    private function give(User $student, string $costume): void
    {
        $this->costumes[$costume]->items()->whereNull('assigned_to')->first()->assignTo($student, $this->teacher);
    }

    private function concertDay(): array
    {
        $event = Event::with('costumes')->find($this->concert->id);

        return $event->asOf($event->starts_at)->studentReadiness();
    }

    private function assertConcertDay(int $ready, int $total): void
    {
        $day = $this->concertDay();

        $this->assertSame($total, $day['total'], 'Students counted for the concert changed.');
        $this->assertSame($ready, $day['ready'], 'Ready students for the concert changed.');
    }

    // jauns students, kas pievienojās pēc koncerta, tajā nepiedalījās
    public function test_student_who_joined_after_the_concert_is_not_counted(): void
    {
        $this->student('Anna', $this->group->costumeSets()->where('name', 'Girls')->value('id'));

        $this->assertConcertDay(ready: 2, total: 2);
    }

    // students, kas grupu pameta pēc koncerta, koncerta dienā joprojām bija tajā
    public function test_student_who_left_after_the_concert_is_still_counted(): void
    {
        $item = $this->roberts->assignedCostumeItems()->first();
        $item->release($this->roberts, 'self');
        $this->actingAs($this->roberts)->post(route('members.costumes.leave', $this->group));

        $this->assertConcertDay(ready: 2, total: 2);
    }

    // komplekta maiņa pēc koncerta nemaina, kas studentam toreiz bija vajadzīgs
    public function test_set_change_after_the_concert_does_not_change_it(): void
    {
        $girls = $this->group->costumeSets()->where('name', 'Girls')->value('id');

        $this->actingAs($this->teacher)->patch(route('admin.members.set'), [
            'user_ids' => [$this->roberts->id],
            'costume_set_id' => $girls,
        ]);

        $this->assertConcertDay(ready: 2, total: 2);
    }

    // tērpa dzēšana pēc sezonas nepārraksta, ko koncertā vajadzēja
    public function test_deleting_a_costume_does_not_rewrite_the_concert(): void
    {
        $vainags = $this->marta->assignedCostumeItems()->where('costume_id', $this->costumes['Vainags']->id)->first();
        $vainags->release($this->teacher, 'admin');

        $this->actingAs($this->teacher)->delete(route('admin.costumes.destroy', $this->costumes['Vainags']));

        $marta = $this->concertDay()['students']->first(fn ($row) => $row['student']->id === $this->marta->id);
        $this->assertContains('Vainags', $marta['needed']->pluck('name')->all());
    }

    // vienības izņemšana no inventāra nepārraksta, kas koncerta dienā bija rokās
    public function test_removing_an_item_does_not_rewrite_the_concert(): void
    {
        $krekls = $this->marta->assignedCostumeItems()->where('costume_id', $this->costumes['Krekls']->id)->first();
        $krekls->release($this->teacher, 'admin');

        $this->actingAs($this->teacher)->delete(route('admin.costumes.items.destroy', $krekls));

        $this->assertConcertDay(ready: 2, total: 2);
    }

    // neatgriezeniski izdzēsts students paliek pagājušā koncerta skaitā
    public function test_purged_student_is_still_counted(): void
    {
        $this->actingAs($this->teacher)->delete(route('admin.members.destroy', $this->roberts));
        $this->actingAs($this->teacher)->delete(route('admin.members.force-destroy', $this->roberts), ['password' => 'password']);

        $this->assertConcertDay(ready: 2, total: 2);
    }
}
