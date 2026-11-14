<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if (in_array($route, ['goc.2fa.challenge', 'goc.logout'], true)) {
            return $next($request);
        }

        // Punya 2FA: wajib lulus verifikasi pada sesi ini
        if ($user->two_factor_confirmed_at) {
            if (! $request->session()->get('goc_2fa_passed')) {
                return redirect()->route('goc.2fa.challenge');
            }

            return $next($request);
        }

        // Belum punya 2FA, tetapi kebijakan mewajibkannya
        if (config('goc.require_2fa') && $route !== 'goc.security') {
            return redirect()->route('goc.security')
                ->with('warning', 'Kebijakan keamanan mewajibkan autentikasi dua faktor. Aktifkan sekarang untuk melanjutkan.');
        }

        return $next($request);
    }
}
