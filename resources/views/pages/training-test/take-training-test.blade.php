<div x-data="{ showConfirm: false }">
    {{-- Header --}}
    <div class="w-full flex items-center gap-3 mb-6">
        <a href="{{ route('training-test.index') }}" wire:navigate class="btn btn-ghost btn-sm btn-circle">
            <x-icon name="o-arrow-left" class="size-5" />
        </a>
        <div class="flex-1">
            <h1 class="text-primary text-2xl font-bold">
                {{ ucfirst($testType) }} - {{ $training->name }}
            </h1>
            <p class="text-base-content/60 text-sm mt-0.5">
                {{ $training->module?->title ?? 'Training Module' }}
                <span class="mx-2">•</span>
                <span class="badge badge-sm badge-primary badge-outline">{{ count($questions) }} Questions</span>
                <span class="mx-2">•</span>
                <span class="text-xs">Passing Score: {{ $test->passing_score ?? 75 }}%</span>
            </p>
        </div>
    </div>

    {{-- Test Info Card --}}
    <div class="bg-gradient-to-r from-primary/5 to-primary/10 rounded-xl p-4 mb-6 border border-primary/20">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <div class="flex items-center gap-2">
                <x-icon name="o-clock" class="size-4 text-primary" />
                <span>
                    @if ($test->max_attempts)
                        Max Attempts: {{ $test->max_attempts }}
                    @else
                        Unlimited Attempts
                    @endif
                </span>
            </div>
            <div class="flex items-center gap-2">
                <x-icon name="o-document-check" class="size-4 text-primary" />
                <span>Answer all questions to submit</span>
            </div>
            @if ($test->randomize_question)
                <div class="flex items-center gap-2">
                    <x-icon name="o-arrow-path-rounded-square" class="size-4 text-primary" />
                    <span>Questions are randomized</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Questions Form --}}
    <form class="space-y-4 md:space-y-5">
        @forelse ($questions as $index => $q)
            <fieldset
                class="relative rounded-xl border bg-base-100 p-4 md:p-5 shadow-sm transition-all duration-200
                {{ in_array($index, $errorQuestionIndexes ?? []) ? 'border-red-400 ring-2 ring-red-200' : 'border-base-200 hover:border-primary/30' }}">
                <legend class="sr-only">Question {{ $index + 1 }}</legend>

                {{-- Question Number Badge --}}
                <div
                    class="absolute top-4 left-4 md:top-5 md:left-5 inline-flex items-center justify-center rounded-lg bg-primary text-white text-xs font-bold px-2.5 py-1 min-w-[32px]">
                    {{ $index + 1 }}
                </div>

                {{-- Question Text --}}
                <div class="pl-12 mb-4">
                    <p class="text-base font-medium text-base-content leading-relaxed">
                        {{ $q['text'] }}
                    </p>
                </div>

                {{-- Options or Essay --}}
                @if ($q['type'] === 'multiple')
                    <div class="space-y-2 pl-12">
                        @foreach ($q['options'] as $optIndex => $option)
                            <label
                                class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-all duration-200
                                {{ ($answers[$q['id']] ?? null) == $option['id'] ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'border-base-200 hover:bg-base-200/50 hover:border-primary/30' }}">
                                <input type="radio" name="question_{{ $q['id'] }}" value="{{ $option['id'] }}"
                                    class="radio radio-sm radio-primary" wire:model="answers.{{ $q['id'] }}" />
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-base-200 text-xs font-semibold text-base-content/70 shrink-0">
                                    {{ chr(65 + $optIndex) }}
                                </span>
                                <span class="text-sm text-base-content flex-1">{{ $option['text'] }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="pl-12">
                        <textarea name="question_{{ $q['id'] }}" rows="4"
                            class="w-full border border-base-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none p-3 text-sm resize-none"
                            placeholder="Type your answer here..." wire:model="answers.{{ $q['id'] }}"></textarea>
                    </div>
                @endif

                {{-- Error indicator --}}
                @if (in_array($index, $errorQuestionIndexes ?? []))
                    <div class="pl-12 mt-2">
                        <p class="text-xs text-red-500 flex items-center gap-1">
                            <x-icon name="o-exclamation-circle" class="size-4" />
                            Please answer this question
                        </p>
                    </div>
                @endif
            </fieldset>
        @empty
            <div class="p-8 border border-dashed rounded-xl text-center text-base-content/60 bg-base-100">
                <x-icon name="o-document-text" class="size-12 mx-auto mb-3 text-base-content/30" />
                <p>No questions configured for this test.</p>
            </div>
        @endforelse
    </form>

    {{-- Submit Button --}}
    @if (count($questions) > 0)
        <div
            class="sticky bottom-0 bg-gradient-to-t from-base-100 via-base-100 to-transparent pt-6 pb-4 mt-6 -mx-4 px-4">
            <div
                class="flex items-center justify-between gap-4 p-4 bg-base-100 rounded-xl border border-base-200 shadow-lg">
                <div class="text-sm text-base-content/70">
                    <span
                        class="font-medium">{{ count(array_filter($answers, fn($a) => $a !== null && $a !== '')) }}</span>
                    of <span class="font-medium">{{ count($questions) }}</span> questions answered
                </div>
                <x-ui.button @click.prevent="showConfirm = true" variant="primary" class="px-6">
                    <x-icon name="o-paper-airplane" class="size-4" />
                    Submit Test
                </x-ui.button>
            </div>
        </div>
    @endif

    {{-- Confirmation Modal --}}
    <div x-show="showConfirm" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="bg-base-100 rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" @click.away="showConfirm = false">
            <div class="text-center mb-4">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-primary/10 flex items-center justify-center">
                    <x-icon name="o-clipboard-document-check" class="size-8 text-primary" />
                </div>
                <h2 class="text-xl font-bold text-base-content">Submit {{ ucfirst($testType) }}?</h2>
                <p class="mt-2 text-sm text-base-content/60">
                    Are you sure you want to submit your answers? You won't be able to change them after submission.
                </p>
            </div>

            <div class="bg-base-200/50 rounded-lg p-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-base-content/70">Questions answered:</span>
                    <span class="font-semibold">{{ count(array_filter($answers, fn($a) => $a !== null && $a !== '')) }}
                        / {{ count($questions) }}</span>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" class="flex-1 btn btn-ghost" @click="showConfirm = false">
                    Cancel
                </button>
                <button type="button" class="flex-1 btn btn-primary" @click="showConfirm = false; $wire.submit()">
                    Yes, Submit
                </button>
            </div>
        </div>
    </div>

    <x-loading-overlay text="Submitting test..." target="submit" />
</div>
