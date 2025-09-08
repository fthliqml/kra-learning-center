@props([
    'variant' => 'default',
    'size' => 'default',
    'href' => null,
])

@php
    $variants = [
        'default' => 'bg-primary text-primary-foreground shadow-xs hover:opacity-90 cursor-pointer',
        'primary' =>
            'bg-gradient-to-r from-primary to-secondary text-primary-foreground shadow-xs hover:opacity-90 cursor-pointer',
    ];

    $sizes = [
        'default' => 'h-9 px-4 py-2',
        'sm' => 'h-8 rounded-md px-3',
        'lg' => 'h-10 rounded-md px-6',
        'xl' => 'h-11 rounded-lg px-8',
        'icon' => 'size-9',
    ];

    $baseClasses =
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:ring-2';

    $variantClass = $variants[$variant] ?? $variants['default'];
    $sizeClass = $sizes[$size] ?? $sizes['default'];
    $classes = $baseClasses . ' ' . $variantClass . ' ' . $sizeClass;
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $classes }}" {{ $attributes }}>
        {{ $slot }}
    </a>
@else
    <button class="{{ $classes }}" {{ $attributes }}>
        {{ $slot }}
    </button>
@endif
