<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? 'Dashboard') . ' | ' . config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-950 text-slate-200 antialiased" x-data="{ open: false }">
@php
    $nav = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'can' => 'dashboard.view'],
        ['route' => 'goc.alerts', 'label' => 'Alert', 'can' => 'alerts.view'],
        ['route' => 'goc.incidents', 'label' => 'Insiden', 'can' => 'incidents.view'],
        ['route' => 'goc.assets', 'label' => 'Aset', 'can' => 'assets.view'],
        ['route' => 'goc.handover', 'label' => 'Serah Terima', 'can' => 'handover.manage'],
        ['route' => 'goc.reports', 'label' => 'Laporan', 'can' => 'reports.view'],
        ['route' => 'goc.users', 'label' => 'Pengguna', 'can' => 'users.manage'],
        ['route' => 'goc.audit', 'label' => 'Audit Trail', 'can' => 'audit.view'],
    ];
    $soon = [];
@endphp

{{-- Overlay mobile --}}
<div x-show="open" x-cloak class="fixed inset-0 z-30 bg-black/70 lg:hidden" @click="open = false"></div>

{{-- Sidebar --}}
<aside class="fixed inset-y-0 left-0 z-40 w-64 transform border-r border-slate-800 bg-slate-900 transition-transform lg:translate-x-0"
       :class="open ? 'translate-x-0' : '-translate-x-full'">
    <div class="flex h-16 items-center gap-3 border-b border-slate-800 px-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500 text-sm font-black text-slate-950">G</div>
        <div class="leading-tight">
            <div class="text-sm font-bold tracking-wide text-white">GARUDA</div>
            <div class="text-[11px] uppercase tracking-widest text-slate-400">Operations Center</div>
        </div>
    </div>

    <nav class="space-y-1 p-3">
        @foreach ($nav as $item)
            @can($item['can'])
                <a href="{{ route($item['route']) }}" wire:navigate
                   class="flex items-center rounded-lg border-l-2 px-3 py-2 text-sm font-medium
                   {{ request()->routeIs($item['route'] . '*') ? 'border-amber-500 bg-slate-800 text-white' : 'border-transparent text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endcan
        @endforeach

        @if (count($soon))
        <div class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-widest text-slate-600">Segera hadir</div>
        @endif
        @foreach ($soon as $label)
            <div class="flex cursor-not-allowed items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-600">
                {{ $label }}
            </div>
        @endforeach
    </nav>
</aside>

{{-- Konten --}}
<div class="lg:pl-64">
    <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-800 bg-slate-950/90 px-4 backdrop-blur sm:px-6">
        <div class="flex items-center gap-3">
            <button class="rounded-md p-2 text-slate-400 hover:bg-slate-800 lg:hidden" @click="open = true" aria-label="Buka menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="text-sm font-semibold text-white">{{ $title ?? 'Dashboard' }}</span>
        </div>

        <div class="flex items-center gap-4">
            <div class="hidden text-right leading-tight sm:block">
                <div class="text-sm font-medium text-white">{{ auth()->user()->name }}</div>
                <div class="text-xs uppercase tracking-wide text-slate-500">{{ auth()->user()->getRoleNames()->first() }}</div>
            </div>
            <a href="{{ route('goc.security') }}" class="text-sm text-slate-400 hover:text-white">Keamanan</a>
            @if (Route::has('profile'))
                <a href="{{ route('profile') }}" class="text-sm text-slate-400 hover:text-white">Profil</a>
            @endif
            <form method="POST" action="{{ route('goc.logout') }}">
                @csrf
                <button class="rounded-md border border-slate-700 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800">Keluar</button>
            </form>
        </div>
    </header>

    <main class="p-4 sm:p-6">
        @if (! auth()->user()->two_factor_confirmed_at && ! request()->routeIs('goc.security'))
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">
                <span>Akun Anda belum dilindungi autentikasi dua faktor (2FA).</span>
                <a href="{{ route('goc.security') }}" class="font-semibold underline hover:text-amber-200">Aktifkan sekarang</a>
            </div>
        @endif
        {{ $slot }}
    </main>
</div>
</body>
</html>
