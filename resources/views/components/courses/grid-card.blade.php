@php
    $userId = auth()->id();
    // Check if context is injected (from Courses list page)
    if (isset($course->training_context)) {
        $isBlended = ($course->training_context['type'] ?? '') === 'BLENDED';
        $isLMS = ($course->training_context['type'] ?? '') === 'LMS';
    } else {
        // Fallback logic for other pages
        $isBlended = $userId && $course->trainings()
            ->where('type', 'BLENDED')
            ->whereHas('assessments', fn($q) => $q->where('employee_id', $userId))
            ->exists();

        $isLMS = $userId && $course->trainings()
            ->where('type', 'LMS')
            ->whereHas('assessments', fn($q) => $q->where('employee_id', $userId))
            ->exists();
    }
@endphp
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
                {{ $course->competency->type ?? '—' }}
            </span>
            @if ($isBlended)
                <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 text-purple-700 border border-purple-300 px-2 py-0.5 text-[10px] font-medium">
                    Blended
                </span>
            @endif
            @if ($isLMS)
                <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-300 px-2 py-0.5 text-[10px] font-medium">
                    LMS
                </span>
            @endif
        </div>
        <div class="font-semibold text-gray-900 line-clamp-2 min-h-[2.5rem]">
            {{ $course->title }}
        </div>

        {{-- Details --}}
        @php
            $modulesCount = (int) ($course->learning_modules_count ?? 0);
            $usersCount = (int) ($course->trainings
                ? $course->trainings->flatMap->assessments->unique('employee_id')->count()
                : 0);
        @endphp
        <!-- Desktop / Tablet Details -->
        <div
            class="mt-4 hidden sm:flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] sm:text-[12px] text-gray-600">
            <span class="inline-flex items-center gap-1 min-w-0">
                <x-icon name="o-book-open" class="size-4 text-gray-500 shrink-0" />
                <span>{{ $modulesCount }} {{ Str::plural('Chapter', $modulesCount) }}</span>
            </span>
            <span class="hidden sm:inline h-3 w-px bg-gray-300/60"></span>
            <span class="inline-flex items-center gap-1 min-w-0">
                <x-icon name="o-user-group" class="size-4 text-gray-500 shrink-0" />
                <span>{{ $usersCount }} {{ Str::plural('Learner', $usersCount) }}</span>
            </span>
        </div>

        <!-- Mobile Compact Meta -->
        <div class="mt-3 flex sm:hidden items-center gap-2 text-[11px] text-gray-600">
            <x-icon name="o-book-open" class="size-1 text-gray-500 shrink-0" />
            <span>{{ $modulesCount }} {{ Str::plural('Chapter', $modulesCount) }}</span>
            <span class="h-3 w-px bg-gray-300/60"></span>
            <x-icon name="o-user-group" class="size-1 text-gray-500 shrink-0" />
            <span>{{ $usersCount }} {{ Str::plural('Learner', $usersCount) }}</span>
        </div>

        {{-- Progress --}}
        @php
            $assignment = $course->userCourses->first();
            $progress = (int) ($course->progress_percent ?? 0);
            
            // Determine posttest status for proper display
            $posttestStatus = 'not_attempted';
            $postTest = \App\Models\Test::where('course_id', $course->id)->where('type', 'posttest')->first();
            if ($postTest) {
                $latestAttempt = \App\Models\TestAttempt::where('test_id', $postTest->id)
                    ->where('user_id', auth()->id())
                    ->orderByDesc('submitted_at')->orderByDesc('id')
                    ->first();
                if ($latestAttempt) {
                    if ($latestAttempt->status === \App\Models\TestAttempt::STATUS_UNDER_REVIEW) {
                        $posttestStatus = 'under_review';
                    } elseif ($latestAttempt->is_passed) {
                        $posttestStatus = 'passed';
                    } else {
                        $posttestStatus = 'failed';
                    }
                }
            }
            
            $barColor = match($posttestStatus) {
                'passed' => 'bg-primary',
                'under_review' => 'bg-amber-500',
                'failed' => 'bg-red-500',
                default => 'bg-primary',
            };
        @endphp
        <div class="mt-2 sm:mt-3" x-data>
            <div class="h-1.5 sm:h-2 w-full rounded-full bg-gray-200/80 overflow-hidden" role="progressbar"
                aria-label="Course progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}"
                aria-valuetext="{{ $progress }} percent">
                <div class="h-full {{ $barColor }} rounded-full transition-all" style="width: {{ $progress }}%">
                </div>
            </div>
            <div class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-600 flex justify-between">
                <span>
                    @if ($posttestStatus === 'passed')
                        <span class="text-green-600 font-medium">Completed</span>
                    @elseif($posttestStatus === 'under_review')
                        <span class="text-amber-600 font-medium">Awaiting Review</span>
                    @elseif($posttestStatus === 'failed')
                        <span class="text-red-600 font-medium">Retry Required</span>
                    @elseif($progress === 0)
                        Not Started
                    @else
                        In Progress
                    @endif
                </span>
                <span>{{ $progress }}%</span>
            </div>
        </div>

        {{-- Action --}}
        @if ($posttestStatus === 'passed')
            <a wire:navigate href="{{ route('courses-result.index', $course) }}" data-card-action
                class="mt-3 inline-flex w-full items-center justify-center gap-2 text-sm font-medium rounded-full px-3 py-2 border border-green-300 bg-green-50 text-green-700"
                aria-label="See results" @click.stop>
                <span>See Results</span>
                <span aria-hidden="true">→</span>
            </a>
        @elseif ($posttestStatus === 'under_review')
            <a wire:navigate href="{{ route('courses-result.index', $course) }}" data-card-action
                class="mt-3 inline-flex w-full items-center justify-center gap-2 text-sm font-medium rounded-full px-3 py-2 border border-amber-300 bg-amber-50 text-amber-700"
                aria-label="View status" @click.stop>
                <span>View Status</span>
                <span aria-hidden="true">→</span>
            </a>
        @elseif ($posttestStatus === 'failed')
            <a wire:navigate href="{{ route('courses-result.index', $course) }}" data-card-action
                class="mt-3 inline-flex w-full items-center justify-center gap-2 text-sm font-medium rounded-full px-3 py-2 border border-red-300 bg-red-50 text-red-700"
                aria-label="Retry test" @click.stop>
                <span>Retry Test</span>
                <span aria-hidden="true">→</span>
            </a>
        @elseif ($progress > 0)
            <a wire:navigate href="{{ route('courses-modules.index', $course) }}" data-card-action
                class="mt-3 inline-flex w-full items-center justify-center gap-2 text-sm font-medium rounded-full px-3 py-2 border border-gray-300"
                aria-label="Continue learning" @click.stop>
                <span>Continue Learning</span>
                <span aria-hidden="true">→</span>
            </a>
        @else
            <a wire:navigate href="{{ route('courses-overview.show', $course) }}" data-card-action
                class="mt-3 inline-flex w-full items-center justify-center gap-2 text-sm font-medium rounded-full px-3 py-2 border border-gray-300"
                aria-label="Go to course overview" @click.stop>
                <span>Course Overview</span>
                <span aria-hidden="true">→</span>
            </a>
        @endif
    </div>
</div>
