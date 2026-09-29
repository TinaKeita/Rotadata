<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// grupas tērpu komplekts (piem. "Meitenes") – nosaka, kuri koncerta tērpi studentam vajadzīgi
class CostumeSet extends Model
{
    protected $fillable = [
        'group_id',
        'name',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function costumes()
    {
        return $this->hasMany(Costume::class);
    }
}
