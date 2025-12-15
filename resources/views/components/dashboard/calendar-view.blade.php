<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
    {{-- Header Navigation --}}
    <div class="flex items-center justify-between mb-6">
        {{-- Prev Button --}}
        <button wire:click="goToPrevMonth"
            class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        {{-- Month & Year Display --}}
        <button wire:click="openJumpModal"
            class="px-5 py-2 bg-secondary text-white rounded-full text-sm hover:bg-secondary/90 transition-colors">
            {{ $this->monthName }} {{ $currentYear }}
        </button>

        {{-- Next Button --}}
        <button wire:click="goToNextMonth"
            class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    {{-- Days of Week Header --}}
    <div class="grid grid-cols-7 gap-1 mb-2">
        @foreach (['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $dayName)
            <div class="text-center text-xs font-medium text-gray-400 dark:text-gray-500 py-2">
                {{ $dayName }}
            </div>
        @endforeach
    </div>

    {{-- Calendar Grid --}}
    <div class="grid grid-cols-7 gap-1">
        @foreach ($calendarDays as $dayData)
            <div class="relative aspect-square p-1" x-data="{ showDetail: false, showHint: false }">
                @if ($dayData['day'])
                    @php
                        // Filter out past events (only show today and future)
                        $futureEvents = array_filter($dayData['events'], function ($event) use ($dayData) {
                            return \Carbon\Carbon::parse($dayData['date'])->startOfDay() >= now()->startOfDay();
                        });
                        $hasEvents = count($futureEvents) > 0;
                    @endphp
                    <div class="h-full flex flex-col items-center justify-start pt-1 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ $hasEvents ? 'cursor-pointer' : 'cursor-default' }} {{ $dayData['isToday'] ? 'bg-secondary/10' : '' }}"
                        @if ($hasEvents)
                            @click="showDetail = !showDetail"
                            @mouseenter="showHint = true"
                            @mouseleave="showHint = false"
                        @endif
                        @click.outside="showDetail = false">

                        {{-- Day Number --}}
                        <span
                            class="text-sm {{ $dayData['isToday']
                                ? 'w-7 h-7 flex items-center justify-center bg-secondary text-white rounded-full'
                                : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $dayData['day'] }}
                        </span>

                        {{-- Event Indicators (dots for multiple events) - only for future/today events --}}
                        @if ($hasEvents)
                            <div class="flex flex-wrap justify-center gap-0.5 mt-1 w-full px-0.5">
                                @php
                                    $maxDots = 4;
                                    $eventsToShow = array_slice($futureEvents, 0, $maxDots);
                                    $remaining = count($futureEvents) - $maxDots;
                                @endphp
                                @foreach ($eventsToShow as $event)
                                    @php
                                        // Color based on event type: certification=orange, warning=amber, normal=blue
                                        $dotColor = match ($event['type'] ?? 'normal') {
                                            'certification' => 'bg-amber-500',
                                            'warning' => 'bg-yellow-400',
                                            default => 'bg-blue-400',
                                        };
                                    @endphp
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                @endforeach
                                @if ($remaining > 0)
                                    <span class="text-[8px] text-gray-400 leading-none">+{{ $remaining }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Hover Hint - Click to see detail --}}
                    @if ($hasEvents)
                        <div x-show="showHint && !showDetail" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                            x-cloak
                            class="absolute z-40 left-1/2 -translate-x-1/2 bottom-full mb-1 px-2 py-1 bg-gray-700 dark:bg-gray-600 text-white text-[10px] rounded whitespace-nowrap pointer-events-none">
                            Click to see detail
                            <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-700 dark:border-t-gray-600"></div>
                        </div>

                        {{-- Detail Popup (on click) --}}
                        <div x-show="showDetail" x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            x-cloak
                            class="absolute z-50 left-1/2 -translate-x-1/2 bottom-full mb-2 w-64 bg-gray-800 dark:bg-gray-900 text-white rounded-lg shadow-lg p-3">
                            {{-- Close button --}}
                            <button @click="showDetail = false" class="absolute top-2 right-2 text-gray-400 hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <div
                                class="flex items-center justify-between text-xs font-semibold text-gray-200 mb-2 border-b border-gray-700 pb-2 pr-5">
                                <span>{{ \Carbon\Carbon::parse($dayData['date'])->format('M d, Y') }}</span>
                                @if (count($futureEvents) > 1)
                                    <span class="text-gray-400">{{ count($futureEvents) }} events</span>
                                @endif
                            </div>
                            <div
                                class="space-y-3 max-h-48 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-transparent">
                                @foreach ($futureEvents as $event)
                                    @php
                                        // Color based on event type
                                        $indicatorColor = match ($event['type'] ?? 'normal') {
                                            'certification' => 'bg-amber-500',
                                            'warning' => 'bg-yellow-400',
                                            default => 'bg-blue-400',
                                        };
                                        $categoryLabel = match ($event['category'] ?? 'training') {
                                            'certification' => 'Certification',
                                            default => 'Training',
                                        };
                                    @endphp
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-start gap-2">
                                            <span
                                                class="w-2 h-2 rounded-full flex-shrink-0 mt-1 {{ $indicatorColor }}"></span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-semibold text-white/90 truncate">
                                                    {{ $event['title'] }}</p>
                                                {{-- Category Badge --}}
                                                <span
                                                    class="inline-block mt-0.5 px-1.5 py-0.5 text-[9px] font-medium rounded {{ ($event['category'] ?? 'training') === 'certification' ? 'bg-amber-500/20 text-amber-300' : 'bg-blue-500/20 text-blue-300' }}">
                                                    {{ $categoryLabel }}
                                                </span>
                                                <div class="mt-1 space-y-0.5 text-[10px] text-gray-400">
                                                    <div class="flex items-center gap-1">
                                                        <svg class="w-3 h-3 flex-shrink-0" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        <span class="truncate">{{ $event['trainer'] }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        <svg class="w-3 h-3 flex-shrink-0" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        <span class="truncate">{{ $event['location'] }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        <svg class="w-3 h-3 flex-shrink-0" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span>{{ $event['time'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            {{-- Popup Arrow --}}
                            <div
                                class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800 dark:border-t-gray-900">
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    {{-- Today Button --}}
    <div class="mt-4 flex justify-center">
        <button wire:click="goToToday" class="text-xs text-secondary hover:text-secondary/80 font-medium">
            Go to Today
        </button>
    </div>

    {{-- Jump to Month/Year Modal --}}
    @if ($showJumpModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeJumpModal">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-5 w-72 mx-4"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Jump to Date</h3>

                <div class="space-y-4">
                    {{-- Month Select --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Month</label>
                        <select wire:model="jumpMonth"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-secondary focus:border-secondary">
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}">
                                    {{ \Carbon\Carbon::create(null, $month, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Year Select --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year</label>
                        <select wire:model="jumpYear"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-secondary focus:border-secondary">
                            @foreach (range(now()->year - 5, now()->year + 5) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Modal Actions --}}
                <div class="mt-5 flex gap-2">
                    <button wire:click="closeJumpModal"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="jumpTo"
                        class="flex-1 px-4 py-2 text-sm text-white bg-secondary rounded-lg hover:bg-secondary/90 transition-colors">
                        Go
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
