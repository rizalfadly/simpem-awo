<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AssetCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inventory/Categories', [
            'categories' => AssetCategory::withCount('assets')->orderBy('name')->get()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'is_active' => $c->is_active, 'usage' => $c->assets_count,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        AssetCategory::create($this->validated($request));

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, AssetCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(AssetCategory $category): RedirectResponse
    {
        if ($category->assets()->withTrashed()->exists()) {
            return back()->with('error', 'Kategori sudah dipakai barang. Nonaktifkan saja, jangan dihapus.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    private function validated(Request $request, ?AssetCategory $category = null): array
    {
        return $request->validate([
            'name'      => ['required', 'string', 'max:80', Rule::unique('asset_categories', 'name')->ignore($category?->id)],
            'is_active' => ['required', 'boolean'],
        ], ['name.unique' => 'Kategori dengan nama itu sudah ada.']);
    }
}
