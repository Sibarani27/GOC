<?php

namespace App\Livewire\Security;

use App\Support\TwoFactor;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.goc')]
#[Title('Keamanan Akun')]
class Index extends Component
{
    public string $code = '';
    public string $password = '';
    public array $recoveryCodes = [];

    public function startSetup(): void
    {
        $user = auth()->user();
        if ($user->two_factor_confirmed_at) {
            return;
        }

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString(TwoFactor::newSecret()),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->reset(['code', 'password']);
        $this->resetValidation();
    }

    public function cancelSetup(): void
    {
        $user = auth()->user();
        if (! $user->two_factor_confirmed_at) {
            $user->forceFill(['two_factor_secret' => null])->save();
        }
        $this->reset(['code', 'password']);
        $this->resetValidation();
    }

    public function confirmSetup(): void
    {
        $user = auth()->user();
        abort_if($user->two_factor_confirmed_at || ! $user->two_factor_secret, 422);

        $this->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Masukkan kode 6 digit.']);

        if (! TwoFactor::verifyTotp($user, $this->code)) {
            $this->addError('code', 'Kode tidak valid. Pastikan jam perangkat Anda akurat, lalu coba lagi.');
            return;
        }

        [$plain, $encrypted] = TwoFactor::makeRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $encrypted,
        ])->save();

        // Pengguna baru saja membuktikan kepemilikan perangkat
        session()->put('goc_2fa_passed', true);

        activity('user')->performedOn($user)->causedBy($user)->log('Autentikasi dua faktor diaktifkan');

        $this->recoveryCodes = $plain;
        $this->reset(['code', 'password']);
    }

    public function regenerateCodes(): void
    {
        $user = auth()->user();
        abort_unless($user->two_factor_confirmed_at, 422);

        $this->validate(
            ['password' => ['required', 'current_password']],
            ['password.current_password' => 'Password salah.', 'password.required' => 'Masukkan password Anda.']
        );

        [$plain, $encrypted] = TwoFactor::makeRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $encrypted])->save();

        activity('user')->performedOn($user)->causedBy($user)->log('Kode pemulihan 2FA dibuat ulang');

        $this->recoveryCodes = $plain;
        $this->reset(['code', 'password']);
    }

    public function disable(): void
    {
        $user = auth()->user();
        abort_unless($user->two_factor_confirmed_at, 422);

        $this->validate(
            ['password' => ['required', 'current_password'], 'code' => ['required', 'string', 'max:20']],
            [
                'password.current_password' => 'Password salah.',
                'password.required' => 'Masukkan password Anda.',
                'code.required' => 'Masukkan kode 2FA atau kode pemulihan.',
            ]
        );

        if (! TwoFactor::verify($user, $this->code)) {
            $this->addError('code', 'Kode tidak valid.');
            return;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        activity('user')->performedOn($user)->causedBy($user)->log('Autentikasi dua faktor dinonaktifkan');

        $this->recoveryCodes = [];
        $this->reset(['code', 'password']);
    }

    public function render()
    {
        $user = auth()->user();
        $confirmed = (bool) $user->two_factor_confirmed_at;
        $pending = ! $confirmed && $user->two_factor_secret;

        $secret = null;
        $qr = null;
        if ($pending) {
            $secret = TwoFactor::secretFor($user);
            $qr = $secret ? TwoFactor::qrSvg($user, $secret) : null;
        }

        return view('livewire.security.index', [
            'confirmed' => $confirmed,
            'pending' => (bool) $pending,
            'secret' => $secret,
            'qr' => $qr,
            'remaining' => $confirmed ? count(TwoFactor::recoveryHashes($user)) : 0,
            'required' => (bool) config('goc.require_2fa'),
        ]);
    }
}
