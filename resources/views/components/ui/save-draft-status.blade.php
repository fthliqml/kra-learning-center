@props([
    'action' => 'saveDraft',
    'showButton' => true,
    'label' => 'Save Draft',
    'variant' => 'secondary',
    // State flags passed from parent Livewire (avoid entangle overhead)
    'dirty' => false,
    'ever' => false,
    'persisted' => false,
])

<div class="flex items-center gap-3">
    @if ($showButton)
        <x-button type="button" :variant="$variant" class="border-gray-300" wire:click="{{ $action }}"
            wire:loading.attr="disabled" wire:target="{{ $action }}" spinner="{{ $action }}">
            <x-icon name="o-bookmark" class="size-4" />
            <span>{{ $label }}</span>
        </x-button>
    @endif

    <div class="text-xs font-medium">
        @if (!$ever && !$persisted && !$dirty)
            <span
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-base-300/60 text-base-content/70 border border-base-500">
                <x-icon name="o-information-circle" class="size-3" />
                Not saved yet
            </span>
        @elseif($dirty)
            <span
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-warning/15 text-warning border border-warning/50">
                <span class="w-2 h-2 rounded-full bg-warning animate-pulse"></span>
                Unsaved changes
            </span>
        @elseif(!$dirty && $ever && $persisted)
            <span
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-success/10 text-success border border-success/50">
                <x-icon name="o-check" class="size-3" />
                All saved
            </span>
        @endif
    </div>
</div>
