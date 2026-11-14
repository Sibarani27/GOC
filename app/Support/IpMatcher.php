<?php

namespace App\Support;

class IpMatcher
{
    /** Cocokkan IP dengan daftar IP tunggal atau CIDR (IPv4 dan IPv6). */
    public static function matches(string $ip, array $rules): bool
    {
        $ipBin = @inet_pton($ip);
        if ($ipBin === false) {
            return false;
        }

        foreach ($rules as $rule) {
            $rule = trim((string) $rule);
            if ($rule === '') {
                continue;
            }

            if (str_contains($rule, '/')) {
                if (self::inCidr($ipBin, $rule)) {
                    return true;
                }
                continue;
            }

            $ruleBin = @inet_pton($rule);
            if ($ruleBin !== false && hash_equals($ruleBin, $ipBin)) {
                return true;
            }
        }

        return false;
    }

    private static function inCidr(string $ipBin, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $subBin = @inet_pton($subnet);

        if ($subBin === false || strlen($subBin) !== strlen($ipBin) || ! ctype_digit($bits)) {
            return false;
        }

        $bits = (int) $bits;
        if ($bits > strlen($ipBin) * 8) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $rem = $bits % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
            return false;
        }
        if ($rem === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $rem)) & 0xFF;

        return (ord($ipBin[$bytes]) & $mask) === (ord($subBin[$bytes]) & $mask);
    }
}
