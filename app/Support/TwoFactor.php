<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

class TwoFactor
{
    public static function google(): Google2FA
    {
        return new Google2FA();
    }

    public static function newSecret(): string
    {
        return self::google()->generateSecretKey(32);
    }

    public static function secretFor(User $user): ?string
    {
        if (! $user->two_factor_secret) {
            return null;
        }

        try {
            return Crypt::decryptString($user->two_factor_secret);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function qrSvg(User $user, string $secret): ?string
    {
        try {
            $url = self::google()->getQRCodeUrl(config('app.name'), $user->email, $secret);
            $writer = new \BaconQrCode\Writer(new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(192, 1),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            ));
            $svg = $writer->writeString($url);

            return preg_replace('/^<\?xml.*?\?>\s*/s', '', $svg);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Kode 6 digit dari aplikasi autentikator. Kode yang sama tidak bisa dipakai dua kali. */
    public static function verifyTotp(User $user, string $input): bool
    {
        $code = preg_replace('/\s+/', '', $input);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $secret = self::secretFor($user);
        if (! $secret || ! self::google()->verifyKey($secret, $code, 1)) {
            return false;
        }

        // Tolak pemakaian ulang kode yang sama (replay)
        return Cache::add("goc-2fa-used:{$user->id}:{$code}", 1, 120);
    }

    public static function verifyRecovery(User $user, string $input): bool
    {
        $norm = strtolower(trim($input));
        if (! preg_match('/^[0-9a-f]{5}-[0-9a-f]{5}$/', $norm)) {
            return false;
        }

        $hash = self::hashCode($norm);
        $codes = self::recoveryHashes($user);

        foreach ($codes as $i => $stored) {
            if (hash_equals($stored, $hash)) {
                unset($codes[$i]); // sekali pakai
                $user->forceFill([
                    'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($codes))),
                ])->save();

                return true;
            }
        }

        return false;
    }

    public static function verify(User $user, string $input): bool
    {
        return self::verifyTotp($user, $input) || self::verifyRecovery($user, $input);
    }

    /** @return array{0: array<int,string>, 1: string} [kode polos untuk ditampilkan, nilai terenkripsi untuk disimpan] */
    public static function makeRecoveryCodes(): array
    {
        $plain = [];
        for ($i = 0; $i < 8; $i++) {
            $hex = bin2hex(random_bytes(5));
            $plain[] = substr($hex, 0, 5) . '-' . substr($hex, 5);
        }

        $hashes = array_map(fn ($c) => self::hashCode($c), $plain);

        return [$plain, Crypt::encryptString(json_encode($hashes))];
    }

    public static function recoveryHashes(User $user): array
    {
        if (! $user->two_factor_recovery_codes) {
            return [];
        }

        try {
            $data = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);
        } catch (DecryptException) {
            return [];
        }

        return is_array($data) ? array_values($data) : [];
    }

    private static function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
