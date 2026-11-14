@props(['severity'])
@php
    $cls = match ($severity) {
        'critical' => 'bg-red-500/10 text-red-300 ring-red-500/30',
        'high' => 'bg-orange-500/10 text-orange-300 ring-orange-500/30',
        'medium' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-500/30',
        default => 'bg-sky-500/10 text-sky-300 ring-sky-500/30',
    };
    $label = ['critical' => 'Kritis', 'high' => 'Tinggi', 'medium' => 'Sedang', 'low' => 'Rendah'][$severity] ?? $severity;
@endphp
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $cls }}">{{ $label }}</span>
