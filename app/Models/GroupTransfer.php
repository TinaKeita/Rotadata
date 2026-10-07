<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// pieprasījums nodot grupu citam skolotājam
class GroupTransfer extends Model
{
    // cik dienas saņēmējs var pieņemt pieprasījumu
    public const EXPIRES_AFTER_DAYS = 7;

    protected $fillable = [
        'group_id',
        'from_user_id',
        'to_user_id',
        'token',
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

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
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
