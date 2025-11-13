<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Certification Point
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.500ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.certification-point-table />

    {{-- Table --}}
    <div wire:loading.remove class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$certificationPoints" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
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

            {{-- Action --}}
            @scope('cell_action', $point)
                <div class="flex justify-center">
                    <x-button icon="o-pencil" class="btn-circle btn-ghost p-2 bg-warning text-white" spinner />
                </div>
            @endscope
        </x-table>
    </div>
</div>
