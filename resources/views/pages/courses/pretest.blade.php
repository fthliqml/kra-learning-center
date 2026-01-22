@php
    // Initialize questions collection
    $questions = $questions ?? collect();

    // Count questions
    $qCount = $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions);
    $isStatsMode = $isStatsMode ?? false;
    $canRetake = $canRetake ?? false;
    $remainingAttempts = $remainingAttempts ?? 0;
    $lastAttempt = $lastAttempt ?? null;

    $firstSectionId = null;
    try {
        $firstTopic = $course?->learningModules?->first();
        $firstSectionId = $firstTopic?->sections?->first()?->id;
    } catch (\Throwable $e) {
        $firstSectionId = null;
    }
@endphp

<div class="p-2 md:px-8 md:py-4 mx-auto max-w-5xl relative">
    {{-- Flash Message for Failed Pretest --}}
    @if (session('pretest_failed'))
        <div class="mb-4 p-4 rounded-lg bg-amber-50 border border-amber-200 flex items-start gap-3">
            <x-icon name="o-exclamation-triangle" class="size-5 text-amber-500 flex-shrink-0 mt-0.5" />
            <div>
                <p class="text-sm font-medium text-amber-800">{{ session('pretest_failed') }}</p>
                <p class="text-xs text-amber-600 mt-1">Please answer all questions correctly to proceed.</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 md:mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                Pre-Test
                @if ($isStatsMode)
                    <span class="ml-2 text-sm font-medium text-blue-700 bg-blue-100 px-2 py-1 rounded-md">Result</span>
                @endif
            </h1>
        </div>

        @if ($isStatsMode && !$canRetake)
            <div class="hidden md:flex items-center gap-2">
                <a wire:navigate
                    href="{{ route('courses-modules.index', ['course' => $course->id, 'section' => $firstSectionId]) }}"
                    class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                    <x-icon name="o-arrow-right" class="size-4" />
                    <span>Proceed to Module</span>
                </a>
            </div>
        @endif
    </div>

    @if ($isStatsMode)
        {{-- Stats Mode: Show result stats without answers --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">
            <div class="flex flex-col items-center text-center">
                {{-- Result Icon --}}
                @if ($lastAttempt?->is_passed)
                    <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mb-4">
                        <x-icon name="o-check-circle" class="size-10 text-green-600" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Congratulations! You Passed the Pre-Test</h2>
                    <p class="text-sm text-gray-600 mt-2">You have successfully completed the pre-test.</p>
                @else
                    <div class="w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                        <x-icon name="o-exclamation-triangle" class="size-10 text-amber-600" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Pre-Test Not Passed</h2>
                    <p class="text-sm text-gray-600 mt-2">
                        @if ($canRetake)
                            You can try again. Remaining attempts: <strong>{{ $remainingAttempts === -1 ? 'Unlimited' : $remainingAttempts }}</strong>
                        @else
                            Your attempts are exhausted. Please proceed to the learning modules.
                        @endif
                    </p>
                @endif

                {{-- Stats Cards --}}
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6 w-full max-w-md">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $lastAttempt?->auto_score ?? 0 }}</div>
                        <div class="text-xs text-gray-500 mt-1">Your Score</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $pretest?->passing_score ?? 0 }}%</div>
                        <div class="text-xs text-gray-500 mt-1">Passing Score</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center col-span-2 md:col-span-1">
                        <div class="text-2xl font-bold {{ $lastAttempt?->is_passed ? 'text-green-600' : 'text-amber-600' }}">
                            {{ $lastAttempt?->is_passed ? 'PASSED' : 'NOT PASSED' }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Status</div>
                    </div>
                </div>

                {{-- Attempt Info --}}
                <div class="mt-4 text-xs text-gray-500">
                    Submitted: {{ $lastAttempt?->submitted_at?->format('d M Y H:i') ?? '-' }}
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row items-center gap-3 mt-6">
                    @if ($canRetake)
                        <button type="button" wire:click="startRetake"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 transition">
                            <x-icon name="o-arrow-path" class="size-4" />
                            <span>Try Again {{ $remainingAttempts === -1 ? '(Unlimited)' : '(' . $remainingAttempts . ' remaining)' }}</span>
                        </button>
                    @endif
                    <a wire:navigate
                        href="{{ route('courses-modules.index', ['course' => $course->id, 'section' => $firstSectionId]) }}"
                        class="inline-flex items-center gap-2 rounded-md {{ $canRetake ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-primary text-white hover:bg-primary/90' }} px-5 py-2.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/40 transition">
                        <x-icon name="o-arrow-right" class="size-4" />
                        <span>Proceed to Module</span>
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- Form Mode --}}
        <div x-data="pretestForm($wire)" x-init="init()">
            {{-- Instructions --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 md:p-6 shadow-sm mb-5 md:mb-6" x-data="{ open: true }">
                <button type="button"
                    class="md:hidden inline-flex items-center gap-2 text-xs font-medium text-gray-600 transition"
                    @click="open = !open">
                    <span x-text="open ? 'Hide Instructions' : 'Show Instructions'"></span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                        viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <div class="flex items-start gap-4 mt-5 md:mt-0" x-show="open" x-transition>
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary">
                        <x-icon name="o-clipboard-document-check" class="size-5" />
                    </div>
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-gray-900">Before Starting</h2>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                            This pre-test helps us understand your starting knowledge level so that learning can be more relevant.
                            Please answer as honestly as possible. Pre-test results do not decrease your progress.
                        </p>
                        <ul class="mt-3 text-xs text-gray-500 grid gap-1 grid-cols-1">
                            <li class="inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary/60"></span>
                                Total Questions:
                                <strong>{{ $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions) }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Questions Form --}}
            <form x-ref="formEl" @submit.prevent="submit" class="space-y-4 md:space-y-5"
                x-bind:aria-busy="submitting ? 'true' : 'false'">
                @forelse ($questions as $index => $q)
                    <fieldset class="rounded-lg border border-gray-200 bg-white p-4 md:p-5 shadow-sm relative"
                        :class="errors['{{ $q['id'] }}'] ? 'border-red-300 ring-1 ring-red-200' : ''">
                        <legend class="sr-only">Question {{ $index + 1 }}</legend>
                        <div
                            class="absolute top-4 left-4 md:top-5 md:left-5 inline-flex items-center justify-center rounded-md bg-primary/10 text-primary text-[11px] font-semibold px-2 py-0.5 h-5 min-w-[28px]">
                            {{ $index + 1 }}
                        </div>
                        <div class="pl-10 pr-0 md:pr-4 mb-5">
                            <p class="text-sm font-medium text-gray-800 leading-snug">{{ $q['text'] }}</p>
                        </div>
                        @if ($q['type'] === 'essay')
                            <div class="space-y-2">
                                <textarea name="{{ $q['id'] }}" x-ref="txt_{{ $q['id'] }}" rows="4"
                                    placeholder="Write your answer here..."
                                    class="w-full rounded-md border border-gray-300 focus:border-primary focus:ring-primary/30 text-sm text-gray-800 placeholder:text-gray-400 resize-y p-3"
                                    :aria-invalid="errors['{{ $q['id'] }}'] ? 'true' : 'false'"
                                    @input="answers['{{ $q['id'] }}']=$event.target.value; if($event.target.value.trim().length){ delete errors['{{ $q['id'] }}'] }"></textarea>
                                <div class="flex justify-between text-[11px] text-gray-400" x-data="{ limit: 2000 }">
                                    <span x-text="(answers['{{ $q['id'] }}']||'').length + ' chars'"></span>
                                    <span>Limit 2000 chars</span>
                                </div>
                            </div>
                        @else
                            <div class="grid gap-1.5 md:gap-2">
                                @foreach ($q['options'] as $optIndex => $opt)
                                    <label
                                        class="flex items-start gap-3 group cursor-pointer rounded-md px-1.5 py-1 hover:bg-gray-50">
                                        <input type="radio" name="{{ $q['id'] }}"
                                            class="mt-1 h-4 w-4 text-primary focus:ring-primary/40 border-gray-300 rounded"
                                            :aria-invalid="errors['{{ $q['id'] }}'] ? 'true' : 'false'"
                                            value="{{ is_array($opt) ? $opt['id'] : $opt }}"
                                            @change="answers['{{ $q['id'] }}']= '{{ is_array($opt) ? $opt['id'] : addslashes($opt) }}'; delete errors['{{ $q['id'] }}']">
                                        <span class="text-sm text-gray-700 group-hover:text-gray-900 leading-snug">
                                            {{ is_array($opt) ? $opt['text'] : $opt }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <template x-if="errors['{{ $q['id'] }}']">
                            <p class="mt-3 text-xs text-red-600 flex items-center gap-1">
                                <x-icon name="o-exclamation-triangle" class="size-4" />
                                <span x-text="errors['{{ $q['id'] }}']"></span>
                            </p>
                        </template>
                    </fieldset>
                @empty
                    <div class="p-6 border border-dashed rounded-lg text-center text-sm text-gray-500 bg-white">
                        No pre-test questions available for this course.
                    </div>
                @endforelse

                {{-- Actions --}}
                @if ($qCount > 0)
                    <div class="pt-2 flex flex-row items-center justify-between md:justify-end gap-3">
                        <button type="button" @click="resetForm" :disabled="submitting"
                            class="inline-flex items-center justify-center gap-2 rounded-md bg-gray-100 text-gray-700 px-4 py-2.5 text-sm font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300/50 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <x-icon name="o-arrow-path" class="size-4" />
                            <span>Reset</span>
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="inline-flex items-center justify-center gap-2 rounded-md bg-primary text-white px-5 py-2.5 text-sm font-medium shadow hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 transition disabled:opacity-60 disabled:cursor-not-allowed">
                            <x-icon name="o-paper-airplane" class="size-4" />
                            <span>Submit</span>
                        </button>
                    </div>
                @endif
            </form>
        </div>
    @endif
</div>

{{-- Script --}}
<script>
    function pretestForm(wire) {
        return {
            lw: wire,
            answers: {},
            errors: {},
            submitted: false,
            submitting: false,
            totalQuestions: {{ $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions) }},
            get answeredCount() {
                return Object.keys(this.answers).length;
            },
            progressPercent() {
                return this.totalQuestions ? (this.answeredCount / this.totalQuestions) * 100 : 0;
            },
            init() {
                // No autosave/restore - start with empty answers
            },
            validate() {
                this.errors = {};
                const required = @json(
                    ($questions instanceof \Illuminate\Support\Collection
                        ? $questions->pluck('id')
                        : collect($questions)->pluck('id')
                    )->values());
                required.forEach(id => {
                    if (!this.answers[id]) {
                        this.errors[id] = 'Required.';
                    }
                });
                return Object.keys(this.errors).length === 0;
            },
            resetForm() {
                // Reset native form elements (radio selections, textareas)
                if (this.$refs.formEl) {
                    this.$refs.formEl.reset();
                }
                // Clear reactive state
                this.answers = {};
                this.errors = {};
                this.submitted = false;
            },
            submit() {
                if (!this.validate()) {
                    return;
                }
                this.submitting = true;
                this.submitted = true;
                Promise.resolve(this.lw.submitPretest(this.answers))
                    .catch(() => {
                        this.submitting = false;
                    })
                    .finally(() => {
                        // Livewire will redirect on success; on non-redirect, reset immediately
                        this.submitting = false;
                    });
            }
        }
    }
</script>
