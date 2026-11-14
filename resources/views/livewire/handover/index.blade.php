@php
    $shiftLabels = \App\Livewire\Handover\Index::SHIFTS;
    $incStatus = \App\Support\IncidentOptions::STATUSES;
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-white">Serah Terima Shift</h1>
            <p class="text-sm text-slate-500">Catatan pergantian petugas jaga, lengkap dengan kondisi alert dan insiden saat itu</p>
        </div>
        <button wire:click="openForm" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">+ Buat serah terima</button>
    </div>

    @if ($notice)
        <div class="rounded-lg border px-4 py-3 text-sm {{ $noticeType === 'ok' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-red-500/30 bg-red-500/10 text-red-300' }}">{{ $notice }}</div>
    @endif

    {{-- Menunggu diterima --}}
    @if ($waiting->isNotEmpty())
        <div class="rounded-xl border border-amber-500/40 bg-slate-900">
            <div class="border-b border-slate-800 px-4 py-3 text-sm font-semibold text-amber-300">Menunggu Anda terima ({{ $waiting->count() }})</div>
            <div class="divide-y divide-slate-800">
                @foreach ($waiting as $w)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3" wire:key="wait-{{ $w->id }}">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-white">Shift {{ $shiftLabels[$w->shift] ?? $w->shift }} dari {{ $w->from?->name ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $w->created_at->format('d M Y H:i') }}{{ $w->to_user_id ? '' : ' · terbuka untuk petugas berikutnya' }}</div>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="toggle({{ $w->id }})" class="rounded-md border border-slate-700 px-3 py-1.5 text-sm text-slate-200 hover:bg-slate-800">Baca</button>
                            <button wire:click="acknowledge({{ $w->id }})" class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-slate-950 hover:bg-amber-400">Terima</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="max-w-xs">
        <select wire:model.live="filter" class="w-full rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            <option value="all">Semua catatan</option>
            <option value="for_me">Untuk saya</option>
            <option value="from_me">Dari saya</option>
        </select>
    </div>

    {{-- Daftar --}}
    <div class="space-y-3">
        @forelse ($handovers as $h)
            @php($snap = $h->snapshot ?? [])
            <div class="rounded-xl border border-slate-800 bg-slate-900" wire:key="ho-{{ $h->id }}">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-300">{{ $shiftLabels[$h->shift] ?? $h->shift }}</span>
                            <span class="text-sm font-medium text-white">{{ $h->from?->name ?? '-' }} → {{ $h->to?->name ?? 'Petugas berikutnya' }}</span>
                            @if ($h->acknowledged_at)
                                <span class="inline-flex rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-300 ring-1 ring-inset ring-emerald-500/30">Diterima</span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-300 ring-1 ring-inset ring-amber-500/30">Menunggu</span>
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $h->created_at->format('d M Y H:i') }}
                            @if ($h->acknowledged_at) · diterima {{ $h->acknowledged_at->format('d M H:i') }} @endif
                            @if (! empty($snap)) · {{ $snap['alerts']['total'] ?? 0 }} alert &amp; {{ $snap['incidents']['total'] ?? 0 }} insiden terbuka @endif
                        </div>
                    </div>
                    <button wire:click="toggle({{ $h->id }})" class="text-sm text-amber-400 hover:text-amber-300">{{ $openId === $h->id ? 'Tutup' : 'Buka' }}</button>
                </div>

                @if ($openId === $h->id)
                    <div class="space-y-4 border-t border-slate-800 px-4 py-4">
                        <div>
                            <div class="mb-1 text-xs uppercase text-slate-500">Ringkasan shift</div>
                            <p class="whitespace-pre-line text-sm text-slate-200">{{ $h->summary }}</p>
                        </div>
                        @if ($h->pending_items)
                            <div>
                                <div class="mb-1 text-xs uppercase text-slate-500">Yang perlu ditindaklanjuti</div>
                                <p class="whitespace-pre-line text-sm text-slate-200">{{ $h->pending_items }}</p>
                            </div>
                        @endif

                        @if (! empty($snap))
                            <div>
                                <div class="mb-2 text-xs uppercase text-slate-500">
                                    Alert terbuka saat serah terima ({{ $snap['alerts']['total'] ?? 0 }})
                                    @if (! empty($snap['alerts']['by_severity']))
                                        · @foreach (['critical' => 'kritis', 'high' => 'tinggi', 'medium' => 'sedang', 'low' => 'rendah'] as $k => $lbl)
                                            @if (! empty($snap['alerts']['by_severity'][$k])) {{ $snap['alerts']['by_severity'][$k] }} {{ $lbl }}&nbsp; @endif
                                        @endforeach
                                    @endif
                                </div>
                                <div class="divide-y divide-slate-800 rounded-lg border border-slate-800">
                                    @forelse (($snap['alerts']['items'] ?? []) as $a)
                                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                                            <div class="min-w-0 truncate text-sm text-slate-200">{{ $a['title'] }}<span class="text-xs text-slate-500"> · {{ $a['asset'] ?? '-' }}</span></div>
                                            <x-goc.severity-badge :severity="$a['severity']" />
                                        </div>
                                    @empty
                                        <div class="px-3 py-3 text-sm text-slate-500">Tidak ada alert terbuka.</div>
                                    @endforelse
                                </div>
                                @if (($snap['alerts']['total'] ?? 0) > count($snap['alerts']['items'] ?? []))
                                    <p class="mt-1 text-xs text-slate-600">Menampilkan {{ count($snap['alerts']['items']) }} teratas berdasarkan tingkat keparahan.</p>
                                @endif
                            </div>

                            <div>
                                <div class="mb-2 text-xs uppercase text-slate-500">Insiden terbuka saat serah terima ({{ $snap['incidents']['total'] ?? 0 }})</div>
                                <div class="divide-y divide-slate-800 rounded-lg border border-slate-800">
                                    @forelse (($snap['incidents']['items'] ?? []) as $i)
                                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                                            <div class="min-w-0 truncate text-sm text-slate-200">
                                                @can('incidents.view')
                                                    <a href="{{ route('goc.incidents.show', $i['id']) }}" wire:navigate class="font-mono text-amber-400 hover:text-amber-300">{{ $i['code'] }}</a>
                                                @else
                                                    <span class="font-mono text-amber-400">{{ $i['code'] }}</span>
                                                @endcan
                                                {{ $i['title'] }}
                                                <span class="text-xs text-slate-500"> · {{ $incStatus[$i['status']] ?? $i['status'] }} · {{ $i['assignee'] ?? 'belum ditugaskan' }}</span>
                                            </div>
                                            <x-goc.severity-badge :severity="$i['severity']" />
                                        </div>
                                    @empty
                                        <div class="px-3 py-3 text-sm text-slate-500">Tidak ada insiden terbuka.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-slate-800 bg-slate-900 px-4 py-12 text-center text-sm text-slate-500">Belum ada catatan serah terima.</div>
        @endforelse
    </div>

    <div>{{ $handovers->links() }}</div>

    {{-- Formulir --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="ho-form">
            <div class="absolute inset-0 bg-black/70" wire:click="closeForm"></div>
            <form wire:submit="save" class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-700 bg-slate-900 p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-bold text-white">Buat serah terima</h2>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Shift Anda</label>
                            <select wire:model="shift" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                @foreach ($shiftLabels as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('shift') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Diserahkan kepada</label>
                            <select wire:model="toUserId" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                <option value="">Petugas berikutnya (siapa saja)</option>
                                @foreach ($recipients as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                            @error('toUserId') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Ringkasan shift</label>
                        <textarea wire:model="summary" rows="4" placeholder="Apa yang terjadi selama shift ini, tindakan yang sudah dilakukan..."
                                  class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500"></textarea>
                        @error('summary') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Yang perlu ditindaklanjuti (opsional)</label>
                        <textarea wire:model="pendingItems" rows="3" placeholder="Hal yang harus dipantau atau diselesaikan oleh petugas berikutnya..."
                                  class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500"></textarea>
                        @error('pendingItems') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    @if ($preview)
                        <div class="rounded-lg border border-slate-800 bg-slate-950 p-3 text-sm text-slate-400">
                            Kondisi berikut akan <span class="text-slate-200">otomatis dilampirkan</span>:
                            <span class="font-semibold text-white">{{ $preview['alerts']['total'] }}</span> alert terbuka
                            (<span class="text-red-300">{{ $preview['alerts']['by_severity']['critical'] ?? 0 }} kritis</span>) dan
                            <span class="font-semibold text-white">{{ $preview['incidents']['total'] }}</span> insiden terbuka.
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="rounded-md border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Batal</button>
                    <button type="submit" class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">Simpan serah terima</button>
                </div>
            </form>
        </div>
    @endif
</div>
