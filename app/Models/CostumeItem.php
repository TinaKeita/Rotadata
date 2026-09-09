<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public function assignTo(User $user, User $by): void
    {
        $this->update([
            'assigned_to' => $user->id,
            'assigned_at' => now(),
        ]);

        $this->assignments()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'assigned_at' => now(),
            'assigned_by' => $by->id,
        ]);
    }

    // nodod vienību citam dalībniekam: aizver pašreizējo vēstures ierakstu un uzreiz atver jaunu
    // vecais ieraksts tiek atzīmēts ar 'transfer', lai vēsturē redzams, ka tā bija nodošana, nevis atdošana
    public function transferTo(User $to): void
    {
        // viens laika zīmogs abiem ierakstiem – pēc tā panelī atpazīst, ka bija nodošana, nevis atsevišķa atdošana un paņemšana
        $now = now();

        $this->assignments()
            ->whereNull('returned_at')
            ->first()
            ?->update([
                'returned_at' => $now,
                'returned_by' => $to->id,
                'return_note' => 'transfer',
            ]);

        $this->update([
            'assigned_to' => $to->id,
            'assigned_at' => $now,
        ]);

        $this->assignments()->create([
            'user_id' => $to->id,
            'user_name' => $to->name,
            'assigned_at' => $now,
            'assigned_by' => $to->id,
        ]);
    }

    // atgriež vienību un aizver atvērto vēstures ierakstu
    // $note: 'self' = students atdeva pats, 'admin' = skolotājs paņēma atpakaļ
    public function release(User $by, string $note): void
    {
        $this->assignments()
            ->whereNull('returned_at')
            ->first()
            ?->update([
                'returned_at' => now(),
                'returned_by' => $by->id,
                'return_note' => $note,
            ]);

        $this->update([
            'assigned_to' => null,
            'assigned_at' => null,
        ]);
    }
}
