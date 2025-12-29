<div>
    <x-modal wire:model="modal" title="Default Survey Templates" subtitle="Set default templates for each competency group"
        separator box-class="w-full max-w-2xl">

        <div class="space-y-5">
            {{-- Level Selector --}}
            <div>
                <label class="text-sm font-medium text-gray-700 mb-2 block">Survey Level</label>
                <x-select wire:model.live="level" :options="$levelOptions" option-value="value" option-label="label"
                    class="w-full" />
            </div>

            <x-hr class="my-4" />

            {{-- Group Comp → Template Mapping --}}
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Select the default template to use when a training is closed for each competency group.
                    The survey will be automatically populated with questions from the selected template.
                </p>

                @foreach ($groupCompOptions as $option)
                    <div class="flex items-center gap-4">
                        <label class="w-24 text-sm font-medium text-gray-700 flex-shrink-0">
                            {{ $option['value'] }}
                        </label>
                        <x-select wire:model="defaults.{{ $option['value'] }}" :options="$templateOptions" option-value="value"
                            option-label="label" placeholder="Select template..." class="flex-1" />
                    </div>
                @endforeach
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4">
                <p class="text-xs text-blue-700">
                    <strong>Note:</strong> When a training is closed, a Level 1 survey will be automatically created
                    using the default template for the training's competency group. If no default is set, the survey
                    will be created without pre-populated questions.
                </p>
            </div>
        </div>

        <x-slot:actions>
            <x-button wire:click="$set('modal', false)" class="btn-ghost">Cancel</x-button>
            <x-button wire:click="save" class="btn-primary" spinner="save">
                <x-icon name="o-check" class="w-4 h-4" />
                Save Defaults
            </x-button>
        </x-slot:actions>
    </x-modal>
</div>
