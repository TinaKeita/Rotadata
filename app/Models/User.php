<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable; use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
