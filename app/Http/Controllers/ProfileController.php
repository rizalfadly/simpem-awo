<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'account' => [
                'nomor_anggota' => $user->nomor_anggota,
                'name'          => $user->name,
                'email'         => $user->email,
                'last_login_at' => $user->last_login_at?->format('d/m/Y H:i'),
            ],
            // null = akun ini tidak tertaut ke data anggota
            'public_profile' => $user->member?->public_profile,
        ]);
    }

    /** Persetujuan anggota sendiri untuk menampilkan nama & foto di struktur pengurus website publik. */
    public function updatePublic(Request $request): RedirectResponse
    {
        $member = $request->user()->member;
        abort_unless($member, 403);

        $data = $request->validate(['public_profile' => ['required', 'boolean']]);
        $member->update(['public_profile' => $data['public_profile']]);

        return back()->with('success', $data['public_profile']
            ? 'Nama dan foto Anda akan tampil di struktur pengurus website publik (jika Anda menjabat).'
            : 'Nama dan foto Anda tidak lagi ditampilkan di website publik.');
    }
}
