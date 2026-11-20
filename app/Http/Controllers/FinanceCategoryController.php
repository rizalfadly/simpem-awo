<?php

namespace App\Http\Controllers;

use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinanceCategoryController extends Controller
{
    public function index(): Response
    {
        $used = FinanceTransaction::withTrashed()->selectRaw('category_id, count(*) as c')->groupBy('category_id')->pluck('c', 'category_id');

        return Inertia::render('Finance/Categories', [
            'categories' => FinanceCategory::orderBy('type')->orderBy('sort_order')->orderBy('name')->get()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'is_active' => $c->is_active,
                'sort_order' => $c->sort_order, 'usage' => (int) ($used[$c->id] ?? 0),
            ]),
            'types' => FinanceTransaction::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:80', Rule::unique('finance_categories', 'name')->where('type', $request->input('type'))],
            'type'       => ['required', Rule::in(array_keys(FinanceTransaction::TYPES))],
            'is_active'  => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ], ['name.unique' => 'Kategori dengan nama itu sudah ada untuk jenis yang sama.']);

        FinanceCategory::create($data);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, FinanceCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:80', Rule::unique('finance_categories', 'name')->where('type', $category->type)->ignore($category->id)],
            'is_active'  => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ], ['name.unique' => 'Kategori dengan nama itu sudah ada untuk jenis yang sama.']);

        $category->update($data);   // jenis (masuk/keluar) tidak dapat diubah

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(FinanceCategory $category): RedirectResponse
    {
        if ($category->transactions()->withTrashed()->exists()) {
            return back()->with('error', 'Kategori sudah dipakai transaksi. Nonaktifkan saja, jangan dihapus.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
