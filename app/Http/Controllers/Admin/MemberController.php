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
            'password' => Hash::make($tempPassword)
        ]);

        $member->assignRole('member');

        $adminGroup->members()->attach($member->id);

        // sūtīt e pastu ziņu ar paroli
        try {
            \Log::info('Preparing welcome email', [
                'username' => env('MAIL_USERNAME'),
                'recipient' => $member->email,
                'member_id' => $member->id,
            ]);

            Mail::to($member->email)->send(new MemberWelcomeMail($member, $tempPassword));

            \Log::info('Welcome email sent', [
                'username' => env('MAIL_USERNAME'),
                'recipient' => $member->email,
                'member_id' => $member->id,
            ]);

            return redirect()->route('admin.dashboard')->with('success', 'Member created and email sent!');
        } catch (\Exception $e) {
            \Log::error('Mail failed: '.$e->getMessage(), [
                'email' => $member->email,
                'member_id' => $member->id,
            ]);

            throw $e;
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

        $user->delete();
        return redirect()->route('admin.members.index')
            ->with('success', 'Member deleted successfully.');
    }

}
