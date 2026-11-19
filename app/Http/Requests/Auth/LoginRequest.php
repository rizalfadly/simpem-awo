<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_anggota' => strtoupper(trim((string) $this->input('nomor_anggota'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'nomor_anggota' => ['required', 'string', 'max:30'],
            'password'      => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_anggota.required' => 'Nomor anggota wajib diisi.',
            'password.required'      => 'Kata sandi wajib diisi.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $ok = Auth::attempt([
            'nomor_anggota' => $this->string('nomor_anggota')->toString(),
            'password'      => $this->string('password')->toString(),
            'is_active'     => true,
        ], $this->boolean('remember'));

        if (! $ok) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'nomor_anggota' => 'Nomor anggota atau kata sandi salah, atau akun tidak aktif.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'nomor_anggota' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate($this->string('nomor_anggota')->lower()->toString().'|'.$this->ip());
    }
}
