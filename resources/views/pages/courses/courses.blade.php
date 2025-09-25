<div x-data="{ mode: (localStorage.getItem('coursesViewMode') ?? 'grid') }" x-init="$watch('mode', v => localStorage.setItem('coursesViewMode', v))" x-cloak>
    {{-- Header --}}
    <div class="w-full grid gap-6 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2">
        {{-- Title --}}
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start lg:col-span-2 lg:mb-4">
            Courses
        </h1>

        <div class="flex items-center gap-4 justify-center lg:justify-start order-2 lg:order-1">
            {{-- Toggle Buttons --}}
            <div
                class="relative inline-flex items-center rounded-full p-0.5 bg-gradient-to-tr from-[#1F2E5C] to-[#3F63A5] shadow-sm overflow-hidden select-none">
                {{-- Sliding Pill --}}
                <span
                    class="absolute left-1 top-1 bottom-1 w-[calc(50%-0.25rem)] rounded-full bg-white shadow transition-transform duration-300 ease-out"
                    :class="mode === 'list' ? 'translate-x-full' : 'translate-x-0'"></span>

                {{-- Grid Button --}}
                <button type="button"
                    class="relative z-10 h-10 w-24 px-3 flex items-center justify-center gap-2 transition-colors"
                    :class="mode === 'grid' ? '!text-[#123456]' : '!text-white/85'" @click="mode='grid'"
                    role="button" :aria-pressed="mode === 'grid'">
                    <x-icon name="o-squares-2x2" class="size-4 text-current transition-colors" />
                    <span class="text-sm font-medium">Grid</span>
                </button>

                {{-- List Button --}}
                <button type="button"
                    class="relative z-10 h-10 w-24 px-3 flex items-center justify-center gap-2 transition-colors"
                    :class="mode === 'list' ? '!text-[#123456]' : '!text-white/85'" @click="mode='list'"
                    role="button" :aria-pressed="mode === 'list'">
                    <x-icon name="o-bars-3" class="size-4 text-current transition-colors" />
                    <span class="text-sm font-medium">List</span>
                </button>
            </div>

            {{-- Results Text --}}
            @if (!empty($resultsText))
                <span class="text-sm text-gray-600 hidden md:inline">{{ $resultsText }}</span>
            @endif
        </div>

        {{-- Row 2: Right - Search + Filter --}}
        <div class="flex items-center justify-center lg:justify-end gap-4 order-1 lg:order-2">
            {{-- Search --}}
            <x-search-input placeholder="Search..." class="max-w-72" input-class="!rounded-full"
                wire:model.live="search" />

            {{-- Filter --}}
            <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                placeholder="Filter"
                class="!w-30 !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer rounded-full shadow-sm [&_svg]:!opacity-100"
                icon-right="o-funnel" />
        </div>
    </div>

    {{-- Courses list --}}
    @php
        $isPaginator = isset($courses) && is_object($courses) && method_exists($courses, 'total');
        $hasItems = $isPaginator ? $courses->total() > 0 : !empty($courses);
    @endphp

    @if ($hasItems)
        {{-- GRID MODE --}}
        <div x-show="mode === 'grid'" class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
            @foreach ($courses as $course)
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                    @if (!empty($course->thumbnail_url))
                        <img src="{{ Storage::url($course->thumbnail_url) }}" alt="{{ $course->title }}"
                            class="w-full h-40 object-cover" loading="lazy" />
                    @else
                        <div class="w-full h-40 bg-gradient-to-br from-gray-100 to-gray-200"></div>
                    @endif
                    <div class="p-4">
                        <div class="text-xs text-gray-500 mb-1">{{ $course->training->group_comp }}</div>
                        <div class="font-semibold text-gray-900 line-clamp-2">{{ $course->title }}</div>
                        <div class="mt-5 text-xs text-gray-500 justify-content-end">Course Overview →</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- LIST MODE --}}
        <div x-show="mode === 'list'" class="mt-4 grid grid-cols-1 gap-4">
            @foreach ($courses as $course)
                <div
                    class="rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition bg-gradient-to-r from-[#5aa7ff1a] to-white">
                    <div class="flex items-stretch gap-4 p-4">
                        {{-- Thumbnail --}}
                        <div class="shrink-0">
                            @if (!empty($course->thumbnail_url))
                                <img src="{{ Storage::url($course->thumbnail_url) }}" alt="{{ $course->title }}"
                                    class="w-52 h-40 object-cover rounded-xl" loading="lazy" />
                            @else
                                <div class="w-52 h-40 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl"></div>
                            @endif
                        </div>
                        {{-- Content --}}
                        <div class="flex-1 flex items-center">
                            <div>
                                <div class="text-sm text-gray-500 mb-1">{{ $course->training->group_comp }}</div>
                                <div class="text-2xl font-semibold text-gray-900">{{ $course->title }}</div>
                            </div>
                        </div>
                        {{-- Action --}}
                        <div class="flex items-center justify-end min-w-40">
                            <div class="text-sm text-gray-600">Course Overview →</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($isPaginator)
            <div class="mt-6">{{ $courses->links() }}</div>
        @endif
    @else
        <div class="mt-8 text-center text-gray-500">No courses found.</div>
    @endif
</div>
