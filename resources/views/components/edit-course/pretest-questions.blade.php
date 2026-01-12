<div class="space-y-4" x-data>
    {{-- Test Configuration --}}
    <div class="bg-base-200/50 rounded-xl p-4 mb-4">
        <h3 class="text-sm font-semibold text-base-content/70 mb-3 flex items-center gap-2">
            <x-icon name="o-cog-6-tooth" class="size-4" />
            Test Configuration
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-input label="Passing Score (%)" type="number" wire:model.live="passingScore" min="0" max="100"
                class="focus-within:border-0" />

            <x-input label="Max Attempts" type="number" wire:model.live="maxAttempts" placeholder="Unlimited"
                min="1" class="focus-within:border-0" />

            <div class="flex items-center gap-3 pt-9">
                <input type="checkbox" class="toggle toggle-sm" wire:model.live="randomizeQuestion" />
                <span class="text-xs font-medium">Randomize</span>
            </div>
        </div>

        {{-- Points Distribution Info --}}
        @if (!empty($questions))
            <div class="mt-4 pt-3 border-t border-base-300/50">
                <h4 class="text-xs font-semibold text-base-content/60 mb-2 flex items-center gap-1.5">
                    <x-icon name="o-calculator" class="size-3.5" />
                    Points Distribution (Total: {{ $pointsInfo['total'] ?? 100 }} points)
                </h4>
                <div class="flex flex-wrap gap-4 text-sm">
                    @if (($pointsInfo['essayCount'] ?? 0) > 0)
                        <div class="flex items-center gap-2">
                            <span class="badge badge-warning badge-sm">Essay</span>
                            <span>{{ $pointsInfo['essayCount'] }} questions × custom =
                                <strong
                                    class="{{ $pointsInfo['isOverLimit'] ?? false ? 'text-error' : '' }}">{{ $pointsInfo['essayTotal'] ?? 0 }}</strong>
                                pts</span>
                        </div>
                    @endif
                    @if (($pointsInfo['mcCount'] ?? 0) > 0)
                        <div class="flex items-center gap-2">
                            <span class="badge badge-info badge-sm">Multiple Choice</span>
                            <span>{{ $pointsInfo['mcCount'] }} questions × {{ $pointsInfo['mcPointsEach'] ?? 0 }} =
                                <strong>{{ $pointsInfo['mcTotal'] ?? 0 }}</strong> pts</span>
                        </div>
                    @endif
                </div>
                @if ($pointsInfo['isOverLimit'] ?? false)
                    <p class="text-xs text-error mt-2 flex items-center gap-1">
                        <x-icon name="o-exclamation-triangle" class="size-3.5" />
                        Essay points exceed 100! Please reduce essay weights.
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Questions List --}}
    <div id="pretest-list" class="space-y-4" x-init="(() => {
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
                data-id="{{ $q['id'] }}" wire:key="pre-q-{{ $q['id'] }}">
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
                                wire:key="pre-q-{{ $q['id'] }}-opt-{{ $oi }}-n{{ $answerNonce }}-{{ substr(md5($q['id'] . '|' . $oi . '|' . $opt), 0, 6) }}">
                                <label class="flex items-center gap-1 cursor-pointer select-none shrink-0">
                                    <input type="radio" name="pre-answer-{{ $q['id'] }}-{{ $answerNonce }}"
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
                @else
                    {{-- Essay Weight Input --}}
                    <div class="mt-3 flex items-center gap-3 pl-10">
                        <div class="flex items-center gap-2">
                            <x-icon name="o-scale" class="size-4 text-warning" />
                            <span class="text-xs font-medium text-base-content/70">Weight (points):</span>
                        </div>
                        <x-input type="number" min="1" max="100"
                            wire:model.live="questions.{{ $i }}.max_points"
                            class="w-24 !h-8 text-center focus-within:border-0" placeholder="10" />
                        <span class="text-xs text-base-content/50">pts</span>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed p-8 text-center text-sm text-gray-500 bg-white/50">
                No questions yet. Click <span class="font-medium">Add Question</span> to create the first one.
            </div>
        @endforelse
    </div>

    {{-- Action buttons: Add Question and Excel Import/Export --}}
    <div class="flex items-center gap-2">
        <x-button type="button" variant="primary" outline icon="o-plus" wire:click="addQuestion"
            class="border-gray-400">Add Question</x-button>

        {{-- Excel Dropdown --}}
        <x-dropdown no-x-anchor right>
            <x-slot:trigger>
                <x-button type="button" class="btn-success h-10" wire:target="file" wire:loading.attr="disabled">
                    <span class="flex items-center gap-2" wire:loading.remove wire:target="file">
                        <x-icon name="o-clipboard-document-list" class="size-4" />
                        Excel
                    </span>
                    <span class="flex items-center gap-2" wire:loading wire:target="file">
                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                    </span>
                </x-button>
            </x-slot:trigger>

            <label class="w-full cursor-pointer relative" wire:loading.class="opacity-60 pointer-events-none"
                wire:target="file">
                <x-menu-item title="Import Questions" icon="o-arrow-up-on-square" />
                <div class="absolute right-2 top-2" wire:loading wire:target="file">
                    <x-icon name="o-arrow-path" class="size-4 animate-spin text-gray-500" />
                </div>
                <input type="file" wire:model="file" accept=".xlsx,.xls" class="hidden" />
            </label>

            <x-menu-item title="Download Template" icon="o-document-arrow-down" wire:click.stop="downloadTemplate"
                spinner="downloadTemplate" />
        </x-dropdown>
    </div>

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
