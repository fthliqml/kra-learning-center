<div>
    {{-- Main Content: 2 Columns Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Stats Cards + Chart --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Stats Cards (2 cards) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Total Training This Year --}}
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Training This Year</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalTrainingThisYear }}
                                <span class="text-base font-normal text-gray-400">trainings</span></p>
                        </div>
                        <div class="p-3 bg-secondary rounded-xl">
                            <x-mary-icon name="o-academic-cap" class="w-7 h-7 text-white" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Year {{ now()->format('Y') }}</span>
                    </div>
                </div>

                {{-- Upcoming Schedules --}}
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Upcoming Schedules</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $upcomingSchedules }}
                                <span class="text-base font-normal text-gray-400">sessions</span>
                            </p>
                        </div>
                        <div class="p-3 bg-secondary rounded-xl">
                            <x-mary-icon name="o-calendar-days" class="w-7 h-7 text-white" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <x-mary-icon name="o-information-circle" class="w-4 h-4 mr-1" />
                        <span>Starting from today</span>
                    </div>
                </div>
            </div>

            {{-- Training Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Monthly Training</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Training statistics for year
                            {{ $selectedYear }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-secondary text-white">
                            <span class="w-2 h-2 rounded-full bg-white mr-2"></span>
                            Training Count
                        </span>
                    </div>
                </div>

                {{-- ApexCharts Container --}}
                <div id="training-chart" wire:ignore></div>

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

        {{-- Right Column: Calendar + Pending Approvals --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Calendar View (Mini) --}}
            <livewire:calendar-view :events="$calendarEvents" />

            {{-- Pending Approvals List --}}
            <livewire:pending-approvals />
        </div>
    </div>

    {{-- ApexCharts Script --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener('livewire:initialized', function() {
                initTrainingChart();
            });

            function initTrainingChart() {
                const chartElement = document.querySelector("#training-chart");
                if (!chartElement) return;

                // Clear any existing chart
                chartElement.innerHTML = '';

                const options = {
                    chart: {
                        type: 'area',
                        height: 350,
                        fontFamily: 'inherit',
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        },
                        dropShadow: {
                            enabled: true,
                            top: 3,
                            left: 0,
                            blur: 4,
                            opacity: 0.15,
                            color: '#6366f1'
                        },
                        events: {
                            // Handle click on data point
                            dataPointSelection: function(event, chartContext, config) {
                                const monthIndex = config.dataPointIndex;
                                @this.call('selectMonth', monthIndex);
                            },
                            // Handle click on marker
                            markerClick: function(event, chartContext, {
                                seriesIndex,
                                dataPointIndex,
                                config
                            }) {
                                @this.call('selectMonth', dataPointIndex);
                            }
                        }
                    },
                    series: [{
                        name: 'Training Count',
                        data: @json($monthlyTrainingData)
                    }],
                    stroke: {
                        curve: 'smooth',
                        width: 3,
                        colors: ['#6366f1']
                    },
                    colors: ['#6366f1'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: 'light',
                            type: 'vertical',
                            shadeIntensity: 0.5,
                            gradientToColors: ['#6366f1'],
                            opacityFrom: 0.5,
                            opacityTo: 0.05,
                            stops: [0, 100]
                        }
                    },
                    xaxis: {
                        categories: @json($monthLabels),
                        labels: {
                            style: {
                                colors: '#9ca3af',
                                fontSize: '12px'
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        crosshairs: {
                            show: true,
                            stroke: {
                                color: '#6366f1',
                                width: 1,
                                dashArray: 3
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#9ca3af',
                                fontSize: '12px'
                            },
                            formatter: function(val) {
                                return Math.floor(val);
                            }
                        },
                        min: 0
                    },
                    grid: {
                        borderColor: '#e5e7eb',
                        strokeDashArray: 4,
                        xaxis: {
                            lines: {
                                show: false
                            }
                        }
                    },
                    markers: {
                        size: 5,
                        colors: ['#6366f1'],
                        strokeColors: '#fff',
                        strokeWidth: 2,
                        hover: {
                            size: 8,
                            sizeOffset: 3
                        }
                    },
                    tooltip: {
                        enabled: true,
                        shared: false,
                        intersect: true,
                        theme: 'light',
                        style: {
                            fontSize: '12px'
                        },
                        y: {
                            formatter: function(val) {
                                return val + ' Trainings';
                            }
                        },
                        marker: {
                            show: true
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    responsive: [{
                        breakpoint: 768,
                        options: {
                            chart: {
                                height: 280
                            },
                            markers: {
                                size: 4
                            }
                        }
                    }]
                };

                const chart = new ApexCharts(chartElement, options);
                chart.render();

                // Store chart instance for later use
                window.trainingChart = chart;
            }

            // Reinitialize chart when Livewire updates
            document.addEventListener('livewire:navigated', function() {
                setTimeout(initTrainingChart, 100);
            });

            // Donut chart instances
            let donutTypeChart = null;
            let donutGroupChart = null;

            // Listen for breakdown data loaded
            Livewire.on('breakdown-loaded', ({
                byType,
                byGroupComp,
                total
            }) => {
                setTimeout(() => {
                    if (byType && Object.keys(byType).length > 0) {
                        renderDonutTypeChart(byType);
                    }
                    if (byGroupComp && Object.keys(byGroupComp).length > 0) {
                        renderDonutGroupChart(byGroupComp);
                    }
                }, 150);
            });

            function renderDonutTypeChart(data) {
                const el = document.querySelector('#donut-type-chart');
                if (!el) return;

                // Destroy existing chart
                if (donutTypeChart) {
                    donutTypeChart.destroy();
                }

                // Map type labels
                const typeLabels = {
                    'IN': 'In-House',
                    'OUT': 'Out-House',
                };

                const labels = Object.keys(data).map(k => typeLabels[k] || k || 'Unspecified');
                const series = Object.values(data);

                const options = {
                    series: series,
                    labels: labels,
                    chart: {
                        type: 'donut',
                        height: 220,
                        fontFamily: 'inherit',
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 400,
                        }
                    },
                    colors: ['#4863a0', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#06b6d4'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '60%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        fontSize: '12px',
                                        fontWeight: 600,
                                        color: '#6b7280'
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        markers: {
                            width: 10,
                            height: 10,
                            radius: 2
                        }
                    },
                    stroke: {
                        width: 2,
                        colors: ['#fff']
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ' Trainings';
                            }
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 180
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                donutTypeChart = new ApexCharts(el, options);
                donutTypeChart.render();
            }

            function renderDonutGroupChart(data) {
                const el = document.querySelector('#donut-group-chart');
                if (!el) return;

                // Destroy existing chart
                if (donutGroupChart) {
                    donutGroupChart.destroy();
                }

                const labels = Object.keys(data).map(k => (k || 'Unspecified').toUpperCase());
                const series = Object.values(data);

                const options = {
                    series: series,
                    labels: labels,
                    chart: {
                        type: 'donut',
                        height: 220,
                        fontFamily: 'inherit',
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 400,
                        }
                    },
                    colors: ['#3b82f6', '#22c55e', '#f97316', '#a855f7', '#ec4899', '#14b8a6'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '60%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        fontSize: '12px',
                                        fontWeight: 600,
                                        color: '#6b7280'
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        markers: {
                            width: 10,
                            height: 10,
                            radius: 2
                        }
                    },
                    stroke: {
                        width: 2,
                        colors: ['#fff']
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ' Trainings';
                            }
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 180
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                donutGroupChart = new ApexCharts(el, options);
                donutGroupChart.render();
            }
        </script>
    @endpush
</div>
