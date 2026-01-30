<div>
    {{-- Main Content: 2 Columns Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Stats Cards + Chart --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Stats Cards (2 cards) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Total Survey Pending (Survey 1 & 3) --}}
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Survey Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1 whitespace-nowrap">
                                {{ (int) ($totalPendingSurveys ?? 0) }}
                                <span class="text-base font-normal text-gray-400">responses</span>
                            </p>
                        </div>
                        <div class="p-3 bg-secondary rounded-xl">
                            <x-mary-icon name="o-clipboard-document-check" class="w-7 h-7 text-white" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-sm">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-gray-500 dark:text-gray-400">Survey 1 &amp; Survey 3</span>
                            <a href="{{ route('admin.pending-surveys.index') }}" wire:navigate
                                class="text-sm text-secondary hover:text-secondary/80 font-medium flex items-center gap-1 transition-colors">
                                See all
                                <x-mary-icon name="o-arrow-right" class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Total Employees --}}
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Employees</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1 whitespace-nowrap">
                                {{ $totalEmployees }}
                                <span class="text-base font-normal text-gray-400">employees</span>
                            </p>
                        </div>
                        <div class="p-3 bg-secondary rounded-xl">
                            <x-mary-icon name="o-users" class="w-7 h-7 text-white" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <x-mary-icon name="o-information-circle" class="w-4 h-4 mr-1" />
                        <span>Active employees</span>
                    </div>
                </div>
            </div>

            {{-- Training Chart --}}
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Monthly Training</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Training statistics for year
                            {{ $selectedYear }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- Year Navigation --}}
                        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                            {{-- Previous Year Button --}}
                            <button wire:click="previousYear"
                                class="p-1.5 rounded-md hover:bg-white dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                @if ($selectedYear <= now()->year - 5) disabled @endif title="Previous Year">
                                <x-mary-icon name="o-chevron-left" class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                            </button>

                            {{-- Year Selector Dropdown (MaryUI) --}}
                            @php
                                $yearOptions = collect($this->availableYears)->map(
                                    fn($y) => ['id' => $y, 'name' => (string) $y],
                                );
                            @endphp
                            <x-select wire:model.live="selectedYear" :options="$yearOptions"
                                class="select-sm !min-h-0 !h-8 !w-24 !border-0 bg-white dark:bg-gray-600 font-semibold" />

                            {{-- Next Year Button --}}
                            <button wire:click="nextYear"
                                class="p-1.5 rounded-md hover:bg-white dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                @if ($selectedYear >= now()->year) disabled @endif title="Next Year">
                                <x-mary-icon name="o-chevron-right" class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                            </button>
                        </div>

                        {{-- Legend Badge --}}
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-secondary text-white">
                            <span class="w-2 h-2 rounded-full bg-white mr-2"></span>
                            Training Count
                        </span>
                    </div>
                </div>

                {{-- ApexCharts Container --}}
                <div id="training-chart" class="w-full min-w-0" wire:ignore></div>

                {{-- Hint for clicking data points --}}
                @if (!$selectedMonth)
                    <div class="mt-3 flex items-center justify-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                        <x-mary-icon name="o-cursor-arrow-rays" class="w-4 h-4" />
                        <span>Click on any data point to view monthly breakdown details</span>
                    </div>
                @endif

                {{-- Selected Month Info --}}
                @if ($selectedMonth)
                    <div x-data="{ expanded: true }"
                        class="mt-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg overflow-hidden">
                        {{-- Header - Clickable to toggle --}}
                        <div class="p-4 flex items-center justify-between cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors"
                            @click="expanded = !expanded">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-200 dark:bg-indigo-800 rounded-lg">
                                    <x-mary-icon name="o-calendar"
                                        class="w-5 h-5 text-indigo-600 dark:text-indigo-300" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                                        {{ \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y') }}
                                    </p>
                                    <p class="text-xs text-indigo-600 dark:text-indigo-400">
                                        {{ $monthlyTrainingData[$selectedMonth - 1] ?? 0 }} Trainings
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-indigo-500 dark:text-indigo-400"
                                    x-text="expanded ? 'Hide details' : 'Show details'"></span>
                                <x-mary-icon name="o-chevron-down"
                                    class="w-5 h-5 text-indigo-500 dark:text-indigo-400 transition-transform duration-200"
                                    x-bind:class="expanded ? 'rotate-180' : ''" />
                            </div>
                        </div>

                        {{-- Expandable Details with Donut Charts --}}
                        <div x-show="expanded" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2" x-cloak
                            class="px-4 pb-4 border-t border-indigo-200 dark:border-indigo-700">

                            @if (!empty($monthBreakdown))
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    {{-- By Type Donut --}}
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                        <h4
                                            class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                            <x-mary-icon name="o-tag" class="w-4 h-4" />
                                            By Type
                                        </h4>
                                        @if (!empty($monthBreakdown['byType']))
                                            <div id="donut-type-chart" wire:ignore></div>
                                        @else
                                            <p class="text-sm text-gray-400 dark:text-gray-500 italic text-center py-8">
                                                No data</p>
                                        @endif
                                    </div>

                                    {{-- By Group Comp Donut --}}
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                        <h4
                                            class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                            <x-mary-icon name="o-building-office" class="w-4 h-4" />
                                            By Group Comp
                                        </h4>
                                        @if (!empty($monthBreakdown['byGroupComp']))
                                            <div id="donut-group-chart" wire:ignore></div>
                                        @else
                                            <p class="text-sm text-gray-400 dark:text-gray-500 italic text-center py-8">
                                                No data</p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic text-center py-8">No breakdown
                                    data available</p>
                            @endif

                            {{-- Close button --}}
                            <div class="mt-4 flex justify-end">
                                <button wire:click="closeMonthDetails"
                                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1 transition-colors">
                                    <x-mary-icon name="o-x-mark" class="w-4 h-4" />
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Column: Calendar + Upcoming Schedules --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Calendar View (Mini) --}}
            <livewire:components.dashboard.calendar-view :events="$calendarEvents" />

            {{-- Upcoming Schedules List --}}
            <livewire:components.dashboard.upcoming-schedules />
        </div>
    </div>

    {{-- Dashboard Charts Initialization --}}
    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', function() {
                window.DashboardCharts.initTrainingChart(
                    @json($monthlyTrainingData),
                    @json($monthLabels),
                    @this
                );
            });

            document.addEventListener('livewire:navigated', function() {
                setTimeout(() => {
                    window.DashboardCharts.initTrainingChart(
                        @json($monthlyTrainingData),
                        @json($monthLabels),
                        @this
                    );
                }, 100);
            });
        </script>
    @endpush
</div>
