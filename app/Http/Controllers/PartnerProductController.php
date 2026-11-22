<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerProduct;
use App\Support\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartnerProductController extends Controller
{
    public function store(Request $request, Partner $partner): RedirectResponse
    {
        $data = $this->validated($request);
        $photo = $this->processPhoto($request);
        unset($data['photo'], $data['remove_photo']);

        $partner->products()->create($data + $photo + ['sort_order' => (int) $partner->products()->max('sort_order') + 1]);

        return back()->with('success', 'Produk ditambahkan.');
    }

    public function update(Request $request, PartnerProduct $product): RedirectResponse
    {
        $data = $this->validated($request);
        $extra = [];

        if ($request->hasFile('photo')) {
            $this->deletePhoto($product);
            $extra = $this->processPhoto($request);
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($product);
            $extra = ['photo_path' => null, 'photo_thumb_path' => null];
        }
        unset($data['photo'], $data['remove_photo']);

        $product->update($data + $extra);

        return back()->with('success', 'Produk diperbarui.');
    }

    public function destroy(PartnerProduct $product): RedirectResponse
    {
        $product->delete();   // berkas foto ikut dihapus (lihat PartnerProduct::booted)

        return back()->with('success', 'Produk dihapus.');
    }

    public function photo(Request $request, PartnerProduct $product): BinaryFileResponse
    {
        abort_unless($request->user()->can('view', $product->partner), 403);

        $path = $request->boolean('thumb') ? $product->photo_thumb_path : $product->photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=86400', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:150'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'price'        => ['nullable', 'integer', 'min:0', 'max:10000000000'],
            'price_note'   => ['nullable', 'string', 'max:60'],
            'is_available' => ['required', 'boolean'],
            'photo'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'price.integer' => 'Harga harus berupa angka bulat (rupiah).',
        ]);
    }

    /** @return array{photo_path?:string, photo_thumb_path?:string} */
    private function processPhoto(Request $request): array
    {
        if (! $request->hasFile('photo')) {
            return [];
        }

        try {
            $r = ImageProcessor::store($request->file('photo'), 'partners/products');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['photo' => 'Foto tidak dapat diproses. Pastikan berkas gambar valid.']);
        }

        return ['photo_path' => $r['path'], 'photo_thumb_path' => $r['thumb']];
    }

    private function deletePhoto(PartnerProduct $product): void
    {
        foreach ([$product->photo_path, $product->photo_thumb_path] as $path) {
            $path && Storage::disk('local')->delete($path);
        }
    }
}
