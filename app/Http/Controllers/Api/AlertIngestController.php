<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlertRequest;
use App\Jobs\ProcessIncomingAlert;
use Illuminate\Http\JsonResponse;

class AlertIngestController extends Controller
{
    public function store(StoreAlertRequest $request): JsonResponse
    {
        abort_unless(
            $request->user()->tokenCan('alerts:create'),
            403,
            'Token tidak memiliki izin alerts:create.'
        );

        abort_unless($request->user()->is_active, 403, 'Akun integrasi dinonaktifkan.');

        // Diproses lewat queue agar sumber data mendapat respons cepat
        ProcessIncomingAlert::dispatch($request->validated());

        return response()->json(['message' => 'Alert diterima dan masuk antrean.'], 202);
    }
}
