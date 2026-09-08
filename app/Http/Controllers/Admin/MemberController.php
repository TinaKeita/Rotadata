<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;  
use App\Mail\MemberWelcomeMail;
use Spatie\Permission\Models\Role;

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

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email|max:255'
        ], [
            'email.unique' => 'A user with this email address is already in the database.',
        ]);

        $tempPassword = Str::random(12);
        $member = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($tempPassword),
            'must_change_password' => true, // pagaidu parole der tikai pirmajai pieslēgšanās reizei
        ]);

        $member->assignRole('member');

        $adminGroup->members()->attach($member->id);

        // sūta e-pastu ar pagaidu paroli; ja neizdodas, dalībnieks tik un tā ir izveidots
        try {
            Mail::to($member->email)->send(new MemberWelcomeMail($member, $tempPassword, $adminGroup->name));

            return redirect()->route('admin.members.index')
                ->with('success', "Dalībnieks “{$member->name}” izveidots un uzaicinājums nosūtīts uz {$member->email}.");
        } catch (\Throwable $e) {
            \Log::error('Uzaicinājuma e-pastu neizdevās nosūtīt: '.$e->getMessage(), [
                'email' => $member->email,
                'member_id' => $member->id,
            ]);

            return redirect()->route('admin.members.index')
                ->with('warning', "Dalībnieks “{$member->name}” izveidots, bet e-pastu neizdevās nosūtīt. Pagaidu parole: {$tempPassword} — nododiet to dalībniekam personīgi.");
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
        $user->delete();

        return redirect()->route('admin.members.index')
            ->with('success', "Dalībnieks “{$name}” dzēsts.");
    }

}
