<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Certification Activity Report
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <div class="flex items-center justify-center gap-2">
                {{-- Export Button --}}
                <x-button class="btn-success h-10" wire:click="export" wire:loading.attr="disabled" spinner="export">
                    <span class="flex items-center gap-2">
                        <x-icon name="o-arrow-down-on-square" class="size-4" />
                        Export
                    </span>
                </x-button>

                {{-- Date Range Filter --}}
                <div class="relative" x-data="{ hasValue: @js(!empty($dateRange)) }" x-init="$watch('$wire.dateRange', value => hasValue = !!value);
                $nextTick(() => {
                    const input = $el.querySelector('input[type=hidden], input.flatpickr-input');
                    if (input && input._flatpickr) {
                        input._flatpickr.config.onChange.push(() => hasValue = true);
                        input._flatpickr.config.onClose.push(() => hasValue = !!input.value);
                    }
                });">
                    <x-datepicker wire:model.live="dateRange" icon="o-calendar"
                        class="!w-52 !h-10 focus-within:border-0" :config="[
                            'mode' => 'range',
                            'altInput' => true,
                            'altFormat' => 'd-m-Y',
                            'dateFormat' => 'Y-m-d',
                        ]" />
                    <span x-show="!hasValue" x-cloak
                        class="absolute left-9 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                        Filter by date...
                    </span>
                </div>

                {{-- Clear Filter Button --}}
                @if ($dateRange || $search)
                    <x-button class="btn-ghost btn-sm h-10 px-2" wire:click="clearFilters" title="Clear all filters">
                        <x-icon name="o-x-mark" class="size-5 text-gray-500" />
                    </x-button>
                @endif
            </div>

            <x-search-input placeholder="Search name, competency..." class="max-w-72"
                wire:model.live.debounce.300ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.certification-activity-report-table />

    {{-- No Data State --}}
    @if ($reports->isEmpty())
        <div wire:loading.remove class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no completed certification records in the selected date range.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$reports" striped
                class="[&>tbody>tr>td]:!py-4 [&>thead>tr>th]:!py-4 text-sm" with-pagination>

                {{-- No --}}
                @scope('cell_no', $report)
                    <div class="text-center">{{ $report->no }}</div>
                @endscope

                {{-- NRP --}}
                @scope('cell_nrp', $report)
                    <div class="text-center font-mono text-xs">{{ $report->nrp }}</div>
                @endscope

                {{-- Name --}}
                @scope('cell_name', $report)
                    <div class="truncate max-w-[180px]" title="{{ $report->name }}">
                        {{ $report->name }}
                    </div>
                @endscope

                {{-- Section --}}
                @scope('cell_section', $report)
                    <div class="text-center text-xs">{{ $report->section }}</div>
                @endscope

                {{-- Competency --}}
                @scope('cell_competency', $report)
                    <div class="truncate max-w-[200px]" title="{{ $report->competency }}">
                        {{ $report->competency }}
                    </div>
                @endscope

                {{-- Theory Score --}}
                @scope('cell_theory_score', $report)
                    @php
                        $passed = $report->theory_raw !== null && $report->theory_raw >= $report->theory_passing;
                    @endphp
                    <div
                        class="text-center font-semibold {{ $passed ? 'text-emerald-600' : ($report->theory_raw !== null ? 'text-rose-600' : 'text-gray-400') }}">
                        {{ $report->theory_score }}
                    </div>
                @endscope

                {{-- Practical Score --}}
                @scope('cell_practical_score', $report)
                    @php
                        $passed =
                            $report->practical_raw !== null && $report->practical_raw >= $report->practical_passing;
                    @endphp
                    <div
                        class="text-center font-semibold {{ $passed ? 'text-emerald-600' : ($report->practical_raw !== null ? 'text-rose-600' : 'text-gray-400') }}">
                        {{ $report->practical_score }}
                    </div>
                @endscope

                {{-- Remarks --}}
                @scope('cell_remarks', $report)
                    <div class="flex justify-center">
                        @if ($report->remarks === 'passed')
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-700">
                                Passed
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-700">
                                Failed
                            </span>
                        @endif
                    </div>
                @endscope

                {{-- Earned Point --}}
                @scope('cell_earned_point', $report)
                    <div
                        class="text-center font-semibold {{ $report->earned_point > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                        {{ $report->earned_point }}
                    </div>
                @endscope

                {{-- Note --}}
                @scope('cell_note', $report)
                    <div class="truncate max-w-[150px] text-xs" title="{{ $report->note }}">
                        {{ $report->note }}
                    </div>
                @endscope

                {{-- Date --}}
                @scope('cell_date', $report)
                    <div class="text-center text-xs whitespace-nowrap">{{ $report->date }}</div>
                @endscope
            </x-table>
        </div>
    @endif
</div>
