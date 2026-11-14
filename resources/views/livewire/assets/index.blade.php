<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-white">Inventaris Aset</h1>
            <p class="text-sm text-slate-500">Alert dicocokkan ke aset lewat IP atau nama</p>
        </div>
        @can('assets.manage')
            <button wire:click="create" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">+ Tambah aset</button>
        @endcan
    </div>

    <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari nama, IP, atau lokasi..."
           class="w-full rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 sm:max-w-sm">

    <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-800 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Tipe</th>
                <th class="px-4 py-3">IP</th>
                <th class="px-4 py-3">Lokasi</th>
                <th class="px-4 py-3">Kritikalitas</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Terakhir terlihat</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
            @forelse ($assets as $asset)
                <tr class="hover:bg-slate-800/40" wire:key="asset-{{ $asset->id }}">
                    <td class="px-4 py-3 font-medium text-white">{{ $asset->name }}</td>
                    <td class="px-4 py-3 text-slate-400">{{ $asset->type }}</td>
                    <td class="px-4 py-3 text-slate-400">{{ $asset->ip_address ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-400">{{ $asset->location ?? '-' }}</td>
                    <td class="px-4 py-3"><x-goc.severity-badge :severity="$asset->criticality" /></td>
                    <td class="px-4 py-3"><x-goc.status-badge :status="$asset->status" /></td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $asset->last_seen_at?->diffForHumans() ?? '-' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        @can('assets.manage')
                            <button wire:click="edit({{ $asset->id }})" class="text-amber-400 hover:text-amber-300">Ubah</button>
                            <button wire:click="delete({{ $asset->id }})" wire:confirm="Hapus aset {{ $asset->name }}?" class="ml-3 text-red-400 hover:text-red-300">Hapus</button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">Belum ada aset.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $assets->links() }}</div>

    {{-- Modal form --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="asset-modal">
            <div class="absolute inset-0 bg-black/70" wire:click="closeForm"></div>
            <form wire:submit="save" class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-700 bg-slate-900 p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-bold text-white">{{ $editingId ? 'Ubah aset' : 'Tambah aset' }}</h2>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Nama</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Tipe</label>
                            <select wire:model="type" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                @foreach (\App\Livewire\Assets\Index::TYPES as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Alamat IP</label>
                            <input type="text" wire:model="ip_address" placeholder="10.0.0.5" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                            @error('ip_address') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Lokasi</label>
                        <input type="text" wire:model="location" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        @error('location') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Status</label>
                            <select wire:model="status" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                @foreach (\App\Livewire\Assets\Index::STATUSES as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Kritikalitas</label>
                            <select wire:model="criticality" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                @foreach (\App\Livewire\Assets\Index::CRITICALITIES as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="rounded-md border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Batal</button>
                    <button type="submit" class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">Simpan</button>
                </div>
            </form>
        </div>
    @endif
</div>
