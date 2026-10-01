<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CostumeItem;
use Illuminate\Http\Request;

// skolotāja meklēšana: tērpu vienības pēc koda vai tērpa nosaukuma (statuss + vēsture) un studenti pēc vārda vai e-pasta
class SearchController extends Controller
{
    // cik rezultātu rādīt katrā sadaļā, lai lapa paliek pārskatāma
    private const LIMIT = 30;

    public function index(Request $request)
    {
        $group = auth()->user()->currentGroup();
        $q = trim((string) $request->query('q', ''));

        if (! $group || $q === '') {
            return view('admin.search', ['q' => $q, 'items' => collect(), 'students' => collect(), 'group' => $group]);
        }

        // kods bez atstarpēm un domuzīmēm, lai "vai03", "VAI 03" un "VAI-03" atrod to pašu
        $compact = strtoupper(preg_replace('/[\s\-]+/', '', $q));
        $like = '%'.$q.'%';

        $items = CostumeItem::query()
            ->whereHas('costume', fn ($c) => $c->where('group_id', $group->id))
            ->where(function ($w) use ($like, $compact) {
                $w->where('code', 'like', $like)
                    ->orWhereRaw("UPPER(REPLACE(code, '-', '')) LIKE ?", ['%'.$compact.'%'])
                    ->orWhereHas('costume', fn ($c) => $c->where('name', 'like', $like));
            })
            ->with(['costume.costumeSet', 'user', 'assignments.assignedBy', 'assignments.returnedBy'])
            ->orderBy('code')
            ->limit(self::LIMIT)
            ->get()
            // precīza koda sakritība vispirms
            ->sortBy(fn ($item) => strtoupper(str_replace('-', '', (string) $item->code)) === $compact ? 0 : 1)
            ->values();

        $students = $group->members()
            ->where(fn ($w) => $w->where('users.name', 'like', $like)->orWhere('users.email', 'like', $like))
            ->with(['assignedCostumeItems' => fn ($i) => $i->whereHas('costume', fn ($c) => $c->where('group_id', $group->id))->with('costume')])
            ->orderBy('users.name')
            ->limit(self::LIMIT)
            ->get();

        $setNames = $group->costumeSets()->pluck('name', 'id');

        return view('admin.search', compact('q', 'items', 'students', 'group', 'setNames'));
    }
}
