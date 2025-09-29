<div x-data="{ mode: (localStorage.getItem('coursesViewMode') ?? 'grid'), perPage: @entangle('perPage').live }" x-init="$watch('mode', v => localStorage.setItem('coursesViewMode', v));
if (window.matchMedia('(max-width: 639px)').matches) { $wire.set('perPage', 8) }" x-cloak>
    {{-- Header --}}
    <div class="w-full grid gap-4 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2">
        {{-- Title --}}
        <h1 class="text-primary text-3xl md:text-4xl font-bold text-center lg:text-start lg:col-span-2 lg:mb-2">
            Courses
        </h1>

        <div class="flex items-center gap-3 sm:gap-4 justify-center lg:justify-start order-2 lg:order-1">
            {{-- Toggle Buttons --}}
            <div class="hidden sm:inline-flex relative items-center rounded-full p-0.5 bg-gradient-to-tr from-[#1F2E5C] to-[#3F63A5] shadow-sm overflow-hidden select-none"
                role="tablist" aria-label="View mode selector">
                {{-- Sliding Pill --}}
                <span
                    class="absolute left-1 top-1 bottom-1 w-[calc(50%-0.25rem)] rounded-full bg-white shadow transition-transform duration-300 ease-out"
                    :class="mode === 'list' ? 'translate-x-full' : 'translate-x-0'"></span>

                {{-- Grid Button --}}
                <button type="button"
                    class="relative z-10 h-9 sm:h-10 w-20 sm:w-24 px-3 flex items-center justify-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-primary/50 rounded-full"
                    :class="mode === 'grid' ? '!text-[#123456]' : '!text-white/85'" @click="mode='grid'"
                    role="tab" :aria-selected="mode === 'grid'" :tabindex="mode === 'grid' ? 0 : -1">
                    <x-icon name="o-squares-2x2" class="size-4 text-current transition-colors" />
                    <span class="text-sm font-medium">Grid</span>
                </button>

                {{-- List Button --}}
                <button type="button"
                    class="relative z-10 h-9 sm:h-10 w-20 sm:w-24 px-3 flex items-center justify-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-primary/50 rounded-full"
                    :class="mode === 'list' ? '!text-[#123456]' : '!text-white/85'" @click="mode='list'"
                    role="tab" :aria-selected="mode === 'list'" :tabindex="mode === 'list' ? 0 : -1">
                    <x-icon name="o-bars-3" class="size-4 text-current transition-colors" />
                    <span class="text-sm font-medium">List</span>
                </button>
            </div>

            {{-- Results Text --}}
            @if (!empty($resultsText))
                <span class="text-sm text-gray-600 hidden md:inline" aria-live="polite">{{ $resultsText }}</span>
            @endif
        </div>

        {{-- Row 2: Right - Search + Filter --}}
        <div class="flex items-center justify-center lg:justify-end gap-2 sm:gap-4 order-1 lg:order-2">
            {{-- Search --}}
            <x-search-input placeholder="Search..." class="w-full max-w-72" input-class="!rounded-full"
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

    {{-- GRID MODE --}}
    <div x-show="mode === 'grid'">

        @if ($hasItems)
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6">
                @foreach ($courses as $course)
                    <div wire:key="course-{{ $course->id }}"
                        x-on:click="if(!$event.target.closest('[data-card-action]')) { (window.Livewire && Livewire.navigate) ? Livewire.navigate('{{ route('courses-overview.show', $course) }}') : window.location.assign('{{ route('courses-overview.show', $course) }}'); }"
                        class="group cursor-pointer bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden hover:shadow-md hover:border-primary/20 focus-within:ring-2 ring-primary/20 transition">
                        <div class="px-3 sm:px-4 pt-3 sm:pt-4">
                            <div class="w-full aspect-[16/9] bg-gray-100 overflow-hidden rounded-lg">
                                @if (!empty($course->thumbnail_url))
                                    <img src="{{ Storage::url($course->thumbnail_url) }}" alt="{{ $course->title }}"
                                        class="h-full w-full object-cover group-hover:scale-[1.02] transition-transform duration-300"
                                        loading="lazy" />
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-gray-100 to-gray-200"></div>
                                @endif
                            </div>
                        </div>
                        <div class="p-3 sm:p-4">
                            <div class="mb-1 flex items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-primary/5 text-primary px-2 py-0.5 text-[11px] font-medium">
                                    {{ $course->training->group_comp }}
                                </span>
                            </div>
                            <div class="font-semibold text-gray-900 line-clamp-2 min-h-[2.75rem]">{{ $course->title }}
                            </div>
                            <div class="mt-2 grid grid-cols-3 gap-2 text-[11px] sm:text-[12px] text-gray-600">
                                <span class="flex items-center gap-1 min-w-0 whitespace-nowrap">
                                    <x-icon name="o-book-open" class="size-4 text-gray-500 shrink-0" />
                                    <span class="truncate">{{ $course->learning_modules_count ?? 0 }} modules</span>
                                </span>
                                <span class="flex items-center gap-1 min-w-0 whitespace-nowrap">
                                    <x-icon name="o-user-group" class="size-4 text-gray-500 shrink-0" />
                                    <span class="truncate">{{ $course->users_count ?? 0 }} learners</span>
                                </span>
                                <span class="flex items-center gap-1 min-w-0 whitespace-nowrap">
                                    <x-icon name="o-clock" class="size-4 text-gray-500 shrink-0" />
                                    <span class="truncate">{{ $course->training->duration }} days</span>
                                </span>
                            </div>
                            @php
                                $assignment = $course->userCourses->first() ?? null;
                                $steps = max(1, (int) ($course->learning_modules_count ?? 0));
                                $current = (int) ($assignment->current_step ?? 0);
                                $progress = $steps > 0 ? min(100, max(0, (int) floor(($current / $steps) * 100))) : 0;

                                // hanya untuk keperluan demo
                                $showDemo = empty($assignment) && !empty($demoMode);
                                if ($showDemo) {
                                    // Synthetic progress for demo mode (variasi berdasarkan ID)
                                    $progress = min(95, max(10, (int) (((($course->id ?? 1) * 37) % 86) + 10)));
                                }

                            @endphp
                            @if ($assignment || $showDemo)
                                <!-- $showDemo hanya untuk keperluan demo -->
                                <div class="mt-3">
                                    <div class="h-2 w-full rounded-full bg-gray-200/80 overflow-hidden"
                                        role="progressbar" aria-label="Course progress" aria-valuemin="0"
                                        aria-valuemax="100" aria-valuenow="{{ $progress }}"
                                        aria-valuetext="{{ $progress }} percent">
                                        <div class="h-full bg-primary rounded-full transition-all"
                                            style="width: {{ $progress }}%"></div>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600 flex justify-between">
                                        <!-- $showDemo hanya untuk keperluan demo -->
                                        <span>{{ $showDemo ? 'Demo progress' : 'Progress' }}</span>
                                        <span>{{ $progress }}%</span>
                                    </div>
                                </div>
                            @endif
                            <a wire:navigate href="{{ route('courses-overview.show', $course) }}" data-card-action
                                class="mt-3 w-full inline-flex items-center justify-center gap-2 text-sm font-medium text-primary/90 hover:text-primary border border-primary/20 rounded-full px-3 py-2"
                                aria-label="Go to course overview" @click.stop>
                                <span>Course Overview</span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="w-full py-20">
                <div class="mx-auto text-center max-w-md">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                            <x-icon name="o-book-open" class="w-8 h-8 text-gray-400" />
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium text-gray-900">No courses found</h3>
                            <p class="text-sm text-gray-500">Try adjusting your search or filter criteria</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- LIST MODE --}}
    <div x-show="mode === 'list'">

        @if ($hasItems)
            <div class="mt-4 grid grid-cols-1 gap-4">
                @foreach ($courses as $course)
                    <div wire:key="course-list-{{ $course->id }}"
                        x-on:click="if(!$event.target.closest('[data-card-action]')) { (window.Livewire && Livewire.navigate) ? Livewire.navigate('{{ route('courses-overview.show', $course) }}') : window.location.assign('{{ route('courses-overview.show', $course) }}'); }"
                        class="group cursor-pointer rounded-xl border border-gray-200/80 shadow-sm overflow-hidden hover:shadow-md transition bg-white">
                        @php
                            $assignment = $course->userCourses->first() ?? null;
                            $steps = max(1, (int) ($course->learning_modules_count ?? 0));
                            $current = (int) ($assignment->current_step ?? 0);
                            $progress = $steps > 0 ? min(100, max(0, (int) floor(($current / $steps) * 100))) : 0;
                            $showDemo = empty($assignment) && !empty($demoMode);
                            if ($showDemo) {
                                $progress = min(95, max(10, (int) (((($course->id ?? 1) * 37) % 86) + 10)));
                            }
                            $hasProgress = $assignment || $showDemo;
                        @endphp
                        <div class="flex flex-col sm:flex-row items-stretch gap-3 sm:gap-4 p-3 sm:p-4">
                            {{-- Thumbnail --}}
                            <div
                                class="sm:shrink-0 w-full sm:w-44 md:w-56 aspect-[16/9] sm:aspect-[4/3] overflow-hidden rounded-lg bg-gray-100">
                                @if (!empty($course->thumbnail_url))
                                    <img src="{{ Storage::url($course->thumbnail_url) }}" alt="{{ $course->title }}"
                                        class="h-full w-full object-cover group-hover:scale-[1.02] transition-transform duration-300"
                                        loading="lazy" />
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-gray-100 to-gray-200"></div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0 sm:min-w-[16rem] flex">
                                <div class="w-full h-full flex flex-col ms-3">
                                    <div class="mb-1 flex items-center gap-2 mt-3">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-primary/5 text-primary px-2 py-0.5 text-[11px] font-medium">
                                            {{ $course->training->group_comp }}
                                        </span>
                                    </div>
                                    <div
                                        class="text-base sm:text-lg md:text-xl font-semibold text-gray-900 line-clamp-2">
                                        {{ $course->title }}</div>
                                    <div class="mt-auto @if ($hasProgress) mb-0 @else mb-3 @endif">
                                        <div
                                            class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-gray-600">
                                            <span class="inline-flex items-center gap-1">
                                                <x-icon name="o-book-open" class="size-4 text-gray-500" />
                                                <span>{{ $course->learning_modules_count ?? 0 }} modules</span>
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <x-icon name="o-user-group" class="size-4 text-gray-500" />
                                                <span>{{ $course->users_count ?? 0 }} learners</span>
                                            </span>
                                            @if ($course->training?->duration)
                                                <span class="inline-flex items-center gap-1">
                                                    <x-icon name="o-clock" class="size-4 text-gray-500" />
                                                    <span>{{ $course->training->duration }} days</span>
                                                </span>
                                            @endif
                                        </div>

                                        @if ($assignment || $showDemo)
                                            {{-- $showDemo hanya untuk keperluan demo --}}
                                            <div class="mt-3">
                                                <div class="h-2 w-full rounded-full bg-gray-200/80 overflow-hidden"
                                                    role="progressbar" aria-label="Course progress" aria-valuemin="0"
                                                    aria-valuemax="100" aria-valuenow="{{ $progress }}"
                                                    aria-valuetext="{{ $progress }} percent">
                                                    <div class="h-full bg-primary rounded-full transition-all"
                                                        style="width: {{ $progress }}%"></div>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-600 flex justify-between">
                                                    {{-- $showDemo hanya untuk keperluan demo --}}
                                                    <span>{{ $showDemo ? 'Demo progress' : 'Progress' }}</span>
                                                    <span>{{ $progress }}%</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Mobile CTA --}}
                                    <div class="mt-5 sm:hidden">
                                        <a wire:navigate href="{{ route('courses-overview.show', $course) }}"
                                            data-card-action
                                            class="w-full inline-flex items-center justify-center gap-2 text-sm font-medium text-primary/90 hover:text-primary border border-primary/20 rounded-full px-3 py-2"
                                            aria-label="Go to course overview" @click.stop>
                                            <span>Course Overview</span>
                                            <span aria-hidden="true">→</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Action (desktop) --}}
                            <div
                                class="hidden sm:flex items-end justify-end self-stretch min-w-40 pr-1 sm:pr-2 md:pr-3">
                                <a wire:navigate href="{{ route('courses-overview.show', $course) }}" data-card-action
                                    class="inline-flex items-center gap-2 text-sm font-medium text-primary/90 hover:text-primary px-3 py-2 rounded-full border border-primary/20"
                                    aria-label="Go to course overview" @click.stop>
                                    <span>Course Overview</span>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="w-full py-20">
                <div class="mx-auto text-center max-w-md">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                            <x-icon name="o-book-open" class="w-8 h-8 text-gray-400" />
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium text-gray-900">No courses found</h3>
                            <p class="text-sm text-gray-500">Try adjusting your search or filter criteria</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($hasItems && $isPaginator)
        <div class="mt-10">{{ $courses->links() }}</div>
    @endif
</div>
