<div class="space-y-4" x-data>
    @livewire('components.confirm-dialog')

    <div id="survey-list" class="space-y-4" x-init="(() => {
        const list = $el;
        new Sortable(list, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: () => {
                const ids = [...list.querySelectorAll('.question-card')].map(el => el.dataset.id);
                $wire.reorderByIds(ids);
            }
        });
    })()">
        @forelse ($questions as $i => $q)
            <div class="border rounded-xl p-3 pr-7 bg-base-100 question-card relative @if (in_array($i, $errorQuestionIndexes ?? [])) border-red-400 ring-1 ring-red-300 @endif"
                data-id="{{ $q['id'] }}" wire:key="survey-q-{{ $q['id'] }}">
                <button type="button" data-tip="drag"
                    class="drag-handle tooltip absolute top-1/2 -translate-y-1/2 right-1 cursor-grab active:cursor-grabbing text-gray-400 hover:text-primary z-10"
                    title="Drag to reorder">
                    <x-icon name="o-bars-3" class="size-4" />
                </button>
                <div class="flex items-center gap-3 mb-3">
                    <span
                        class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ $loop->iteration }}</span>
                    <x-select :options="[
                        ['value' => 'multiple', 'label' => 'Multiple Choice'],
                        ['value' => 'essay', 'label' => 'Essay'],
                    ]" option-value="value" option-label="label" class="w-52"
                        wire:model="questions.{{ $i }}.question_type" wire:change="$refresh" />
                    <div class="flex-1 relative">
                        <x-input class="w-full pr-10 focus-within:border-0" placeholder="Write the survey question"
                            wire:model.defer="questions.{{ $i }}.text" />
                        <button type="button" title="Remove question"
                            class="absolute cursor-pointer inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent hover:bg-red-50"
                            wire:click="removeQuestion({{ $i }})">
                            <x-icon name="o-trash" class="size-4" />
                        </button>
                    </div>
                </div>
                @if (($q['question_type'] ?? '') === 'multiple')
                    <div class="space-y-2">
                        @foreach ($q['options'] ?? [] as $oi => $opt)
                            <div class="flex items-center gap-1.5"
                                wire:key="survey-q-{{ $q['id'] }}-opt-{{ $oi }}-{{ substr(md5($q['id'] . '|' . $oi . '|' . $opt), 0, 6) }}">
                                <span class="text-[11px] font-bold w-3 text-gray-500">
                                    {{ chr(65 + $oi) }}
                                </span>
                                <div class="flex-1 relative">
                                    <x-input class="w-full pr-10 focus-within:border-0"
                                        placeholder="Enter answer option..."
                                        wire:model.defer="questions.{{ $i }}.options.{{ $oi }}" />
                                    <button type="button" title="Remove option"
                                        class="absolute inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent hover:bg-red-50"
                                        wire:click="removeOption({{ $i }}, {{ $oi }})">
                                        <x-icon name="o-x-mark" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        <x-button type="button" size="xs" class="border-gray-400" outline icon="o-plus"
                            wire:click="addOption({{ $i }})">Add Option</x-button>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed p-8 text-center text-sm text-gray-500 bg-white/50">
                No survey questions yet. Click <span class="font-medium">Add Question</span> to create the first
                one.
            </div>
        @endforelse
    </div>

    <x-button type="button" variant="primary" outline icon="o-plus" wire:click="addQuestion" class="border-gray-400">
        Add Question
    </x-button>

    <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-base-300/50 mt-4">
        <div class="flex items-center gap-3 mt-5">
            <x-ui.button type="button" variant="secondary" class="border-gray-300" wire:click="saveDraft"
                wire:loading.attr="disabled" wire:target="saveDraft" spinner="saveDraft">
                <x-icon name="o-bookmark" class="size-4" />
                <span>Save Draft</span>
            </x-ui.button>
            <x-ui.save-draft-status :dirty="$isDirty ?? false" :ever="$hasEverSaved ?? false" :persisted="$persisted ?? false" />
        </div>

        <div class="flex gap-2 ml-auto mt-5">
            <x-button type="button" icon="o-arrow-down-tray" class="border-gray-400"
                @click="window.dispatchEvent(new CustomEvent('open-import-template-modal'))">
                Import Questions
            </x-button>
            <x-button type="button" icon="o-trash" class="border-gray-400 btn btn-error" spinner
                wire:click="$dispatch('confirm', {
                    title: 'Are you sure you want to clear all?',
                    text: 'This will remove all survey questions, but the changes won’t be saved until you click the Save Draft button.',
                    action: 'clearQuestions',
                })">
                Clear
            </x-button>
            <x-button type="button" icon="o-check" class="border-gray-400 btn btn-primary" spinner wire:click="save"
                wire:loading.attr="disabled" wire:target="save">
                Save
            </x-button>
        </div>
    </div>

    <x-loading-overlay text="Saving survey questions..." target="saveDraft" />
    <x-loading-overlay text="Saving survey questions..." target="save" />
    <x-loading-overlay text="Clearing questions..." target="clearQuestions" />

    <!-- Import From Template Modal -->
    <div x-data="{ show: false }" x-on:open-import-template-modal.window="show = true"
        x-on:close-import-template-modal.window="show = false" x-show="show" style="display:none"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
            <button class="absolute top-2 right-2 text-gray-400 hover:text-primary" @click="show = false">
                <x-icon name="o-x-mark" class="size-5" />
            </button>
            <h2 class="text-lg font-bold mb-4">Import Questions from Template</h2>
            <div class="space-y-4">
                <x-select wire:model="selectedTemplateId" :options="$templateOptions" option-value="id" option-label="title"
                    placeholder="Select an active template for this level" class="w-full" />

                <div class="flex justify-end gap-2">
                    <x-button type="button" class="btn btn-ghost border-gray-400"
                        @click="show = false">Cancel</x-button>
                    <x-ui.button type="button" variant="primary" wire:click="importFromTemplate"
                        wire:loading.attr="disabled" wire:target="importFromTemplate">
                        <span wire:loading.remove wire:target="importFromTemplate"
                            class="inline-flex items-center gap-2">
                            <x-icon name="o-arrow-down-tray" class="size-4" />
                            Import
                        </span>
                        <span wire:loading wire:target="importFromTemplate" class="inline-flex items-center gap-2">
                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                            Importing...
                        </span>
                    </x-ui.button>
                </div>
                <p class="text-xs text-gray-500">Imported questions will replace existing ones.</p>
            </div>
        </div>
    </div>
</div>
