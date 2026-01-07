<div x-data="{
    showConfirm: false,
    answers: @entangle('answers'),
    get answeredCount() {
        return Object.values(this.answers).filter(a => a !== null && a !== '').length;
    }
}">
    {{-- Header --}}
    <div class="w-full flex items-center gap-3 mb-4">
        <a href="{{ route('training-test.index') }}" wire:navigate class="btn btn-ghost btn-xs btn-circle">
            <x-icon name="o-arrow-left" class="size-4" />
        </a>
        <div class="flex-1">
            <h1 class="text-primary text-xl font-bold">
                {{ ucfirst($testType) }} - {{ $training->name }}
            </h1>
            <p class="text-base-content/60 text-xs mt-0.5">
                {{ $training->module?->title ?? 'Training Module' }}
                <span class="mx-1.5">•</span>
                <span class="badge badge-xs badge-primary badge-outline">{{ count($questions) }} Questions</span>
                <span class="mx-1.5">•</span>
                <span class="text-[10px]">Passing Score: {{ $test->passing_score ?? 75 }}%</span>
            </p>
        </div>
    </div>

    {{-- Divider --}}
    <div class="border-b-2 border-base-300 mb-4"></div>

    {{-- Test Info Card --}}
    <div class="bg-gradient-to-r from-primary/5 to-primary/10 rounded-lg p-3 mb-4 border border-primary/20">
        <div class="flex flex-wrap items-center gap-4 text-xs">
            <div class="flex items-center gap-1.5">
                <x-icon name="o-clock" class="size-3.5 text-primary" />
                <span>
                    @if ($test->max_attempts)
                        Max Attempts: {{ $test->max_attempts }}
                    @else
                        Unlimited Attempts
                    @endif
                </span>
            </div>
            <div class="flex items-center gap-1.5">
                <x-icon name="o-document-check" class="size-3.5 text-primary" />
                <span>Answer all questions to submit</span>
            </div>
            @if ($test->randomize_question)
                <div class="flex items-center gap-1.5">
                    <x-icon name="o-arrow-path-rounded-square" class="size-3.5 text-primary" />
                    <span>Questions are randomized</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Questions Form --}}
    <form class="space-y-3">
        @forelse ($questions as $index => $q)
            <fieldset
                class="relative rounded-lg border bg-base-100 p-3 md:p-4 shadow-sm transition-all duration-200
                {{ in_array($index, $errorQuestionIndexes ?? []) ? 'border-red-400 ring-2 ring-red-200' : 'border-gray-300 hover:border-primary/40' }}">
                <legend class="sr-only">Question {{ $index + 1 }}</legend>

                {{-- Question Number Badge --}}
                <div
                    class="absolute top-3 left-3 md:top-4 md:left-4 inline-flex items-center justify-center rounded-md bg-primary text-white text-[10px] font-bold px-2 py-0.5 min-w-[24px]">
                    {{ $index + 1 }}
                </div>

                {{-- Question Text --}}
                <div class="pl-10 mb-3">
                    <p class="text-sm font-medium text-base-content leading-relaxed">
                        {{ $q['text'] }}
                    </p>
                </div>

                {{-- Options or Essay --}}
                @if ($q['type'] === 'multiple')
                    <div class="space-y-1.5 pl-10">
                        @foreach ($q['options'] as $optIndex => $option)
                            <label
                                class="flex items-center gap-2.5 p-2.5 border rounded-md cursor-pointer transition-all duration-200
                                {{ ($answers[$q['id']] ?? null) == $option['id'] ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'border-gray-300 hover:bg-base-200/50 hover:border-primary/40' }}">
                                <input type="radio" name="question_{{ $q['id'] }}" value="{{ $option['id'] }}"
                                    class="radio radio-xs radio-primary"
                                    wire:model.live="answers.{{ $q['id'] }}" />
                                <span
                                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-base-200 text-[10px] font-semibold text-base-content/70 shrink-0">
                                    {{ chr(65 + $optIndex) }}
                                </span>
                                <span class="text-xs text-base-content flex-1">{{ $option['text'] }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="pl-10">
                        <textarea name="question_{{ $q['id'] }}" rows="3"
                            class="w-full border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary outline-none p-2.5 text-xs resize-none"
                            placeholder="Type your answer here..." wire:model.live="answers.{{ $q['id'] }}"></textarea>
                    </div>
                @endif

                {{-- Error indicator --}}
                @if (in_array($index, $errorQuestionIndexes ?? []))
                    <div class="pl-10 mt-1.5">
                        <p class="text-[10px] text-red-500 flex items-center gap-1">
                            <x-icon name="o-exclamation-circle" class="size-3" />
                            Please answer this question
                        </p>
                    </div>
                @endif
            </fieldset>
        @empty
            <div class="p-6 border border-dashed rounded-lg text-center text-base-content/60 bg-base-100">
                <x-icon name="o-document-text" class="size-10 mx-auto mb-2 text-base-content/30" />
                <p class="text-sm">No questions configured for this test.</p>
            </div>
        @endforelse
    </form>

    {{-- Submit Button --}}
    @if (count($questions) > 0)
        <div
            class="sticky bottom-0 bg-gradient-to-t from-base-100 via-base-100 to-transparent pt-4 pb-3 mt-4 -mx-3 px-3">
            <div
                class="flex items-center justify-between gap-3 p-3 bg-base-100 rounded-lg border border-base-200 shadow-md">
                <div class="text-xs text-base-content/70">
                    <span class="font-semibold" x-text="answeredCount">0</span>
                    of <span class="font-semibold">{{ count($questions) }}</span> questions answered
                </div>
                <x-ui.button @click.prevent="showConfirm = true" variant="primary" class="btn-sm px-4">
                    <x-icon name="o-paper-airplane" class="size-3.5" />
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
        <div class="bg-base-100 rounded-xl shadow-2xl p-5 w-full max-w-sm mx-4"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" @click.away="showConfirm = false">
            <div class="text-center mb-3">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-primary/10 flex items-center justify-center">
                    <x-icon name="o-clipboard-document-check" class="size-6 text-primary" />
                </div>
                <h2 class="text-lg font-bold text-base-content">Submit {{ ucfirst($testType) }}?</h2>
                <p class="mt-1.5 text-xs text-base-content/60">
                    Are you sure you want to submit? You won't be able to change answers after submission.
                </p>
            </div>

            <div class="bg-base-200/50 rounded-md p-2.5 mb-3">
                <div class="flex justify-between text-xs">
                    <span class="text-base-content/70">Questions answered:</span>
                    <span class="font-semibold">
                        <span x-text="answeredCount">0</span> / {{ count($questions) }}
                    </span>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" class="flex-1 btn btn-sm btn-ghost" @click="showConfirm = false">
                    Cancel
                </button>
                <button type="button" class="flex-1 btn btn-sm btn-primary"
                    @click="showConfirm = false; $wire.submit()">
                    Yes, Submit
                </button>
            </div>
        </div>
    </div>

    <x-loading-overlay text="Submitting test..." target="submit" />
</div>
