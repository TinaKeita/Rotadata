<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// papildu tērps vienam studentam vienā koncertā (piem. solista tērps) – nāk klāt komplekta prasībām
class EventStudentCostume extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'costume_id',
        'quantity',
    ];

    // group_id ņem no koncerta – datubāzes ārējās atslēgas tad garantē, ka papildu tērps ir no tās pašas grupas
    protected static function booted(): void
    {
        static::creating(function (EventStudentCostume $row) {
            $row->group_id ??= Event::whereKey($row->event_id)->value('group_id');
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function costume()
    {
        return $this->belongsTo(Costume::class);
    }
}
