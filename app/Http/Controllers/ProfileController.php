<?php

namespace App\Http\Controllers;

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
        ]);
    }
}
