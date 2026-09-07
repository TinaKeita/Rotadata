<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Costume extends Model
{
    protected $fillable = [
        'name',
        'code_prefix',
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

    // izveido īsu, cilvēkam salasāmu prefiksu, kas ir unikāls grupas ietvaros
    public static function makeCodePrefix(string $name, int $groupId, ?int $ignoreId = null): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z]/', '', Str::ascii($name)));
        $base = substr($base, 0, 3) ?: 'ITM';

        $prefix = $base;
        $suffix = 2;

        while (
            static::where('group_id', $groupId)
                ->where('code_prefix', $prefix)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $prefix = $base.$suffix++;
        }

        return $prefix;
    }
}
