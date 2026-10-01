<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// grupas tērpu komplekts (piem. "Meitenes") – nosaka, kuri koncerta tērpi studentam vajadzīgi
class CostumeSet extends Model
{
    // iebūvētie komplekti, kas katrai grupai tiek izveidoti automātiski
    public const BUILT_IN = ['Girls', 'Boys'];

    protected $fillable = [
        'group_id',
        'name',
        'built_in',
    ];

    protected $casts = [
        'built_in' => 'boolean',
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
