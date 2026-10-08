<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\GroupTransferRequestMail;
use App\Mail\GroupTransferResultMail;
use App\Models\Group;
use App\Models\GroupTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Grupas nodošana citam skolotājam: pašreizējais skolotājs izvēlas esošu skolotāja kontu un apstiprina ar savu paroli,
 * saņēmējs e-pastā saņem saiti un pieņem vai noraida. Pieņemot grupa ar visu saturu pāriet saņēmējam.
 */
class GroupTransferController extends Controller
{
    // skolotāju meklēšana nodošanas formai (ieteikumi rakstīšanas laikā)
    public function teachers(Request $request)
    {
        $group = $this->ownGroup();
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        // % un _ ir parasti simboli, nevis SQL aizstājējzīmes – citādi ar "%%" varētu izvilkt visus skolotājus
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q).'%';

        // meklē pēc vārda vai pēc precīza e-pasta – ar e-pasta daļu nevar izvilkt citu skolotāju adreses
        $teachers = User::role('admin')
            ->whereKeyNot(auth()->id())
            ->where(fn ($w) => $w->whereRaw("name LIKE ? ESCAPE '!'", [$like])->orWhereRaw('lower(email) = ?', [mb_strtolower($q)]))
            ->orderBy('name')
            ->limit(8)
            ->get();

        return response()->json($teachers->map(fn (User $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'email' => self::maskEmail($t->email),
        ])->values());
    }

    // "janis@example.com" -> "j***@example.com": pietiek, lai atšķirtu divus vienāda vārda skolotājus,
    // bet pilnu adresi reģistrējies svešinieks neuzzina
    public static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domain;
    }

    // nosūta pieprasījumu saņēmējam (skolotājs apstiprina ar savu paroli)
    public function store(Request $request)
    {
        $group = $this->ownGroup();

        $validated = $request->validateWithBag('transfer', [
            'to_user_id' => ['required', 'integer'],
            'password' => ['required', 'current_password'],
        ], [
            'to_user_id.required' => 'Choose the teacher to hand the group to.',
        ]);

        $recipient = User::role('admin')->whereKeyNot(auth()->id())->find($validated['to_user_id']);

        if (! $recipient) {
            throw ValidationException::withMessages(['to_user_id' => 'Choose an existing teacher account.'])->errorBag('transfer');
        }

        // vienlaikus tikai viens atvērts pieprasījums – iepriekšējā atcelšana un jaunā izveide notiek kopā,
        // ar bloķētu grupas rindu, lai divi vienlaicīgi pieprasījumi neatstātu divus atvērtus
        $transfer = DB::transaction(function () use ($group, $recipient) {
            Group::whereKey($group->id)->lockForUpdate()->firstOrFail();

            $group->transfers()->where('status', 'pending')->update(['status' => 'cancelled', 'responded_at' => now()]);

            return $group->transfers()->create([
                'from_user_id' => auth()->id(),
                'to_user_id' => $recipient->id,
                'token' => Str::random(48),
                'expires_at' => now()->addDays(GroupTransfer::EXPIRES_AFTER_DAYS),
            ]);
        });

        $sent = rescue(fn () => Mail::to($recipient->email)->send(new GroupTransferRequestMail($transfer, $this->impact($group))) || true, false);

        return redirect()->route('admin.group.settings')->with(
            $sent ? 'success' : 'warning',
            $sent
                ? "Request sent to {$recipient->name}. The group stays yours until they accept."
                : "Request saved, but the email to {$recipient->name} could not be sent. They can still accept it from their dashboard."
        );
    }

    // atceļ vēl neatbildētu pieprasījumu
    public function cancel()
    {
        $group = $this->ownGroup();

        // ar bloķētu grupas rindu – atcelšana nevar notikt vienlaikus ar pieņemšanu
        DB::transaction(function () use ($group) {
            Group::whereKey($group->id)->lockForUpdate()->firstOrFail();

            $group->transfers()->where('status', 'pending')->update(['status' => 'cancelled', 'responded_at' => now()]);
        });

        return redirect()->route('admin.group.settings')->with('success', 'Handover request cancelled.');
    }

    // saņēmēja pārskata lapa (atver no e-pasta vai paneļa)
    public function show(string $token)
    {
        $transfer = $this->incoming($token);
        $group = $transfer->group;

        return view('admin.group.transfer', [
            'transfer' => $transfer,
            'impact' => $group ? $this->impact($group) : null,
        ]);
    }

    // saņēmējs pieņem: grupa pāriet viņam un tiek pievienota viņa grupām (esošās paliek)
    public function accept(string $token)
    {
        $transfer = $this->incoming($token);
        abort_unless($transfer->isOpen(), 410, 'This handover request is no longer open.');

        $me = auth()->user();

        // pārbaudes atkārto transakcijā ar bloķētām rindām (vispirms grupa, tad pieprasījums) – divi vienlaicīgi
        // pieņemšanas, atcelšanas vai jauna pieprasījuma klikšķi gaida viens otru, nevis abi iziet pārbaudes
        $group = DB::transaction(function () use ($transfer, $me) {
            $group = Group::whereKey($transfer->group_id)->lockForUpdate()->first();
            $locked = GroupTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            abort_unless($group && $locked->status === 'pending' && $locked->expires_at->isFuture(), 410, 'This handover request is no longer open.');

            // nosūtītājam joprojām jābūt grupas skolotājam (piem. nav nodevis kādam citam starplaikā)
            abort_unless((int) $group->admin_id === (int) $locked->from_user_id, 410, 'This group has changed hands in the meantime.');

            // ja saņēmējs bija šīs grupas dalībnieks, tagad viņš ir tās skolotājs
            $group->members()->detach($me->id);

            $group->update(['admin_id' => $me->id]);

            $locked->update(['status' => 'accepted', 'responded_at' => now()]);

            return $group;
        });

        rescue(fn () => Mail::to($transfer->fromUser->email)->send(new GroupTransferResultMail($transfer->fresh(['group', 'fromUser', 'toUser']), true)));

        // pārņemtā grupa uzreiz kļūst par atvērto; saņēmēja citas grupas paliek
        $me->switchToGroup($group->fresh());

        return redirect()->route('admin.dashboard')->with('success', "You now run “{$group->name}”. Switch between your groups in the menu.");
    }

    // saņēmējs noraida: nekas nemainās, nosūtītājs saņem e-pastu
    public function decline(string $token)
    {
        $transfer = $this->incoming($token);
        abort_unless($transfer->isOpen(), 410, 'This request is no longer open.');

        // atkārto pārbaudi ar bloķētu rindu – pieprasījums nevar tikt reizē pieņemts un noraidīts
        DB::transaction(function () use ($transfer) {
            $locked = GroupTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 410, 'This request is no longer open.');

            $locked->update(['status' => 'declined', 'responded_at' => now()]);
        });

        rescue(fn () => Mail::to($transfer->fromUser->email)->send(new GroupTransferResultMail($transfer->fresh(['group', 'fromUser', 'toUser']), false)));

        return redirect()->route('admin.dashboard')->with('success', 'Request declined. Nothing has changed.');
    }

    private function ownGroup(): Group
    {
        $group = auth()->user()->currentGroup();
        abort_if(is_null($group), 403, 'You do not have a group.');

        return $group;
    }

    // pieprasījums, kas adresēts tieši pieslēgtajam skolotājam
    private function incoming(string $token): GroupTransfer
    {
        $transfer = GroupTransfer::with(['group', 'fromUser', 'toUser'])->where('token', $token)->firstOrFail();
        $this->authorize('respond', $transfer);

        return $transfer;
    }

    // ko saņēmējs pārņems – rāda e-pastā un pārskata lapā
    private function impact(Group $group): array
    {
        $itemIds = $group->costumeItems()->pluck('costume_items.id');

        return [
            'students' => $group->members()->count(),
            'costumes' => $group->costumes()->count(),
            'items' => $itemIds->count(),
            'itemsOut' => $group->costumeItems()->whereNotNull('assigned_to')->count(),
            'upcoming' => $group->events()->upcoming()->count(),
        ];
    }
}
