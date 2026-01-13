@php
    use Illuminate\Support\Str;

    // Determine resource types
    $hasVideo = ($videoResources->count() ?? 0) > 0;
    $hasReading = ($readingResources->count() ?? 0) > 0;
    $videoCount = (int) ($videoResources->count() ?? 0);

    // Video gate: track which videos must be completed for this section.
    $videoEndIds = [];
    foreach ($videoResources ?? collect() as $vid) {
        $ctype = strtolower($vid->content_type ?? '');
        $raw = $vid->url ?? '';
        $url = rsrc_url($raw);

        if ($ctype === 'yt') {
            $videoEndIds[] = 'yt-' . $vid->id;
        } elseif ($url && Str::endsWith(strtolower($url), ['.mp4', '.webm'])) {
            $videoEndIds[] = 'mp4-' . $vid->id;
        }
    }

    $videoGateKey = 'videoGate:course:' . ($course->id ?? 'x') . ':section:' . ($activeSection->id ?? 'x');
@endphp

<div class="p-1 md:p-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" x-data="Object.assign(window.videoGate(@js($videoEndIds), @js($videoGateKey)), { remedial: {{ !empty($canRetakePosttest) ? 'true' : 'false' }}, quizOpen: @entangle('showQuizModal') })"
        @module-video-ended.window="markEnded($event.detail.id)">
        <main class="lg:col-span-12">
            {{-- Posttest Eligibility --}}
            @isset($eligibleForPosttest)
                @if (!empty($hasPosttestAttempt))
                    {{-- User has already attempted posttest - show "Lihat Hasil" instead --}}
                    <div
                        class="mb-4 p-3 md:p-4 rounded-lg border border-blue-200 bg-blue-50 text-blue-800 flex items-center justify-between">
                        <div class="text-sm md:text-[13px] font-medium">
                            Anda telah mengerjakan Post-Test. Retry Post-Test dapat dilakukan melalui halaman Hasil.
                        </div>
                        <a wire:navigate href="{{ route('courses-result.index', $course) }}"
                            class="inline-flex items-center gap-2 rounded-md bg-blue-600 text-white px-3 py-1.5 text-xs md:text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400/50">
                            Lihat Hasil
                            <x-icon name="o-arrow-right" class="size-4" />
                        </a>
                    </div>
                @elseif ($eligibleForPosttest)
                    <div
                        class="mb-4 p-3 md:p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 flex items-center justify-between">
                        <div class="text-sm md:text-[13px] font-medium">Semua materi selesai. Anda dapat melanjutkan ke
                            Post-Test.</div>
                        <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                            class="inline-flex items-center gap-2 rounded-md bg-green-600 text-white px-3 py-1.5 text-xs md:text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-400/50">
                            Mulai Post-Test
                            <x-icon name="o-arrow-right" class="size-4" />
                        </a>
                    </div>
                @endif
            @endisset

            {{-- Active Section --}}
            @if ($activeSection)
                <div class="flex items-center justify-between mb-5 md:mb-6">
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900">{{ $activeSection->title }}</h1>
                    <div class="hidden md:flex items-center gap-2">
                        @if (!empty($hasPosttestAttempt) && ($isLastSection ?? false))
                            {{-- User has posttest attempt and is on last section - show Lihat Hasil --}}
                            <a wire:navigate href="{{ route('courses-result.index', $course) }}"
                                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400/40">
                                <x-icon name="o-arrow-right" class="size-4" />
                                <span>Lihat Hasil</span>
                            </a>
                        @else
                            <button wire:click="completeSubtopic" wire:loading.attr="disabled"
                                wire:target="completeSubtopic" wire:loading.class="opacity-70 pointer-events-none"
                                x-bind:title="!done ? 'Selesaikan menonton video terlebih dahulu.' : ''"
                                x-bind:aria-disabled="(!done).toString()" x-bind:disabled="!done"
                                x-bind:class="!done ? 'opacity-60 cursor-not-allowed' : ''"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                                <x-icon name="o-arrow-right" class="size-4" />
                                <span>
                                    @if (!empty($hasSectionQuiz) && empty($hasPosttestAttempt))
                                        Quiz
                                    @else
                                        {{ $isLastSection ?? false ? 'Post-Test' : 'Next' }}
                                    @endif
                                </span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Resources --}}
                @if (!$hasVideo && !$hasReading)
                    <div class="p-6 border border-dashed rounded-md text-sm text-gray-500">
                        Belum ada konten untuk section ini.
                    </div>
                @else
                    {{-- Video Resources --}}
                    @if ($hasVideo)
                        <div
                            class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow mb-4">
                            <div class="md:p-6">
                                <div class="grid gap-4">
                                    @foreach ($videoResources as $vid)
                                        @php
                                            $ctype = strtolower($vid->content_type ?? '');
                                            $raw = $vid->url ?? '';
                                            $url = rsrc_url($raw);
                                        @endphp
                                        <div class="relative aspect-video bg-black/5 rounded-lg overflow-hidden">
                                            @if ($ctype === 'yt')
                                                @php
                                                    $embedUrl = yt_embed_url($raw);
                                                    $sep = Str::contains($embedUrl, '?') ? '&' : '?';
                                                    $origin = request()->getSchemeAndHttpHost();
                                                    $embedUrl .=
                                                        $sep .
                                                        'enablejsapi=1&controls=0&modestbranding=1&rel=0&iv_load_policy=3&playsinline=1&origin=' .
                                                        urlencode($origin);
                                                @endphp
                                                @php
                                                    $ytId = Str::afterLast(Str::before($embedUrl, '?'), '/');
                                                @endphp
                                                <div class="js-plyr w-full h-full" data-plyr-provider="youtube"
                                                    data-plyr-embed-id="{{ $ytId }}"
                                                    data-end-id="yt-{{ $vid->id }}"
                                                    data-video-gate-key="{{ $videoGateKey }}">
                                                </div>
                                            @elseif (Str::endsWith(strtolower($url), ['.mp4', '.webm']))
                                                <video class="w-full h-full" controls src="{{ $url }}"
                                                    @ended="$dispatch('module-video-ended', { id: 'mp4-{{ $vid->id }}' })"></video>
                                            @else
                                                <iframe class="w-full h-full" src="{{ $url }}" loading="lazy"
                                                    referrerpolicy="strict-origin-when-cross-origin"
                                                    allowfullscreen></iframe>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Reading Resources --}}
                    @if ($hasReading)
                        <div
                            class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                            <div class="w-full px-4 md:px-6 py-3 md:py-4 flex items-center justify-between text-left">
                                <h3 class="text-sm font-semibold text-gray-900">Reading</h3>
                            </div>
                            <div class="space-y-4">
                                @foreach ($readingResources as $doc)
                                    @php
                                        $url = rsrc_url($doc->url ?? '');
                                        // Force same-origin relative path for PDF to avoid CORS issues when APP_URL differs
                                        if ($url) {
                                            $parts = @parse_url($url) ?: [];
                                            // If host differs from current request host, use path component only
                                            if (
                                                ($parts['host'] ?? '') &&
                                                ($parts['host'] ?? '') !== request()->getHost()
                                            ) {
                                                // Reconstruct path with query if present
                                                $rel =
                                                    ($parts['path'] ?? '') .
                                                    (isset($parts['query']) ? '?' . $parts['query'] : '');
                                                if ($rel) {
                                                    $url = $rel;
                                                } // keep relative path
                                            }
                                        }
                                    @endphp
                                    <article class="px-4 pb-4 rounded-lg text-sm text-gray-800">
                                        @if ($url)
                                            @if (Str::endsWith(strtolower($url), ['.pdf']))
                                                <div x-data="{ ready: false }" x-init="$nextTick(() => { if (window.__initFlipbook) window.__initFlipbook($el) })">
                                                    <div class="flipbook-root relative w-full h-[35vh] md:h-[80vh] bg-white rounded overflow-hidden border border-gray-200 flex items-center justify-center"
                                                        data-pdf-url="{{ $url }}" tabindex="0"
                                                        role="application" aria-label="Flipbook viewer">
                                                        <div
                                                            class="pdf-loading absolute inset-0 flex items-center justify-center text-sm text-gray-500 pointer-events-none">
                                                            Memuat viewer...
                                                        </div>
                                                        <div class="flip-container w-full h-full"></div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Bottom Action (mobile) --}}
                <div class="mt-6 flex items-center justify-end md:hidden">
                    <button wire:click="completeSubtopic" wire:loading.attr="disabled" wire:target="completeSubtopic"
                        wire:loading.class="opacity-70 pointer-events-none"
                        x-bind:title="!done ? 'Selesaikan menonton video terlebih dahulu.' : ''"
                        x-bind:aria-disabled="(!done).toString()" x-bind:disabled="!done"
                        x-bind:class="!done ? 'opacity-60 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                        <x-icon name="o-arrow-right" class="size-5" />
                        <span>
                            @if (!empty($hasSectionQuiz) && empty($canRetakePosttest))
                                Quiz
                            @else
                                {{ $isLastSection ?? false ? 'Posttest' : 'Next' }}
                            @endif
                        </span>
                    </button>
                </div>
            @endif
        </main>
    </div>

    {{-- Quiz Modal --}}
    <div x-data="{ quizOpen: @entangle('showQuizModal') }">
        <div x-cloak x-show="quizOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/40"></div>
        <div x-cloak x-show="quizOpen" x-transition
            class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div class="w-full max-w-3xl bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden flex flex-col max-h-[90vh]"
                @keydown.escape.window="quizOpen=false; $wire.closeQuizModalOnly()">
                {{-- Modal Header (fixed) --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 flex-shrink-0">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900">Quiz:
                        {{ $activeSection->title ?? '' }}
                    </h2>
                    <button class="p-1.5 rounded-md hover:bg-gray-100"
                        @click="quizOpen=false; $wire.closeQuizModalOnly()" aria-label="Tutup">
                        <x-icon name="o-x-mark" class="size-5 text-gray-500" />
                    </button>
                </div>
                {{-- Modal Content (scrollable) --}}
                <div class="p-4 md:p-6 overflow-y-auto flex-1" x-data="sectionQuizInModal($wire)">
                    @php $qCount = is_array($quizQuestions ?? null) ? count($quizQuestions) : 0; @endphp
                    @if ($qCount === 0)
                        <div class="p-6 text-sm text-gray-500 border border-dashed rounded-lg">Belum ada soal untuk
                            materi
                            ini.</div>
                    @else
                        <template x-if="!result">
                            <form x-ref="formEl" @submit.prevent="submit" class="space-y-4 md:space-y-5">
                                @foreach ($quizQuestions ?? [] as $index => $q)
                                    <fieldset
                                        class="rounded-lg border border-gray-200 bg-white p-4 md:p-5 shadow-sm relative">
                                        <legend class="sr-only">Soal {{ $index + 1 }}</legend>
                                        <div
                                            class="absolute top-4 left-4 md:top-5 md:left-5 inline-flex items-center justify-center rounded-md bg-primary/10 text-primary text-[11px] font-semibold px-2 py-0.5 h-5 min-w-[28px]">
                                            {{ $index + 1 }}</div>
                                        <div class="pl-10 pr-0 md:pr-4 mb-5">
                                            <p class="text-sm font-medium text-gray-800 leading-snug">
                                                {{ $q['text'] }}
                                            </p>
                                        </div>
                                        @if (strtolower($q['type']) === 'essay')
                                            <div class="space-y-2">
                                                <textarea name="{{ $q['id'] }}" x-ref="txt_{{ $q['id'] }}" rows="4"
                                                    placeholder="Tulis jawaban Anda di sini..."
                                                    class="w-full rounded-md border border-gray-300 focus:border-primary focus:ring-primary/30 text-sm text-gray-800 placeholder:text-gray-400 resize-y p-3"
                                                    :aria-invalid="errors['{{ $q['id'] }}'] ? 'true' : 'false'"
                                                    @input="answers['{{ $q['id'] }}']=$event.target.value; if($event.target.value.trim().length){ delete errors['{{ $q['id'] }}'] }"></textarea>
                                                <template x-if="errors['{{ $q['id'] }}']">
                                                    <p class="text-xs text-red-600">Wajib diisi.</p>
                                                </template>
                                            </div>
                                        @else
                                            <div class="grid gap-1.5 md:gap-2">
                                                @foreach ($q['options'] as $opt)
                                                    <label
                                                        class="flex items-start gap-3 group cursor-pointer rounded-md px-1.5 py-1 hover:bg-gray-50">
                                                        <input type="radio" name="{{ $q['id'] }}"
                                                            class="mt-1 h-4 w-4 text-primary focus:ring-primary/40 border-gray-300 rounded"
                                                            :aria-invalid="errors['{{ $q['id'] }}'] ? 'true' : 'false'"
                                                            value="{{ is_array($opt) ? $opt['id'] : $opt }}"
                                                            @change="answers['{{ $q['id'] }}']= '{{ is_array($opt) ? $opt['id'] : addslashes($opt) }}'; delete errors['{{ $q['id'] }}']">
                                                        <span
                                                            class="text-sm text-gray-700 group-hover:text-gray-900 leading-snug">
                                                            {{ is_array($opt) ? $opt['text'] : $opt }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                    </fieldset>
                                @endforeach

                                <div class="pt-2 flex flex-row items-center justify-end gap-3">
                                    <button type="submit" :disabled="submitting"
                                        class="inline-flex items-center justify-center gap-2 rounded-md bg-primary text-white px-5 py-2.5 text-sm font-medium shadow hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 transition disabled:opacity-60 disabled:cursor-not-allowed">
                                        <span class="inline-flex items-center gap-2" x-show="!submitting">
                                            <x-icon name="o-paper-airplane" class="size-4" />
                                            <span>Submit</span>
                                        </span>
                                        <span class="inline-flex items-center gap-2" x-show="submitting">
                                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                            <span>Mengirim...</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </template>

                        <template x-if="result">
                            <div class="space-y-5">
                                {{-- Score Summary Card --}}
                                <div
                                    class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary/5 via-primary/10 to-primary/5 border border-primary/20 p-5">
                                    <div class="flex items-center gap-5">
                                        {{-- Score Circle --}}
                                        <div class="relative flex-shrink-0">
                                            <div class="w-20 h-20 rounded-full bg-white shadow-lg flex items-center justify-center border-4"
                                                :class="result.score === result.total ? 'border-green-400' : (result.score >=
                                                    result.total / 2 ? 'border-amber-400' : 'border-red-400')">
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-gray-900"
                                                        x-text="result.score"></div>
                                                    <div class="text-xs text-gray-500" x-text="'/ ' + result.total">
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- Perfect Score Badge --}}
                                            <template x-if="result.score === result.total">
                                                <div
                                                    class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-green-500 flex items-center justify-center shadow-md">
                                                    <x-icon name="o-check" class="size-4 text-white" />
                                                </div>
                                            </template>
                                        </div>
                                        {{-- Summary Text --}}
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900">Hasil Quiz</h3>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <span
                                                    x-text="result.score === result.total ? 'Sempurna! Semua jawaban benar.' :
                                                    (result.score >= result.total/2 ? 'Bagus! Anda menjawab sebagian besar dengan benar.' :
                                                    'Tetap semangat! Pelajari kembali materinya.')"></span>
                                            </p>
                                            <div class="flex items-center gap-3 mt-2">
                                                <span
                                                    class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 px-2 py-1 rounded-full">
                                                    <x-icon name="o-check-circle" class="size-3.5" />
                                                    <span x-text="result.score + ' Benar'"></span>
                                                </span>
                                                <span
                                                    class="inline-flex items-center gap-1 text-xs font-medium text-red-700 bg-red-50 px-2 py-1 rounded-full">
                                                    <x-icon name="o-x-circle" class="size-3.5" />
                                                    <span x-text="(result.total - result.score) + ' Salah'"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Questions Review --}}
                                <div class="space-y-3">
                                    <template x-for="(item, idx) in (result.items || [])" :key="item.id">
                                        <div class="rounded-xl border-2 transition-all overflow-hidden"
                                            :class="item.is_correct ? 'border-green-200 bg-green-50/30' :
                                                'border-red-200 bg-red-50/30'">
                                            {{-- Question Header --}}
                                            <div class="px-4 py-3 flex items-start gap-3"
                                                :class="item.is_correct ? 'bg-green-100/50' : 'bg-red-100/50'">
                                                {{-- Status Icon --}}
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <template x-if="item.is_correct">
                                                        <div
                                                            class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                                            <x-icon name="o-check" class="size-4 text-white" />
                                                        </div>
                                                    </template>
                                                    <template x-if="!item.is_correct">
                                                        <div
                                                            class="w-6 h-6 rounded-full bg-red-500 flex items-center justify-center">
                                                            <x-icon name="o-x-mark" class="size-4 text-white" />
                                                        </div>
                                                    </template>
                                                </div>
                                                {{-- Question Number & Text --}}
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                                            :class="item.is_correct ? 'bg-green-200 text-green-800' :
                                                                'bg-red-200 text-red-800'"
                                                            x-text="'Soal ' + (idx + 1)"></span>
                                                        <span class="text-xs font-medium"
                                                            :class="item.is_correct ? 'text-green-700' : 'text-red-700'"
                                                            x-text="item.is_correct ? 'Benar' : 'Salah'"></span>
                                                    </div>
                                                    <p class="text-sm font-medium text-gray-900 leading-relaxed"
                                                        x-text="item.text"></p>
                                                </div>
                                            </div>

                                            {{-- Answers Comparison --}}
                                            <div class="px-4 py-3 space-y-2 bg-white/50">
                                                {{-- User Answer --}}
                                                <div class="flex items-start gap-2">
                                                    <div class="flex-shrink-0 w-24 text-xs font-medium text-gray-500">
                                                        Jawaban Anda</div>
                                                    <div class="flex-1 flex items-center gap-2">
                                                        <span
                                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-sm font-medium"
                                                            :class="item.is_correct ? 'bg-green-100 text-green-800' :
                                                                'bg-red-100 text-red-800'">
                                                            <template x-if="item.is_correct">
                                                                <x-icon name="o-check-circle" class="size-4" />
                                                            </template>
                                                            <template x-if="!item.is_correct">
                                                                <x-icon name="o-x-circle" class="size-4" />
                                                            </template>
                                                            <span x-text="item.user_answer || '-'"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                                {{-- Correct Answer (only if wrong) --}}
                                                <template x-if="!item.is_correct">
                                                    <div class="flex items-start gap-2">
                                                        <div
                                                            class="flex-shrink-0 w-24 text-xs font-medium text-gray-500">
                                                            Jawaban Benar</div>
                                                        <div class="flex-1">
                                                            <span
                                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-sm font-medium bg-green-100 text-green-800">
                                                                <x-icon name="o-check-circle" class="size-4" />
                                                                <span x-text="item.correct_answer || '-'"></span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Continue Button --}}
                                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-200">
                                    <button
                                        class="inline-flex items-center gap-2 rounded-lg bg-primary text-white px-5 py-2.5 text-sm font-medium shadow-lg shadow-primary/25 hover:bg-primary/90 hover:shadow-primary/30 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed transition-all"
                                        :disabled="advancing" wire:loading.attr="disabled"
                                        wire:loading.class="opacity-70 pointer-events-none"
                                        wire:target="closeQuizModalAndAdvance"
                                        @click="if (advancing) return; advancing = true; quizOpen=false; Promise.resolve($wire.closeQuizModalAndAdvance())">
                                        <span class="inline-flex items-center gap-2" x-show="!advancing">
                                            <span>Lanjut ke Materi Berikutnya</span>
                                            <x-icon name="o-arrow-right" class="size-4" />
                                        </span>
                                        <span class="inline-flex items-center gap-2" x-show="advancing">
                                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                            <span>Memuat...</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Script --}}
<script>
    function sectionQuizInModal(wire) {
        return {
            lw: wire,
            answers: {},
            errors: {},
            submitting: false,
            advancing: false,
            result: null,
            init() {
                this.$nextTick(() => {
                    this.$refs.formEl?.addEventListener('input', this.saveDraft?.bind?.(this));
                    this.$refs.formEl?.addEventListener('change', this.saveDraft?.bind?.(this));
                });
                // Hydrate result from server if present
                this.result = @json($quizResult ?? null);
            },
            validate() {
                this.errors = {};
                const required = @json(collect($quizQuestions ?? [])->pluck('id')->values());
                required.forEach(id => {
                    if (!this.answers[id]) this.errors[id] = 'Wajib diisi.';
                });
                return Object.keys(this.errors).length === 0;
            },
            submit() {
                if (!this.validate()) return;
                this.submitting = true;
                Promise.resolve(this.lw.submitSectionQuiz(this.answers))
                    .then(() => {
                        // Pull result from server after mutation
                        this.result = @this.get('quizResult');
                    })
                    .finally(() => {
                        this.submitting = false;
                    });
            }
        }
    }
</script>
