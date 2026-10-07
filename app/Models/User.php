<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable; use HasRoles; use SoftDeletes;

    // cik dienas der skolotāja izveidotā pagaidu parole
    public const TEMPORARY_PASSWORD_DAYS = 7;

    public const TEMPORARY_PASSWORD_EXPIRED_MESSAGE = 'Your temporary password has expired. Ask your teacher to resend your invite — the new email lets you choose your own password.';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'temporary_password_expires_at',
        'deactivated_with_group_id',
        'invite_email_failed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
            'invite_email_failed_at' => 'datetime',
        ];
    }

    // konts joprojām lieto skolotāja doto pagaidu paroli, un tās termiņš ir beidzies
    public function temporaryPasswordExpired(): bool
    {
        return $this->must_change_password
            && $this->temporary_password_expires_at
            && $this->temporary_password_expires_at->isPast();
    }
    // "Forgot password" saite Rotadata e-pasta noformējumā, nevis Laravel noklusējuma veidnē
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordLinkNotification($token));
    }

    public function adminGroups()
    {
        return $this->hasMany(Group::class, 'admin_id');
    }

    // sesijas atslēga, kurā glabā skolotāja pašlaik atvērto grupu
    public const CURRENT_GROUP_KEY = 'current_group_id';

    // vienā pieprasījumā nolasītā pašreizējā grupa (false – vēl nav nolasīta)
    private Group|null|false $currentGroupCache = false;

    /**
     * Skolotāja pašlaik atvērtā grupa: tā, ko viņš pēdējo izvēlējās navigācijā, vai vecākā no viņa grupām.
     * Visas skolotāja lapas (panelis, dalībnieki, tērpi, koncerti, iestatījumi) strādā ar šo grupu.
     */
    public function currentGroup(): ?Group
    {
        if ($this->currentGroupCache !== false) {
            return $this->currentGroupCache;
        }

        $id = session(self::CURRENT_GROUP_KEY);
        $group = $id ? $this->adminGroups()->whereKey($id)->first() : null;

        return $this->currentGroupCache = $group ?? $this->adminGroups()->orderBy('id')->first();
    }

    // padara grupu par pašreizējo (pārslēdzot navigācijā, izveidojot jaunu vai pārņemot)
    public function switchToGroup(Group $group): void
    {
        session([self::CURRENT_GROUP_KEY => $group->id]);
        $this->currentGroupCache = $group;
    }

    // pivot costume_set_id – studenta tērpu komplekts katrā grupā
    public function memberGroups()
    {
        return $this->belongsToMany(Group::class, 'group_user')->withPivot('costume_set_id');
    }

    // uzaicinājumi pievienoties citu skolotāju grupām
    public function groupInvitations()
    {
        return $this->hasMany(GroupInvitation::class);
    }

    // grupa, kuras dēļ šis konts tika deaktivizēts (grupas dzēšana vai skolotāja veikta izņemšana)
    public function deactivatedFromGroup()
    {
        return $this->belongsTo(Group::class, 'deactivated_with_group_id');
    }

    public function assignedCostumeItems()
    {
        return $this->hasMany(CostumeItem::class, 'assigned_to');
    }

    // šī lietotāja pilna tērpu piešķiršanas vēsture, jaunākā pirmā
    public function costumeAssignments()
    {
        return $this->hasMany(CostumeItemAssignment::class)->latest('assigned_at');
    }

    // vai šis lietotājs ir šīs grupas administrators (skolotājs)
    public function ownsGroup(Group $group): bool
    {
        return $group->admin_id === $this->id;
    }

    // vai šis lietotājs ir šīs grupas dalībnieks (students)
    public function inGroup(Group $group): bool
    {
        return $this->memberGroups()->whereKey($group->id)->exists();
    }

    // vai šis administrators pārvalda kādu grupu, kurā ir dotais dalībnieks
    public function sharesGroupWithMember(User $member): bool
    {
        return $this->adminGroups()
            ->whereHas('members', fn ($query) => $query->whereKey($member->id))
            ->exists();
    }
}
