<x-modal wire:model="show" :title="$title" wrapper-class="z-[9998]" box-class="z-[9999]">
    <p class="text-gray-700 mb-4">{{ $text }}</p>

    <x-slot:actions>
        <x-ui.button wire:click="cancel" type="button" :disabled="$processing">
            Cancel
        </x-ui.button>

        <x-ui.button variant="primary" wire:click="proceed" :disabled="$processing">
            <span class="inline-flex items-center gap-2" wire:loading.remove wire:target="proceed">
                Yes, continue
            </span>
            <span class="inline-flex items-center gap-2" wire:loading wire:target="proceed">
                <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                Processing...
            </span>
        </x-ui.button>
    </x-slot:actions>
</x-modal>
