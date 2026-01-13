@php
    // Initialize questions collection
    $questions = $questions ?? collect();

    // Count questions
    $qCount = $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions);
    $isReviewMode = $isReviewMode ?? false;

    $lastSectionId = null;
    try {
        $mods = $course?->learningModules ?? collect();
        $allSections = $mods->flatMap(fn($m) => $m->sections ?? collect());
        $lastSectionId = $allSections->last()?->id;
    } catch (\Throwable $e) {
        $lastSectionId = null;
    }
@endphp

@if ($isReviewMode)
    <div class="p-2 md:px-8 md:py-4 mx-auto max-w-5xl relative">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 md:mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                    Post-Test
                    <span class="ml-2 text-sm font-medium text-primary bg-primary/10 px-2 py-1 rounded-md">Review
                        Mode</span>
                </h1>
            </div>

            <div class="hidden md:flex items-center gap-2">
                <a wire:navigate
                    href="{{ route('courses-modules.index', ['course' => $course->id, 'section' => $lastSectionId]) }}"
                    class="inline-flex items-center gap-2 rounded-md bg-gray-100 px-3 py-2 text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300/50 disabled:opacity-60 disabled:cursor-not-allowed">
                    <x-icon name="o-arrow-left" class="size-4" />
                    <span>Back</span>
                </a>
                <a wire:navigate href="{{ route('courses-result.index', ['course' => $course->id]) }}"
                    class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                    <x-icon name="o-arrow-right" class="size-4" />
                    <span>Next</span>
                </a>
            </div>
        </div>

        {{-- Review Summary --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 md:p-6 shadow-sm mb-5 md:mb-6">
            <div class="flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-green-100 text-green-600">
                    <x-icon name="o-check-circle" class="size-5" />
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Hasil Post-Test Anda</h2>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                        Berikut adalah jawaban Anda pada post-test. Anda dapat mereview kembali jawaban yang benar dan
                        salah.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700 bg-gray-100 px-3 py-1.5 rounded-full">
                            <x-icon name="o-document-text" class="size-4" />
                            Skor: {{ $attempt->total_score ?? ($attempt->auto_score ?? 0) }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700 bg-gray-100 px-3 py-1.5 rounded-full">
                            <x-icon name="o-calendar" class="size-4" />
                            Disubmit: {{ $attempt?->submitted_at?->format('d M Y H:i') ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Questions (Review Mode) --}}
        <div class="space-y-4 md:space-y-5">
            @forelse ($questions as $index => $q)
                @php
                    $isCorrect = $q['is_correct'] ?? null;
                    $userAnswerId = $q['user_answer_id'] ?? null;
                    $userEssayAnswer = $q['user_essay_answer'] ?? null;
                    $earnedPoints = $q['earned_points'] ?? 0;
                    $maxPoints = $q['max_points'] ?? 1;
                @endphp
                <div
                    class="rounded-xl border-2 overflow-hidden {{ $isCorrect === true ? 'border-green-200 bg-green-50/30' : ($isCorrect === false ? 'border-red-200 bg-red-50/30' : 'border-gray-200 bg-white') }}">
                    <div
                        class="px-4 py-3 flex items-start gap-3 {{ $isCorrect === true ? 'bg-green-100/50' : ($isCorrect === false ? 'bg-red-100/50' : 'bg-gray-50') }}">
                        <div class="flex-shrink-0 mt-0.5">
                            @if ($isCorrect === true)
                                <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                    <x-icon name="o-check" class="size-4 text-white" />
                                </div>
                            @elseif($isCorrect === false)
                                <div class="w-6 h-6 rounded-full bg-red-500 flex items-center justify-center">
                                    <x-icon name="o-x-mark" class="size-4 text-white" />
                                </div>
                            @else
                                <div class="w-6 h-6 rounded-full bg-amber-500 flex items-center justify-center">
                                    <x-icon name="o-clock" class="size-4 text-white" />
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $isCorrect === true ? 'bg-green-200 text-green-800' : ($isCorrect === false ? 'bg-red-200 text-red-800' : 'bg-amber-200 text-amber-800') }}">
                                    Soal {{ $index + 1 }}
                                </span>
                                @if ($isCorrect === true)
                                    <span class="text-xs font-medium text-green-700">Benar</span>
                                @elseif($isCorrect === false)
                                    <span class="text-xs font-medium text-red-700">Salah</span>
                                @else
                                    <span class="text-xs font-medium text-amber-700">Menunggu Review
                                        ({{ $earnedPoints }}/{{ $maxPoints }} poin)
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm font-medium text-gray-900 leading-relaxed">{{ $q['text'] }}</p>
                        </div>
                    </div>

                    <div class="px-4 py-3 space-y-2 bg-white/50">
                        @if ($q['type'] === 'essay')
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-gray-500">Jawaban Anda:</div>
                                <div class="p-3 rounded-lg bg-gray-100 text-sm text-gray-700">
                                    {{ $userEssayAnswer ?: '-' }}
                                </div>
                            </div>
                        @else
                            <div class="space-y-1.5">
                                @foreach ($q['options'] as $opt)
                                    @php
                                        $optId = is_array($opt) ? $opt['id'] : $opt;
                                        $optText = is_array($opt) ? $opt['text'] : $opt;
                                        $optIsCorrect = is_array($opt) ? $opt['is_correct'] ?? false : false;
                                        $isUserAnswer = (string) $optId === (string) $userAnswerId;
                                    @endphp
                                    <div
                                        class="flex items-start gap-2 p-2 rounded-lg {{ $optIsCorrect ? 'bg-green-100 border border-green-300' : ($isUserAnswer && !$optIsCorrect ? 'bg-red-100 border border-red-300' : 'bg-gray-50 border border-transparent') }}">
                                        <div class="flex-shrink-0 mt-0.5">
                                            @if ($optIsCorrect)
                                                <x-icon name="o-check-circle" class="size-4 text-green-600" />
                                            @elseif($isUserAnswer && !$optIsCorrect)
                                                <x-icon name="o-x-circle" class="size-4 text-red-600" />
                                            @else
                                                <div class="w-4 h-4 rounded-full border border-gray-300"></div>
                                            @endif
                                        </div>
                                        <span
                                            class="text-sm {{ $optIsCorrect ? 'text-green-800 font-medium' : ($isUserAnswer && !$optIsCorrect ? 'text-red-800' : 'text-gray-600') }}">
                                            {{ $optText }}
                                            @if ($isUserAnswer)
                                                <span class="ml-1 text-xs font-medium">(Jawaban Anda)</span>
                                            @endif
                                            @if ($optIsCorrect)
                                                <span class="ml-1 text-xs font-medium text-green-600">✓ Benar</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 border border-dashed rounded-lg text-center text-sm text-gray-500 bg-white">
                    Belum ada soal posttest untuk course ini.
                </div>
            @endforelse
        </div>

        {{-- Bottom Action (mobile) --}}
        <div class="mt-6 flex items-center justify-between md:hidden">
            <a wire:navigate
                href="{{ route('courses-modules.index', ['course' => $course->id, 'section' => $lastSectionId]) }}"
                class="inline-flex items-center gap-2 rounded-md bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300/50 disabled:opacity-60 disabled:cursor-not-allowed">
                <x-icon name="o-arrow-left" class="size-5" />
                <span>Back</span>
            </a>
            <a wire:navigate href="{{ route('courses-result.index', ['course' => $course->id]) }}"
                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                <x-icon name="o-arrow-right" class="size-5" />
                <span>Next</span>
            </a>
        </div>
    </div>
@else
    @php
        $requiredIds = ($questions instanceof \Illuminate\Support\Collection
            ? $questions->pluck('id')
            : collect($questions)->pluck('id')
        )
            ->values()
            ->all();
    @endphp

    <div x-data="window.posttestForm($wire, @js($requiredIds), {{ $qCount }})" x-init="init()" class="p-2 md:px-8 md:py-4 mx-auto max-w-5xl relative">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 md:mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Post-Test</h1>
            </div>
            {{-- Back button (retake flow): go back to last learning section --}}
            @if (!empty($showMaterialPicker))
                <a wire:navigate
                    href="{{ route('courses-modules.index', $course) }}{{ !empty($lastSectionId) ? '?section=' . (int) $lastSectionId : '' }}"
                    class="inline-flex items-center gap-2 rounded-md bg-gray-800 text-white px-3 py-2 text-xs md:text-sm font-medium hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-700/50">
                    <x-icon name="o-arrow-left" class="size-4" />
                    <span>Back</span>
                </a>
            @endif
        </div>

        {{-- Instructions --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 md:p-6 shadow-sm mb-5 md:mb-6"
            x-data="{ open: true }">
            <button type="button"
                class="md:hidden inline-flex items-center gap-2 text-xs font-medium text-gray-600 transition"
                @click="open = !open">
                <span
                    x-text="open ? 'Sembunyikan Instruksi ' : '
                                Tampilkan Instruksi '"></span>
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
                    <h2 class="text-base font-semibold text-gray-900">Sebelum Memulai</h2>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed">Post-test ini mengukur pemahaman Anda
                        setelah menyelesaikan materi.</p>
                    <ul class="mt-3 text-xs text-gray-500 grid gap-1 grid-cols-1">
                        <li class="inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary/60"></span>
                            Jumlah Soal:
                            <strong>{{ $questions instanceof \Illuminate\Support\Collection ? $questions->count() : count($questions) }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Form Post-Test --}}
        <form x-ref="formEl" @submit.prevent="submit" class="space-y-4 md:space-y-5"
            x-bind:aria-busy="submitting ? 'true' : 'false'">
            @forelse ($questions as $index => $q)
                <fieldset class="rounded-lg border border-gray-200 bg-white p-4 md:p-5 shadow-sm relative"
                    :class="errors['{{ $q['id'] }}'] ? 'border-red-300 ring-1 ring-red-200' : ''">
                    <legend class="sr-only">Soal {{ $index + 1 }}</legend>
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
                                placeholder="Tulis jawaban Anda di sini..."
                                class="w-full rounded-md border border-gray-300 focus:border-primary focus:ring-primary/30 text-sm text-gray-800 placeholder:text-gray-400 resize-y p-3"
                                :aria-invalid="errors['{{ $q['id'] }}'] ? 'true' : 'false'"
                                @input="answers['{{ $q['id'] }}']=$event.target.value; if($event.target.value.trim().length){ delete errors['{{ $q['id'] }}'] }"></textarea>
                            <div class="flex justify-between text-[11px] text-gray-400" x-data="{ limit: 2000 }">
                                <span x-text="(answers['{{ $q['id'] }}']||'').length + ' karakter'"></span>
                                <span>Batas saran 2000</span>
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
                    Belum ada soal posttest untuk course ini.
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
