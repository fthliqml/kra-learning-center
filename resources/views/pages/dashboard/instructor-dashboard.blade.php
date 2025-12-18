<div>
    {{-- Quick Action Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Training Schedule Card --}}
        <a href="{{ route('training-schedule.index') }}" wire:navigate
            class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:border-secondary/30 transition-all duration-200">
            <div class="flex items-center gap-4">
                <div
                    class="p-3 bg-gradient-to-br from-secondary to-secondary/80 rounded-xl group-hover:scale-105 transition-transform">
                    <x-mary-icon name="o-calendar-days" class="w-7 h-7 text-white" />
                </div>
                <div class="flex-1">
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-secondary transition-colors">
                        Training Schedule
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">View and manage your training schedules</p>
                </div>
                <x-mary-icon name="o-chevron-right"
                    class="w-5 h-5 text-gray-400 group-hover:text-secondary group-hover:translate-x-1 transition-all" />
            </div>
        </a>

        {{-- Instructor Daily Record Card --}}
        <a href="{{ route('reports.training-activity') }}" wire:navigate
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
    </div>

    {{-- Main Content: 2 Columns Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Upcoming Schedule (65-70%) --}}
        <div class="lg:col-span-2">
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

                {{-- Schedule List --}}
                @if (count($upcomingSchedules) > 0)
                    <div class="space-y-3">
                        @foreach ($upcomingSchedules as $schedule)
                            @php
                                $isCertification = ($schedule['schedule_type'] ?? 'training') === 'certification';
                                $badgeColor = $isCertification ? 'bg-amber-500' : 'bg-secondary';
                                $badgeColorLight = $isCertification ? 'bg-amber-500/90' : 'bg-secondary/90';
                            @endphp
                            <div
                                class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                {{-- Date Badge --}}
                                <div class="flex-shrink-0 text-center">
                                    <div class="w-14 {{ $badgeColor }} rounded-lg overflow-hidden shadow-sm">
                                        <div class="{{ $badgeColorLight }} text-white text-xs font-medium py-1">
                                            {{ \Carbon\Carbon::parse($schedule['start_date'])->format('M') }}
                                        </div>
                                        <div
                                            class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xl font-bold py-2">
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
                                        class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
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
                                            'IN'
                                                => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            'OUT'
                                                => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        };
                                        $typeLabel = match ($schedule['type']) {
                                            'IN' => 'In-House',
                                            'OUT' => 'Out-House',
                                            default => $schedule['type'],
                                        };
                                    @endphp
                                    <span
                                        class="flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-medium {{ $typeBadgeClass }}">
                                        {{ $typeLabel }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
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
        </div>

        {{-- Right Column: Calendar (30-35%) --}}
        <div class="lg:col-span-1">
            {{-- Calendar View (Mini) --}}
            <livewire:components.dashboard.calendar-view :events="$calendarEvents" />
        </div>
    </div>
</div>
