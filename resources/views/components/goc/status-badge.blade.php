@props(['status'])
@php
    $cls = match ($status) {
        'new', 'offline', 'open' => 'bg-red-500/10 text-red-300 ring-red-500/30',
        'acknowledged', 'degraded', 'contained' => 'bg-amber-500/10 text-amber-300 ring-amber-500/30',
        'in_progress', 'investigating' => 'bg-sky-500/10 text-sky-300 ring-sky-500/30',
        'resolved', 'online' => 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/30',
        default => 'bg-slate-500/10 text-slate-300 ring-slate-500/30',
    };
    $labels = [
        'new' => 'Baru', 'acknowledged' => 'Diakui', 'in_progress' => 'Diproses',
        'resolved' => 'Selesai', 'false_positive' => 'False positive',
        'online' => 'Online', 'offline' => 'Offline', 'degraded' => 'Terganggu', 'unknown' => 'Tidak diketahui',
        'open' => 'Terbuka', 'investigating' => 'Investigasi', 'contained' => 'Terkendali', 'closed' => 'Ditutup',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $cls }}">{{ $labels[$status] ?? $status }}</span>
