<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MemberAddedMail;
use App\Mail\MemberWelcomeMail;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    // forma jauna lietotāja pievienošanai
    public function create()
    {
        return view('admin.members.create');
    }

    public function store(Request $request)
    {
        $adminGroup = auth()->user()->adminGroups()->first();
        abort_if(is_null($adminGroup), 403, 'You do not have a group yet.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        // ja šāds konts jau pastāv, pievieno to grupai, nevis veido jaunu
        $existing = User::withTrashed()->where('email', $validated['email'])->first();

        return $existing
            ? $this->attachExisting($existing, $adminGroup)
            : $this->createAndInvite($validated, $adminGroup);
    }

    // pievieno esošu kontu skolotāja grupai un paziņo par to e-pastā (bez paroles)
    private function attachExisting(User $user, Group $group)
    {
        if ($user->trashed()) {
            return back()->withInput()->with('error',
                'That email belongs to an account that was deactivated when its group was deleted. It can’t be added right now.');
        }

        if ($user->hasRole('admin')) {
            return back()->withInput()->with('error', 'That email belongs to a teacher account.');
        }

        if ($group->members()->whereKey($user->id)->exists()) {
            return redirect()->route('admin.members.index')
                ->with('warning', "“{$user->name}” is already in this group.");
        }

        if (! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        $group->members()->attach($user->id);

        rescue(fn () => Mail::to($user->email)->send(new MemberAddedMail($user, $group->name)));

        return redirect()->route('admin.members.index')
            ->with('success', "“{$user->name}” was added to your group and notified by email.");
    }

    // izveido jaunu kontu ar pagaidu paroli un nosūta uzaicinājuma e-pastu
    private function createAndInvite(array $validated, Group $group)
    {
        $tempPassword = Str::random(12);

        $member = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'must_change_password' => true, // pagaidu parole der tikai pirmajai pieslēgšanās reizei
        ]);

        $member->assignRole('member');
        $group->members()->attach($member->id);

        // sūta e-pastu ar pagaidu paroli; ja neizdodas, dalībnieks tik un tā ir izveidots
        try {
            Mail::to($member->email)->send(new MemberWelcomeMail($member, $tempPassword, $group->name));

            return redirect()->route('admin.members.index')
                ->with('success', "Member “{$member->name}” created and an invitation was sent to {$member->email}.");
        } catch (\Throwable $e) {
            \Log::error('Failed to send member invitation email: '.$e->getMessage(), [
                'email' => $member->email,
                'member_id' => $member->id,
            ]);

            return redirect()->route('admin.members.index')
                ->with('warning', "Member “{$member->name}” created, but the email could not be sent. Temporary password: {$tempPassword} — give it to the member in person.");
        }
    }

    // parāda visus lietotājus
    public function index()
    {
        $adminGroup = auth()->user()->adminGroups()->first();
        $members = $adminGroup ? $adminGroup->members : collect();

        return view('admin.members.index', compact('members'));
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load([
            'costumeAssignments.item.costume',
            'costumeAssignments.returnedBy',
        ]);

        return view('admin.members.show', compact('user'));
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $name = $user->name;

        // atbrīvo visas dalībnieka tērpu vienības un aizver atvērtos vēstures ierakstus pirms dzēšanas
        // (datubāzes ārējā atslēga arī iztīra assigned_to, bet vēstures ieraksts citādi paliktu "vēl neatdots")
        foreach ($user->assignedCostumeItems as $item) {
            $item->release(auth()->user(), 'removed');
        }

        // skolotāja veikta dzēšana ir galīga (atšķirībā no grupas dzēšanas, kas ir atgriezeniska)
        $user->forceDelete();

        return redirect()->route('admin.members.index')
            ->with('success', "Member “{$name}” deleted. Their costumes have been released.");
    }

}
