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
