<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Costume extends Model
{
    protected $fillable = [
        'name',
        'image',
        'quantity',
        'group_id'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function items()
    {
        return $this->hasMany(CostumeItem::class);
    }
}

