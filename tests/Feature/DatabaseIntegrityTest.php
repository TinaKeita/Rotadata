<?php

namespace Tests\Feature;

use App\Models\Costume;
use App\Models\CostumeSet;
use App\Models\Event;
use App\Models\EventStudentCostume;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// datubāze pati neļauj saitēm pārkāpt grupas robežas – arī tad, ja kāda koda vieta aizmirstu pārbaudi
class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Group $groupA;
    private Group $groupB;
    private CostumeSet $setB;
    private Costume $costumeA;
    private Costume $costumeB;
    private Event $eventA;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        foreach (['A', 'B'] as $name) {
            $teacher = User::factory()->create();
            $teacher->assignRole('admin');
            $this->{'group'.$name} = Group::create(['name' => "Grupa {$name}", 'admin_id' => $teacher->id]);
            $this->{'costume'.$name} = Costume::create(['name' => "Krekls {$name}", 'group_id' => $this->{'group'.$name}->id]);
        }

        $this->setB = $this->groupB->costumeSets()->firstOrFail();

        $this->eventA = Event::create([
            'group_id' => $this->groupA->id,
            'title' => 'Koncerts',
            'starts_at' => now()->addDays(3),
            'created_by' => $this->groupA->admin_id,
        ]);

        $this->student = User::factory()->create();
        $this->groupA->members()->attach($this->student->id);
    }

    public function test_costume_cannot_use_a_set_from_another_group(): void
    {
        $this->expectException(QueryException::class);

        DB::table('costumes')->where('id', $this->costumeA->id)->update(['costume_set_id' => $this->setB->id]);
    }

    public function test_membership_cannot_use_a_set_from_another_group(): void
    {
        $this->expectException(QueryException::class);

        DB::table('group_user')->where('group_id', $this->groupA->id)->update(['costume_set_id' => $this->setB->id]);
    }

    public function test_invitation_cannot_use_a_set_from_another_group(): void
    {
        $this->expectException(QueryException::class);

        GroupInvitation::create([
            'group_id' => $this->groupA->id,
            'user_id' => User::factory()->create()->id,
            'costume_set_id' => $this->setB->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_concert_cannot_require_another_groups_costume(): void
    {
        $this->expectException(QueryException::class);

        $this->eventA->costumes()->attach($this->costumeB->id);
    }

    public function test_extra_costume_must_be_from_the_concert_group(): void
    {
        $this->expectException(QueryException::class);

        EventStudentCostume::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->student->id,
            'costume_id' => $this->costumeB->id,
            'quantity' => 1,
        ]);
    }

    // pareizas saites strādā kā līdz šim, un group_id tiek aizpildīts automātiski
    public function test_same_group_links_still_work(): void
    {
        $this->eventA->costumes()->attach($this->costumeA->id);
        EventStudentCostume::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->student->id,
            'costume_id' => $this->costumeA->id,
            'quantity' => 1,
        ]);

        $this->assertSame($this->groupA->id, (int) DB::table('event_costume')->value('group_id'));
        $this->assertSame($this->groupA->id, (int) DB::table('event_student_costumes')->value('group_id'));
    }

    // komplekta dzēšana atsauces noņem pati, nevis apstājas pie ārējās atslēgas
    public function test_deleting_a_set_clears_it_from_costumes_and_members(): void
    {
        $setA = $this->groupA->costumeSets()->firstOrFail();
        $this->costumeA->update(['costume_set_id' => $setA->id]);
        $this->groupA->members()->updateExistingPivot($this->student->id, ['costume_set_id' => $setA->id]);

        $setA->delete();

        $this->assertNull($this->costumeA->fresh()->costume_set_id);
        $this->assertNull(DB::table('group_user')->where('user_id', $this->student->id)->value('costume_set_id'));
    }

    // grupas neatgriezeniska iztīrīšana strādā arī tad, ja komplektiem ir atsauces
    public function test_purging_a_group_with_sets_in_use_works(): void
    {
        $setA = $this->groupA->costumeSets()->firstOrFail();
        $this->costumeA->update(['costume_set_id' => $setA->id]);
        $this->groupA->members()->updateExistingPivot($this->student->id, ['costume_set_id' => $setA->id]);
        $this->eventA->costumes()->attach($this->costumeA->id);

        $this->groupA->delete();
        $this->groupA->purge();

        $this->assertDatabaseMissing('groups', ['id' => $this->groupA->id]);
        $this->assertDatabaseMissing('costumes', ['id' => $this->costumeA->id]);
        $this->assertSame(0, DB::table('event_costume')->count());
    }
}
