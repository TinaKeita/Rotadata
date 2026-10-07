<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\CostumeItemUnavailableException;
use App\Mail\MemberRemovedMail;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// esošo dalībnieku pārvaldība: saraksts, studenta lapa, komplekts, izsniegšana, izņemšana un atjaunošana.
// Studentu pievienošana un uzaicinājumi – MemberInviteController
class MemberController extends Controller
{
    // maina komplektu vienam vai vairākiem studentiem (Members saraksts un studenta lapa)
    public function updateSet(Request $request)
    {
        $adminGroup = auth()->user()->currentGroup();
        abort_if(is_null($adminGroup), 403, 'You do not have a group yet.');

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
            'costume_set_id' => 'nullable|integer|exists:costume_sets,id,group_id,'.$adminGroup->id,
        ], [
            'user_ids.required' => 'Tick at least one student first.',
        ]);

        // tikai šīs grupas dalībnieki – svešus id klusi ignorējam
        $ids = $adminGroup->members()->whereIn('users.id', $validated['user_ids'])->pluck('users.id');

        // notikušie koncerti saglabā toreizējos komplektus
        Event::snapshotFinished();

        foreach ($ids as $id) {
            $adminGroup->members()->updateExistingPivot($id, ['costume_set_id' => $validated['costume_set_id'] ?? null]);
        }

        $setName = isset($validated['costume_set_id'])
            ? $adminGroup->costumeSets()->whereKey($validated['costume_set_id'])->value('name')
            : null;

        $message = $setName
            ? $ids->count().' '.Str::plural('student', $ids->count())." moved to “{$setName}”."
            : $ids->count().' '.Str::plural('student', $ids->count()).' now have no set.';

        return back()->with('success', $message);
    }

    // skolotājs izsniedz studentam konkrētu vienību, kas viņam ir rokās – izvēlas to pēc koda uz birkas (piem. VAI-03),
    // lai sistēmā piešķirtā vienība vienmēr sakrīt ar fiziski iedoto
    public function handOut(Request $request, User $user)
    {
        $this->authorize('view', $user);

        $adminGroup = auth()->user()->currentGroup();
        abort_if(is_null($adminGroup), 403, 'You do not have a group yet.');

        // students var būt citā šī skolotāja grupā – izsniegt drīkst tikai atvērtās grupas dalībniekam
        abort_unless($user->inGroup($adminGroup), 422, "That member isn't in the group you have open.");

        // uzaicināts, bet vēl nav izvēlējies savu paroli – tērpus vēl nevar izsniegt
        abort_if($user->must_change_password, 422, "{$user->name} hasn't signed in yet. Costumes can be handed out once they've set their own password.");

        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
        ]);

        // tikai šīs grupas tērpu vienības
        $item = $adminGroup->costumeItems()->whereKey($validated['item_id'])->first();
        abort_if(is_null($item), 422, 'That item is not in your group.');

        try {
            $item->assignTo($user, auth()->user());
        } catch (CostumeItemUnavailableException) {
            return back()->with('error', "Item {$item->code} is already with someone else. Check the code on the label.");
        }

        return back()->with('success', "{$item->code} ({$item->costume->name}) handed out to {$user->name}.");
    }

    // parāda visus lietotājus
    public function index()
    {
        $adminGroup = auth()->user()->currentGroup();
        // roles un citu grupu skaits jau šeit, lai skats var izvēlēties "Remove" (atsaista) vai "Delete" (dzēš kontu) pogu
        $members = $adminGroup
            ? $adminGroup->members()->with('roles')->withCount('memberGroups')->get()
            : collect();

        // nesen izņemti dalībnieki, kurus deaktivizēja tieši šīs grupas dēļ – paša dzēstus kontus
        // un citas grupas dēļ deaktivizētus skolotājs neredz un neatjauno
        $trashedMembers = $adminGroup
            ? User::onlyTrashed()
                ->where('deactivated_with_group_id', $adminGroup->id)
                ->get()
            : collect();

        // esošiem kontiem nosūtīti uzaicinājumi, uz kuriem vēl nav atbildēts
        $invitations = $adminGroup
            ? $adminGroup->invitations()->open()->with('user')->latest()->get()
            : collect();

        $sets = $adminGroup?->costumeSets ?? collect();

        return view('admin.members.index', compact('members', 'trashedMembers', 'invitations', 'sets'));
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        // students var būt vairākās grupās – rāda tikai izsniegumus no ŠĪ skolotāja grupas(-ām),
        // nevis visu vēsturi, kurā ietilptu arī cita skolotāja grupas tērpi
        $groupIds = auth()->user()->adminGroups()->pluck('id');

        $user->load([
            'costumeAssignments' => function ($query) use ($groupIds) {
                $query->whereHas('item.costume', fn ($q) => $q->whereIn('group_id', $groupIds))
                    ->with(['item.costume', 'returnedBy']);
            },
        ]);

        // komplekts skolotāja grupā (students var būt vairākās grupās, bet skolotājam ir viena)
        $adminGroup = auth()->user()->currentGroup();
        $sets = $adminGroup?->costumeSets ?? collect();
        $currentSetId = $adminGroup?->members()->whereKey($user->id)->first()?->pivot->costume_set_id;

        // tērpi ar brīvajām vienībām izsniegšanas formai – skolotājs izvēlas konkrēto kodu
        $costumes = $adminGroup
            ? $adminGroup->costumes()->with(['items' => fn ($q) => $q->whereNull('assigned_to')->orderBy('code')])->orderBy('name')->get()
            : collect();

        return view('admin.members.show', compact('user', 'sets', 'currentSetId', 'costumes'));
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $adminGroup = auth()->user()->currentGroup();
        abort_if(is_null($adminGroup), 403);

        $name = $user->name;

        // notikušie koncerti saglabā šo studentu savā skaitā
        Event::snapshotFinished();

        // skolotāju kontus nekad nedzēšam, un students var būt arī citās grupās -> abos gadījumos tikai
        // atsaistam NO ŠĪS grupas, konts un pārējās grupas/tiesības paliek neskartas
        if ($user->hasRole('admin') || $user->memberGroups()->count() > 1) {
            // tērpu atbrīvošana un atsaistīšana notiek kopā vai nemaz
            DB::transaction(function () use ($user, $adminGroup) {
                $heldFromThisGroup = $user->assignedCostumeItems()
                    ->whereHas('costume', fn ($query) => $query->where('group_id', $adminGroup->id))
                    ->get();

                foreach ($heldFromThisGroup as $item) {
                    $item->release(auth()->user(), 'left_group');
                }

                $adminGroup->members()->detach($user->id);
            });

            // students uzzina par izņemšanu (tāpat kā par pievienošanu); e-pasta kļūme darbību neaptur.
            // Uzaicinātais, kurš vēl nav pieslēdzies, citus e-pastus kā uzaicinājumu nesaņem
            if (! $user->must_change_password) {
                rescue(fn () => Mail::to($user->email)->send(new MemberRemovedMail($user, $adminGroup->name)));
            }

            $reason = $user->hasRole('admin') ? "they're a teacher" : "they're still in other groups";

            return redirect()->route('admin.members.index')
                ->with('success', "“{$name}” removed from your group. Since {$reason}, their account was kept.");
        }

        // vienīgā grupa un nav skolotājs -> konta mīkstā dzēšana (atbrīvo VISAS vienības un aizver atvērtos vēstures ierakstus)
        // datubāzes ārējā atslēga arī iztīra assigned_to, bet vēstures ieraksts citādi paliktu "vēl neatdots"
        DB::transaction(function () use ($user, $adminGroup) {
            foreach ($user->assignedCostumeItems as $item) {
                $item->release(auth()->user(), 'removed');
            }

            // skolotāja veikta dzēšana tagad ir atgriezeniska, tāpat kā grupas dzēšana –
            // atzīmē, kuras grupas dēļ konts deaktivizēts, lai to varētu vēlāk atjaunot
            $user->update(['deactivated_with_group_id' => $adminGroup->id]);
            $user->delete();
        });

        $purgeDate = now()->addDays(Group::PURGE_AFTER_DAYS)->format('d.m.Y');

        if (! $user->must_change_password) {
            rescue(fn () => Mail::to($user->email)->send(new MemberRemovedMail($user, $adminGroup->name, $purgeDate)));
        }

        return redirect()->route('admin.members.index')
            ->with('success', "Member “{$name}” deleted. Their costumes have been released. You can restore the account until {$purgeDate}.");
    }

    // atjauno skolotāja izņemtu (vienīgās grupas) dalībnieku
    public function restore(User $user)
    {
        abort_unless($user->trashed(), 404);
        $this->authorize('restore', $user);

        $name = $user->name;

        // koncerti, kas notika, kamēr students bija izņemts, viņu savā skaitā neieskaita
        Event::snapshotFinished();

        DB::transaction(function () use ($user) {
            $user->restore();
            $user->update(['deactivated_with_group_id' => null]);
        });

        return redirect()->route('admin.members.index')
            ->with('success', "“{$name}” restored and added back to your group.");
    }

    // iztīra izņemto dalībnieku uzreiz, negaidot 30 dienas – prasa paroli, jo tas ir neatgriezeniski
    public function forceDestroy(Request $request, User $user)
    {
        abort_unless($user->trashed(), 404);
        $this->authorize('forceDelete', $user);

        $request->validateWithBag('forceDestroy'.$user->id, [
            'password' => ['required', 'current_password'],
        ]);

        $name = $user->name;
        $user->forceDelete();

        return redirect()->route('admin.members.index')
            ->with('success', "“{$name}” permanently deleted.");
    }

}
