<div class="p-4 md:p-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Content: Video + Reading -->
        <main class="lg:col-span-12">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 md:p-6 space-y-6">
                @if ($activeSection)
                    <div class="flex items-center justify-between">
                        <h1 class="text-lg md:text-xl font-semibold text-gray-900">{{ $activeSection->title }}</h1>
                        <div class="flex gap-2">
                            <button wire:click="completeSubtopic"
                                class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-2 text-xs md:text-sm font-medium text-white hover:bg-emerald-700">
                                <x-icon name="o-check-circle" class="size-4" />
                                <span>Tandai Selesai</span>
                            </button>
                        </div>
                    </div>

                    @php
                        $hasVideo = ($videoResources->count() ?? 0) > 0;
                        $hasReading = ($readingResources->count() ?? 0) > 0;
                    @endphp

                    @if (!$hasVideo && !$hasReading)
                        <div class="p-6 border border-dashed rounded-md text-sm text-gray-500">
                            Belum ada konten untuk section ini.
                        </div>
                    @else
                        @if ($hasVideo)
                            <section class="space-y-3">
                                <h3 class="text-sm font-semibold text-gray-800">Video</h3>
                                <div class="grid gap-4">
                                    @foreach ($videoResources as $vid)
                                        <div class="aspect-video bg-black/5 rounded-md overflow-hidden">
                                            @if (Str::endsWith(strtolower($vid->url), ['.mp4', '.webm']))
                                                <video class="w-full h-full" controls src="{{ $vid->url }}"></video>
                                            @else
                                                <iframe class="w-full h-full" src="{{ $vid->url }}"
                                                    allowfullscreen></iframe>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($hasReading)
                            <section class="space-y-3">
                                <h3 class="text-sm font-semibold text-gray-800">Reading</h3>
                                <div class="space-y-4">
                                    @foreach ($readingResources as $doc)
                                        <article class="p-4 border rounded-md text-sm text-gray-800 bg-gray-50">
                                            @if ($doc->filename)
                                                <div class="font-medium text-gray-900 mb-1">{{ $doc->filename }}</div>
                                            @endif
                                            @if ($doc->url)
                                                <a href="{{ $doc->url }}" target="_blank"
                                                    class="text-primary hover:underline">Buka Dokumen</a>
                                            @else
                                                <div class="text-gray-600">Konten bacaan tersedia.</div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endif
                @else
                    <div class="p-6 border border-dashed rounded-md text-sm text-gray-500">
                        Pilih module dan section dari sidebar untuk mulai belajar.
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>
