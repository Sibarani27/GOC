<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Str;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $log = activity('auth')->withProperties([
            'email' => Str::limit((string) ($event->credentials['email'] ?? ''), 150),
            'ip' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 200),
        ]);

        if ($event->user) {
            $log->causedBy($event->user);
        }

        $log->log('Login gagal');
    }
}
