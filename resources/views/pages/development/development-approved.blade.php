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

    {{-- Table --}}
    <div wire:loading.remove class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
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
</div>
