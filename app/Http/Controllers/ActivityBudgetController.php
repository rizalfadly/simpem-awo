<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityBudgetItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Rencana anggaran (RAB) dikunci setelah proposal diajukan.
 * Realisasi (actual) diisi saat menyusun LPJ. Pencatatan kas sebenarnya menyusul di V1.4.
 */
class ActivityBudgetController extends Controller
{
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->canEditProposal()) {
            return back()->with('error', 'Rencana anggaran terkunci setelah proposal diajukan.');
        }

        $activity->budgetItems()->create($this->validated($request));

        return back()->with('success', 'Item anggaran ditambahkan.');
    }

    public function update(Request $request, ActivityBudgetItem $budgetItem): RedirectResponse
    {
        $activity = $budgetItem->activity;
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->canEditProposal()) {
            return back()->with('error', 'Rencana anggaran terkunci setelah proposal diajukan.');
        }

        $budgetItem->update($this->validated($request));

        return back()->with('success', 'Item anggaran diperbarui.');
    }

    public function destroy(Request $request, ActivityBudgetItem $budgetItem): RedirectResponse
    {
        $activity = $budgetItem->activity;
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->canEditProposal()) {
            return back()->with('error', 'Rencana anggaran terkunci setelah proposal diajukan.');
        }

        $budgetItem->delete();

        return back()->with('success', 'Item anggaran dihapus.');
    }

    /** Realisasi per item, hanya selama LPJ berstatus draf/revisi. */
    public function actual(Request $request, ActivityBudgetItem $budgetItem): RedirectResponse
    {
        $activity = $budgetItem->activity->load('report');
        abort_unless($request->user()->can('manage', $activity), 403);

        if ($activity->status !== 'completed' || ! $activity->report?->isEditable()) {
            return back()->with('error', 'Realisasi hanya dapat diisi saat LPJ berstatus Draf atau Perlu revisi.');
        }

        $data = $request->validate([
            'actual_amount' => ['nullable', 'integer', 'min:0', 'max:100000000000000'],
        ], ['actual_amount.integer' => 'Realisasi harus berupa angka bulat (rupiah).']);

        $budgetItem->update($data);

        return back()->with('success', 'Realisasi disimpan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type'        => ['required', Rule::in(array_keys(ActivityBudgetItem::TYPES))],
            'category'    => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:190'],
            'quantity'    => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit'        => ['nullable', 'string', 'max:20'],
            'unit_price'  => ['required', 'integer', 'min:0', 'max:1000000000'],
            'notes'       => ['nullable', 'string', 'max:255'],
        ], [
            'category.required'    => 'Kategori wajib diisi.',
            'description.required' => 'Uraian wajib diisi.',
            'quantity.min'         => 'Jumlah minimal 0,01.',
            'unit_price.integer'   => 'Harga satuan harus berupa angka bulat (rupiah).',
            'unit_price.max'       => 'Harga satuan maksimal Rp 1.000.000.000.',
        ]);
    }
}
