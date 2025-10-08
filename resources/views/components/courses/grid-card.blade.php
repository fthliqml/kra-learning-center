<div wire:key="course-{{ $course->id }}"
    x-on:click="if(!$event.target.closest('[data-card-action]')) { (window.Livewire && Livewire.navigate) ? Livewire.navigate('{{ route('courses-overview.show', $course) }}') : window.location.assign('{{ route('courses-overview.show', $course) }}'); }"
    class="group cursor-pointer bg-white rounded-xl border border-gray-200/80 shadow-sm overflow-hidden hover:shadow-md hover:border-primary/20 focus-within:ring-2 ring-primary/20 transition">

    {{-- Thumbnail --}}
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

    {{-- Content --}}
    <div class="p-3 sm:p-4">
        {{-- Title --}}
        <div class="mb-1 flex items-center gap-2">
            <span
                class="inline-flex items-center gap-1 rounded-full bg-primary/5 text-primary px-2 py-0.5 text-[11px] font-medium">
                {{ $course->group_comp ?? '—' }}
            </span>
        </div>
        <div class="font-semibold text-gray-900 line-clamp-2 min-h-[2.5rem]">
            {{ $course->title }}
        </div>

        {{-- Details --}}
        @php
            $modulesCount = (int) ($course->learning_modules_count ?? 0);
            $usersCount = (int) ($course->users_count ?? 0);
        @endphp
        <!-- Desktop / Tablet Details -->
        <div
            class="mt-2 hidden sm:flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] sm:text-[12px] text-gray-600">
            <span class="inline-flex items-center gap-1 min-w-0">
                <x-icon name="o-book-open" class="size-4 text-gray-500 shrink-0" />
                <span>{{ $modulesCount }} {{ Str::plural('Topic', $modulesCount) }}</span>
            </span>
            <span class="hidden sm:inline h-3 w-px bg-gray-300/60"></span>
            <span class="inline-flex items-center gap-1 min-w-0">
                <x-icon name="o-user-group" class="size-4 text-gray-500 shrink-0" />
                <span>{{ $usersCount }} {{ Str::plural('Learner', $usersCount) }}</span>
            </span>
        </div>

        <!-- Mobile Compact Meta -->
        <div class="mt-1 flex sm:hidden items-center gap-2 text-[11px] text-gray-600">
            <x-icon name="o-book-open" class="size-1 text-gray-500 shrink-0" />
            <span>{{ $modulesCount }} {{ Str::plural('Topic', $modulesCount) }}</span>
            <span class="h-3 w-px bg-gray-300/60"></span>
            <x-icon name="o-user-group" class="size-1 text-gray-500 shrink-0" />
            <span>{{ $usersCount }} {{ Str::plural('Learner', $usersCount) }}</span>
        </div>

        {{-- Progress --}}
        @php
            $assignment = $course->userCourses->first();
            $progress = (int) ($course->progress_percent ?? 0);
        @endphp
        @if ($assignment ?? false)
            <div class="mt-2 sm:mt-3" x-data>
                <div class="h-1.5 sm:h-2 w-full rounded-full bg-gray-200/80 overflow-hidden" role="progressbar"
                    aria-label="Course progress" aria-valuemin="0" aria-valuemax="100"
                    aria-valuenow="{{ $progress }}" aria-valuetext="{{ $progress }} percent">
                    <div class="h-full bg-primary rounded-full transition-all" style="width: {{ $progress }}%">
                    </div>
                </div>
                <div class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-600 flex justify-between">
                    <span>{{ $progress !== 100 ? 'In Progress' : 'Completed' }}</span>
                    <span>{{ $progress }}%</span>
                </div>
            </div>
        @endif

        {{-- Action --}}
        <a wire:navigate href="{{ route('courses-overview.show', $course) }}" data-card-action
            class="mt-3 hidden sm:inline-flex w-full items-center justify-center gap-2 text-sm font-medium text-primary/90 hover:text-primary border border-primary/20 rounded-full px-3 py-2"
            aria-label="Go to course overview" @click.stop>
            <span>Course Overview</span>
            <span aria-hidden="true">→</span>
        </a>
    </div>
</div>
