<div class="w-full" x-data>
    <div class="max-w-6xl mx-auto px-4 md:px-6">
        {{-- Breadcrumb (optional) --}}
        <nav class="text-xs mb-4 text-gray-500 flex items-center gap-1" aria-label="Breadcrumb">
            <a wire:navigate href="{{ route('courses.index') }}" class="hover:text-primary">Courses</a>
            <span>/</span>
            <span class="text-gray-500 line-clamp-1">{{ $course->training->group_comp }}</span>
            <span>/</span>
            <span class="text-gray-700 font-medium line-clamp-1">{{ $course->title }}</span>
        </nav>

        {{-- Title --}}
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 leading-tight">
            {{ $course->title }}
        </h1>

        {{-- Hero / Thumbnail --}}
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

                {{-- Modules List --}}
                <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base md:text-lg font-semibold">Learning Module</h2>
                        <span class="text-xs text-gray-500">Duration: {{ $durationDays }} days</span>
                    </div>
                    <ul class="divide-y divide-gray-200">
                        @forelse ($modules as $index => $module)
                            <li class="py-3 flex items-start gap-3">
                                <span
                                    class="mt-1 w-2 h-2 rounded-full bg-primary/60 flex-shrink-0 translate-y-1"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800">
                                        Chapter {{ $loop->iteration }}: {{ $module->title }}
                                    </p>
                                    <p class="text-xs text-gray-500 line-clamp-2 mt-1 flex items-center gap-4">
                                        <span class="inline-flex items-center gap-1">
                                            <x-icon name="o-play-circle" class="size-3 text-gray-400" />
                                            <span>1 Video</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <x-icon name="o-book-open" class="size-3 text-gray-400" />
                                            <span>2 Modules</span>
                                        </span>
                                    </p>
                                </div>
                                @if ($module->is_completed)
                                    <span
                                        class="inline-flex items-center gap-1 text-[10px] font-medium text-green-600 bg-green-50 border border-green-200 rounded-full px-2 py-0.5">
                                        Done
                                    </span>
                                @endif
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-gray-500">No learning modules yet.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            {{-- Right Sidebar --}}
            <aside class="space-y-5">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-base font-semibold mb-4">Course Content</h2>
                    <ol class="text-sm divide-y divide-gray-200 mb-5">
                        <li class="py-2 flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-gray-500">1.</span><span>Pretest</span>
                        </li>
                        <li class="py-2 flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-gray-500">2.</span><span>Learning Module</span>
                        </li>
                        <li class="py-2 flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-gray-500">3.</span><span>Posttest</span>
                        </li>
                        <li class="py-2 flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-gray-500">4.</span><span>Result</span>
                        </li>
                    </ol>
                    <button type="button"
                        class="w-full inline-flex items-center justify-center gap-2 text-sm font-medium text-white bg-primary hover:bg-primary/90 rounded-md h-10 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary">
                        Start Course
                    </button>
                </div>

                {{-- Course Info --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 text-sm">
                    <h3 class="text-sm font-semibold mb-3">Course Info</h3>
                    <ul class="space-y-2">
                        <li class="flex justify-between"><span class="text-gray-500">Group</span><span
                                class="font-medium">{{ $course->training->group_comp }}</span></li>
                        <li class="flex justify-between"><span class="text-gray-500">Duration</span><span
                                class="font-medium">{{ $durationDays }} days</span></li>
                        <li class="flex justify-between"><span class="text-gray-500">Learners</span><span
                                class="font-medium">{{ $assignUsers }}</span></li>
                        <li class="flex justify-between"><span class="text-gray-500">Chapter</span><span
                                class="font-medium">{{ $modulesCount }}</span></li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>
