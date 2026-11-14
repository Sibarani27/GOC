<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Akun dinonaktifkan: keluarkan paksa
        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
            ]);
        }

        // Catat login sekali per sesi
        if ($user && ! $request->session()->has('goc_login_stamped')) {
            $request->session()->put('goc_login_stamped', true);
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
            activity('auth')->causedBy($user)->log('Login ke sistem');
        }

        return $next($request);
    }
}
