<?php

namespace App\Support;

use App\Models\Alert;
use App\Models\Asset;
use App\Models\Incident;
use Carbon\Carbon;

class ReportBuilder
{
    public const MAX_DAYS = 366;

    /**
     * Definisi metrik (dihitung dari insiden yang DIBUKA pada periode laporan):
     *  - MTTD: rata-rata (insiden dibuka - waktu deteksi alert pertama)
     *  - MTTC: rata-rata (terkendali - dibuka), hanya insiden yang sudah terkendali
     *  - MTTR: rata-rata (ditutup - dibuka), hanya insiden yang sudah ditutup
     */
    public static function build(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $alerts = fn () => Alert::whereBetween('detected_at', [$start, $end]);

        $alertTotal = $alerts()->count();
        $bySeverity = $alerts()->selectRaw('severity, count(*) as total')->groupBy('severity')->pluck('total', 'severity')->all();
        $byStatus = $alerts()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all();
        $bySource = $alerts()->selectRaw('source, count(*) as total')->groupBy('source')->orderByDesc('total')->pluck('total', 'source')->all();
        $events = (int) $alerts()->sum('occurrences');

        // Tren harian
        $perDay = $alerts()->selectRaw('date(detected_at) as d, count(*) as total')->groupBy('d')->pluck('total', 'd')->all();
        $daily = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $daily[] = ['date' => $key, 'total' => (int) ($perDay[$key] ?? 0)];
        }

        // Aset dan IP teratas
        $topAssetRows = $alerts()->whereNotNull('asset_id')->selectRaw('asset_id, count(*) as total')
            ->groupBy('asset_id')->orderByDesc('total')->limit(5)->get();
        $assetNames = Asset::withTrashed()->whereIn('id', $topAssetRows->pluck('asset_id'))->pluck('name', 'id');
        $topAssets = $topAssetRows->map(fn ($r) => [
            'name' => $assetNames[$r->asset_id] ?? ('Aset #' . $r->asset_id),
            'total' => (int) $r->total,
        ])->all();

        $topIps = $alerts()->whereNotNull('source_ip')->selectRaw('source_ip, count(*) as total')
            ->groupBy('source_ip')->orderByDesc('total')->limit(5)->get()
            ->map(fn ($r) => ['ip' => $r->source_ip, 'total' => (int) $r->total])->all();

        // Insiden
        $incidents = Incident::whereBetween('opened_at', [$start, $end])->get();

        $mttd = $mttc = $mttr = [];
        foreach ($incidents as $i) {
            if ($i->detected_at && $i->opened_at) {
                $mttd[] = max(0, ($i->opened_at->getTimestamp() - $i->detected_at->getTimestamp()) / 60);
            }
            if ($i->contained_at && $i->opened_at) {
                $mttc[] = max(0, ($i->contained_at->getTimestamp() - $i->opened_at->getTimestamp()) / 60);
            }
            if ($i->closed_at && $i->opened_at) {
                $mttr[] = max(0, ($i->closed_at->getTimestamp() - $i->opened_at->getTimestamp()) / 60);
            }
        }
        $avg = fn (array $a) => count($a) ? array_sum($a) / count($a) : null;

        return [
            'from' => $start->format('Y-m-d'),
            'to' => $end->format('Y-m-d'),
            'days' => count($daily),
            'generated_at' => now()->format('d M Y H:i'),
            'alerts' => [
                'total' => $alertTotal,
                'events' => $events,
                'by_severity' => $bySeverity,
                'by_status' => $byStatus,
                'by_source' => $bySource,
                'false_positive_pct' => $alertTotal ? round(($byStatus['false_positive'] ?? 0) / $alertTotal * 100, 1) : 0.0,
                'daily' => $daily,
                'max_daily' => max(1, collect($daily)->max('total') ?? 1),
                'top_assets' => $topAssets,
                'top_ips' => $topIps,
            ],
            'incidents' => [
                'opened' => $incidents->count(),
                'resolved' => $incidents->whereIn('status', IncidentOptions::CLOSED_STATUSES)->count(),
                'still_open' => $incidents->whereNotIn('status', IncidentOptions::CLOSED_STATUSES)->count(),
                'by_category' => $incidents->countBy('category')->sortDesc()->all(),
                'by_severity' => $incidents->countBy('severity')->all(),
                'mttd' => $avg($mttd),
                'mttc' => $avg($mttc),
                'mttr' => $avg($mttr),
                'n_mttd' => count($mttd),
                'n_mttc' => count($mttc),
                'n_mttr' => count($mttr),
            ],
        ];
    }

    public static function duration(?float $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        $m = (int) round($minutes);
        if ($m < 60) {
            return $m . ' menit';
        }
        if ($m < 1440) {
            return intdiv($m, 60) . ' jam ' . ($m % 60) . ' menit';
        }

        return intdiv($m, 1440) . ' hari ' . intdiv($m % 1440, 60) . ' jam';
    }

    public static function severityLabel(string $s): string
    {
        return ['critical' => 'Kritis', 'high' => 'Tinggi', 'medium' => 'Sedang', 'low' => 'Rendah'][$s] ?? $s;
    }

    public static function statusLabel(string $s): string
    {
        return [
            'new' => 'Baru', 'acknowledged' => 'Diakui', 'in_progress' => 'Diproses',
            'resolved' => 'Selesai', 'false_positive' => 'False positive',
        ][$s] ?? $s;
    }

    public static function categoryLabel(string $c): string
    {
        return IncidentOptions::CATEGORIES[$c] ?? $c;
    }
}
