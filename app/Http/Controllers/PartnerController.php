<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Partner;
use App\Support\ImageProcessor;
use App\Support\PartnerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Database mitra (UMKM, instansi, sponsor, komunitas). Anggota melihat info usaha; kontak internal hanya untuk pengurus. */
class PartnerController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $viewer = PartnerAccess::viewer($user);
        $filters = [
            'q'      => trim((string) $request->query('q', '')),
            'type'   => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $partners = Partner::query()
            ->visibleTo($user)
            ->withCount('products')
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('name', 'like', $s)->orWhere('sector', 'like', $s)->orWhere('city', 'like', $s)->orWhere('owner_name', 'like', $s);
            }))
            ->when(array_key_exists($filters['type'], Partner::TYPES), fn ($q) => $q->where('type', $filters['type']))
            ->when($viewer && array_key_exists($filters['status'], Partner::STATUSES), fn ($q) => $q->where('status', $filters['status']))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Partner $p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'type'          => $p->type,
                'type_label'    => $p->typeLabel(),
                'sector'        => $p->sector,
                'city'          => $p->city,
                'description'   => str($p->description ?? '')->limit(110)->toString(),
                'status'        => $p->status,
                'status_label'  => Partner::STATUSES[$p->status] ?? $p->status,
                'logo'          => $p->logo_thumb_path ? "/partners/{$p->id}/logo?thumb=1" : null,
                'products_count' => $p->products_count,
                'is_public'     => $viewer ? $p->is_public : null,
            ]);

        return Inertia::render('Partners/Index', [
            'partners' => $partners,
            'filters'  => $filters,
            'types'    => Partner::TYPES,
            'statuses' => Partner::STATUSES,
            'can'      => ['viewer' => $viewer, 'manage' => $user->can('partners.manage')],
        ]);
    }

    public function show(Request $request, Partner $partner): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $partner), 403);

        $internal = $user->can('viewInternal', $partner);
        $partner->load('member:id,name,nomor_anggota');

        $products = $partner->products()->get()->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'description' => $p->description, 'price' => $p->price, 'price_note' => $p->price_note,
            'is_available' => $p->is_available, 'photo' => $p->photo_thumb_path ? "/partners/products/{$p->id}/photo?thumb=1" : null,
            'has_photo' => (bool) $p->photo_path,
        ]);

        $promotions = $partner->promotions()
            ->when(! $internal, fn ($q) => $q->current())
            ->orderByDesc('starts_on')->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'title' => $p->title, 'description' => $p->description, 'terms' => $p->terms,
                'starts_on' => $p->starts_on->format('Y-m-d'), 'ends_on' => $p->ends_on->format('Y-m-d'),
                'period' => $p->starts_on->format('d/m/Y').' - '.$p->ends_on->format('d/m/Y'),
                'audience' => $p->audience, 'audience_label' => \App\Models\PartnerPromotion::AUDIENCES[$p->audience] ?? $p->audience,
                'status' => $p->status, 'phase' => $p->phase(),
            ]);

        return Inertia::render('Partners/Show', [
            'partner' => [
                'id' => $partner->id, 'name' => $partner->name, 'type' => $partner->type, 'type_label' => $partner->typeLabel(),
                'sector' => $partner->sector, 'owner_name' => $partner->owner_name,
                'member' => $partner->member ? ['id' => $partner->member->id, 'name' => $partner->member->name] : null,
                'description' => $partner->description, 'address' => $partner->address, 'city' => $partner->city,
                'website' => $partner->website, 'instagram' => $partner->instagram, 'public_contact' => $partner->public_contact,
                'status' => $partner->status, 'status_label' => Partner::STATUSES[$partner->status] ?? $partner->status,
                'joined_at' => $partner->joined_at?->format('d/m/Y'), 'logo' => $partner->logo_path ? "/partners/{$partner->id}/logo" : null,
                'is_public' => $internal ? $partner->is_public : null,
                'internal' => $internal ? [
                    'contact_person' => $partner->contact_person, 'contact_phone' => $partner->contact_phone,
                    'contact_email' => $partner->contact_email, 'notes' => $partner->notes,
                ] : null,
            ],
            'products'   => $products,
            'promotions' => $promotions,
            'can' => [
                'internal' => $internal,
                'manage'   => $user->can('partners.manage'),
                'publish'  => $user->can('content.publish'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partners/Form', ['partner' => null, 'options' => $this->options()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $logo = $this->processLogo($request);
        unset($data['logo'], $data['remove_logo']);

        $partner = Partner::create($data + $logo + [
            'slug' => Partner::uniqueSlug($data['name']), 'status' => 'active', 'created_by' => $request->user()->id,
        ]);

        return to_route('partners.show', $partner)->with('success', "Mitra {$partner->name} ditambahkan.");
    }

    public function edit(Partner $partner): Response
    {
        return Inertia::render('Partners/Form', [
            'partner' => [
                'id' => $partner->id, 'name' => $partner->name, 'type' => $partner->type, 'sector' => $partner->sector,
                'owner_name' => $partner->owner_name, 'member_id' => $partner->member_id, 'description' => $partner->description,
                'address' => $partner->address, 'city' => $partner->city, 'website' => $partner->website, 'instagram' => $partner->instagram,
                'public_contact' => $partner->public_contact, 'contact_person' => $partner->contact_person, 'contact_phone' => $partner->contact_phone,
                'contact_email' => $partner->contact_email, 'notes' => $partner->notes, 'joined_at' => $partner->joined_at?->format('Y-m-d'),
                'has_logo' => (bool) $partner->logo_path,
            ],
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $data = $this->validated($request);
        $extra = [];

        if ($request->hasFile('logo')) {
            $this->deleteLogo($partner);
            $extra = $this->processLogo($request);
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($partner);
            $extra = ['logo_path' => null, 'logo_thumb_path' => null];
        }
        unset($data['logo'], $data['remove_logo']);

        $partner->update($data + $extra);

        return to_route('partners.show', $partner)->with('success', 'Data mitra diperbarui.');
    }

    public function status(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Partner::STATUSES))]]);

        // Mitra yang dinonaktifkan otomatis ditarik dari website publik; harus dipilih ulang secara sadar saat diaktifkan kembali.
        $partner->update(['status' => $data['status'], 'is_public' => $data['status'] === 'active' ? $partner->is_public : false]);

        return back()->with('success', $data['status'] === 'active' ? 'Mitra diaktifkan.' : 'Mitra dinonaktifkan dan ditarik dari website publik.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        if ($partner->partnerships()->where('status', 'active')->exists()) {
            return back()->with('error', 'Masih ada kerja sama berjalan dengan mitra ini. Selesaikan atau hentikan dulu.');
        }

        $partner->delete();

        return to_route('partners.index')->with('success', 'Mitra dihapus.');
    }

    public function logo(Request $request, Partner $partner): BinaryFileResponse
    {
        abort_unless($request->user()->can('view', $partner), 403);

        $path = $request->boolean('thumb') ? $partner->logo_thumb_path : $partner->logo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=86400', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'type'           => ['required', Rule::in(array_keys(Partner::TYPES))],
            'sector'         => ['nullable', 'string', 'max:80'],
            'owner_name'     => ['nullable', 'string', 'max:120'],
            'member_id'      => ['nullable', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'description'    => ['nullable', 'string', 'max:2000'],
            'address'        => ['nullable', 'string', 'max:255'],
            'city'           => ['nullable', 'string', 'max:80'],
            'website'        => ['nullable', 'url:http,https', 'max:255'],
            'instagram'      => ['nullable', 'string', 'max:80', 'regex:/^@?[A-Za-z0-9._]{1,30}$/'],
            'public_contact' => ['nullable', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'contact_phone'  => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]+$/'],
            'contact_email'  => ['nullable', 'email', 'max:190'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'joined_at'      => ['nullable', 'date', 'before_or_equal:today'],
            'logo'           => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_logo'    => ['nullable', 'boolean'],
        ], [
            'name.required'    => 'Nama mitra wajib diisi.',
            'website.url'      => 'Alamat website harus diawali http:// atau https://',
            'instagram.regex'  => 'Username Instagram hanya boleh huruf, angka, titik, dan garis bawah.',
            'contact_phone.regex' => 'Nomor telepon hanya boleh angka dan tanda + - ( ).',
            'logo.image'       => 'Logo harus berupa gambar.',
            'logo.max'         => 'Ukuran logo maksimal 8 MB.',
        ]);
    }

    /** @return array{logo_path?:string, logo_thumb_path?:string} */
    private function processLogo(Request $request): array
    {
        if (! $request->hasFile('logo')) {
            return [];
        }

        try {
            $r = ImageProcessor::store($request->file('logo'), 'partners');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['logo' => 'Logo tidak dapat diproses. Pastikan berkas gambar valid.']);
        }

        return ['logo_path' => $r['path'], 'logo_thumb_path' => $r['thumb']];
    }

    private function deleteLogo(Partner $partner): void
    {
        foreach ([$partner->logo_path, $partner->logo_thumb_path] as $path) {
            $path && Storage::disk('local')->delete($path);
        }
    }

    private function options(): array
    {
        return [
            'types'   => Partner::TYPES,
            'members' => Member::where('status', Member::ACTIVE)->orderBy('name')->get(['id', 'name', 'nomor_anggota']),
        ];
    }
}
