<?php

namespace App\Models;

use App\Exceptions\CostumeItemUnavailableException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CostumeItem extends Model
{
    protected $fillable = [
        'costume_id',
        'qr_code',
        'code',
        'assigned_to',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function costume()
    {
        return $this->belongsTo(Costume::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // pilna piešķiršanas vēsture, jaunākā pirmā
    public function assignments()
    {
        return $this->hasMany(CostumeItemAssignment::class)->latest('assigned_at');
    }

    // pašreiz atvērtais (vēl neatdotais) vēstures ieraksts
    public function openAssignment()
    {
        return $this->hasOne(CostumeItemAssignment::class)->whereNull('returned_at');
    }

    // piešķir vienību lietotājam un atver jaunu vēstures ierakstu
    // rindu slēdzam un pārbaudi atkārtojam transakcijā, lai divi vienlaicīgi skenējumi (piem. dubultklikšķis
    // lēnā tīklā) nevarētu abi "uzvarēt" – ja kāds cits jau paspējis piešķirt, izmet CostumeItemUnavailableException
    public function assignTo(User $user, User $by): void
    {
        DB::transaction(function () use ($user, $by) {
            $locked = self::whereKey($this->id)->lockForUpdate()->first();

            if (! $locked || $locked->assigned_to !== null) {
                throw new CostumeItemUnavailableException('This item was already claimed by someone else.');
            }

            $locked->update([
                'assigned_to' => $user->id,
                'assigned_at' => now(),
            ]);

            $locked->assignments()->create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'assigned_at' => $locked->assigned_at,
                'assigned_by' => $by->id,
            ]);
        });

        $this->refresh();
    }

    // nodod vienību citam dalībniekam: aizver pašreizējo vēstures ierakstu un uzreiz atver jaunu
    // vecais ieraksts tiek atzīmēts ar 'transfer', lai vēsturē redzams, ka tā bija nodošana, nevis atdošana
    public function transferTo(User $to): void
    {
        DB::transaction(function () use ($to) {
            $locked = self::whereKey($this->id)->lockForUpdate()->first();

            if (! $locked || is_null($locked->assigned_to) || (int) $locked->assigned_to === $to->id) {
                throw new CostumeItemUnavailableException('This item is no longer available to take over.');
            }

            // viens laika zīmogs abiem ierakstiem – pēc tā panelī atpazīst, ka bija nodošana, nevis atsevišķa atdošana un paņemšana
            $now = now();

            $locked->assignments()
                ->whereNull('returned_at')
                ->first()
                ?->update([
                    'returned_at' => $now,
                    'returned_by' => $to->id,
                    'return_note' => 'transfer',
                ]);

            $locked->update([
                'assigned_to' => $to->id,
                'assigned_at' => $now,
            ]);

            $locked->assignments()->create([
                'user_id' => $to->id,
                'user_name' => $to->name,
                'assigned_at' => $now,
                'assigned_by' => $to->id,
            ]);
        });

        $this->refresh();
    }

    // atgriež vienību un aizver atvērto vēstures ierakstu
    // $note: 'self' = students atdeva pats, 'admin' = skolotājs paņēma atpakaļ
    // ja vienība starplaikā jau atgriezta (piem. divi vienlaicīgi atdošanas klikšķi), vienkārši neko nedara
    public function release(User $by, string $note): void
    {
        DB::transaction(function () use ($by, $note) {
            $locked = self::whereKey($this->id)->lockForUpdate()->first();

            if (! $locked || is_null($locked->assigned_to)) {
                return;
            }

            $locked->assignments()
                ->whereNull('returned_at')
                ->first()
                ?->update([
                    'returned_at' => now(),
                    'returned_by' => $by->id,
                    'return_note' => $note,
                ]);

            $locked->update([
                'assigned_to' => null,
                'assigned_at' => null,
            ]);
        });

        $this->refresh();
    }
}
