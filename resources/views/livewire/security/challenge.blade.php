<div class="w-full max-w-sm">
    <div class="mb-6 flex items-center justify-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500 text-base font-black text-slate-950">G</div>
        <div class="leading-tight">
            <div class="text-sm font-bold tracking-wide text-white">GARUDA</div>
            <div class="text-[11px] uppercase tracking-widest text-slate-400">Operations Center</div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
        <h1 class="text-lg font-bold text-white">Verifikasi dua faktor</h1>
        <p class="mt-1 text-sm text-slate-400">Masukkan kode 6 digit dari aplikasi autentikator Anda, atau salah satu kode pemulihan.</p>

        <form wire:submit="verify" class="mt-5 space-y-4">
            <div>
                <input type="text" wire:model="code" autofocus autocomplete="one-time-code" placeholder="123456"
                       class="w-full rounded-lg border-slate-700 bg-slate-950 text-center font-mono text-lg tracking-widest text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                @error('code') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">Verifikasi</button>
        </form>

        <form method="POST" action="{{ route('goc.logout') }}" class="mt-4 text-center">
            @csrf
            <button class="text-sm text-slate-500 hover:text-slate-300">Batal dan keluar</button>
        </form>
    </div>
</div>
