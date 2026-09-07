<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostumeItemAssignment extends Model
{
    protected $fillable = [
        'costume_item_id',
        'user_id',
        'user_name',
        'assigned_at',
        'assigned_by',
        'returned_at',
        'returned_by',
        'return_note',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(CostumeItem::class, 'costume_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    // vēl neatdotie ieraksti
    public function scopeOpen($query)
    {
        return $query->whereNull('returned_at');
    }

    public function getIsOpenAttribute(): bool
    {
        return is_null($this->returned_at);
    }
}
