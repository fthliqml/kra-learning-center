<div>
    {{-- Header --}}
    <div class="w-full flex gap-10 lg:gap-5 mb-5 lg:mb-9 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start flex-1 whitespace-nowrap">
            Certification Point
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            {{-- Export Button --}}
            <x-button class="btn-success h-10" wire:click="export" wire:loading.attr="disabled" spinner="export">
                <span class="flex items-center gap-2">
                    <x-icon name="o-arrow-down-on-square" class="size-4" />
                    Export
                </span>
            </x-button>

            {{-- Sort Order Filter --}}
            <x-select wire:model.live="sortOrder" :options="$sortOptions" option-value="value" option-label="label"
                placeholder="Sort By"
                class="!h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                icon-right="o-funnel" />

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.500ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="6" :rows="10" targets="search,sortOrder,export" />

    {{-- Table --}}
    <div wire:loading.remove class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$certificationPoints" striped class="[&>tbody>tr>td]:py-4 [&>thead>tr>th]:!py-4"
            with-pagination>
            {{-- No --}}
            @scope('cell_no', $point)
                {{ $loop->iteration }}
            @endscope

            {{-- NRP --}}
            @scope('cell_nrp', $point)
                <div class="font-medium">{{ $point->nrp }}</div>
            @endscope

            {{-- Name --}}
            @scope('cell_name', $point)
                <div class="truncate max-w-[40ch] xl:max-w-[52ch]">{{ $point->name }}</div>
            @endscope

            {{-- Section --}}
            @scope('cell_section', $point)
                <div class="truncate max-w-[30ch] xl:max-w-[40ch]">{{ $point->section }}</div>
            @endscope

            {{-- Point --}}
            @scope('cell_point', $point)
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-xs font-semibold">
                    {{ $point->point }}
                </span>
            @endscope
        </x-table>
    </div>
</div>
