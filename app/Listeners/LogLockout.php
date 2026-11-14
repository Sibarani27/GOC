<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Lockout;

class LogLockout
{
    public function handle(Lockout $event): void
    {
        activity('auth')
            ->withProperties(['ip' => $event->request->ip()])
            ->log('Login diblokir sementara: terlalu banyak percobaan');
    }
}
