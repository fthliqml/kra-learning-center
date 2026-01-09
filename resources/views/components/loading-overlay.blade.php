@props([
    'text' => 'Loading...',
    'target' => '',
])

<div wire:loading {{ $target ? "wire:target=$target" : '' }} class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm">
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center text-white">
        <div class="loading loading-spinner loading-lg text-primary scale-125 mx-auto"></div>
        <span class="block mt-4 text-lg font-semibold tracking-wide drop-shadow-md">
            {{ $text }}
        </span>
    </div>
</div>
