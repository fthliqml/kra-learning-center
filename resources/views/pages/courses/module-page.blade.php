@php
    use Illuminate\Support\Str;

    $hasVideo = ($videoResources->count() ?? 0) > 0;
    $hasReading = ($readingResources->count() ?? 0) > 0;
    $videoCount = (int) ($videoResources->count() ?? 0);
@endphp

<div class="p-1 md:p-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" x-data="Object.assign(window.videoGate({{ $videoCount }}), { remedial: {{ !empty($canRetakePosttest) ? 'true' : 'false' }} })"
        @module-video-ended.window="ended[$event.detail.id] = true">
        <main class="lg:col-span-12">
            @isset($eligibleForPosttest)
                @if ($eligibleForPosttest)
                    <div
                        class="mb-4 p-3 md:p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 flex items-center justify-between">
                        <div class="text-sm md:text-[13px] font-medium">Semua materi selesai. Anda dapat melanjutkan ke
                            Posttest.</div>
                        <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                            class="inline-flex items-center gap-2 rounded-md bg-green-600 text-white px-3 py-1.5 text-xs md:text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-400/50">
                            Mulai Posttest
                            <x-icon name="o-arrow-right" class="size-4" />
                        </a>
                    </div>
                @elseif (!empty($canRetakePosttest))
                    <div
                        class="mb-4 p-3 md:p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 flex items-center justify-between gap-2 flex-wrap">
                        <div class="text-sm md:text-[13px] font-medium">Anda dapat mencoba Posttest lagi kapan saja, atau
                            kembali mempelajari materi.</div>
                        <div class="flex items-center gap-2">
                            <a wire:navigate href="{{ route('courses-posttest.index', $course) }}"
                                class="inline-flex items-center gap-2 rounded-md bg-amber-600 text-white px-3 py-1.5 text-xs md:text-sm font-medium hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400/50">
                                Coba Lagi Posttest
                                <x-icon name="o-arrow-right" class="size-4" />
                            </a>
                        </div>
                    </div>
                @endif
            @endisset
            @if ($activeSection)
                <div class="flex items-center justify-between mb-5 md:mb-6">
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900">{{ $activeSection->title }}</h1>
                    <div class="hidden md:flex items-center gap-2">
                        <button wire:click="completeSubtopic" :disabled="!(done || remedial)"
                            wire:loading.attr="disabled" wire:target="completeSubtopic"
                            wire:loading.class="opacity-70 pointer-events-none"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                            <x-icon name="o-arrow-right" class="size-4" />
                            <span>{{ $isLastSection ?? false ? 'Posttest' : 'Next' }}</span>
                        </button>
                    </div>
                </div>

                @if (!$hasVideo && !$hasReading)
                    <div class="p-6 border border-dashed rounded-md text-sm text-gray-500">
                        Belum ada konten untuk section ini.
                    </div>
                @else
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

                    @if ($hasReading)
                        <div
                            class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                            <div class="w-full px-4 md:px-6 py-3 md:py-4 flex items-center justify-between text-left">
                                <h3 class="text-sm font-semibold text-gray-900">Reading</h3>
                            </div>
                            <div class="space-y-4">
                                @foreach ($readingResources as $doc)
                                    @php $url = rsrc_url($doc->url ?? ''); @endphp
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

                <!-- Bottom Action (mobile) -->
                <div class="mt-6 flex items-center justify-end md:hidden">
                    <button wire:click="completeSubtopic" :disabled="!(done || remedial)" wire:loading.attr="disabled"
                        wire:target="completeSubtopic" wire:loading.class="opacity-70 pointer-events-none"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                        <x-icon name="o-arrow-right" class="size-5" />
                        <span>{{ $isLastSection ?? false ? 'Posttest' : 'Next' }}</span>
                    </button>
                    <template x-if="remedial && !done">
                        <span
                            class="ml-2 text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1">Remedial
                            aktif</span>
                    </template>
                </div>
            @else
                <div class="p-6 border border-dashed rounded-md text-sm text-gray-500">
                    Pilih module dan section dari sidebar untuk mulai belajar.
                </div>
            @endif
        </main>
    </div>
</div>
