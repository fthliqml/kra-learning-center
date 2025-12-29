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

            {{-- Default Template for Level --}}
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Select the default template to use for this survey level. The survey will be automatically populated
                    with questions from the selected template when training is closed.
                </p>
                <div class="flex items-center gap-4">
                    <label class="w-32 text-sm font-medium text-gray-700 flex-shrink-0">
                        Default Template
                    </label>
                    <x-select wire:model="defaultTemplateId" :options="$templateOptions" option-value="value" option-label="label"
                        placeholder="Select template..." class="flex-1" />
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4">
                <p class="text-xs text-blue-700">
                    <strong>Note:</strong> When a training is closed, a survey for the selected level will be
                    automatically created using the default template. If no default is set, the survey will be created
                    without pre-populated questions.
                </p>
            </div>
        </div>

        <x-slot:actions>
            <x-button wire:click="$set('modal', false)" class="btn-ghost">Cancel</x-button>
            <x-button wire:click="save" class="btn-primary" spinner="save">
                <x-icon name="o-check" class="w-4 h-4" />
                Save Default
            </x-button>
        </x-slot:actions>
    </x-modal>
</div>
