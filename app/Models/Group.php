<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Group extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'admin_id',
    ];

    // pēc cik dienām mīksti dzēsta grupa tiek neatgriezeniski iztīrīta
    public const PURGE_AFTER_DAYS = 30;

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_user');
    }

    public function costumes()
    {
        return $this->hasMany(Costume::class);
    }

    // visas šīs grupas tērpu vienības (caur tērpiem)
    public function costumeItems()
    {
        return CostumeItem::whereHas('costume', fn ($query) => $query->where('group_id', $this->id));
    }

    // datums, kad grupa tiks neatgriezeniski iztīrīta
    public function purgeAt(): ?\Illuminate\Support\Carbon
    {
        return $this->deleted_at?->copy()->addDays(self::PURGE_AFTER_DAYS);
    }

    /**
     * Mīksti dzēš grupu un deaktivizē tos dalībniekus, kuriem šī bija vienīgā grupa.
     * Atgriež ['deactivated' => Collection, 'removed' => Collection] paziņojumu sūtīšanai.
     */
    public function softDeleteWithMembers(): array
    {
        $deactivated = new Collection();
        $removed = new Collection();

        foreach ($this->members()->get() as $member) {
            if ($member->hasRole('admin')) {
                continue; // skolotāju neaiztiekam
            }

            // šajā brīdī grupa vēl nav dzēsta, tāpēc skaits ietver arī šo grupu
            if ($member->memberGroups()->count() > 1) {
                $removed->push($member);
            } else {
                $deactivated->push($member);
            }
        }

        if ($deactivated->isNotEmpty()) {
            $ids = $deactivated->pluck('id');
            User::whereIn('id', $ids)->update(['deactivated_with_group_id' => $this->id]);
            User::whereIn('id', $ids)->delete(); // mīkstā dzēšana
        }

        $this->delete(); // mīkstā dzēšana

        return ['deactivated' => $deactivated, 'removed' => $removed];
    }

    /**
     * Atjauno grupu un ar to deaktivizētos dalībniekus. Atgriež atjaunotos dalībniekus.
     */
    public function restoreWithMembers(): Collection
    {
        $this->restore();

        $reactivated = User::onlyTrashed()
            ->where('deactivated_with_group_id', $this->id)
            ->get();

        User::onlyTrashed()->where('deactivated_with_group_id', $this->id)->restore();
        User::where('deactivated_with_group_id', $this->id)->update(['deactivated_with_group_id' => null]);

        return $reactivated;
    }

    /**
     * Neatgriezeniski dzēš grupu: kaskāde iztīra tērpus, vienības, vēsturi un dalībnieku sarakstu,
     * un dzēš dalībniekus, kuri bija tikai šajā grupā.
     */
    public function purge(): void
    {
        User::onlyTrashed()->where('deactivated_with_group_id', $this->id)->forceDelete();

        $this->forceDelete();
    }
}
