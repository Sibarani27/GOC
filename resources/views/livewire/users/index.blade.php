<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-white">Manajemen Pengguna</h1>
            <p class="text-sm text-slate-500">Kelola akun dan hak akses. Akun yang dinonaktifkan langsung dikeluarkan dari sistem.</p>
        </div>
        <button wire:click="create" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">+ Tambah pengguna</button>
    </div>

    @if ($notice)
        <div class="rounded-lg border px-4 py-3 text-sm {{ $noticeType === 'ok' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-red-500/30 bg-red-500/10 text-red-300' }}">
            {{ $notice }}
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari nama atau email..."
               class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:ring-amber-500 sm:col-span-2">
        <select wire:model.live="roleFilter" class="rounded-lg border-slate-700 bg-slate-900 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            <option value="">Semua peran</option>
            @foreach (\App\Livewire\Users\Index::ROLES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-800 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Pengguna</th>
                <th class="px-4 py-3">Peran</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Login terakhir</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
            @forelse ($users as $u)
                <tr class="hover:bg-slate-800/40" wire:key="user-{{ $u->id }}">
                    <td class="px-4 py-3">
                        <div class="font-medium text-white">{{ $u->name }} @if ($u->id === auth()->id()) <span class="text-xs text-slate-500">(Anda)</span> @endif</div>
                        <div class="text-xs text-slate-500">{{ $u->email }}</div>
                        @if ($u->two_factor_confirmed_at)
                            <span class="mt-1 inline-flex rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-medium uppercase text-emerald-300">2FA aktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php($roleName = $u->roles->first()?->name)
                        <span class="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-300">{{ \App\Livewire\Users\Index::ROLES[$roleName] ?? 'Tanpa peran' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if ($u->is_active)
                            <span class="inline-flex rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-300 ring-1 ring-inset ring-emerald-500/30">Aktif</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-500/10 px-2 py-0.5 text-xs font-medium text-slate-400 ring-1 ring-inset ring-slate-500/30">Nonaktif</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                        {{ $u->last_login_at ? \Illuminate\Support\Carbon::parse($u->last_login_at)->diffForHumans() : 'Belum pernah' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <button wire:click="edit({{ $u->id }})" class="text-amber-400 hover:text-amber-300">Ubah</button>
                        @if ($u->two_factor_confirmed_at && $u->id !== auth()->id())
                            <button wire:click="resetTwoFactor({{ $u->id }})" wire:confirm="Reset 2FA untuk {{ $u->email }}? Pengguna harus mendaftar ulang." class="ml-3 text-slate-400 hover:text-white">Reset 2FA</button>
                        @endif
                        @if ($u->id !== auth()->id())
                            @if ($u->is_active)
                                <button wire:click="toggleActive({{ $u->id }})" wire:confirm="Nonaktifkan akun {{ $u->email }}? Pengguna akan langsung dikeluarkan." class="ml-3 text-red-400 hover:text-red-300">Nonaktifkan</button>
                            @else
                                <button wire:click="toggleActive({{ $u->id }})" class="ml-3 text-emerald-400 hover:text-emerald-300">Aktifkan</button>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">Tidak ada pengguna yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $users->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="user-form">
            <div class="absolute inset-0 bg-black/70" wire:click="closeForm"></div>
            <form wire:submit="save" autocomplete="off" class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-700 bg-slate-900 p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-bold text-white">{{ $editingId ? 'Ubah pengguna' : 'Tambah pengguna' }}</h2>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Nama</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Email</label>
                        <input type="email" wire:model="email" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        @error('email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs uppercase text-slate-500">Peran</label>
                        <select wire:model="role" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                            @foreach (\App\Livewire\Users\Index::ROLES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">{{ $editingId ? 'Password baru (opsional)' : 'Password' }}</label>
                            <input type="password" wire:model="password" autocomplete="new-password" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs uppercase text-slate-500">Ulangi password</label>
                            <input type="password" wire:model="password_confirmation" autocomplete="new-password" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                    </div>
                    @error('password') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                    <p class="text-xs text-slate-600">Minimal 12 karakter, kombinasi huruf besar, huruf kecil, dan angka.</p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="rounded-md border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Batal</button>
                    <button type="submit" class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">Simpan</button>
                </div>
            </form>
        </div>
    @endif
</div>
