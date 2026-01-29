<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Development Recap
        </h1>

        <div
            class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row md:flex-wrap">
            <div class="flex flex-wrap items-center justify-center lg:justify-end gap-2">
                {{-- Export to Excel (align style with Training Request) --}}
                <x-button class="btn-success h-10 text-white shadow-sm" wire:click="export" wire:loading.attr="disabled"
                    spinner="export">
                    <x-icon name="o-arrow-down-on-square" class="size-4 mr-2" />
                    Export
                </x-button>

                {{-- Year Filter (same style as Development Plan) --}}
                <x-input type="number" wire:model.live.debounce.500ms="selectedYear" icon="o-calendar"
                    class="number-no-spinner !w-24 md:!w-28" min="2000" max="2100" />
            </div>

            <div class="flex items-center justify-center gap-2">
                <x-search-input placeholder="Search..." class="max-w-72"
                    wire:model.live.debounce.600ms="search" />
            </div>
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="6" :rows="10" targets="selectedYear,search" />

    {{-- No Data State --}}
    @if ($rows->isEmpty())
        <div wire:loading.remove wire:target="selectedYear,search"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no approved training plans for {{ $selectedYear }}.
                </p>
            </div>
        </div>
    @else
        @role('admin')
            <div class="mb-4 p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-900">Create Schedule</div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            Select employees from the Plan column, then create the schedule.
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <x-button class="btn-primary h-10 text-white shadow-sm" wire:click="createTrainingSchedule"
                            wire:loading.attr="disabled" spinner="createTrainingSchedule">
                            <x-icon name="o-calendar-days" class="size-4 mr-2" />
                            Create Schedule
                            @if (!empty($this->selectedTrainingPlanIds))
                                <span
                                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-white/20">
                                    {{ count($this->selectedTrainingPlanIds) }} selected
                                </span>
                            @endif
                        </x-button>
                    </div>
                </div>

                @error('selectedTrainingPlanIds')
                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>
        @endrole

        {{-- Table --}}
        <div wire:loading.remove wire:target="selectedYear,search"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$rows" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                @scope('cell_nrp', $row)
                    <div class="text-center font-mono text-sm">{{ $row->nrp }}</div>
                @endscope

                @scope('cell_name', $row)
                    <div class="truncate max-w-[24ch] xl:max-w-[32ch]">{{ $row->name }}</div>
                @endscope

                @scope('cell_section', $row)
                    <div class="truncate max-w-[24ch] xl:max-w-[32ch] text-sm">{{ $row->section ?? '-' }}</div>
                @endscope

                @scope('cell_plan1', $row)
                    <div class="max-w-[24ch] xl:max-w-[32ch]">
                        <div class="flex items-start gap-2">
                            @role('admin')
                                @if (!empty($row->plan1['id']))
                                    <div class="pt-0.5">
                                        <input type="checkbox" class="checkbox checkbox-sm checkbox-primary"
                                            wire:model.live="selectedTrainingPlanIds" value="{{ $row->plan1['id'] }}" />
                                    </div>
                                @endif
                            @endrole

                            <div class="min-w-0">
                                <div class="truncate">{{ $row->plan1['label'] ?? '-' }}</div>
                                @if (!empty($row->plan1['label']) && ($row->plan1['label'] ?? '-') !== '-')
                                    @if (!empty($row->plan1['scheduled']))
                                        <div class="mt-0.5">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                Scheduled
                                            </span>
                                        </div>
                                    @else
                                        <div class="mt-0.5">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">
                                                Waiting
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endscope

                @scope('cell_plan2', $row)
                    <div class="max-w-[24ch] xl:max-w-[32ch]">
                        <div class="flex items-start gap-2">
                            @role('admin')
                                @if (!empty($row->plan2['id']))
                                    <div class="pt-0.5">
                                        <input type="checkbox" class="checkbox checkbox-sm checkbox-primary"
                                            wire:model.live="selectedTrainingPlanIds" value="{{ $row->plan2['id'] }}" />
                                    </div>
                                @endif
                            @endrole

                            <div class="min-w-0">
                                <div class="truncate">{{ $row->plan2['label'] ?? '-' }}</div>
                                @if (!empty($row->plan2['label']) && ($row->plan2['label'] ?? '-') !== '-')
                                    @if (!empty($row->plan2['scheduled']))
                                        <div class="mt-0.5">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                Scheduled
                                            </span>
                                        </div>
                                    @else
                                        <div class="mt-0.5">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">
                                                Waiting
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endscope

                @scope('cell_plan3', $row)
                    <div class="max-w-[24ch] xl:max-w-[32ch]">
                        <div class="flex items-start gap-2">
                            @role('admin')
                                @if (!empty($row->plan3['id']))
                                    <div class="pt-0.5">
                                        <input type="checkbox" class="checkbox checkbox-sm checkbox-primary"
                                            wire:model.live="selectedTrainingPlanIds" value="{{ $row->plan3['id'] }}" />
                                    </div>
                                @endif
                            @endrole

                            <div class="min-w-0">
                                <div class="truncate">{{ $row->plan3['label'] ?? '-' }}</div>
                                @if (!empty($row->plan3['label']) && ($row->plan3['label'] ?? '-') !== '-')
                                    @if (!empty($row->plan3['scheduled']))
                                        <div class="mt-0.5">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                Scheduled
                                            </span>
                                        </div>
                                    @else
                                        <div class="mt-0.5">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">
                                                Waiting
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endscope
            </x-table>
        </div>
    @endif
</div>
