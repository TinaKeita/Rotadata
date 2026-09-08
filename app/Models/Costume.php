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

    // pievieno tērpam jaunas vienības ar QR kodu un turpina salasāmo kodu numerāciju
    public function addItems(int $count): void
    {
        $prefix = $this->code_prefix ?: static::makeCodePrefix($this->name, $this->group_id, $this->id);

        // pēdējais izmantotais numurs (piem. BRU-05 -> 5), lai jaunās vienības turpinātu virkni
        $lastNumber = $this->items()
            ->pluck('code')
            ->map(fn ($code) => (int) Str::afterLast((string) $code, '-'))
            ->max() ?? 0;

        for ($i = 1; $i <= $count; $i++) {
            $this->items()->create([
                'qr_code' => Str::uuid(),
                'code' => sprintf('%s-%02d', $prefix, $lastNumber + $i),
                'assigned_to' => null,
            ]);
        }

        $this->update([
            'code_prefix' => $prefix,
            'quantity' => $this->items()->count(),
        ]);
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
