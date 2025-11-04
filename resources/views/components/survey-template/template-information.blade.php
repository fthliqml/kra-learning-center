<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4">
        <x-input label="Title" placeholder="Template title" class="focus-within:border-0"
            wire:model.defer="templateTitle" />
        <x-textarea label="Description" placeholder="Describe this template" class="focus-within:border-0" rows="4"
            wire:model.defer="templateDescription" />
        <x-select label="Level" :options="[
            ['value' => 1, 'label' => 'Level 1'],
            ['value' => 2, 'label' => 'Level 2'],
            ['value' => 3, 'label' => 'Level 3'],
        ]" option-value="value" option-label="label" class="w-52"
            wire:model.defer="templateLevel" />
    </div>
    <div class="flex justify-end">
        <x-ui.button type="button" variant="primary" wire:click="saveTemplateInfo" wire:loading.attr="disabled"
            wire:target="saveTemplateInfo">
            <x-icon name="o-bookmark" />
            <span wire:loading.remove wire:target="saveTemplateInfo">Save</span>
            <span wire:loading wire:target="saveTemplateInfo">Saving...</span>
        </x-ui.button>
    </div>

    <x-loading-overlay text="Saving template information..." target="saveTemplateInfo" />
</div>
