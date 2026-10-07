<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// uzaicinājums esošam kontam pievienoties grupai – dalībnieks kļūst tikai tad, kad pats to pieņem
class GroupInvitation extends Model
{
    public const EXPIRES_AFTER_DAYS = 7;

    protected $fillable = [
        'group_id',
        'user_id',
        'invited_by',
        'costume_set_id',
        'status',
        'expires_at',
        'responded_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // vēl gaida atbildi, nav beidzies termiņš un grupa nav izdzēsta
    public function scopeOpen($query)
    {
        return $query->where('status', 'pending')->where('expires_at', '>', now())->whereHas('group');
    }

    public function isOpen(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture() && $this->group !== null;
    }
}
