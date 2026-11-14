<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-xl font-bold text-white">Keamanan Akun</h1>
        <p class="text-sm text-slate-500">Lindungi akun Anda dengan autentikasi dua faktor (2FA).</p>
    </div>

    @if (session('warning'))
        <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">{{ session('warning') }}</div>
    @endif

    {{-- Kode pemulihan (ditampilkan sekali) --}}
    @if (count($recoveryCodes))
        <div class="rounded-xl border border-amber-500/40 bg-slate-900 p-5">
            <div class="mb-1 text-sm font-semibold text-amber-300">Simpan kode pemulihan Anda sekarang</div>
            <p class="mb-3 text-sm text-slate-400">Kode ini hanya ditampilkan sekali. Setiap kode dapat dipakai satu kali jika Anda kehilangan akses ke aplikasi autentikator. Simpan di tempat aman (misalnya pengelola password).</p>
            <div class="grid grid-cols-2 gap-2 rounded-lg bg-slate-950 p-4 font-mono text-sm text-slate-200">
                @foreach ($recoveryCodes as $rc)
                    <div>{{ $rc }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="mb-4 flex items-center justify-between">
            <div class="text-sm font-semibold text-white">Autentikasi dua faktor</div>
            @if ($confirmed)
                <span class="inline-flex rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-300 ring-1 ring-inset ring-emerald-500/30">Aktif</span>
            @else
                <span class="inline-flex rounded-full bg-slate-500/10 px-2 py-0.5 text-xs font-medium text-slate-400 ring-1 ring-inset ring-slate-500/30">Belum aktif</span>
            @endif
        </div>

        @if (! $confirmed && ! $pending)
            <p class="mb-4 text-sm text-slate-400">
                Setelah diaktifkan, selain password Anda juga akan diminta kode 6 digit dari aplikasi autentikator
                (Google Authenticator, Microsoft Authenticator, Aegis, atau sejenisnya) setiap kali masuk.
                @if ($required) <span class="text-amber-300">Kebijakan saat ini mewajibkan 2FA untuk semua pengguna.</span> @endif
            </p>
            <button wire:click="startSetup" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">Aktifkan 2FA</button>
        @endif

        @if ($pending)
            <ol class="mb-4 list-decimal space-y-1 pl-5 text-sm text-slate-400">
                <li>Buka aplikasi autentikator, lalu pindai kode QR di bawah.</li>
                <li>Masukkan kode 6 digit yang muncul untuk mengonfirmasi.</li>
            </ol>

            <div class="flex flex-wrap items-start gap-6">
                @if ($qr)
                    <div class="rounded-lg bg-white p-3">{!! $qr !!}</div>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="text-xs uppercase text-slate-500">Atau masukkan kunci ini secara manual</div>
                    <div class="mt-1 break-all rounded-lg bg-slate-950 p-3 font-mono text-sm tracking-wider text-slate-200">{{ $secret ? trim(chunk_split($secret, 4, ' ')) : '-' }}</div>
                </div>
            </div>

            <form wire:submit="confirmSetup" class="mt-5 flex flex-wrap items-start gap-3">
                <div>
                    <input type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" placeholder="123456"
                           class="w-40 rounded-lg border-slate-700 bg-slate-950 text-center font-mono text-lg tracking-widest text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                    @error('code') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400">Konfirmasi &amp; aktifkan</button>
                <button type="button" wire:click="cancelSetup" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Batal</button>
            </form>
        @endif

        @if ($confirmed)
            <p class="mb-4 text-sm text-slate-400">Sisa kode pemulihan: <span class="font-semibold text-white">{{ $remaining }}</span></p>

            <div class="space-y-4 border-t border-slate-800 pt-4">
                <div>
                    <label class="mb-1 block text-xs uppercase text-slate-500">Konfirmasi dengan password Anda</label>
                    <input type="password" wire:model="password" autocomplete="current-password"
                           class="w-full max-w-xs rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                    @error('password') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-start gap-3">
                    <button wire:click="regenerateCodes" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Buat ulang kode pemulihan</button>
                </div>

                <div class="border-t border-slate-800 pt-4">
                    <div class="mb-2 text-sm font-semibold text-white">Nonaktifkan 2FA</div>
                    <div class="flex flex-wrap items-start gap-3">
                        <div>
                            <input type="text" wire:model="code" autocomplete="one-time-code" placeholder="Kode 2FA / pemulihan"
                                   class="w-56 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                            @error('code') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="disable" wire:confirm="Nonaktifkan autentikasi dua faktor? Akun Anda menjadi kurang aman."
                                class="rounded-lg border border-red-500/40 px-4 py-2 text-sm text-red-300 hover:bg-red-500/10">Nonaktifkan</button>
                    </div>
                    <p class="mt-2 text-xs text-slate-600">Butuh password dan kode 2FA (atau kode pemulihan) yang valid.</p>
                </div>
            </div>
        @endif
    </div>
</div>
