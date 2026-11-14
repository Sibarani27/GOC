<?php

namespace App\Livewire;

use App\Models\Alert;
use App\Models\Asset;
use App\Models\Incident;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.goc')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $since = now()->subHours(23)->startOfHour();

        // Jumlah alert per jam (24 jam terakhir), dihitung di PHP agar kompatibel dengan SQLite
        $perHour = Alert::where('detected_at', '>=', $since)
            ->pluck('detected_at')
            ->countBy(fn ($t) => $t->copy()->startOfHour()->format('Y-m-d H'));

        $hours = collect(range(0, 23))->map(function ($i) use ($since, $perHour) {
            $h = $since->copy()->addHours($i);

            return ['label' => $h->format('H'), 'total' => $perHour[$h->format('Y-m-d H')] ?? 0];
        });

        return view('livewire.dashboard', [
            'openAlerts' => Alert::open()->count(),
            'openCritical' => Alert::open()->where('severity', 'critical')->count(),
            'alerts24h' => Alert::where('detected_at', '>=', now()->subDay())->count(),
            'openIncidents' => Incident::whereNotIn('status', ['resolved', 'closed'])->count(),
            'bySeverity' => Alert::open()->selectRaw('severity, count(*) as total')->groupBy('severity')->pluck('total', 'severity'),
            'assetsByStatus' => Asset::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'hours' => $hours,
            'maxHour' => max(1, $hours->max('total')),
            'recent' => Alert::with('asset')->latest('detected_at')->limit(8)->get(),
        ]);
    }
}
