<div class="p-4 md:p-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Content: Video + Reading -->
        <main class="lg:col-span-12">
            @if ($activeSection)
                <!-- Title outside any card -->
                <div class="flex items-center justify-between mb-5 md:mb-6" x-data="{
                    videoCount: {{ (int) ($videoResources->count() ?? 0) }},
                    ended: {},
                    get done() { return this.videoCount === 0 || Object.keys(this.ended).length >= this.videoCount; }
                }"
                    @module-video-ended.window="ended[$event.detail.id] = true">
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900">{{ $activeSection->title }}</h1>
                    <div class="flex gap-2">
                        <button wire:click="completeSubtopic" :disabled="!done"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed">
                            <x-icon name="o-arrow-right" class="size-4" />
                            <span>Next</span>
                        </button>
                    </div>
                </div>

                @php
                    $hasVideo = ($videoResources->count() ?? 0) > 0;
                    $hasReading = ($readingResources->count() ?? 0) > 0;
                    $videoCount = (int) ($videoResources->count() ?? 0);
                    $readingCount = (int) ($readingResources->count() ?? 0);
                    $toUrl = function ($u) {
                        if (!$u) {
                            return '';
                        }
                        // absolute http(s)
                        if (Str::startsWith($u, ['http://', 'https://'])) {
                            return $u;
                        }
                        // storage or public path
                        try {
                            return Storage::url($u);
                        } catch (\Throwable $e) {
                            return asset($u);
                        }
                    };
                    $ytEmbed = function ($u) use ($toUrl) {
                        $u = $toUrl($u);
                        if (!$u) {
                            return '';
                        }
                        $parts = @parse_url($u) ?: [];
                        $host = $parts['host'] ?? '';
                        $path = $parts['path'] ?? '';
                        $query = $parts['query'] ?? '';
                        $id = null;
                        if (Str::contains($host, 'youtu.be')) {
                            $id = ltrim($path ?? '', '/');
                        } elseif (Str::contains($host, 'youtube.com')) {
                            if (Str::startsWith($path, '/watch')) {
                                parse_str($query ?? '', $q);
                                $id = $q['v'] ?? null;
                            } elseif (Str::startsWith($path, '/shorts/')) {
                                $id = trim(Str::after($path, '/shorts/'));
                            } elseif (Str::startsWith($path, '/embed/')) {
                                return $u; // already an embed URL
                            }
                        }
                        if ($id) {
                            return 'https://www.youtube-nocookie.com/embed/' . $id;
                        }
                        return $u;
                    };
                @endphp

                @if (!$hasVideo && !$hasReading)
                    <div class="p-6 border border-dashed rounded-md text-sm text-gray-500">
                        Belum ada konten untuk section ini.
                    </div>
                @else
                    <!-- Video Card -->
                    @if ($hasVideo)
                        <div
                            class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow mb-5">
                            <div class="p-4 md:p-6">
                                <div class="grid gap-4">
                                    @foreach ($videoResources as $vid)
                                        <div class="aspect-video bg-black/5 rounded-lg overflow-hidden">
                                            @php
                                                $ctype = strtolower($vid->content_type ?? '');
                                                $raw = $vid->url ?? '';
                                                $url = $toUrl($raw);
                                            @endphp
                                            @if ($ctype === 'yt')
                                                @php
                                                    $embedUrl = $ytEmbed($raw);
                                                    if (Str::contains($embedUrl, '?')) {
                                                        $embedUrl .= '&enablejsapi=1';
                                                    } else {
                                                        $embedUrl .= '?enablejsapi=1';
                                                    }
                                                @endphp
                                                <iframe class="w-full h-full yt-player"
                                                    id="yt-player-{{ $vid->id }}"
                                                    data-end-id="yt-{{ $vid->id }}" src="{{ $embedUrl }}"
                                                    loading="lazy"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                    referrerpolicy="strict-origin-when-cross-origin"
                                                    allowfullscreen></iframe>
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

                    <!-- Reading Card (Accordion) -->
                    @if ($hasReading)
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow"
                            x-data="{
                                open: false,
                                key: 'reading_open_course_{{ $course->id ?? 'c' }}_section_{{ $activeSection->id ?? 's' }}',
                                init() {
                                    let raw = null;
                                    try { raw = localStorage.getItem(this.key); } catch (e) {}
                                    if (raw !== null) {
                                        try { this.open = JSON.parse(raw); } catch (e) { this.open = !!raw; }
                                    }
                                    this.$watch('open', (v) => {
                                        try { localStorage.setItem(this.key, JSON.stringify(v)); } catch (e) {}
                                    });
                                }
                            }">
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                class="w-full px-4 md:px-6 py-3 md:py-4 flex items-center justify-between text-left">
                                <h3 class="text-sm font-semibold text-gray-900">Reading</h3>
                                <svg class="size-4 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''"
                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.127l3.71-3.896a.75.75 0 111.08 1.04l-4.24 4.46a.75.75 0 01-1.08 0l-4.24-4.46a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak>
                                <div class="p-4 md:p-6 space-y-4">
                                    @foreach ($readingResources as $doc)
                                        @php $url = $toUrl($doc->url ?? ''); @endphp
                                        <article class="p-4 border rounded-lg text-sm text-gray-800 bg-gray-50">
                                            @if ($doc->filename)
                                                <div class="font-medium text-gray-900 mb-1">{{ $doc->filename }}</div>
                                            @endif
                                            @if ($url)
                                                @if (Str::endsWith(strtolower($url), ['.pdf']))
                                                    <iframe class="w-full h-[60vh] rounded"
                                                        src="{{ $url }}"></iframe>
                                                    <div class="mt-2">
                                                        <a href="{{ $url }}" target="_blank"
                                                            class="text-primary hover:underline">Buka di tab baru</a>
                                                    </div>
                                                @else
                                                    <a href="{{ $url }}" target="_blank"
                                                        class="text-primary hover:underline">Buka Dokumen</a>
                                                @endif
                                            @else
                                                <div class="text-gray-600">Konten bacaan tersedia.</div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            @else
                <div class="p-6 border border-dashed rounded-md text-sm text-gray-500">
                    Pilih module dan section dari sidebar untuk mulai belajar.
                </div>
            @endif
        </main>
    </div>
</div>

<script>
    (function() {
        // Guard to avoid redefining across re-renders
        if (window.__moduleVideoGateReady) return;
        window.__moduleVideoGateReady = true;

        window.__ytPlayers = window.__ytPlayers || {};

        window.__initYtPlayers = function() {
            if (!(window.YT && YT.Player)) return;
            document.querySelectorAll('iframe.yt-player').forEach(function(el) {
                if (el.dataset.playerBound === '1') return;
                el.dataset.playerBound = '1';
                var id = el.id;
                var endId = el.dataset.endId || id;
                try {
                    var player = new YT.Player(id, {
                        events: {
                            'onStateChange': function(e) {
                                if (e && e.data === YT.PlayerState.ENDED) {
                                    window.dispatchEvent(new CustomEvent(
                                        'module-video-ended', {
                                            detail: {
                                                id: endId
                                            }
                                        }));
                                }
                            }
                        }
                    });
                    window.__ytPlayers[id] = player;
                } catch (err) {
                    // no-op
                }
            });
        };

        function loadYtApi() {
            if (window.YT && YT.Player) {
                window.__initYtPlayers();
                return;
            }
            if (window.__ytApiLoading) return;
            window.__ytApiLoading = true;
            var tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            var firstScriptTag = document.getElementsByTagName('script')[0];
            if (firstScriptTag && firstScriptTag.parentNode) {
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            } else {
                document.head.appendChild(tag);
            }
        }

        window.onYouTubeIframeAPIReady = function() {
            window.__ytApiReady = true;
            window.__initYtPlayers();
        };

        // Initialize on common lifecycle hooks
        document.addEventListener('DOMContentLoaded', loadYtApi, {
            once: true
        });
        window.addEventListener('load', loadYtApi, {
            once: true
        });
        document.addEventListener('livewire:load', loadYtApi);
        document.addEventListener('livewire:navigated', function() {
            // Re-load/init after Livewire DOM updates
            if (window.__ytApiReady) {
                window.__initYtPlayers();
            } else {
                loadYtApi();
            }
        });

        // Fallback: observe DOM insertions to bind newly added players
        if ('MutationObserver' in window) {
            var mo = new MutationObserver(function(mutations) {
                if (!(window.YT && YT.Player)) return;
                for (var i = 0; i < mutations.length; i++) {
                    var m = mutations[i];
                    if (!m.addedNodes) continue;
                    for (var j = 0; j < m.addedNodes.length; j++) {
                        var n = m.addedNodes[j];
                        if (n.nodeType !== 1) continue;
                        if ((n.matches && n.matches('iframe.yt-player')) || (n.querySelector && n
                                .querySelector('iframe.yt-player'))) {
                            window.__initYtPlayers();
                            return;
                        }
                    }
                }
            });
            mo.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    })();
</script>
