<div class="space-y-6">
    <x-form wire:submit.prevent="saveDraft" no-separator>
        {{-- Title --}}
        <x-input label="Title*" placeholder="Title of the training module..." wire:model.defer="formData.title"
            class="focus-within:border-0" :error="$errors->first('formData.title')" />

        {{-- Competency --}}
        <x-choices label="Competency*" wire:model.defer="formData.competency_id" :options="$competencyOptions" option-value="value"
            option-label="label" placeholder="Select Competency" :error="$errors->first('formData.competency_id')" single searchable
            search-function="competencySearch" class="focus-within:border-0" />

        {{-- Objective --}}
        <x-textarea label="Objective*" placeholder="Describe the training objectives..." class="focus-within:border-0"
            wire:model.defer="formData.objective" :error="$errors->first('formData.objective')" rows="3" />

        {{-- Training Content --}}
        <x-textarea label="Training Content*" placeholder="Outline the main topics..." class="focus-within:border-0"
            wire:model.defer="formData.training_content" :error="$errors->first('formData.training_content')" rows="3" />

        {{-- Method --}}
        <x-input label="Method*" placeholder="Describe the development concept..." wire:model.defer="formData.method"
            class="focus-within:border-0" :error="$errors->first('formData.method')" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Duration --}}
            <x-input label="Duration (Hours)*" type="number" wire:model.defer="formData.duration" placeholder="6"
                class="focus-within:border-0" min="1" step="0.5" :error="$errors->first('formData.duration')" />

            {{-- Frequency --}}
            <x-input label="Frequency (Days)*" type="number" wire:model.defer="formData.frequency" placeholder="15"
                class="focus-within:border-0" min="1" :error="$errors->first('formData.frequency')" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Theory Passing Score --}}
            <x-input label="Theory Passing Score (%)*" type="number" wire:model.defer="formData.theory_passing_score"
                placeholder="75" class="focus-within:border-0" min="0" max="100" step="0.01"
                :error="$errors->first('formData.theory_passing_score')" />

            {{-- Practical Passing Grade --}}
            <x-choices label="Practical Passing Grade*" wire:model.defer="formData.practical_passing_score"
                :options="$practicalGradeOptions" option-value="value" option-label="label" placeholder="Select grade (A-E)"
                class="focus-within:border-0" :error="$errors->first('formData.practical_passing_score')" single />
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-base-300/50 mt-4">
            <div class="flex items-center gap-3">
                <x-ui.button type="submit" variant="secondary" class="border-gray-300" wire:loading.attr="disabled"
                    wire:target="saveDraft" spinner="saveDraft">
                    <x-icon name="o-bookmark" class="size-4" />
                    <span>Save Draft</span>
                </x-ui.button>
                <x-ui.save-draft-status :dirty="$isDirty ?? false" :ever="$hasEverSaved ?? false" :persisted="$persisted ?? false" />
            </div>
            <div class="flex gap-2 ml-auto">
                <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goBack">
                    <x-icon name="o-arrow-left" class="size-4" />
                    <span>Back</span>
                </x-ui.button>
                <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNext">
                    <span>Next</span>
                    <x-icon name="o-arrow-right" class="size-4" />
                </x-ui.button>
            </div>
        </div>
    </x-form>
</div>
