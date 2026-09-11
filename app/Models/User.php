<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable; use HasRoles; use SoftDeletes;

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
            'invite_email_failed_at' => 'datetime',
        ];
    }
    public function adminGroups()
    {
        return $this->hasMany(Group::class, 'admin_id');
    }

    public function memberGroups()
    {
        return $this->belongsToMany(Group::class, 'group_user');
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
