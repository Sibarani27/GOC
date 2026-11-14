<div wire:poll.15s class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-white">Manajemen Insiden</h1>
            <p class="text-sm text-slate-500">Lacak penanganan dari deteksi sampai penutupan</p>
        </div>
        @can('incidents.manage')
            <button wire:click="openForm" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">+ Insiden baru</button>
        @endcan
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari kode atau judul..."
               class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500">
        <select wire:model.live="severity" class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            <option value="">Semua tingkat</option>
            <option value="critical">Kritis</option>
            <option value="high">Tinggi</option>
            <option value="medium">Sedang</option>
            <option value="low">Rendah</option>
        </select>
        <select wire:model.live="status" class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            <option value="open">Masih ditangani</option>
            @foreach (\App\Support\IncidentOptions::STATUSES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
            <option value="all">Semua status</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-800 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Kode</th>
                <th class="px-4 py-3">Tingkat</th>
                <th class="px-4 py-3">Insiden</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Penanggung jawab</th>
                <th class="px-4 py-3">Dibuka</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
            @forelse ($incidents as $inc)
                <tr class="hover:bg-slate-800/40" wire:key="inc-{{ $inc->id }}">
                    <td class="whitespace-nowrap px-4 py-3">
                        <a href="{{ route('goc.incidents.show', $inc) }}" wire:navigate class="font-mono text-amber-400 hover:text-amber-300">{{ $inc->code }}</a>
                    </td>
                    <td class="px-4 py-3"><x-goc.severity-badge :severity="$inc->severity" /></td>
                    <td class="px-4 py-3">
                        <a href="{{ route('goc.incidents.show', $inc) }}" wire:navigate class="font-medium text-white hover:text-amber-300">{{ $inc->title }}</a>
                        <div class="text-xs text-slate-500">{{ \App\Support\IncidentOptions::CATEGORIES[$inc->category] ?? $inc->category }}</div>
                    </td>
                    <td class="px-4 py-3"><x-goc.status-badge :status="$inc->status" /></td>
                    <td class="px-4 py-3 text-slate-400">{{ $inc->assignee?->name ?? 'Belum ditugaskan' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $inc->opened_at?->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">Tidak ada insiden yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $incidents->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="inc-form">
            <div class="absolute inset-0 bg-black/70" wire:click="closeForm"></div>
            <form wire:submit="createIncident" class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-700 bg-slate-900 p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-bold text-white">Insiden baru</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Judul</label>
                        <input type="text" wire:model="formTitle" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        @error('formTitle') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Kategori</label>
                            <select wire:model="formCategory" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                @foreach (\App\Support\IncidentOptions::CATEGORIES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Tingkat</label>
                            <select wire:model="formSeverity" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                <option value="low">Rendah</option>
                                <option value="medium">Sedang</option>
                                <option value="high">Tinggi</option>
                                <option value="critical">Kritis</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Deskripsi</label>
                        <textarea wire:model="formDescription" rows="4" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500"></textarea>
                        @error('formDescription') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="rounded-md border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Batal</button>
                    <button type="submit" class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">Buat insiden</button>
                </div>
            </form>
        </div>
    @endif
</div>
