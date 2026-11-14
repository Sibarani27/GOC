<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public array $data)
    {
    }

    public function handle(): void
    {
        $d = $this->data;
        $source = strtolower($d['source']);
        $externalId = $d['external_id'] ?? null;

        // Idempoten: kiriman ulang dengan source + external_id yang sama diabaikan
        if ($externalId && Alert::where('source', $source)->where('external_id', $externalId)->exists()) {
            return;
        }

        $detectedAt = isset($d['detected_at']) ? Carbon::parse($d['detected_at']) : now();

        // Cocokkan dengan aset terdaftar (berdasarkan IP, lalu nama)
        $asset = null;
        if (!empty($d['asset_ip'])) {
            $asset = Asset::where('ip_address', $d['asset_ip'])->first();
        }
        if (!$asset && !empty($d['asset_name'])) {
            $asset = Asset::where('name', $d['asset_name'])->first();
        }
        $asset?->update(['last_seen_at' => now()]);

        // Sidik jari untuk menggabungkan alert yang sama
        $fingerprint = sha1(strtolower(implode('|', [
            $source,
            $d['title'],
            $asset?->id ?? ($d['asset_ip'] ?? $d['asset_name'] ?? ''),
            $d['source_ip'] ?? '',
        ])));

        // Gabungkan jika ada alert serupa yang masih terbuka dalam 6 jam terakhir
        $existing = Alert::open()
            ->where('fingerprint', $fingerprint)
            ->where('detected_at', '>=', now()->subHours(6))
            ->first();

        if ($existing) {
            $existing->increment('occurrences');
            $existing->update(['detected_at' => $detectedAt]);
            return;
        }

        Alert::create([
            'source'          => $source,
            'external_id'     => $externalId,
            'title'           => $d['title'],
            'description'     => $d['description'] ?? null,
            'severity'        => $d['severity'],
            'status'          => 'new',
            'asset_id'        => $asset?->id,
            'source_ip'       => $d['source_ip'] ?? null,
            'destination_ip'  => $d['destination_ip'] ?? null,
            'mitre_tactic'    => $d['mitre_tactic'] ?? null,
            'mitre_technique' => $d['mitre_technique'] ?? null,
            'fingerprint'     => $fingerprint,
            'detected_at'     => $detectedAt,
            'raw_payload'     => $d['payload'] ?? null,
        ]);
    }
}
