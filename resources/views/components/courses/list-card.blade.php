@php
    if (!isset($course) || $course === null) {
        return;
    }
@endphp
<div wire:key="course-list-{{ $course->id }}"
    x-on:click="if(!$event.target.closest('[data-card-action]')) { (window.Livewire && Livewire.navigate) ? Livewire.navigate('{{ route('courses-overview.show', $course) }}') : window.location.assign('{{ route('courses-overview.show', $course) }}'); }"
    class="group cursor-pointer rounded-xl border border-gray-200/80 shadow-sm overflow-hidden hover:shadow-md transition bg-white">
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
                {{-- Title --}}
                <div class="mb-1 flex items-center gap-2 mt-3">
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-primary/5 text-primary px-2 py-0.5 text-[11px] font-medium">
                        {{ $course->group_comp }}
                    </span>
                </div>

                <div class="text-base sm:text-lg md:text-xl font-semibold text-gray-900 line-clamp-2">
                    {{ $course->title }}
                </div>

                {{-- Details --}}
                @php
                    $assignment = $course->userCourses->first() ?? null;
                    $hasProgress = $assignment && $assignment->status === 'in_progress';
                @endphp

                <div class="mt-auto @if ($hasProgress) mb-0 @else mb-3 @endif">
                    @php
                        $modulesCount = (int) ($course->learning_modules_count ?? 0);
                        $usersCount = (int) ($course->trainings
                            ? $course->trainings->flatMap->assessments->unique('employee_id')->count()
                            : 0);
                        $progress = (int) ($course->progress_percent ?? 0);
                    @endphp
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-gray-600">
                        <span class="inline-flex items-center gap-1 min-w-0">
                            <x-icon name="o-book-open" class="size-4 text-gray-500" />
                            <span>{{ $modulesCount }} {{ Str::plural('Chapter', $modulesCount) }}</span>
                        </span>
                        <span class="hidden md:inline h-3 w-px bg-gray-300/60"></span>
                        <span class="inline-flex items-center gap-1 min-w-0">
                            <x-icon name="o-user-group" class="size-4 text-gray-500" />
                            <span>{{ $usersCount }} {{ Str::plural('Learner', $usersCount) }}</span>
                        </span>
                    </div>

                    {{-- Progress --}}
                    @if ($progress > 0)
                        <div class="mt-3">
                            <div class="h-2 w-full rounded-full bg-gray-200/80 overflow-hidden" role="progressbar"
                                aria-label="Course progress" aria-valuemin="0" aria-valuemax="100"
                                aria-valuenow="{{ $progress }}" aria-valuetext="{{ $progress }} percent">
                                <div class="h-full bg-primary rounded-full transition-all"
                                    style="width: {{ $progress }}%"></div>
                            </div>
                            <div class="mt-1 text-xs text-gray-600 flex justify-between">
                                <span>{{ $progress !== 100 ? 'In Progress' : 'Completed' }}</span>
                                <span>{{ $progress }}%</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action (Mobile) --}}
                <div class="mt-5 sm:hidden">
                    <a wire:navigate href="{{ route('courses-overview.show', $course) }}" data-card-action
                        class="w-full inline-flex items-center justify-center gap-2 text-sm font-medium text-primary/90 hover:text-primary border border-primary/20 rounded-full px-3 py-2"
                        aria-label="Go to course overview" @click.stop>
                        <span>Course Overview</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Action (desktop) --}}
        <div class="hidden sm:flex items-end justify-end self-stretch min-w-40 pr-1 sm:pr-2 md:pr-3">
            @if ($progress === 100)
                <button type="button" data-card-action
                    class="inline-flex items-center gap-2 text-sm font-medium rounded-full px-3 py-2 border border-gray-300 cursor-default"
                    aria-label="See results (coming soon)" @click.stop>
                    <span>See Results</span>
                    <span aria-hidden="true">→</span>
                </button>
            @elseif ($progress > 0)
                <a wire:navigate href="{{ route('courses-modules.index', $course) }}" data-card-action
                    class="inline-flex items-center gap-2 text-sm font-medium rounded-full px-3 py-2 border border-gray-300"
                    aria-label="Continue learning" @click.stop>
                    <span>Continue Learning</span>
                    <span aria-hidden="true">→</span>
                </a>
            @else
                <a wire:navigate href="{{ route('courses-overview.show', $course) }}" data-card-action
                    class="inline-flex items-center gap-2 text-sm font-medium rounded-full px-3 py-2 border border-gray-300"
                    aria-label="Go to course overview" @click.stop>
                    <span>Course Overview</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endif
        </div>
    </div>
</div>
