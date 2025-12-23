<div>
    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-5 mb-5 lg:mb-9 justify-between items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Instructor Daily Record
        </h1>

        <div
            class="flex gap-3 flex-col w-full lg:w-auto items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <div class="flex items-center justify-center gap-2">
                {{-- Add Button --}}
                <x-button class="btn-primary h-10" wire:click="$dispatch('open-daily-record-modal')">
                    <span class="flex items-center gap-2">
                        <x-icon name="o-plus" class="size-4" />
                        Add
                    </span>
                </x-button>

                {{-- Export Button --}}
                <x-button class="btn-success h-10" wire:click="export" wire:loading.attr="disabled" spinner="export">
                    <span class="flex items-center gap-2">
                        <x-icon name="o-arrow-down-on-square" class="size-4" />
                        Export
                    </span>
                </x-button>

                {{-- Date Range Filter --}}
                <div class="relative" x-data="{ hasValue: @js(!empty($dateRange)) }" x-init="$watch('$wire.dateRange', value => hasValue = !!value);
                // Also listen for flatpickr changes
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

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.300ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.instructor-daily-record-table />

    {{-- No Data State --}}
    @if ($records->isEmpty())
        <div wire:loading.remove class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no daily records in the selected date range.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$records" striped
                class="[&>tbody>tr>td]:!py-4 [&>thead>tr>th]:!py-4 text-sm" with-pagination>

                {{-- No --}}
                @scope('cell_no', $record)
                    <div class="text-center">{{ $record->no }}</div>
                @endscope

                {{-- NRP --}}
                @scope('cell_nrp', $record)
                    <div class="text-center font-mono text-xs">{{ $record->nrp }}</div>
                @endscope

                {{-- Name --}}
                @scope('cell_name', $record)
                    <div class="truncate max-w-[150px]" title="{{ $record->name }}">
                        {{ $record->name }}
                    </div>
                @endscope

                {{-- Date --}}
                @scope('cell_date', $record)
                    <div class="text-center text-xs whitespace-nowrap">{{ $record->formatted_date }}</div>
                @endscope

                {{-- Code --}}
                @scope('cell_code', $record)
                    <div class="text-center font-mono text-xs">{{ $record->code }}</div>
                @endscope

                {{-- Activity --}}
                @scope('cell_activity', $record)
                    <div class="truncate max-w-[250px]" title="{{ $record->activity }}">
                        {{ $record->activity }}
                    </div>
                @endscope

                {{-- Remarks --}}
                @scope('cell_remarks', $record)
                    <div class="truncate max-w-[180px] text-xs" title="{{ $record->remarks }}">
                        {{ $record->remarks ?: '-' }}
                    </div>
                @endscope

                {{-- Hour --}}
                @scope('cell_hour', $record)
                    <div class="text-center font-semibold text-xs">{{ $record->formatted_hour }}</div>
                @endscope

                {{-- Action --}}
                @scope('cell_action', $record)
                    <div class="flex gap-1 justify-center">
                        {{-- Edit Button --}}
                        <x-button icon="o-pencil-square" class="btn-circle btn-ghost btn-sm bg-blue-50 text-blue-600"
                            wire:click="$dispatch('open-daily-record-modal', { id: {{ $record->id }} })"
                            title="Edit" />

                        {{-- Delete Button --}}
                        <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm bg-red-50 text-red-600"
                            wire:click="$dispatch('confirm', {
                                title: 'Delete Record',
                                text: 'Are you sure you want to delete this record?',
                                action: 'delete-record',
                                id: {{ $record->id }}
                            })"
                            title="Delete" />
                    </div>
                @endscope
            </x-table>
        </div>
    @endif

    <livewire:components.confirm-dialog />
    <livewire:components.instructor-daily-record-modal />
</div>
