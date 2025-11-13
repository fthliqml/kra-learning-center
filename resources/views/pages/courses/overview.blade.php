<div class="w-full" x-data>
    <div class="max-w-6xl mx-auto px-4 md:px-6">
        {{-- Breadcrumb --}}
        <nav class="text-xs mb-4 text-gray-500 flex items-center gap-1" aria-label="Breadcrumb">
            <a wire:navigate href="{{ route('courses.index') }}" class="hover:text-primary">Courses</a>
            <span>/</span>
            <span class="text-gray-500 line-clamp-1">{{ $course->group_comp }}</span>
            <span>/</span>
            <span class="text-gray-700 font-medium line-clamp-1">{{ $course->title }}</span>
        </nav>

        {{-- Title --}}
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 leading-tight">
            {{ $course->title }}
        </h1>

        {{-- Thumbnail --}}
        <div class="w-full mb-8">
            <div
                class="relative w-full aspect-[16/6] md:aspect-[16/5] rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                @if (!empty($course->thumbnail_url))
                    <img src="{{ Storage::url($course->thumbnail_url) }}" alt="{{ $course->title }} thumbnail"
                        class="h-full w-full object-cover" loading="lazy" />
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200"></div>
                @endif
                <div class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-black/30 to-transparent"></div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-6">
            {{-- Description --}}
            <div class="md:col-span-2 space-y-5">
                <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-base md:text-lg font-semibold mb-3">Description</h2>
                    <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
                        @if ($course->description)
                            <p class="whitespace-pre-line">{{ $course->description }}</p>
                        @else
                            <p class="text-gray-500 italic">No description provided for this course.</p>
                        @endif
                    </div>
                </section>

                {{-- Learning Modules List --}}
                <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base md:text-lg font-semibold">Learning Modules</h2>
                    </div>
                    @if ($modulesCount === 0)
                        <div class="py-6 text-center text-sm text-gray-500">No learning modules yet.</div>
                    @else
                        <x-accordion wire:model="accordion">
                            @foreach ($modules as $module)
                                @php
                                    $sectionCount = $module->derived_section_count ?? 0;
                                    $videoCount = $module->derived_video_count ?? 0;
                                    $readingCount = $module->derived_reading_count ?? 0;
                                    $counts = $module->formatted_counts ?? [];
                                @endphp
                                <x-collapse name="module-{{ $module->id }}" no-icon separator
                                    class="border rounded-lg mb-2 overflow-hidden">
                                    <x-slot:heading
                                        class="flex flex-col gap-2 px-4 pt-7 pb-0 md:flex-row md:items-start md:justify-between">
                                        <div class="flex items-start gap-2 min-w-0">
                                            <span
                                                class="text-sm font-medium text-gray-800 break-words">{{ $module->title }}</span>
                                            @if ($module->is_completed ?? false)
                                                <span
                                                    class="inline-flex items-center gap-1 text-[10px] font-medium text-green-600 bg-green-50 border border-green-200 rounded-full px-2 py-0.5 whitespace-nowrap">Done</span>
                                            @endif
                                        </div>
                                        <div
                                            class="hidden md:flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-medium text-gray-500 leading-none">
                                            <span class="inline-flex items-center gap-1 whitespace-nowrap">
                                                <x-icon name="o-folder" class="size-3 text-gray-400 relative top-px" />
                                                <span class="align-middle pt-1">{{ $sectionCount }}
                                                    {{ Str::plural('Section', $sectionCount) }}</span>
                                            </span>
                                            @if ($videoCount > 0)
                                                <span class="text-gray-300 align-middle">•</span>
                                                <span class="inline-flex items-center gap-1 whitespace-nowrap">
                                                    <x-icon name="o-play-circle"
                                                        class="size-3 text-gray-400 relative top-px" />
                                                    <span class="align-middle pt-1">{{ $videoCount }}
                                                        {{ Str::plural('Video', $videoCount) }}</span>
                                                </span>
                                            @endif
                                            @if ($readingCount > 0)
                                                <span class="text-gray-300 align-middle">•</span>
                                                <span class="inline-flex items-center gap-1 whitespace-nowrap">
                                                    <x-icon name="o-book-open"
                                                        class="size-3 text-gray-400 relative top-px" />
                                                    <span class="align-middle pt-1">{{ $readingCount }}
                                                        {{ Str::plural('Reading', $readingCount) }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </x-slot:heading>
                                    <x-slot:content class="bg-white px-4 pt-3 pb-4 text-xs text-gray-600 space-y-3">
                                        @if (($module->sections?->count() ?? 0) === 0)
                                            <p class="text-gray-400 italic">No sections in this module.</p>
                                        @else
                                            <ol class="space-y-5">
                                                @foreach ($module->sections as $sec)
                                                    @php
                                                        $resources = $sec->resources ?? collect();
                                                        $videoCount = $resources->where('content_type', 'yt')->count();
                                                        $readingCount = $resources
                                                            ->where('content_type', 'pdf')
                                                            ->count();
                                                    @endphp
                                                    <li class="flex items-start gap-2">
                                                        <x-icon name="o-folder-open"
                                                            class="hidden md:inline size-4 mt-0.5 text-primary/60" />
                                                        <div class="min-w-0 flex-1 space-y-0.5">
                                                            <p class="font-medium text-gray-800 truncate">
                                                                {{ $sec->title }}</p>
                                                            <div
                                                                class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-gray-500 leading-tight">
                                                                @if ($videoCount > 0)
                                                                    <span
                                                                        class="inline-flex items-center gap-1 whitespace-nowrap">
                                                                        {{ $videoCount }}
                                                                        {{ Str::plural('Video', $videoCount) }}
                                                                    </span>
                                                                @endif
                                                                @if ($readingCount > 0)
                                                                    @if ($videoCount > 0)
                                                                        <span
                                                                            class="text-gray-300 align-middle">•</span>
                                                                    @endif
                                                                    <span
                                                                        class="inline-flex items-center gap-1 whitespace-nowrap">
                                                                        {{ $readingCount }}
                                                                        {{ Str::plural('Reading', $readingCount) }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if ($sec->is_quiz_on)
                                                            <span
                                                                class="text-[10px] inline-flex items-center gap-1 text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-full px-2 py-0.5">Quiz</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ol>
                                        @endif
                                    </x-slot:content>
                                </x-collapse>
                            @endforeach
                        </x-accordion>
                    @endif
                </section>
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-5">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-base font-semibold mb-4">Course Content</h2>
                    <ol class="text-sm divide-y divide-gray-200 mb-5">
                        <li class="py-2 flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-gray-500">1.</span><span>Pre Test</span>
                        </li>
                        <li class="py-2 flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-gray-500">2.</span><span>Learning Module</span>
                        </li>
                        <li class="py-2 flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-gray-500">3.</span><span>Post Test</span>
                        </li>
                        <li class="py-2 flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-gray-500">4.</span><span>Result</span>
                        </li>
                    </ol>
                    @php $progress = (int) ($course->progressForUser() ?? 0); @endphp
                    @if ($progress === 100)
                        <a wire:navigate href="{{ route('courses-result.index', $course) }}"
                            class="w-full inline-flex items-center justify-center gap-2 text-sm font-medium text-white bg-primary hover:bg-primary/90 rounded-md h-10 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary"
                            aria-label="See results">
                            See Results
                        </a>
                    @elseif ($progress > 0)
                        <a wire:navigate href="{{ route('courses-modules.index', $course) }}"
                            class="w-full inline-flex items-center justify-center gap-2 text-sm font-medium text-white bg-primary hover:bg-primary/90 rounded-md h-10 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary"
                            aria-label="Continue learning">
                            Continue Learning
                        </a>
                    @else
                        <a wire:navigate href="{{ route('courses-pretest.index', $course) }}"
                            class="w-full inline-flex items-center justify-center gap-2 text-sm font-medium text-white bg-primary hover:bg-primary/90 rounded-md h-10 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary"
                            aria-label="Start course">
                            Start Course
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>
