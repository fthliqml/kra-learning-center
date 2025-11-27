<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training History
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <div class="flex items-center justify-center gap-2">
                <x-select wire:model.live="filter" :options="$typeOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search training..." class="max-w-md" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.training-history-table />

    {{-- Table --}}
    <div wire:loading.remove class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$histories" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
            with-pagination>
            {{-- Custom cell untuk kolom Nomor --}}
            @scope('cell_no', $history)
                {{ $history->no ?? $loop->iteration }}
            @endscope

            {{-- Custom cell untuk kolom Type --}}
            @scope('cell_type', $history)
                @php
                    $badgeClass = match ($history->type) {
                        'In-House' => 'border-green-500 bg-green-50 text-green-700',
                        'Out-House' => 'border-amber-500 bg-amber-50 text-amber-700',
                        'K-Learn' => 'border-indigo-500 bg-indigo-50 text-indigo-700',
                        default => 'border-primary bg-[#E4F3FF] text-primary',
                    };
                @endphp
                <div class="flex justify-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs border {{ $badgeClass }}">
                        {{ $history->type }}
                    </span>
                </div>
            @endscope

            {{-- Custom cell untuk kolom Status --}}
            @scope('cell_status', $history)
                <div class="flex justify-center">
                    @if ($history->status === null)
                        <span class="badge badge-ghost badge-sm">Not Assessed</span>
                    @elseif($history->status === 'passed')
                        <span class="badge badge-success badge-sm">Passed</span>
                    @elseif($history->status === 'failed')
                        <span class="badge badge-error badge-sm">Failed</span>
                    @else
                        <span class="badge badge-warning badge-sm">In Progress</span>
                    @endif
                </div>
            @endscope

            {{-- Custom cell untuk kolom Certificate --}}
            @scope('cell_certificate', $history)
                <div class="flex justify-center">
                    @if ($history->status === 'passed')
                        <a href="#" class="text-blue-600 hover:text-blue-800 underline text-sm">
                            View Certificate
                        </a>
                    @else
                        <span class="text-gray-400 text-sm">-</span>
                    @endif
                </div>
            @endscope
        </x-table>
    </div>
</div>
