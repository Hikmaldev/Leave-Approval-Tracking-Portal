@props([
    'variant' => 'primary',
    'size' => 'default',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
    'reason' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-md font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';

    $sizes = [
        'default' => 'h-10 px-4 text-[13px]',
        'sm' => 'h-9 px-3 text-xs',
    ];

    $variants = [
        'primary' => 'border border-brand-700 bg-brand-700 text-white hover:bg-brand-600 hover:border-brand-600',
        'secondary' => 'border border-neutral-300 bg-white text-neutral-900 hover:bg-neutral-100',
        'danger' => 'border border-red-300 bg-white text-red-700 hover:bg-red-50',
    ];

    $classes = $base.' '.($sizes[$size] ?? $sizes['default']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled)
        @if ($disabled && $reason) title="{{ $reason }}" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >{{ $slot }}</button>
@endif
