<div>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Training This Month --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Training This Month</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $trainingThisMonth }}</p>
                </div>
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl">
                    <x-mary-icon name="o-academic-cap" class="w-7 h-7 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
            <div class="mt-3 flex items-center text-sm">
                <span class="text-gray-500 dark:text-gray-400">{{ now()->format('F Y') }}</span>
            </div>
        </div>

        {{-- Upcoming Schedules --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Upcoming Schedules</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $upcomingSchedules }}</p>
                </div>
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl">
                    <x-mary-icon name="o-calendar-days" class="w-7 h-7 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
            <div class="mt-3 flex items-center text-sm">
                <span class="text-gray-500 dark:text-gray-400">Upcoming training sessions</span>
            </div>
        </div>

        {{-- Pending Approvals --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Approval</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $pendingApprovals }}</p>
                </div>
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl">
                    <x-mary-icon name="o-clock" class="w-7 h-7 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
            <div class="mt-3 flex items-center text-sm">
                <span class="text-gray-500 dark:text-gray-400">Awaiting approval</span>
            </div>
        </div>
    </div>

    {{-- Training Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Monthly Training</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Training statistics for year {{ $selectedYear }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></span>
                    Training Count
                </span>
            </div>
        </div>

        {{-- ApexCharts Container --}}
        <div id="training-chart" wire:ignore></div>

        {{-- Selected Month Info --}}
        @if ($selectedMonth)
            <div class="mt-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                <p class="text-sm text-indigo-700 dark:text-indigo-300">
                    <span class="font-medium">Selected month:</span>
                    {{ \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y') }}
                    - {{ $monthlyTrainingData[$selectedMonth - 1] ?? 0 }} Trainings
                </p>
            </div>
        @endif
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
                        type: 'line',
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
                            opacity: 0.1
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
                        width: 3
                    },
                    colors: ['#6366f1'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.1,
                            stops: [0, 90, 100]
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
        </script>
    @endpush
</div>
