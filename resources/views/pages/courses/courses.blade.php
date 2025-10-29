<div x-data="{ mode: (localStorage.getItem('coursesViewMode') ?? 'grid'), perPage: @entangle('perPage').live }" x-init="$watch('mode', v => localStorage.setItem('coursesViewMode', v));
if (window.matchMedia('(max-width: 639px)').matches) { $wire.set('perPage', 8) }" x-cloak>
    {{-- Header --}}
    <div class="w-full grid gap-4 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2">
        <h1 class="text-primary text-3xl md:text-4xl font-bold text-center lg:text-start lg:col-span-2 lg:mb-2">
            Courses
        </h1>

        <div class="flex items-center gap-3 sm:gap-4 justify-center lg:justify-start order-2 lg:order-1">
            {{-- Toggle Grid/List --}}
            <div class="hidden sm:inline-flex relative items-center rounded-full p-0.5 bg-gradient-to-tr from-[#1F2E5C] to-[#3F63A5] shadow-sm overflow-hidden select-none"
                role="tablist" aria-label="View mode selector">
                <span
                    class="absolute left-1 top-1 bottom-1 w-[calc(50%-0.25rem)] rounded-full bg-white shadow transition-transform duration-300 ease-out"
                    :class="mode === 'list' ? 'translate-x-full' : 'translate-x-0'"></span>

                <button type="button"
                    class="relative z-10 h-9 sm:h-10 w-20 sm:w-24 px-3 flex items-center justify-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-primary/50 rounded-full"
                    :class="mode === 'grid' ? '!text-[#123456]' : '!text-white/85'" @click="mode='grid'"
                    role="tab" :aria-selected="mode === 'grid'" :tabindex="mode === 'grid' ? 0 : -1">
                    <x-icon name="o-squares-2x2" class="size-4 text-current transition-colors" />
                    <span class="text-sm font-medium">Grid</span>
                </button>

                <button type="button"
                    class="relative z-10 h-9 sm:h-10 w-20 sm:w-24 px-3 flex items-center justify-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-primary/50 rounded-full"
                    :class="mode === 'list' ? '!text-[#123456]' : '!text-white/85'" @click="mode='list'"
                    role="tab" :aria-selected="mode === 'list'" :tabindex="mode === 'list' ? 0 : -1">
                    <x-icon name="o-bars-3" class="size-4 text-current transition-colors" />
                    <span class="text-sm font-medium">List</span>
                </button>
            </div>

            @if (!empty($resultsText))
                <span class="text-sm text-gray-600 hidden md:inline" aria-live="polite">{{ $resultsText }}</span>
            @endif
        </div>

        {{-- Search & Filter --}}
        <div class="flex items-center justify-center lg:justify-end gap-2 sm:gap-4 order-1 lg:order-2">
            <x-search-input placeholder="Search..." class="w-full max-w-72" input-class="!rounded-full"
                wire:model.live="search" />

            <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                placeholder="All"
                class="!w-30 !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer rounded-full shadow-sm [&_svg]:!opacity-100"
                icon-right="o-funnel" />
        </div>
    </div>

    {{-- Courses list --}}
    @php
        $isPaginator = isset($courses) && is_object($courses) && method_exists($courses, 'total');
        $hasItems = $isPaginator ? $courses->total() > 0 : !empty($courses);
    @endphp

    {{-- GRID MODE --}}
    <div x-show="mode === 'grid'">
        @if ($hasItems)
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6">
                @foreach ($courses as $course)
                    <x-courses.grid-card :course="$course" wire:key="course-{{ $course->id }}" />
                @endforeach
            </div>
        @else
            <x-courses.empty-state />
        @endif
    </div>

    {{-- LIST MODE --}}
    <div x-show="mode === 'list'">
        @if ($hasItems)
            <div class="mt-4 grid grid-cols-1 gap-4">
                @foreach ($courses as $course)
                    <x-courses.list-card :course="$course" wire:key="course-list-{{ $course->id }}" />
                @endforeach
            </div>
        @else
            <x-courses.empty-state />
        @endif
    </div>

    @if ($hasItems && $isPaginator)
        <div class="mt-10">{{ $courses->links() }}</div>
    @endif
</div>
