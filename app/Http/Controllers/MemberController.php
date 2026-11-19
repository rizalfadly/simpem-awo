<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Member;
use App\Models\Period;
use App\Models\Position;
use App\Models\User;
use App\Support\Audit;
use App\Support\MemberNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q'        => trim((string) $request->query('q', '')),
            'status'   => (string) $request->query('status', ''),
            'division' => (string) $request->query('division', ''),
        ];

        $members = Member::query()
            ->with(['activeAssignments.position:id,name', 'activeAssignments.division:id,name', 'user:id,member_id,is_active'])
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('name', 'like', $s)->orWhere('nomor_anggota', 'like', $s)
                    ->orWhere('phone', 'like', $s)->orWhere('email', 'like', $s);
            }))
            ->when(array_key_exists($filters['status'], Member::STATUSES), fn ($q) => $q->where('status', $filters['status']))
            ->when(ctype_digit($filters['division']) && $filters['division'] !== '',
                fn ($q) => $q->whereHas('activeAssignments', fn ($a) => $a->where('division_id', (int) $filters['division'])))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Member $m) => [
                'id'             => $m->id,
                'nomor_anggota'  => $m->nomor_anggota,
                'name'           => $m->name,
                'photo_url'      => $m->photo_url,
                'status'         => $m->status,
                'status_label'   => $m->statusLabel(),
                'positions'      => $m->positionLabels(),
                'has_account'    => (bool) $m->user,
                'account_active' => (bool) $m->user?->is_active,
            ]);

        return Inertia::render('Members/Index', [
            'members'   => $members,
            'filters'   => $filters,
            'statuses'  => Member::STATUSES,
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Members/Form', ['member' => null, 'statuses' => Member::STATUSES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            $this->rules() + ['status' => ['required', Rule::in(array_keys(Member::STATUSES))]],
            $this->messages(),
        );

        $member = DB::transaction(function () use ($request, $data) {
            $data['nomor_anggota'] = MemberNumberGenerator::next(Carbon::parse($data['joined_at'])->year);
            $data['status_changed_at'] = now();
            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('members', 'public');
            }
            unset($data['photo']);

            return Member::create($data);
        });

        return to_route('members.show', $member)->with('success', "Anggota {$member->nomor_anggota} berhasil ditambahkan.");
    }

    public function show(Request $request, Member $member): Response
    {
        $member->load(['user', 'assignments.period', 'assignments.position', 'assignments.division', 'activeAssignments.position', 'activeAssignments.division']);
        $fmt = fn ($d) => $d?->format('d/m/Y');

        return Inertia::render('Members/Show', [
            'member' => [
                'id'            => $member->id,
                'nomor_anggota' => $member->nomor_anggota,
                'name'          => $member->name,
                'nickname'      => $member->nickname,
                'gender'        => $member->gender,
                'birth_place'   => $member->birth_place,
                'birth_date'    => $fmt($member->birth_date),
                'phone'         => $member->phone,
                'email'         => $member->email,
                'address'       => $member->address,
                'photo_url'     => $member->photo_url,
                'joined_at'     => $fmt($member->joined_at),
                'status'        => $member->status,
                'status_label'  => $member->statusLabel(),
                'positions'     => $member->positionLabels(),
            ],
            'account' => $member->user ? [
                'is_active'     => $member->user->is_active,
                'last_login_at' => $member->user->last_login_at?->format('d/m/Y H:i'),
                'is_self'       => $member->user->is($request->user()),
            ] : null,
            'assignments' => $member->assignments->map(fn ($a) => [
                'id'         => $a->id,
                'period'     => $a->period->name,
                'position'   => $a->position->name,
                'division'   => $a->division?->name,
                'started_at' => $fmt($a->started_at),
                'ended_at'   => $fmt($a->ended_at),
                'is_active'  => $a->ended_at === null,
                'notes'      => $a->notes,
            ])->values(),
            'options' => [
                'periods'   => Period::orderByDesc('start_date')->get(['id', 'name', 'is_current']),
                'divisions' => Division::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'positions' => Position::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'scope']),
            ],
            'statuses' => Member::STATUSES,
        ]);
    }

    public function edit(Request $request, Member $member): Response
    {
        $this->guardTarget($request->user(), $member);

        return Inertia::render('Members/Form', [
            'member' => [
                'id'            => $member->id,
                'nomor_anggota' => $member->nomor_anggota,
                'name'          => $member->name,
                'nickname'      => $member->nickname,
                'gender'        => $member->gender,
                'birth_place'   => $member->birth_place,
                'birth_date'    => $member->birth_date?->format('Y-m-d'),
                'phone'         => $member->phone,
                'email'         => $member->email,
                'address'       => $member->address,
                'photo_url'     => $member->photo_url,
                'joined_at'     => $member->joined_at->format('Y-m-d'),
            ],
            'statuses' => Member::STATUSES,
        ]);
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $this->guardTarget($request->user(), $member);

        $data = $request->validate(
            $this->rules($member) + ['remove_photo' => ['nullable', 'boolean']],
            $this->messages(),
        );

        if ($request->hasFile('photo')) {
            $this->deletePhoto($member);
            $data['photo_path'] = $request->file('photo')->store('members', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($member);
            $data['photo_path'] = null;
        }
        unset($data['photo'], $data['remove_photo']);

        DB::transaction(function () use ($member, $data) {
            $member->update($data);
            // Nama & email akun mengikuti data anggota.
            $member->user?->update(['name' => $member->name, 'email' => $member->email]);
        });

        return to_route('members.show', $member)->with('success', 'Data anggota diperbarui.');
    }

    public function changeStatus(Request $request, Member $member): RedirectResponse
    {
        $actor = $request->user();
        $this->guardTarget($actor, $member);

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Member::STATUSES))]]);

        if ($data['status'] === $member->status) {
            return back()->with('error', 'Status tidak berubah.');
        }

        $leaving = $data['status'] !== Member::ACTIVE;

        if ($leaving && $member->user?->is($actor)) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan status keanggotaan sendiri.');
        }

        $notes = [];
        DB::transaction(function () use ($member, $data, $leaving, &$notes) {
            $member->update(['status' => $data['status'], 'status_changed_at' => now()]);

            if (! $leaving) {
                return;
            }

            if ($member->user && $member->user->is_active) {
                $member->user->update(['is_active' => false]);
                $notes[] = 'akun dinonaktifkan';
            }

            $open = $member->activeAssignments()->get();
            foreach ($open as $a) {
                $a->update(['ended_at' => $a->started_at->gt(today()) ? $a->started_at : today()]);
            }
            if ($open->count()) {
                $notes[] = $open->count().' jabatan diakhiri';
            }
        });

        $label = Member::STATUSES[$data['status']];

        return back()->with('success', "Status diubah menjadi {$label}".($notes ? ' ('.implode(', ', $notes).')' : '').'.');
    }

    public function regenerateQr(Request $request, Member $member): RedirectResponse
    {
        $this->guardTarget($request->user(), $member);

        $member->update(['qr_token' => Str::random(40)]);

        return back()->with('success', 'QR Code baru dibuat. Kartu lama tidak lagi valid.');
    }

    public function activateAccount(Request $request, Member $member): RedirectResponse
    {
        if ($member->status !== Member::ACTIVE) {
            return back()->with('error', 'Hanya anggota berstatus Aktif yang dapat diberi akun.');
        }
        if ($member->user) {
            return back()->with('error', 'Anggota ini sudah memiliki akun.');
        }

        // Akun lama (terhapus) dengan nomor yang sama dipulihkan, bukan dibuat ganda.
        $existing = User::withTrashed()->where('nomor_anggota', $member->nomor_anggota)->first();

        $emailTaken = $member->email && User::withTrashed()
            ->where('email', $member->email)
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();
        if ($emailTaken) {
            return back()->with('error', 'Email anggota sudah dipakai akun lain. Ubah atau kosongkan email anggota terlebih dahulu.');
        }

        $plain = Str::password(10, symbols: false);

        $user = DB::transaction(function () use ($member, $existing, $plain) {
            $attrs = [
                'member_id'            => $member->id,
                'name'                 => $member->name,
                'email'                => $member->email,
                'password'             => $plain,
                'is_active'            => true,
                'must_change_password' => true,
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($attrs);
                $user = $existing;
            } else {
                $user = User::create($attrs + ['nomor_anggota' => $member->nomor_anggota]);
            }

            if ($user->roles()->doesntExist() && Role::where('name', 'Anggota')->exists()) {
                $user->assignRole('Anggota');
                Audit::record('roles_changed', "Peran {$user->auditLabel()} ditetapkan", $user, [], ['roles' => ['Anggota']]);
            }

            return $user;
        });

        return back()
            ->with('success', "Akun {$user->nomor_anggota} diaktifkan.")
            ->with('generated_password', ['nomor' => $user->nomor_anggota, 'name' => $user->name, 'password' => $plain]);
    }

    public function destroy(Request $request, Member $member): RedirectResponse
    {
        $actor = $request->user();
        $this->guardTarget($actor, $member);

        if ($member->user?->is($actor)) {
            return back()->with('error', 'Anda tidak dapat menghapus keanggotaan sendiri.');
        }

        DB::transaction(function () use ($member) {
            foreach ($member->activeAssignments()->get() as $a) {
                $a->update(['ended_at' => $a->started_at->gt(today()) ? $a->started_at : today()]);
            }
            $member->user?->delete();   // soft delete akun (otomatis tidak bisa login)
            $member->delete();          // soft delete anggota
        });

        return to_route('members.index')->with('success', "Anggota {$member->nomor_anggota} dihapus.");
    }

    private function rules(?Member $member = null): array
    {
        $email = ['nullable', 'email', 'max:190'];
        if ($member?->user) {
            $email[] = Rule::unique('users', 'email')->ignore($member->user->id);
        }

        return [
            'name'        => ['required', 'string', 'max:120'],
            'nickname'    => ['nullable', 'string', 'max:60'],
            'gender'      => ['nullable', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:80'],
            'birth_date'  => ['nullable', 'date', 'before:today'],
            'phone'       => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'email'       => $email,
            'address'     => ['nullable', 'string', 'max:500'],
            'joined_at'   => ['required', 'date', 'before_or_equal:today'],
            'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required'           => 'Nama lengkap wajib diisi.',
            'phone.regex'             => 'Nomor telepon hanya boleh angka dan tanda + - ( ).',
            'email.unique'            => 'Email sudah dipakai akun lain.',
            'birth_date.before'       => 'Tanggal lahir harus sebelum hari ini.',
            'joined_at.required'      => 'Tanggal bergabung wajib diisi.',
            'joined_at.before_or_equal' => 'Tanggal bergabung tidak boleh di masa depan.',
            'photo.image'             => 'Berkas harus berupa gambar.',
            'photo.mimes'             => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photo.max'               => 'Ukuran foto maksimal 2 MB.',
        ];
    }

    private function deletePhoto(Member $member): void
    {
        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
    }

    /** Hanya Super Admin yang boleh mengubah anggota yang akunnya Super Admin. */
    private function guardTarget(User $actor, Member $member): void
    {
        abort_if($member->user?->isSuperAdmin() && ! $actor->isSuperAdmin(), 403);
    }
}
