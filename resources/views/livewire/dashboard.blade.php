<div wire:poll.10s class="space-y-6">
    <div>
        <h1 class="text-xl font-bold text-white">Gambaran Situasi</h1>
        <p class="text-sm text-slate-500">Diperbarui otomatis tiap 10 detik</p>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Alert terbuka</div>
            <div class="mt-2 text-3xl font-bold text-white">{{ $openAlerts }}</div>
        </div>
        <div class="rounded-xl border border-red-500/30 bg-slate-900 p-4">
            <div class="text-xs uppercase tracking-wide text-red-300">Kritis (terbuka)</div>
            <div class="mt-2 text-3xl font-bold text-red-300">{{ $openCritical }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Alert 24 jam</div>
            <div class="mt-2 text-3xl font-bold text-white">{{ $alerts24h }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Insiden terbuka</div>
            <div class="mt-2 text-3xl font-bold text-white">{{ $openIncidents }}</div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Grafik per jam --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 lg:col-span-2">
            <div class="mb-4 text-sm font-semibold text-white">Alert per jam (24 jam terakhir)</div>
            <div class="flex h-40 items-end gap-1">
                @foreach ($hours as $h)
                    <div class="group flex h-full flex-1 flex-col justify-end" title="{{ $h['label'] }}:00 — {{ $h['total'] }} alert">
                        <div class="w-full rounded-t bg-amber-500/80 group-hover:bg-amber-400"
                             style="height: {{ $h['total'] > 0 ? max(4, round($h['total'] / $maxHour * 100)) : 0 }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[10px] text-slate-500">
                <span>{{ $hours->first()['label'] }}:00</span>
                <span>{{ $hours->last()['label'] }}:00</span>
            </div>
        </div>

        {{-- Severity & aset --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Alert terbuka per tingkat</div>
                <div class="space-y-2">
                    @foreach (['critical', 'high', 'medium', 'low'] as $sev)
                        <div class="flex items-center justify-between">
                            <x-goc.severity-badge :severity="$sev" />
                            <span class="text-sm font-semibold text-white">{{ $bySeverity[$sev] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Status aset</div>
                <div class="space-y-2">
                    @foreach (['online', 'degraded', 'offline', 'unknown'] as $st)
                        <div class="flex items-center justify-between">
                            <x-goc.status-badge :status="$st" />
                            <span class="text-sm font-semibold text-white">{{ $assetsByStatus[$st] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Alert terbaru --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
            <div class="text-sm font-semibold text-white">Alert terbaru</div>
            @can('alerts.view')
                <a href="{{ route('goc.alerts') }}" wire:navigate class="text-sm text-amber-400 hover:text-amber-300">Lihat semua</a>
            @endcan
        </div>
        <div class="divide-y divide-slate-800">
            @forelse ($recent as $a)
                <div class="flex items-center justify-between gap-3 px-4 py-3" wire:key="r{{ $a->id }}">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium text-white">{{ $a->title }}</div>
                        <div class="text-xs text-slate-500">
                            {{ $a->asset?->name ?? ($a->source_ip ?? '-') }} · {{ $a->source }} · {{ $a->detected_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <x-goc.severity-badge :severity="$a->severity" />
                        <x-goc.status-badge :status="$a->status" />
                    </div>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-sm text-slate-500">Belum ada alert.</div>
            @endforelse
        </div>
    </div>
</div>
