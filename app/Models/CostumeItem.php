<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostumeItem extends Model
{
    protected $fillable = [
        'costume_id',
        'qr_code',
        'assigned_to',
        'assigned_at'
    ];

    public function costume()
    {
        return $this->belongsTo(Costume::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

