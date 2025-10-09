@php
    use Illuminate\Support\Str;

    $hasVideo = ($videoResources->count() ?? 0) > 0;
    $hasReading = ($readingResources->count() ?? 0) > 0;
    $videoCount = (int) ($videoResources->count() ?? 0);
@endphp

<div class="p-1 md:p-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" x-data="window.videoGate({{ $videoCount }})"
        @module-video-ended.window="ended[$event.detail.id] = true">
        <main class="lg:col-span-12">
            @if ($activeSection)
                <div class="flex items-center justify-between mb-5 md:mb-6">
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900">{{ $activeSection->title }}</h1>
                    <div class="hidden md:flex items-center gap-2">
                        <button wire:click="completeSubtopic" :disabled="!done"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                            <x-icon name="o-arrow-right" class="size-4" />
                            <span>Next</span>
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
                                                <iframe class="w-full h-full yt-player"
                                                    id="yt-player-{{ $vid->id }}"
                                                    data-end-id="yt-{{ $vid->id }}" src="{{ $embedUrl }}"
                                                    loading="lazy"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                    referrerpolicy="strict-origin-when-cross-origin"
                                                    allowfullscreen></iframe>
                                                <!-- Shield to block YT overlays (copy link, pause suggestions) -->
                                                <div class="absolute inset-0 z-[5] bg-transparent" aria-hidden="true">
                                                </div>
                                                <!-- Top gradient to mask title/copy link -->
                                                <div class="pointer-events-none absolute inset-x-0 top-0 h-12 z-[6] bg-gradient-to-b from-black/60 to-transparent"
                                                    aria-hidden="true"></div>
                                                @php
                                                    $ytId = Str::afterLast(Str::before($embedUrl, '?'), '/');
                                                    $thumbUrl =
                                                        'https://i.ytimg.com/vi_webp/' . $ytId . '/hqdefault.webp';
                                                @endphp
                                                <!-- Full-cover thumbnail overlay and custom Play button -->
                                                <div class="absolute inset-0 z-[6]"
                                                    data-yt-overlay-container="yt-player-{{ $vid->id }}">
                                                    <div class="absolute inset-0 bg-center bg-cover"
                                                        style="background-image:url('{{ $thumbUrl }}')"></div>
                                                    <div class="absolute inset-0 bg-black/10 md:bg-black/5"></div>
                                                    <div class="absolute inset-0 flex items-center justify-center">
                                                        <button type="button"
                                                            class="group inline-flex items-center justify-center"
                                                            data-yt-overlay="yt-player-{{ $vid->id }}"
                                                            aria-label="Play video">
                                                            <span
                                                                class="relative inline-flex items-center justify-center w-14 h-14 md:w-16 md:h-16 rounded-full bg-black/50 backdrop-blur-md ring-1 ring-white/20 shadow-lg transition group-hover:bg-black/60">
                                                                <x-icon name="o-play"
                                                                    class="size-7 md:size-8 text-white translate-x-0.5" />
                                                            </span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <!-- Custom Controls -->
                                                <div class="absolute inset-x-0 bottom-0 p-2 md:p-3 z-[7] bg-gradient-to-t from-black/60 to-transparent text-white text-[11px] md:text-[12px] transition-opacity duration-300"
                                                    data-yt-for="yt-player-{{ $vid->id }}" data-yt-autohide="1">
                                                    <div
                                                        class="relative rounded-xl bg-black/30 backdrop-blur-md border border-white/10 shadow-md px-2.5 py-1.5 md:px-3 md:py-2 max-w-full">
                                                        <div class="flex flex-wrap items-center gap-2 md:gap-3 min-w-0">
                                                            <!-- Play/Pause -->
                                                            <button type="button"
                                                                class="px-2 py-1 md:px-2.5 md:py-1.5 rounded-md bg-white/10 hover:bg-white/15"
                                                                data-yt-action="togglePlay" data-yt-icon="play"
                                                                aria-label="Play/Pause">
                                                                <x-icon name="o-play" class="size-5 text-white"
                                                                    data-yt-icon-variant="play" />
                                                                <x-icon name="o-pause" class="size-5 text-white hidden"
                                                                    style="display:none" data-yt-icon-variant="pause" />
                                                            </button>
                                                            <!-- Mute/Volume -->
                                                            <button type="button"
                                                                class="px-2 py-1 md:px-2.5 md:py-1.5 rounded-md bg-white/10 hover:bg-white/15"
                                                                data-yt-action="toggleMute" data-yt-icon="volume"
                                                                aria-label="Mute/Unmute">
                                                                <x-icon name="o-speaker-wave" class="size-5 text-white"
                                                                    data-yt-icon-variant="vol-high" />
                                                                <x-icon name="o-speaker-x-mark"
                                                                    class="size-5 text-white hidden"
                                                                    style="display:none" data-yt-icon-variant="muted" />
                                                                <x-icon name="o-speaker-wave"
                                                                    class="size-5 text-white hidden"
                                                                    style="display:none"
                                                                    data-yt-icon-variant="vol-mid" />
                                                                <x-icon name="o-speaker-wave"
                                                                    class="size-5 text-white hidden"
                                                                    style="display:none"
                                                                    data-yt-icon-variant="vol-low" />
                                                            </button>
                                                            <input type="range" min="0" max="100"
                                                                step="1"
                                                                class="h-1.5 accent-primary cursor-pointer hidden flex-1 min-w-[72px] md:min-w-0 md:w-24"
                                                                style="display:none" data-yt-el="volume"
                                                                aria-label="Volume" />
                                                            <!-- Time -->
                                                            <div
                                                                class="ml-1 tabular-nums select-none whitespace-nowrap text-[11px] md:text-[12px]">
                                                                <span data-yt-el="currentTime">0:00</span>
                                                                <span class="opacity-70">/</span>
                                                                <span data-yt-el="duration">0:00</span>
                                                            </div>
                                                            <!-- Right controls -->
                                                            <div class="ml-auto flex items-center gap-2 shrink-0">
                                                                <!-- Quality Gear (custom menu) -->
                                                                <button type="button"
                                                                    class="px-2 py-1 md:px-2.5 md:py-1.5 rounded-md bg-white/10 hover:bg-white/15"
                                                                    data-yt-action="qualityMenu" aria-label="Quality">
                                                                    <x-icon name="o-cog-6-tooth"
                                                                        class="size-5 text-white" />
                                                                </button>
                                                                <!-- Fullscreen -->
                                                                <button type="button"
                                                                    class="px-2 py-1 md:px-2.5 md:py-1.5 rounded-md bg-white/10 hover:bg-white/15"
                                                                    data-yt-action="fullscreen"
                                                                    aria-label="Fullscreen">
                                                                    <x-icon name="o-arrows-pointing-out"
                                                                        class="size-5 text-white" />
                                                                </button>
                                                            </div>
                                                            <!-- Quality menu panel (populated by JS) -->
                                                            <div data-yt-quality-menu
                                                                class="hidden absolute bottom-full right-2 mb-2 w-40 rounded-lg bg-black/70 backdrop-blur-md border border-white/10 shadow-lg p-1.5 z-20">
                                                                <!-- JS will render options here -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @elseif (Str::endsWith(strtolower($url), ['.mp4', '.webm']))
                                                <video class="w-full h-full" controls src="{{ $url }}"
                                                    @ended="$dispatch('module-video-ended', { id: 'mp4-{{ $vid->id }}' })"></video>
                                            @else
                                                <iframe class="w-full h-full" src="{{ $url }}"
                                                    loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                                                    allowfullscreen></iframe>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($hasReading)
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow"
                            x-data="Object.assign({ open: true }, window.readingAccordionState('reading_open_course_{{ $course->id ?? 'c' }}_section_{{ $activeSection->id ?? 's' }}'))">
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                class="w-full px-4 md:px-6 py-3 md:py-4 flex items-center justify-between text-left">
                                <h3 class="text-sm font-semibold text-gray-900">Reading</h3>
                                <svg class="size-4 text-gray-500 transition-transform"
                                    :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"
                                    aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.127l3.71-3.896a.75.75 0 111.08 1.04l-4.24 4.46a.75.75 0 01-1.08 0l-4.24-4.46a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak>
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
                                                @else
                                                    <a href="{{ $url }}" target="_blank" rel="noopener"
                                                        class="text-primary hover:underline">Buka Dokumen</a>
                                                @endif
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Bottom Action (mobile) -->
                <div class="mt-6 flex items-center justify-end md:hidden">
                    <button wire:click="completeSubtopic" :disabled="!done"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                        <x-icon name="o-arrow-right" class="size-5" />
                        <span>Next</span>
                    </button>
                </div>
            @else
                <div class="p-6 border border-dashed rounded-md text-sm text-gray-500">
                    Pilih module dan section dari sidebar untuk mulai belajar.
                </div>
            @endif
        </main>
    </div>
</div>
