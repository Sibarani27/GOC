<div wire:poll.10s class="space-y-4">
    <div>
        <h1 class="text-xl font-bold text-white">Manajemen Alert</h1>
        <p class="text-sm text-slate-500">Diperbarui otomatis tiap 10 detik</p>
    </div>

    {{-- Filter --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari judul, IP, sumber, atau aset..."
               class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500">
        <select wire:model.live="severity"
                class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            <option value="">Semua tingkat</option>
            <option value="critical">Kritis</option>
            <option value="high">Tinggi</option>
            <option value="medium">Sedang</option>
            <option value="low">Rendah</option>
        </select>
        <select wire:model.live="status"
                class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            <option value="open">Masih terbuka</option>
            <option value="new">Baru</option>
            <option value="acknowledged">Diakui</option>
            <option value="in_progress">Diproses</option>
            <option value="resolved">Selesai</option>
            <option value="false_positive">False positive</option>
            <option value="all">Semua status</option>
        </select>
    </div>

    {{-- Tabel --}}
    <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-800 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Waktu</th>
                <th class="px-4 py-3">Tingkat</th>
                <th class="px-4 py-3">Alert</th>
                <th class="px-4 py-3">Sumber</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
            @forelse ($alerts as $a)
                <tr class="hover:bg-slate-800/40" wire:key="alert-{{ $a->id }}">
                    <td class="whitespace-nowrap px-4 py-3 text-slate-400">
                        <div>{{ $a->detected_at->format('d M H:i') }}</div>
                        <div class="text-xs text-slate-600">{{ $a->detected_at->diffForHumans() }}</div>
                    </td>
                    <td class="px-4 py-3"><x-goc.severity-badge :severity="$a->severity" /></td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-white">
                            {{ $a->title }}
                            @if ($a->occurrences > 1)
                                <span class="ml-1 rounded bg-slate-700 px-1.5 py-0.5 text-xs text-slate-300">×{{ $a->occurrences }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500">{{ $a->asset?->name ?? '-' }} · {{ $a->source_ip ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-400">{{ $a->source }}</td>
                    <td class="px-4 py-3"><x-goc.status-badge :status="$a->status" /></td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <button wire:click="show({{ $a->id }})" class="text-amber-400 hover:text-amber-300">Detail</button>
                        @can('alerts.manage')
                            @if ($a->status === 'new')
                                <button wire:click="setStatus({{ $a->id }}, 'acknowledged')" class="ml-3 text-slate-300 hover:text-white">Akui</button>
                            @endif
                            @if (! in_array($a->status, ['resolved', 'false_positive']))
                                <button wire:click="setStatus({{ $a->id }}, 'resolved')" class="ml-3 text-emerald-400 hover:text-emerald-300">Selesai</button>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">Tidak ada alert yang cocok dengan filter.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $alerts->links() }}</div>

    {{-- Modal detail --}}
    @if ($selected)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="alert-modal">
            <div class="absolute inset-0 bg-black/70" wire:click="close"></div>
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-700 bg-slate-900 p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white">{{ $selected->title }}</h2>
                        <div class="mt-2 flex gap-2">
                            <x-goc.severity-badge :severity="$selected->severity" />
                            <x-goc.status-badge :status="$selected->status" />
                        </div>
                    </div>
                    <button wire:click="close" class="text-slate-400 hover:text-white" aria-label="Tutup">✕</button>
                </div>

                @if ($selected->description)
                    <p class="mt-4 whitespace-pre-line text-sm text-slate-300">{{ $selected->description }}</p>
                @endif

                <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div><dt class="text-xs uppercase text-slate-500">Sumber</dt><dd>{{ $selected->source }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Terdeteksi</dt><dd>{{ $selected->detected_at->format('d M Y H:i:s') }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Aset</dt><dd>{{ $selected->asset?->name ?? '-' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Jumlah kejadian</dt><dd>{{ $selected->occurrences }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">IP sumber</dt><dd>{{ $selected->source_ip ?? '-' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">IP tujuan</dt><dd>{{ $selected->destination_ip ?? '-' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">MITRE taktik</dt><dd>{{ $selected->mitre_tactic ?? '-' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">MITRE teknik</dt><dd>{{ $selected->mitre_technique ?? '-' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Diakui oleh</dt><dd>{{ $selected->acknowledger?->name ?? '-' }}</dd></div>
                </dl>

                @if ($selected->incidents->isNotEmpty())
                    <div class="mt-4 text-sm">
                        <div class="mb-1 text-xs uppercase text-slate-500">Insiden terkait</div>
                        @foreach ($selected->incidents as $inc)
                            <a href="{{ route('goc.incidents.show', $inc) }}" wire:navigate class="mr-2 font-mono text-amber-400 hover:text-amber-300">{{ $inc->code }}</a>
                        @endforeach
                    </div>
                @endif

                @if ($selected->raw_payload)
                    <div class="mt-4">
                        <div class="mb-1 text-xs uppercase text-slate-500">Payload mentah</div>
                        <pre class="max-h-56 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-300">{{ json_encode($selected->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @endif

                @can('incidents.manage')
                    @if ($selected->incidents->isEmpty())
                        <div class="mt-6 border-t border-slate-800 pt-4">
                            <button wire:click="createIncident({{ $selected->id }})"
                                    class="rounded-md bg-red-500/20 px-3 py-1.5 text-sm font-medium text-red-300 ring-1 ring-inset ring-red-500/30 hover:bg-red-500/30">
                                Buat insiden dari alert ini
                            </button>
                        </div>
                    @endif
                @endcan

                @can('alerts.manage')
                    <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-800 pt-4">
                        @foreach (['acknowledged' => 'Akui', 'in_progress' => 'Proses', 'resolved' => 'Selesai', 'false_positive' => 'False positive'] as $st => $label)
                            @if ($selected->status !== $st)
                                <button wire:click="setStatus({{ $selected->id }}, '{{ $st }}')"
                                        class="rounded-md border border-slate-700 px-3 py-1.5 text-sm text-slate-200 hover:bg-slate-800">{{ $label }}</button>
                            @endif
                        @endforeach
                    </div>
                @endcan
            </div>
        </div>
    @endif
</div>
