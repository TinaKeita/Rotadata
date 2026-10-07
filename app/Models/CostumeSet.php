<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    // dzēšot komplektu, tā tērpi kļūst kopīgi un dalībniekiem komplekts tiek noņemts. To dara šeit, nevis ar
    // datubāzes "set null", jo saliktā ārējā atslēga (komplekts + grupa) neļauj dzēst komplektu, uz kuru kāds vēl atsaucas
    protected static function booted(): void
    {
        static::deleting(function (CostumeSet $set) {
            Costume::withTrashed()->where('costume_set_id', $set->id)->update(['costume_set_id' => null]);
            DB::table('group_user')->where('costume_set_id', $set->id)->update(['costume_set_id' => null]);
            GroupInvitation::where('costume_set_id', $set->id)->update(['costume_set_id' => null]);
        });
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function costumes()
    {
        return $this->hasMany(Costume::class);
    }
}
