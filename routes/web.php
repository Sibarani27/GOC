<?php

use App\Http\Middleware\EnsureTwoFactor;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\IpAllowList;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Halaman login dan profil ikut dibatasi IP dan diberi header keamanan
Route::middleware([IpAllowList::class . ':panel', SecurityHeaders::class])->group(function () {
    Route::view('profile', 'profile')
        ->middleware(['auth', EnsureUserIsActive::class, EnsureTwoFactor::class])
        ->name('profile');

    require __DIR__ . '/auth.php';
});

require __DIR__ . '/goc.php';
