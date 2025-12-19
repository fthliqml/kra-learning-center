<div class="space-y-4" x-data>
    <div id="posttest-list" class="space-y-4" x-init="(() => {
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
                data-id="{{ $q['id'] }}" wire:key="post-q-{{ $q['id'] }}">
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
                        wire:model="questions.{{ $i }}.type" wire:change="$refresh" />
                    <div class="flex-1 relative">
                        <x-input class="w-full pr-10 focus-within:border-0" placeholder="Write the question"
                            wire:model.defer="questions.{{ $i }}.question" />
                        <button type="button" title="Remove question"
                            class="absolute inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent hover:bg-red-50"
                            wire:click="removeQuestion({{ $i }})">
                            <x-icon name="o-trash" class="size-4" />
                        </button>
                    </div>
                </div>
                @if (($q['type'] ?? '') === 'multiple')
                    @php($answerIndex = $q['answer'] ?? null)
                    @php($answerNonce = $q['answer_nonce'] ?? 0)
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 mb-1 mt-2">
                            <span class="text-[10px] uppercase tracking-wide text-base-content/50">Mark Correct
                                Answer</span>
                            @if (!is_null($answerIndex) && isset(($q['options'] ?? [])[$answerIndex]))
                                <span class="badge badge-success badge-sm">Current: {{ chr(65 + $answerIndex) }}</span>
                                <button type="button" class="btn btn-ghost btn-xs text-error"
                                    wire:click="setCorrectAnswer({{ $i }}, {{ $answerIndex }})">Unset</button>
                            @else
                                <span class="text-[10px] text-gray-400">None selected</span>
                            @endif
                        </div>
                        @foreach ($q['options'] ?? [] as $oi => $opt)
                            <div class="flex items-center gap-1.5"
                                wire:key="post-q-{{ $q['id'] }}-opt-{{ $oi }}-n{{ $answerNonce }}-{{ substr(md5($q['id'] . '|' . $oi . '|' . $opt), 0, 6) }}">
                                <label class="flex items-center gap-1 cursor-pointer select-none shrink-0">
                                    <input type="radio" name="post-answer-{{ $q['id'] }}-{{ $answerNonce }}"
                                        value="{{ $oi }}" @if (!is_null($answerIndex) && $answerIndex === $oi) checked @endif
                                        class="radio radio-success radio-xs"
                                        wire:click="setCorrectAnswer({{ $i }}, {{ $oi }})" />
                                    <span
                                        class="text-[11px] font-bold w-3 {{ $answerIndex === $oi ? 'text-success' : 'text-gray-500' }}">
                                        {{ chr(65 + $oi) }}
                                    </span>
                                </label>
                                <div class="flex-1 relative">
                                    <x-input
                                        class="w-full pr-10 focus-within:border-0 {{ $answerIndex === null && count(array_filter($q['options'] ?? [], fn($x) => trim($x) !== '')) >= 2 && $oi === 0 && in_array($i, $errorQuestionIndexes ?? []) ? 'border-error/60' : '' }}"
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
                No questions yet. Click <span class="font-medium">Add Question</span> to create the first one.
            </div>
        @endforelse
    </div>

    <x-button type="button" variant="primary" outline icon="o-plus" wire:click="addQuestion"
        class="border-gray-400">Add Question</x-button>

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
</div>
