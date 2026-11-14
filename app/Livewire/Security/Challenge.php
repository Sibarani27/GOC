<?php

namespace App\Livewire\Security;

use App\Support\TwoFactor;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.goc-guest')]
#[Title('Verifikasi Dua Faktor')]
class Challenge extends Component
{
    public string $code = '';

    public function mount()
    {
        $user = auth()->user();

        if (! $user->two_factor_confirmed_at || session('goc_2fa_passed')) {
            return $this->redirectRoute('dashboard');
        }
    }

    public function verify()
    {
        $user = auth()->user();
        $key = 'goc-2fa:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('code', 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.');
            return;
        }

        $this->validate(['code' => ['required', 'string', 'max:32']], ['code.required' => 'Masukkan kode Anda.']);

        if (TwoFactor::verify($user, $this->code)) {
            RateLimiter::clear($key);
            session()->put('goc_2fa_passed', true);
            session()->regenerate();

            activity('auth')->causedBy($user)->log('Verifikasi 2FA berhasil');

            return $this->redirectRoute('dashboard');
        }

        RateLimiter::hit($key, 300);

        activity('auth')->causedBy($user)
            ->withProperties(['ip' => request()->ip()])
            ->log('Verifikasi 2FA gagal');

        $this->addError('code', 'Kode tidak valid.');
        $this->code = '';
    }

    public function render()
    {
        return view('livewire.security.challenge');
    }
}
