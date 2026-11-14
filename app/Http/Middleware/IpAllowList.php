<?php

namespace App\Http\Middleware;

use App\Support\IpMatcher;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IpAllowList
{
    /** Pemakaian: IpAllowList::class.':panel' atau IpAllowList::class.':api' */
    public function handle(Request $request, Closure $next, string $scope = 'panel'): Response
    {
        $rules = config($scope === 'api' ? 'goc.api_allowed_ips' : 'goc.allowed_ips', []);

        if (empty($rules)) {
            return $next($request);
        }

        $ip = (string) $request->ip();
        if (IpMatcher::matches($ip, $rules)) {
            return $next($request);
        }

        // Catat sekali per IP tiap 10 menit agar log tidak dibanjiri
        if (Cache::add("goc-ipdeny:{$scope}:{$ip}", 1, 600)) {
            activity('security')
                ->withProperties(['ip' => $ip, 'scope' => $scope, 'path' => $request->path()])
                ->log('Akses ditolak: IP tidak diizinkan');
        }

        abort(403, 'Akses dari alamat IP ini tidak diizinkan.');
    }
}
