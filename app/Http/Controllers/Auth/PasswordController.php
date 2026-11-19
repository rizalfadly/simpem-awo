<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.different'                => 'Kata sandi baru harus berbeda dari yang lama.',
        ]);

        $request->user()->update([
            'password'             => $validated['password'],
            'must_change_password' => false,
        ]);

        return to_route('dashboard')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
