<div>
    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-4 mb-4 items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('test-review.participants', $training) }}" wire:navigate
                class="btn btn-ghost btn-sm btn-circle">
                <x-icon name="o-arrow-left" class="size-5" />
            </a>
            <div>
                <h1 class="text-primary text-2xl font-bold">
                    Review Answers
                </h1>
                <p class="text-base-content/60 text-sm mt-0.5">
                    <span class="font-medium">{{ $participant->name }}</span>
                    <span class="mx-2">•</span>
                    {{ $training->name }}
                </p>
            </div>
        </div>
    </div>

    {{-- Divider --}}
    <div class="border-b-2 border-base-300 mb-6"></div>

    {{-- No Tests Available --}}
    @if (!$hasPretest && !$hasPosttest)
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-2">
            <div class="flex flex-col items-center justify-center py-12 px-4">
                <x-icon name="o-document-text" class="w-16 h-16 text-gray-300 mb-3" />
                <h3 class="text-base font-semibold text-gray-700 mb-1">No Test Attempts</h3>
                <p class="text-sm text-gray-500 text-center">
                    This participant has not taken any tests yet.
                </p>
            </div>
        </div>
    @else
        {{-- Test Tabs --}}
        <div role="tablist" class="tabs tabs-lifted mb-6">
            @if ($hasPretest)
                <button type="button" role="tab"
                    class="tab {{ $selectedTest === 'pretest' ? 'tab-active [--tab-bg:white] [--tab-border-color:oklch(var(--p))]' : '' }}"
                    wire:click="$set('selectedTest', 'pretest')">
                    <x-icon name="o-clipboard-document-check" class="size-4 mr-2" />
                    Pretest
                    @if ($pretestAttempt?->status === 'under_review')
                        <span class="badge badge-warning badge-xs ml-2">Review</span>
                    @endif
                </button>
            @endif
            @if ($hasPosttest)
                <button type="button" role="tab"
                    class="tab {{ $selectedTest === 'posttest' ? 'tab-active [--tab-bg:white] [--tab-border-color:oklch(var(--p))]' : '' }}"
                    wire:click="$set('selectedTest', 'posttest')">
                    <x-icon name="o-clipboard-document-list" class="size-4 mr-2" />
                    Posttest
                    @if ($posttestAttempt?->status === 'under_review')
                        <span class="badge badge-warning badge-xs ml-2">Review</span>
                    @endif
                </button>
            @endif
        </div>

        {{-- Test Summary Card --}}
        @if ($currentAttempt)
            <div class="card bg-base-100 border border-base-200 mb-6">
                <div class="card-body p-4">
                    <div class="flex flex-wrap items-center gap-6">
                        <div>
                            <p class="text-xs text-base-content/60 uppercase tracking-wide">Status</p>
                            <span
                                class="badge mt-1 {{ $currentAttempt->status === 'under_review' ? 'badge-warning' : 'badge-success' }}">
                                {{ $currentAttempt->status === 'under_review' ? 'Under Review' : 'Reviewed' }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/60 uppercase tracking-wide">Auto Score</p>
                            <p class="text-lg font-bold">{{ $currentAttempt->auto_score ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/60 uppercase tracking-wide">Manual Score</p>
                            <p class="text-lg font-bold">{{ $currentAttempt->manual_score ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/60 uppercase tracking-wide">Total Score</p>
                            <p class="text-lg font-bold text-primary">{{ $currentAttempt->total_score ?? 0 }}%</p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/60 uppercase tracking-wide">Passing Score</p>
                            <p class="text-lg font-bold">{{ $currentTest->passing_score ?? 0 }}%</p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/60 uppercase tracking-wide">Result</p>
                            <span
                                class="badge mt-1 {{ $currentAttempt->is_passed ? 'badge-success' : 'badge-error' }}">
                                {{ $currentAttempt->is_passed ? 'Passed' : 'Failed' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Questions and Answers --}}
        <div class="space-y-4">
            @foreach ($questionsWithAnswers as $index => $item)
                <div
                    class="card bg-base-100 border {{ $item['isEssay'] && $isUnderReview ? 'border-warning' : 'border-base-200' }}">
                    <div class="card-body p-4">
                        {{-- Question Header --}}
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="badge badge-outline badge-sm">Question {{ $index + 1 }}</span>
                                    <span
                                        class="badge badge-sm {{ $item['isEssay'] ? 'badge-info' : 'badge-secondary' }}">
                                        {{ $item['isEssay'] ? 'Essay' : 'Multiple Choice' }}
                                    </span>
                                    <span class="badge badge-ghost badge-sm">
                                        {{ $item['earnedPoints'] }}/{{ $item['maxPoints'] }} pts
                                    </span>
                                </div>
                                <p class="text-base font-medium">{!! $item['question']->text !!}</p>
                            </div>

                            {{-- Result Badge (for MC) --}}
                            @if (!$item['isEssay'])
                                <div>
                                    @if ($item['isCorrect'])
                                        <span class="badge badge-success">
                                            <x-icon name="o-check" class="size-3 mr-1" />
                                            Correct
                                        </span>
                                    @else
                                        <span class="badge badge-error">
                                            <x-icon name="o-x-mark" class="size-3 mr-1" />
                                            Wrong
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Answer Section --}}
                        <div class="mt-4 pt-4 border-t border-base-200">
                            @if ($item['isEssay'])
                                {{-- Essay Answer --}}
                                <div class="mb-4">
                                    <p class="text-xs text-base-content/60 uppercase tracking-wide mb-2">
                                        Participant's Answer
                                    </p>
                                    <div class="bg-base-200/50 rounded-lg p-4">
                                        <p class="whitespace-pre-wrap text-sm">
                                            {{ $item['answer']?->essay_answer ?? 'No answer provided' }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Essay Scoring --}}
                                @if ($isUnderReview)
                                    <div class="flex items-center gap-4">
                                        <label class="text-sm font-medium">Score:</label>
                                        <input type="number" class="input input-bordered input-sm w-24"
                                            wire:model="essayScores.{{ $selectedTest }}_{{ $item['question']->id }}"
                                            min="0" max="{{ $item['maxPoints'] }}" placeholder="0">
                                        <span class="text-sm text-base-content/60">
                                            / {{ $item['maxPoints'] }} pts
                                        </span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium">Score:</span>
                                        <span class="badge badge-primary">
                                            {{ $item['earnedPoints'] }}/{{ $item['maxPoints'] }} pts
                                        </span>
                                    </div>
                                @endif
                            @else
                                {{-- Multiple Choice Options --}}
                                <p class="text-xs text-base-content/60 uppercase tracking-wide mb-2">
                                    Options
                                </p>
                                <div class="space-y-2">
                                    @foreach ($item['question']->options as $option)
                                        @php
                                            $isSelected = $item['answer']?->selected_option_id === $option->id;
                                            $isCorrectOption = $option->is_correct;
                                        @endphp
                                        <div
                                            class="flex items-center gap-3 p-3 rounded-lg {{ $isCorrectOption ? 'bg-success/10 border border-success/30' : ($isSelected ? 'bg-error/10 border border-error/30' : 'bg-base-200/50') }}">
                                            <div
                                                class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center {{ $isSelected ? ($isCorrectOption ? 'border-success bg-success text-success-content' : 'border-error bg-error text-error-content') : 'border-base-300' }}">
                                                @if ($isSelected)
                                                    <x-icon name="o-check" class="size-4" />
                                                @endif
                                            </div>
                                            <span
                                                class="flex-1 {{ $isCorrectOption ? 'font-medium text-success' : '' }}">
                                                {{ $option->text }}
                                            </span>
                                            @if ($isCorrectOption)
                                                <span class="badge badge-success badge-sm">Correct Answer</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Submit Review Button --}}
        @if ($isUnderReview && $hasEssayToGrade)
            <div class="mt-6 flex justify-end">
                <button type="button" class="btn btn-primary" wire:click="submitReview"
                    wire:confirm="Are you sure you want to submit this review? This will finalize the scores.">
                    <x-icon name="o-check-circle" class="size-5" />
                    Submit Review
                </button>
            </div>
        @endif
    @endif
</div>
