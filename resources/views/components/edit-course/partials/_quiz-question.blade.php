@php($qKey = 't' . $ti . '-s' . $si . '-q' . $qi)
<div class="relative rounded-md p-3 pr-10 bg-base-100/70 ring-1 question-card transition {{ in_array($qKey, $errorQuestionKeys ?? []) ? 'ring-error/70 border border-error/60 bg-error/5' : 'ring-base-300/40' }}"
    wire:key="sec-q-{{ $section['id'] }}-{{ $qq['id'] }}">
    @if (!in_array($qKey, $errorQuestionKeys ?? []))
        <span class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-info/60 to-info/10"></span>
    @endif
    <div class="flex items-center gap-3">
        <span
            class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-info/10 text-info text-xs font-semibold">{{ $loop->iteration }}</span>
        <x-select :options="[['value' => 'multiple', 'label' => 'Multiple'], ['value' => 'essay', 'label' => 'Essay']]" option-value="value" option-label="label" class="w-40"
            wire:model="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.type"
            wire:change="$refresh" />
        <div class="flex-1 relative">
            <x-input class="w-full pr-10 focus-within:border-0" placeholder="Write the question"
                wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.question" />
            <button type="button" title="Delete question"
                class="absolute inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent hover:bg-red-50"
                wire:click="removeSectionQuizQuestion({{ $ti }}, {{ $si }}, {{ $qi }})">
                <x-icon name="o-trash" class="size-4" />
            </button>
        </div>
    </div>

    @if (($qq['type'] ?? '') === 'multiple')
        @php($answerIndex = $qq['answer'] ?? null)
        @php($answerNonce = $qq['answer_nonce'] ?? 0)
        <div class="space-y-2">
            <div class="flex items-center gap-2 mb-1 mt-3">
                <span class="text-[10px] uppercase tracking-wide text-base-content/50">Mark Correct Answer</span>
                @if (!is_null($answerIndex) && isset($qq['options'][$answerIndex]))
                    <span class="badge badge-success badge-sm">Current: {{ chr(65 + $answerIndex) }}</span>
                    <button type="button" class="btn btn-ghost btn-xs text-error"
                        wire:click="setCorrectAnswer({{ $ti }}, {{ $si }}, {{ $qi }}, {{ $answerIndex }})">Unset</button>
                @else
                    <span class="text-[10px] text-gray-400">None selected</span>
                @endif
            </div>
            @foreach ($qq['options'] ?? [] as $oi => $opt)
                <div class="flex items-center gap-2"
                    wire:key="sec-q-{{ $qq['id'] }}-opt-{{ $oi }}-n{{ $answerNonce }}-{{ substr(md5($qq['id'] . '|' . $oi . '|' . $opt), 0, 6) }}">
                    <label class="flex items-center gap-1 cursor-pointer select-none">
                        <input type="radio" name="answer-{{ $qq['id'] }}-{{ $answerNonce }}"
                            value="{{ $oi }}" @if (!is_null($answerIndex) && $answerIndex === $oi) checked @endif
                            class="radio radio-success radio-xs"
                            wire:click="setCorrectAnswer({{ $ti }}, {{ $si }}, {{ $qi }}, {{ $oi }})" />
                        <span
                            class="text-[11px] font-medium {{ $answerIndex === $oi ? 'text-success' : 'text-gray-500' }}">
                            {{ chr(65 + $oi) }}
                        </span>
                    </label>
                    <x-input
                        class="flex-1 pr-10 focus-within:border-0 {{ $answerIndex === null && count(array_filter($qq['options'] ?? [], fn($x) => trim($x) !== '')) >= 2 && $oi === 0 && in_array($qKey, $errorQuestionKeys ?? []) ? 'border-error/60' : '' }}"
                        placeholder="Option"
                        wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.options.{{ $oi }}" />
                    <x-button icon="o-x-mark" class="btn-ghost text-error" title="Remove option"
                        wire:click="removeSectionQuizOption({{ $ti }}, {{ $si }}, {{ $qi }}, {{ $oi }})" />
                </div>
            @endforeach
            <x-button type="button" size="xs" class="border-gray-400" outline icon="o-plus"
                wire:click="addSectionQuizOption({{ $ti }}, {{ $si }}, {{ $qi }})">Add
                Option</x-button>
        </div>
    @endif
</div>
