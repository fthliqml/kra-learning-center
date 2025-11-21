@php
    use Illuminate\Support\Str;

    // Determine resource types
    $hasVideo = ($videoResources->count() ?? 0) > 0;
    $hasReading = ($readingResources->count() ?? 0) > 0;
    $videoCount = (int) ($videoResources->count() ?? 0);
@endphp

<div class="p-1 md:p-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" x-data="Object.assign(window.videoGate({{ $videoCount }}), { remedial: {{ !empty($canRetakePosttest) ? 'true' : 'false' }}, quizOpen: @entangle('showQuizModal') })"
        @module-video-ended.window="ended[$event.detail.id] = true">
        <main class="lg:col-span-12">
            {{-- Posttest Eligibility --}}
            @isset($eligibleForPosttest)
                @if ($eligibleForPosttest)
                    <div
                        class="mb-4 p-3 md:p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 flex items-center justify-between">
                        <div class="text-sm md:text-[13px] font-medium">Semua materi selesai. Anda dapat melanjutkan ke
                            Post Test.</div>
                        <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                            class="inline-flex items-center gap-2 rounded-md bg-green-600 text-white px-3 py-1.5 text-xs md:text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-400/50">
                            Mulai Post Test
                            <x-icon name="o-arrow-right" class="size-4" />
                        </a>
                    </div>
                @elseif (!empty($canRetakePosttest))
                    <div
                        class="mb-4 p-3 md:p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 flex items-center justify-between gap-2 flex-wrap">
                        <div class="text-sm md:text-[13px] font-medium">
                            Anda dapat mencoba Post Test lagi kapan saja, atau kembali mempelajari materi.
                        </div>
                        <div class="flex items-center gap-2">
                            <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                                class="inline-flex items-center gap-2 rounded-md bg-amber-600 text-white px-3 py-1.5 text-xs md:text-sm font-medium hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400/50">
                                Coba Lagi Post Test
                                <x-icon name="o-arrow-right" class="size-4" />
                            </a>
                        </div>
                    </div>
                @endif
            @endisset

            {{-- Active Section --}}
            @if ($activeSection)
                <div class="flex items-center justify-between mb-5 md:mb-6">
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900">{{ $activeSection->title }}</h1>
                    <div class="hidden md:flex items-center gap-2">
                        <button wire:click="completeSubtopic" :disabled="!(done || remedial)"
                            wire:loading.attr="disabled" wire:target="completeSubtopic"
                            wire:loading.class="opacity-70 pointer-events-none"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                            <x-icon name="o-arrow-right" class="size-4" />
                            <span>
                                @if (!empty($hasSectionQuiz) && empty($canRetakePosttest))
                                    Quiz
                                @else
                                    {{ $isLastSection ?? false ? 'Post Test' : 'Next' }}
                                @endif
                            </span>
                        </button>
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
                                                    data-end-id="yt-{{ $vid->id }}">
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
                                            if (($parts['host'] ?? '') && ($parts['host'] ?? '') !== request()->getHost()) {
                                                // Reconstruct path with query if present
                                                $rel = ($parts['path'] ?? '') . (isset($parts['query']) ? ('?' . $parts['query']) : '');
                                                if ($rel) $url = $rel; // keep relative path
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
                    <button wire:click="completeSubtopic" :disabled="!(done || remedial)" wire:loading.attr="disabled"
                        wire:target="completeSubtopic" wire:loading.class="opacity-70 pointer-events-none"
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
    <div x-cloak x-show="quizOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden"
            @keydown.escape.window="quizOpen=false; $wire.closeQuizModalAndAdvance()">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <h2 class="text-base md:text-lg font-semibold text-gray-900">Quiz: {{ $activeSection->title ?? '' }}
                </h2>
                <button class="p-1.5 rounded-md hover:bg-gray-100"
                    @click="quizOpen=false; $wire.closeQuizModalAndAdvance()" aria-label="Tutup">
                    <x-icon name="o-x-mark" class="size-5 text-gray-500" />
                </button>
            </div>
            <div class="p-4 md:p-6" x-data="sectionQuizInModal($wire)">
                @php $qCount = is_array($quizQuestions ?? null) ? count($quizQuestions) : 0; @endphp
                @if ($qCount === 0)
                    <div class="p-6 text-sm text-gray-500 border border-dashed rounded-lg">Belum ada soal untuk materi
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
                                        <p class="text-sm font-medium text-gray-800 leading-snug">{{ $q['text'] }}
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
                        <div class="space-y-4">
                            <div class="rounded-lg border p-4 bg-white">
                                <div class="text-sm text-gray-700">
                                    <span class="font-semibold">Ringkasan:</span>
                                    <span x-text="`${result.score}/${result.total}`"></span>
                                    <template x-if="result.hasEssay">
                                        <span class="ml-2 text-amber-700">(terdapat jawaban essay — penilaian
                                            manual)</span>
                                    </template>
                                </div>
                            </div>
                            <template x-for="(item, idx) in (result.items || [])" :key="item.id">
                                <div class="rounded-lg border p-4">
                                    <div class="flex items-start gap-2">
                                        <template x-if="item.type === 'multiple'">
                                            <div>
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                    :class="item.is_correct ?
                                                        'bg-green-50 text-green-700 ring-1 ring-green-200' :
                                                        'bg-red-50 text-red-700 ring-1 ring-red-200'">
                                                    <span x-text="item.is_correct ? 'Benar' : 'Salah'"></span>
                                                </span>
                                            </div>
                                        </template>
                                        <template x-if="item.type !== 'multiple'">
                                            <div>
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">Essay</span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-900 font-medium"
                                        x-text="`${idx+1}. ${item.text}`"></div>
                                    <div class="mt-2 text-sm">
                                        <div class="text-gray-700"><span class="font-semibold">Jawaban Anda:</span>
                                            <span x-text="item.user_answer || '-' "></span>
                                        </div>
                                        <template x-if="item.type === 'multiple' && item.is_correct === false">
                                            <div class="text-gray-700"><span class="font-semibold">Jawaban
                                                    Benar:</span> <span x-text="item.correct_answer || '-' "></span>
                                            </div>
                                        </template>
                                        <template x-if="item.type !== 'multiple'">
                                            <div class="text-amber-700 text-xs mt-1">Menunggu penilaian.</div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    class="inline-flex items-center gap-2 rounded-md bg-primary text-white px-4 py-2 text-sm font-medium hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed"
                                    :disabled="advancing" wire:loading.attr="disabled"
                                    wire:loading.class="opacity-70 pointer-events-none"
                                    wire:target="closeQuizModalAndAdvance"
                                    @click="if (advancing) return; advancing = true; quizOpen=false; Promise.resolve($wire.closeQuizModalAndAdvance())">
                                    <span class="inline-flex items-center gap-2" x-show="!advancing">
                                        <span>Lanjut</span>
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
