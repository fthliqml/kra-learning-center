<x-modal wire:model="open" title="Filter Trainings" separator box-class="max-w-md">
    <div class="space-y-6">
        <div class="space-y-5">
            <x-select label="Trainer" :options="$trainerOptions" placeholder="Select Trainer" option-label="label"
                option-value="value" wire:model="trainerId" />
            <x-select label="Training Type" :options="[
                ['label' => 'K-LEARN', 'value' => 'K-LEARN'],
                ['label' => 'IN', 'value' => 'IN'],
                ['label' => 'OUT', 'value' => 'OUT'],
            ]" placeholder="Select Type" option-label="label"
                option-value="value" wire:model="type" />
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <button type="button" wire:click="resetFilters"
                class="text-sm text-gray-500 hover:text-gray-700 underline">Reset</button>
            <div class="flex gap-2">
                <x-button flat label="Cancel" wire:click="closeModal" />
                <x-button primary label="Apply" wire:click="apply" />
            </div>
        </div>
    </div>
</x-modal>
