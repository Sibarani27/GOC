<?php

use App\Http\Controllers\Api\AlertIngestController;
use App\Http\Middleware\IpAllowList;
use Illuminate\Support\Facades\Route;

Route::middleware([IpAllowList::class . ':api', 'auth:sanctum', 'throttle:600,1'])->group(function () {
    Route::post('/alerts', [AlertIngestController::class, 'store']);
});
