<x-modal wire:model="show" :title="$title">
    <p class="text-gray-700 mb-4">{{ $text }}</p>

    <x-slot:actions>
        <x-ui.button wire:click="cancel" type="button">
            Batal
        </x-ui.button>

        <x-ui.button variant="primary" wire:click="proceed" spinner>
            Ya, lanjutkan
        </x-ui.button>
    </x-slot:actions>
</x-modal>
