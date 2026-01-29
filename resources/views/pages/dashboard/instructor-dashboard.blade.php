<div>
    {{-- Quick Action Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Instructor Daily Record Card --}}
        <a href="{{ route('reports.instructor-daily-record') }}" wire:navigate
            class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:border-primary/30 transition-all duration-200">
            <div class="flex items-center gap-4">
                <div
                    class="p-3 bg-gradient-to-br from-primary to-primary/80 rounded-xl group-hover:scale-105 transition-transform">
                    <x-mary-icon name="o-clipboard-document-list" class="w-7 h-7 text-white" />
                </div>
                <div class="flex-1">
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary transition-colors">
                        Instructor Daily Record
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Record and track your daily teaching activities
                    </p>
                </div>
                <x-mary-icon name="o-chevron-right"
                    class="w-5 h-5 text-gray-400 group-hover:text-primary group-hover:translate-x-1 transition-all" />
            </div>
        </a>

        {{-- Pending Survey 1 Card --}}
        <a href="{{ route('instructor.survey1.pending') }}" wire:navigate
            class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:border-amber-400/40 transition-all duration-200">
            <div class="flex items-center gap-4">
                <div
                    class="p-3 bg-gradient-to-br from-amber-500 to-amber-500/80 rounded-xl group-hover:scale-105 transition-transform">
                    <x-mary-icon name="o-clipboard-document-check" class="w-7 h-7 text-white" />
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-amber-600 transition-colors">
                            Pending Survey 1
                        </h3>
                        <span class="badge badge-warning badge-soft whitespace-nowrap">
                            {{ (int) ($pendingSurvey1Count ?? 0) }} belum isi
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">See participants who haven’t completed Survey 1
                    </p>
                </div>
                <x-mary-icon name="o-chevron-right"
                    class="w-5 h-5 text-gray-400 group-hover:text-amber-600 group-hover:translate-x-1 transition-all" />
            </div>
        </a>
    </div>

    {{-- Main Content: single column layout (calendar removed) --}}
    <div class="grid grid-cols-1 gap-6">
        {{-- Main cards: Upcoming Schedule + Test Review side-by-side on large screens --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Upcoming Schedule List --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-secondary/10 rounded-lg">
                            <x-mary-icon name="o-clock" class="w-5 h-5 text-secondary" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upcoming Schedule</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Your next training sessions</p>
                        </div>
                    </div>
                    <a href="{{ route('training-schedule.index') }}" wire:navigate
                        class="text-sm text-secondary hover:text-secondary/80 font-medium flex items-center gap-1 transition-colors">
                        View All
                        <x-mary-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>

                {{-- Schedule List with max height and scroll --}}
                @if (count($upcomingSchedules) > 0)
                    <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1 custom-scrollbar">
                        @foreach ($upcomingSchedules as $schedule)
                            @php
                                $isCertification = ($schedule['schedule_type'] ?? 'training') === 'certification';
                                $badgeColor = $isCertification ? 'bg-amber-500' : 'bg-secondary';
                                $badgeColorLight = $isCertification ? 'bg-amber-500/90' : 'bg-secondary/90';
                            @endphp
                            @if (!$isCertification)
                                <a href="{{ route('training-schedule.index', ['training_id' => $schedule['id']]) }}"
                                    wire:navigate
                                    class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-secondary/5 dark:hover:bg-secondary/10 transition-colors">
                                @else
                                    <div
                                        class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            @endif
                            {{-- Date Badge --}}
                            <div class="flex-shrink-0 text-center">
                                <div class="w-12 {{ $badgeColor }} rounded-lg overflow-hidden shadow-sm">
                                    <div class="{{ $badgeColorLight }} text-white text-[10px] font-medium py-0.5">
                                        {{ \Carbon\Carbon::parse($schedule['start_date'])->format('M') }}
                                    </div>
                                    <div
                                        class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-lg font-bold py-1.5">
                                        {{ \Carbon\Carbon::parse($schedule['start_date'])->format('d') }}
                                    </div>
                                </div>
                            </div>

                            {{-- Schedule Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $schedule['name'] }}
                                        @if ($isCertification && !empty($schedule['session_type_label']))
                                            <span class="font-normal text-gray-500 dark:text-gray-400">-
                                                {{ $schedule['session_type_label'] }}</span>
                                        @endif
                                    </h4>
                                    {{-- Schedule Type Badge --}}
                                    @if ($isCertification)
                                        <span
                                            class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                            Certification
                                        </span>
                                    @else
                                        <span
                                            class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            Training
                                        </span>
                                    @endif
                                </div>
                                <div
                                    class="mt-1 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <x-mary-icon name="o-calendar" class="w-3.5 h-3.5" />
                                        @if (!empty($schedule['end_date']) && $schedule['end_date'] !== $schedule['start_date'])
                                            {{ \Carbon\Carbon::parse($schedule['start_date'])->format('d') }} -
                                            {{ \Carbon\Carbon::parse($schedule['end_date'])->format('d M Y') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($schedule['start_date'])->format('l, d M Y') }}
                                        @endif
                                    </span>
                                    @if (!empty($schedule['location']))
                                        <span class="flex items-center gap-1">
                                            <x-mary-icon name="o-map-pin" class="w-3.5 h-3.5" />
                                            {{ $schedule['location'] }}
                                        </span>
                                    @endif
                                    @if (!empty($schedule['time']))
                                        <span class="flex items-center gap-1">
                                            <x-mary-icon name="o-clock" class="w-3.5 h-3.5" />
                                            {{ $schedule['time'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Type Badge (In-House/Out-House for Training) --}}
                            @if (!$isCertification && !empty($schedule['type']))
                                @php
                                    $typeBadgeClass = match ($schedule['type']) {
                                        'IN' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'OUT' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        'LMS'
                                            => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                        'BLENDED'
                                            => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                    $typeLabel = match ($schedule['type']) {
                                        'IN' => 'In-House',
                                        'OUT' => 'Out-House',
                                        'LMS' => 'LMS',
                                        'BLENDED' => 'Blended',
                                        default => $schedule['type'],
                                    };
                                @endphp
                                <span
                                    class="flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-medium {{ $typeBadgeClass }}">
                                    {{ $typeLabel }}
                                </span>
                            @endif
                            @if (!$isCertification)
                                </a>
                            @else
                    </div>
                @endif
                @endforeach
            </div>

            {{-- Show "and X more" if there are more schedules --}}
            @if ($totalUpcomingCount > 5)
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('training-schedule.index') }}" wire:navigate
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-secondary transition-colors flex items-center justify-center gap-1">
                        <x-mary-icon name="o-plus-circle" class="w-4 h-4" />
                        and {{ $totalUpcomingCount - 5 }} more schedules
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-10">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                    <x-mary-icon name="o-calendar" class="w-8 h-8 text-gray-400" />
                </div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No Upcoming Schedule</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">You don't have any scheduled sessions.</p>
            </div>
            @endif
        </div>

        {{-- Test Review Section --}}
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 h-full flex flex-col">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-warning/10 rounded-lg">
                        <x-mary-icon name="o-document-magnifying-glass" class="w-5 h-5 text-warning" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tests Need Review</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Trainings with pending test submissions
                        </p>
                    </div>
                </div>
                <a href="{{ route('test-review.index') }}" wire:navigate
                    class="text-sm text-warning hover:text-warning/80 font-medium flex items-center gap-1 transition-colors">
                    View All
                    <x-mary-icon name="o-arrow-right" class="w-4 h-4" />
                </a>
            </div>

            @if (count($trainingsNeedReview) > 0)
                <div class="space-y-3">
                    @foreach ($trainingsNeedReview as $training)
                        <a href="{{ route('test-review.participants', $training['id']) }}" wire:navigate
                            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-warning/5 dark:hover:bg-warning/10 transition-colors group">
                            <div class="flex items-center gap-3 min-w-0">
                                {{-- Training Type Badge --}}
                                <div class="flex-shrink-0">
                                    @php
                                        $typeClass = match ($training['type']) {
                                            'IN'
                                                => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                                            'OUT'
                                                => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                                            'LMS'
                                                => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400',
                                            'BLENDED'
                                                => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                                            default => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                                        };
                                        $typeLabel = match ($training['type']) {
                                            'IN' => 'IN',
                                            'OUT' => 'OUT',
                                            'LMS' => 'LMS',
                                            'BLENDED' => 'BLD',
                                            default => $training['type'],
                                        };
                                    @endphp
                                    <span
                                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg {{ $typeClass }} text-xs font-bold">
                                        {{ $typeLabel }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <h4
                                        class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-warning transition-colors">
                                        {{ $training['name'] }}
                                    </h4>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        @if ($training['has_pretest'])
                                            <span
                                                class="text-[10px] px-1.5 py-0.5 rounded bg-primary/10 text-primary font-medium">Pre</span>
                                        @endif
                                        @if ($training['has_posttest'])
                                            <span
                                                class="text-[10px] px-1.5 py-0.5 rounded bg-secondary/10 text-secondary font-medium">Post</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Need Review Count Badge --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-warning/10 text-warning">
                                    <x-mary-icon name="o-clock" class="w-3.5 h-3.5" />
                                    {{ $training['need_review_count'] }} pending
                                </span>
                                <x-mary-icon name="o-chevron-right"
                                    class="w-4 h-4 text-gray-400 group-hover:text-warning group-hover:translate-x-0.5 transition-all" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-center py-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-success/10 rounded-full mb-3">
                        <x-mary-icon name="o-check-circle" class="w-7 h-7 text-success" />
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">All Caught Up!</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No tests pending review at the moment.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Note: Calendar removed from instructor dashboard per request --}}
</div>
</div>
