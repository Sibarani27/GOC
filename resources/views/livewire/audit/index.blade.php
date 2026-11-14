@php
    $subjectLabels = ['Incident' => 'Insiden', 'Alert' => 'Alert', 'Asset' => 'Aset', 'User' => 'Pengguna'];
    $eventLabels = ['created' => 'Dibuat', 'updated' => 'Diubah', 'deleted' => 'Dihapus'];
@endphp
<div class="space-y-4">
    <div>
        <h1 class="text-xl font-bold text-white">Audit Trail</h1>
        <p class="text-sm text-slate-500">Rekaman aktivitas pengguna dan perubahan data. Hanya dapat dibaca.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari deskripsi..."
               class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 lg:col-span-2">
        <select wire:model.live="logName" class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            <option value="">Semua kategori</option>
            @foreach ($logNames as $ln)
                <option value="{{ $ln }}">{{ $ln }}</option>
            @endforeach
        </select>
        <select wire:model.live="causerId" class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            <option value="">Semua pengguna</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500" title="Dari tanggal">
            <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500" title="Sampai tanggal">
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-800 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Waktu</th>
                <th class="px-4 py-3">Pengguna</th>
                <th class="px-4 py-3">Kategori</th>
                <th class="px-4 py-3">Aktivitas</th>
                <th class="px-4 py-3">Objek</th>
                <th class="px-4 py-3 text-right">Detail</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
            @forelse ($logs as $log)
                <tr class="hover:bg-slate-800/40" wire:key="log-{{ $log->id }}">
                    <td class="whitespace-nowrap px-4 py-3 text-slate-400">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                    <td class="px-4 py-3 text-slate-300">{{ $log->causer?->name ?? 'Sistem' }}</td>
                    <td class="px-4 py-3"><span class="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-300">{{ $log->log_name }}</span></td>
                    <td class="px-4 py-3 text-white">{{ $eventLabels[$log->description] ?? $log->description }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-400">
                        @if ($log->subject_type)
                            {{ $subjectLabels[class_basename($log->subject_type)] ?? class_basename($log->subject_type) }} #{{ $log->subject_id }}
                        @else - @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if ($log->properties->isNotEmpty())
                            <button wire:click="toggle({{ $log->id }})" class="text-amber-400 hover:text-amber-300">{{ $openId === $log->id ? 'Tutup' : 'Lihat' }}</button>
                        @endif
                    </td>
                </tr>
                @if ($openId === $log->id)
                    <tr wire:key="log-detail-{{ $log->id }}">
                        <td colspan="6" class="bg-slate-950 px-4 py-3">
                            <pre class="max-h-64 overflow-auto text-xs text-slate-300">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">Belum ada aktivitas yang tercatat.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
