<div>
    {{-- Header --}}
    <div class="flex flex-col gap-6 mb-8">
        {{-- Title & Actions --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <h1 class="text-primary text-3xl lg:text-4xl font-bold">
                Training Activity Report
            </h1>

            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <x-button class="btn-success h-10 w-full sm:w-auto text-white shadow-sm" wire:click="export"
                    wire:loading.attr="disabled" spinner="export">
                    <x-icon name="o-arrow-down-on-square" class="size-4 mr-2" />
                    Export
                </x-button>

                <x-search-input placeholder="Search..." wire:model.live.debounce.300ms="search"
                    class="max-w-72" />
            </div>
        </div>

        {{-- Filters Toolbar --}}
        <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap gap-3 w-full items-center">
                    @if ($hasFullAccess)
                        {{-- Department Filter --}}
                        <div class="w-full lg:w-56">
                            <x-select wire:model.live="filterDepartment" :options="$departments" placeholder="All Departments"
                                placeholder-value="" class="select-sm w-full h-10" />
                        </div>

                        {{-- Section Filter --}}
                        <div class="w-full lg:w-48">
                            <x-select wire:model.live="filterSection" :options="$sections" placeholder="All Sections"
                                placeholder-value="" class="select-sm w-full h-10" />
                        </div>
                    @endif

                    {{-- Date Range Filter --}}
                    <div class="w-full lg:w-64 relative" x-data="{ hasValue: @js(!empty($dateRange)) }" x-init="$watch('$wire.dateRange', value => hasValue = !!value);
                    $nextTick(() => {
                        const input = $el.querySelector('input[type=hidden], input.flatpickr-input');
                        if (input && input._flatpickr) {
                            input._flatpickr.config.onChange.push(() => hasValue = true);
                            input._flatpickr.config.onClose.push(() => hasValue = !!input.value);
                        }
                    });">
                        <div class="relative w-full">
                            <x-datepicker wire:model.live="dateRange" icon="o-calendar" class="input-sm w-full h-10"
                                :config="[
                                    'mode' => 'range',
                                    'altInput' => true,
                                    'altFormat' => 'd-m-Y',
                                    'dateFormat' => 'Y-m-d',
                                ]" />
                            <span x-show="!hasValue" x-cloak
                                class="absolute left-11 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                                Filter by date...
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Clear Filter --}}
                @if ($dateRange || $search || $filterDepartment || $filterSection)
                    <div
                        class="w-full lg:w-auto flex justify-end lg:justify-start pt-2 lg:pt-0 border-t lg:border-t-0 border-gray-100 lg:border-0 mt-2 lg:mt-0">
                        <x-button class="btn-ghost btn-sm text-error hover:bg-red-50" wire:click="clearFilters">
                            <x-icon name="o-trash" class="size-4 mr-1" />
                            Clear Filters
                        </x-button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.training-activity-report-table />

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
                    There are no completed training records in the selected date range.
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

                {{-- Event Code --}}
                @scope('cell_event_code', $report)
                    <div class="text-center font-mono text-xs">{{ $report->event_code }}</div>
                @endscope

                {{-- Training Name --}}
                @scope('cell_training_name', $report)
                    <div class="truncate max-w-[200px]" title="{{ $report->training_name }}">
                        {{ $report->training_name }}
                    </div>
                @endscope

                {{-- Group Comp --}}
                @scope('cell_group_comp', $report)
                    <div class="text-center text-xs font-medium text-gray-700">
                        {{ $report->group_comp }}
                    </div>
                @endscope

                {{-- Type --}}
                @scope('cell_type', $report)
                    <div class="text-center">
                        @php
                            $typeBadge = match($report->type) {
                                'IN' => 'bg-green-100 text-green-700 border-green-300',
                                'OUT' => 'bg-amber-100 text-amber-700 border-amber-300',
                                'LMS' => 'bg-indigo-100 text-indigo-700 border-indigo-300',
                                'BLENDED' => 'bg-purple-100 text-purple-700 border-purple-300',
                                default => 'bg-gray-100 text-gray-700 border-gray-300',
                            };
                            $typeLabel = match($report->type) {
                                'IN' => 'In-House',
                                'OUT' => 'Out-House',
                                'LMS' => 'LMS',
                                'BLENDED' => 'Blended',
                                default => $report->type,
                            };
                        @endphp
                        <span class="badge badge-sm border whitespace-nowrap {{ $typeBadge }}">
                            {{ $typeLabel }}
                        </span>
                    </div>
                @endscope

                {{-- Instructor --}}
                @scope('cell_instructor', $report)
                    <div class="truncate max-w-[150px]" title="{{ $report->instructor }}">
                        {{ $report->instructor }}
                    </div>
                @endscope

                {{-- Venue --}}
                @scope('cell_venue', $report)
                    <div class="truncate max-w-[180px] text-xs" title="{{ $report->venue }}">
                        {{ $report->venue }}
                    </div>
                @endscope

                {{-- NRP --}}
                @scope('cell_nrp', $report)
                    <div class="text-center font-mono text-xs">{{ $report->nrp }}</div>
                @endscope

                {{-- Employee Name --}}
                @scope('cell_employee_name', $report)
                    <div class="truncate max-w-[150px]" title="{{ $report->employee_name }}">
                        {{ $report->employee_name }}
                    </div>
                @endscope

                {{-- Section --}}
                @scope('cell_section', $report)
                    <div class="text-center text-xs">{{ $report->section }}</div>
                @endscope

                {{-- Attendance --}}
                @scope('cell_attendance', $report)
                    <div
                        class="text-center text-xs font-semibold {{ $report->attendance_raw >= 75 ? 'text-emerald-600' : ($report->attendance_raw !== null ? 'text-rose-600' : 'text-gray-400') }}">
                        {{ $report->attendance }}
                    </div>
                @endscope

                {{-- Period --}}
                @scope('cell_period', $report)
                    <div class="text-center text-xs whitespace-nowrap">{{ $report->period }}</div>
                @endscope

                {{-- Duration --}}
                @scope('cell_duration', $report)
                    <div class="text-center text-xs whitespace-nowrap">{{ $report->duration }}</div>
                @endscope

                {{-- Theory Score --}}
                @scope('cell_theory_score', $report)
                    <div
                        class="text-center font-semibold {{ $report->theory_raw >= 70 ? 'text-emerald-600' : ($report->theory_raw !== null ? 'text-rose-600' : 'text-gray-400') }}">
                        {{ $report->theory_score }}
                    </div>
                @endscope

                {{-- Practical Score --}}
                @scope('cell_practical_score', $report)
                    <div
                        class="text-center font-semibold {{ $report->practical_raw >= 70 ? 'text-emerald-600' : ($report->practical_raw !== null ? 'text-rose-600' : 'text-gray-400') }}">
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

                {{-- Date Report --}}
                @scope('cell_date_report', $report)
                    <div class="text-center text-xs whitespace-nowrap">{{ $report->date_report }}</div>
                @endscope

                {{-- Certificate --}}
                @scope('cell_certificate', $report)
                    <div class="text-center">
                        @if ($report->certificate_url)
                            <a href="{{ $report->certificate_url }}" target="_blank"
                                class="text-blue-600 hover:text-blue-800 underline text-xs font-medium">
                                View
                            </a>
                        @else
                            <span class="text-gray-400 text-xs">-</span>
                        @endif
                    </div>
                @endscope

                {{-- Note --}}
                @scope('cell_note', $report)
                    <div class="truncate max-w-[150px] text-xs" title="{{ $report->note }}">
                        {{ $report->note }}
                    </div>
                @endscope
            </x-table>
        </div>
    @endif
</div>
