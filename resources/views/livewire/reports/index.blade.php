@php
    use App\Support\ReportBuilder as R;
@endphp
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-white">Laporan Operasional</h1>
            <p class="text-sm text-slate-500">Ringkasan alert, insiden, dan waktu respons untuk periode yang dipilih</p>
        </div>
        @if ($r)
            @can('reports.export')
                <div class="flex gap-2">
                    <a href="{{ route('goc.reports.pdf', ['from' => $from, 'to' => $to]) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800">Unduh PDF</a>
                    <a href="{{ route('goc.reports.xlsx', ['from' => $from, 'to' => $to]) }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">Unduh Excel</a>
                </div>
            @endcan
        @endif
    </div>

    {{-- Periode --}}
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-800 bg-slate-900 p-4">
        <div class="flex gap-2">
            @foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $d => $label)
                <button wire:click="setPreset({{ $d }})" class="rounded-md border border-slate-700 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800">{{ $label }}</button>
            @endforeach
        </div>
        <div>
            <label class="mb-1 block text-xs uppercase text-slate-500">Dari</label>
            <input type="date" wire:model.live="from" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div>
            <label class="mb-1 block text-xs uppercase text-slate-500">Sampai</label>
            <input type="date" wire:model.live="to" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
        </div>
    </div>

    @if ($error)
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">{{ $error }}</div>
    @endif

    @if ($r)
        {{-- Waktu respons --}}
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['MTTD', 'mttd', 'n_mttd', 'Rata-rata dari deteksi alert sampai insiden dibuka'],
                ['MTTC', 'mttc', 'n_mttc', 'Rata-rata dari insiden dibuka sampai terkendali'],
                ['MTTR', 'mttr', 'n_mttr', 'Rata-rata dari insiden dibuka sampai ditutup'],
            ] as [$label, $key, $nKey, $hint])
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-bold text-white">{{ R::duration($r['incidents'][$key]) }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ $hint }}</div>
                    <div class="text-xs text-slate-600">dari {{ $r['incidents'][$nKey] }} insiden</div>
                </div>
            @endforeach
        </div>

        {{-- Ringkasan angka --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><div class="text-xs uppercase text-slate-500">Alert</div><div class="mt-2 text-2xl font-bold text-white">{{ $r['alerts']['total'] }}</div><div class="text-xs text-slate-600">{{ $r['alerts']['events'] }} kejadian</div></div>
            <div class="rounded-xl border border-red-500/30 bg-slate-900 p-4"><div class="text-xs uppercase text-red-300">Kritis</div><div class="mt-2 text-2xl font-bold text-red-300">{{ $r['alerts']['by_severity']['critical'] ?? 0 }}</div></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><div class="text-xs uppercase text-slate-500">False positive</div><div class="mt-2 text-2xl font-bold text-white">{{ $r['alerts']['false_positive_pct'] }}%</div></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><div class="text-xs uppercase text-slate-500">Insiden dibuka</div><div class="mt-2 text-2xl font-bold text-white">{{ $r['incidents']['opened'] }}</div></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><div class="text-xs uppercase text-slate-500">Selesai / masih terbuka</div><div class="mt-2 text-2xl font-bold text-white">{{ $r['incidents']['resolved'] }} / {{ $r['incidents']['still_open'] }}</div></div>
        </div>

        {{-- Tren --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <div class="mb-4 text-sm font-semibold text-white">Alert per hari ({{ $r['days'] }} hari)</div>
            <div class="flex h-40 items-end gap-px">
                @foreach ($r['alerts']['daily'] as $d)
                    <div class="group flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $d['date'] }}: {{ $d['total'] }} alert">
                        <div class="w-full rounded-t bg-amber-500/80 group-hover:bg-amber-400"
                             style="height: {{ $d['total'] > 0 ? max(3, round($d['total'] / $r['alerts']['max_daily'] * 100)) : 0 }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[10px] text-slate-500">
                <span>{{ $r['from'] }}</span><span>{{ $r['to'] }}</span>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- Per tingkat --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Alert per tingkat</div>
                <div class="space-y-2">
                    @foreach (['critical', 'high', 'medium', 'low'] as $s)
                        @php($n = $r['alerts']['by_severity'][$s] ?? 0)
                        <div class="flex items-center gap-3">
                            <div class="w-20"><x-goc.severity-badge :severity="$s" /></div>
                            <div class="h-2 flex-1 rounded bg-slate-800"><div class="h-2 rounded bg-amber-500" style="width: {{ $r['alerts']['total'] ? round($n / $r['alerts']['total'] * 100) : 0 }}%"></div></div>
                            <div class="w-10 text-right text-sm font-semibold text-white">{{ $n }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Insiden per kategori --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Insiden per kategori</div>
                @forelse ($r['incidents']['by_category'] as $cat => $n)
                    <div class="flex items-center gap-3 py-1">
                        <div class="w-40 truncate text-sm text-slate-300">{{ R::categoryLabel($cat) }}</div>
                        <div class="h-2 flex-1 rounded bg-slate-800"><div class="h-2 rounded bg-sky-500" style="width: {{ $r['incidents']['opened'] ? round($n / $r['incidents']['opened'] * 100) : 0 }}%"></div></div>
                        <div class="w-10 text-right text-sm font-semibold text-white">{{ $n }}</div>
                    </div>
                @empty
                    <div class="py-6 text-center text-sm text-slate-500">Tidak ada insiden pada periode ini.</div>
                @endforelse
            </div>

            {{-- Aset teratas --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">Aset dengan alert terbanyak</div>
                @forelse ($r['alerts']['top_assets'] as $x)
                    <div class="flex justify-between py-1 text-sm"><span class="truncate text-slate-300">{{ $x['name'] }}</span><span class="font-semibold text-white">{{ $x['total'] }}</span></div>
                @empty
                    <div class="py-6 text-center text-sm text-slate-500">Tidak ada data.</div>
                @endforelse
            </div>

            {{-- IP teratas --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3 text-sm font-semibold text-white">IP sumber dengan alert terbanyak</div>
                @forelse ($r['alerts']['top_ips'] as $x)
                    <div class="flex justify-between py-1 text-sm"><span class="font-mono text-slate-300">{{ $x['ip'] }}</span><span class="font-semibold text-white">{{ $x['total'] }}</span></div>
                @empty
                    <div class="py-6 text-center text-sm text-slate-500">Tidak ada data.</div>
                @endforelse
            </div>
        </div>

        <p class="text-xs text-slate-600">MTTD, MTTC, dan MTTR dihitung dari insiden yang dibuka pada periode ini. MTTC hanya mencakup insiden yang sudah berstatus terkendali, dan MTTR hanya insiden yang sudah ditutup.</p>
    @endif
</div>
