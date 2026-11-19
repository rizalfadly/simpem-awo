<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\MemberPosition;
use App\Models\Period;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Struktur pengurus per periode. Terbuka untuk semua anggota yang login. */
class StructureController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $periods = Period::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $period = $request->integer('period')
            ? $periods->firstWhere('id', $request->integer('period'))
            : ($periods->firstWhere('is_current', true) ?? $periods->first());

        if (! $period) {
            return Inertia::render('Structure/Index', ['periods' => [], 'period' => null, 'leadership' => [], 'divisions' => []]);
        }

        $rows = MemberPosition::with(['member:id,name,photo_path', 'position', 'division'])
            ->where('period_id', $period->id)
            ->when($period->is_current, fn ($q) => $q->whereNull('ended_at'))
            ->get()
            ->sortBy([fn ($a, $b) => $a->position->sort_order <=> $b->position->sort_order, fn ($a, $b) => strcmp($a->member->name, $b->member->name)]);

        $entry = fn (MemberPosition $a) => [
            'id'        => $a->id,
            'member_id' => $a->member->id,
            'name'      => $a->member->name,
            'photo_url' => $a->member->photo_url,
            'position'  => $a->position->name,
        ];

        return Inertia::render('Structure/Index', [
            'periods'    => $periods,
            'period'     => $period->only(['id', 'name', 'is_current']),
            'leadership' => $rows->whereNull('division_id')->map($entry)->values(),
            'divisions'  => Division::where('is_active', true)->orderBy('name')->get()->map(fn (Division $d) => [
                'id'      => $d->id,
                'name'    => $d->name,
                'entries' => $rows->where('division_id', $d->id)->map($entry)->values(),
            ])->values(),
        ]);
    }
}
