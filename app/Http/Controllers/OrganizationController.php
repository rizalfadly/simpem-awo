<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\MemberPosition;
use App\Models\Period;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Data master: divisi, jabatan, periode kepengurusan. */
class OrganizationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Organization/Index', [
            'divisions' => Division::withCount('assignments')->orderBy('name')->get()->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name, 'description' => $d->description,
                'is_active' => $d->is_active, 'assignments_count' => $d->assignments_count,
            ]),
            'positions' => Position::withCount('assignments')->orderBy('sort_order')->orderBy('name')->get()->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'scope' => $p->scope,
                'scope_label' => Position::SCOPES[$p->scope] ?? $p->scope,
                'max_holders' => $p->max_holders, 'sort_order' => $p->sort_order,
                'is_active' => $p->is_active, 'assignments_count' => $p->assignments_count,
            ]),
            'periods' => Period::withCount('assignments')->orderByDesc('start_date')->get()->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name,
                'start_date' => $p->start_date->format('Y-m-d'), 'end_date' => $p->end_date->format('Y-m-d'),
                'range' => $p->start_date->format('d/m/Y').' - '.$p->end_date->format('d/m/Y'),
                'is_current' => $p->is_current, 'assignments_count' => $p->assignments_count,
            ]),
            'scopes' => Position::SCOPES,
        ]);
    }

    // ---------- Divisi ----------
    public function storeDivision(Request $request): RedirectResponse
    {
        Division::create($this->divisionData($request));

        return back()->with('success', 'Divisi ditambahkan.');
    }

    public function updateDivision(Request $request, Division $division): RedirectResponse
    {
        $division->update($this->divisionData($request, $division));

        return back()->with('success', 'Divisi diperbarui.');
    }

    public function destroyDivision(Division $division): RedirectResponse
    {
        if ($division->assignments()->exists()) {
            return back()->with('error', 'Divisi sudah memiliki riwayat penugasan. Nonaktifkan saja, jangan dihapus.');
        }
        $division->delete();

        return back()->with('success', 'Divisi dihapus.');
    }

    // ---------- Jabatan ----------
    public function storePosition(Request $request): RedirectResponse
    {
        Position::create($this->positionData($request));

        return back()->with('success', 'Jabatan ditambahkan.');
    }

    public function updatePosition(Request $request, Position $position): RedirectResponse
    {
        $position->update($this->positionData($request, $position));

        return back()->with('success', 'Jabatan diperbarui.');
    }

    public function destroyPosition(Position $position): RedirectResponse
    {
        if ($position->assignments()->exists()) {
            return back()->with('error', 'Jabatan sudah memiliki riwayat penugasan. Nonaktifkan saja, jangan dihapus.');
        }
        $position->delete();

        return back()->with('success', 'Jabatan dihapus.');
    }

    // ---------- Periode ----------
    public function storePeriod(Request $request): RedirectResponse
    {
        $period = Period::create($this->periodData($request));

        return back()->with('success', 'Periode ditambahkan.'.$this->afterPeriodSaved($period));
    }

    public function updatePeriod(Request $request, Period $period): RedirectResponse
    {
        $period->update($this->periodData($request, $period));

        return back()->with('success', 'Periode diperbarui.'.$this->afterPeriodSaved($period));
    }

    public function destroyPeriod(Period $period): RedirectResponse
    {
        if ($period->assignments()->exists()) {
            return back()->with('error', 'Periode sudah memiliki penugasan dan tidak dapat dihapus.');
        }
        $period->delete();

        return back()->with('success', 'Periode dihapus.');
    }

    // ---------- helpers ----------
    private function divisionData(Request $request, ?Division $division = null): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:80', Rule::unique('divisions', 'name')->ignore($division?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['required', 'boolean'],
        ], ['name.unique' => 'Nama divisi sudah ada.']);
    }

    private function positionData(Request $request, ?Position $position = null): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:80', Rule::unique('positions', 'name')->ignore($position?->id)],
            'scope'       => ['required', Rule::in(array_keys(Position::SCOPES))],
            'max_holders' => ['nullable', 'integer', 'min:1', 'max:99'],
            'sort_order'  => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active'   => ['required', 'boolean'],
        ], ['name.unique' => 'Nama jabatan sudah ada.']);
    }

    private function periodData(Request $request, ?Period $period = null): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:40', Rule::unique('periods', 'name')->ignore($period?->id)],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'is_current' => ['required', 'boolean'],
        ], [
            'name.unique'    => 'Nama periode sudah ada.',
            'end_date.after' => 'Tanggal berakhir harus setelah tanggal mulai.',
        ]);
    }

    /**
     * Hanya satu periode berjalan. Saat periode lain dijadikan berjalan, periode sebelumnya
     * ditutup dan penugasan yang masih terbuka di dalamnya diakhiri otomatis (tercatat di audit log).
     */
    private function afterPeriodSaved(Period $period): string
    {
        if (! $period->is_current) {
            return '';
        }

        $ended = 0;
        DB::transaction(function () use ($period, &$ended) {
            Period::where('id', '!=', $period->id)->where('is_current', true)->get()->each(function (Period $old) use (&$ended) {
                $old->update(['is_current' => false]);
                MemberPosition::where('period_id', $old->id)->whereNull('ended_at')->get()->each(function (MemberPosition $a) use ($old, &$ended) {
                    $a->update(['ended_at' => $old->end_date->lt($a->started_at) ? $a->started_at : $old->end_date]);
                    $ended++;
                });
            });
        });

        return $ended ? " {$ended} jabatan di periode sebelumnya diakhiri otomatis." : '';
    }
}
