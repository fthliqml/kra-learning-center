<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Development Approved
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <x-select wire:model="selectedYear" :options="collect(range(now()->year - 3, now()->year + 1))
                ->map(fn($y) => ['value' => (string) $y, 'label' => (string) $y])
                ->values()" option-value="value" option-label="label"
                class="!h-10 focus-within:border-0" placeholder="Select year" />

            <x-search-input placeholder="Search employee..." class="max-w-md" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="7" :rows="10" targets="selectedYear,search" />

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
        {{-- Table --}}
        <div wire:loading.remove wire:target="selectedYear,search"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$rows" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                @scope('cell_no', $row)
                    <div class="text-center">{{ $row->no }}</div>
                @endscope

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
                    <div class="truncate max-w-[24ch] xl:max-w-[32ch]">{{ $row->plan1 }}</div>
                @endscope

                @scope('cell_plan2', $row)
                    <div class="truncate max-w-[24ch] xl:max-w-[32ch]">{{ $row->plan2 }}</div>
                @endscope

                @scope('cell_plan3', $row)
                    <div class="truncate max-w-[24ch] xl:max-w-[32ch]">{{ $row->plan3 }}</div>
                @endscope

                @scope('cell_status', $row)
                    <div class="flex justify-center">
                        @if ($row->status === 'scheduled')
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-700">
                                Scheduled
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">
                                Waiting
                            </span>
                        @endif
                    </div>
                @endscope
            </x-table>
        </div>
    @endif
</div>
