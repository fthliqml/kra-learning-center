<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Certification History
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <x-search-input placeholder="Search certification..." class="max-w-md"
                wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="5" :rows="10" targets="search" />

    {{-- No Data State --}}
    @if ($histories->isEmpty())
        <div wire:loading.remove class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    You don't have any approved certification history yet.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$histories" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- Custom cell untuk kolom Nomor --}}
                @scope('cell_no', $history)
                    {{ $loop->iteration }}
                @endscope

                {{-- Custom cell untuk kolom Certification Name --}}
                @scope('cell_certification_name', $history)
                    <div class="truncate max-w-[40ch] xl:max-w-[50ch]">{{ $history->certification_name }}</div>
                @endscope

                {{-- Custom cell untuk kolom Competency --}}
                @scope('cell_competency', $history)
                    <div class="text-center">{{ $history->competency }}</div>
                @endscope

                {{-- Custom cell untuk kolom Approved Date --}}
                @scope('cell_approved_date', $history)
                    <div class="text-center text-sm">
                        {{ $history->approved_date ? \Carbon\Carbon::parse($history->approved_date)->format('d M Y') : '-' }}
                    </div>
                @endscope

                {{-- Custom cell untuk kolom Status --}}
                @scope('cell_status', $history)
                    <div class="flex justify-center">
                        @if ($history->status === 'passed')
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-700 whitespace-nowrap">
                                Passed
                            </span>
                        @elseif($history->status === 'failed')
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-700 whitespace-nowrap">
                                Failed
                            </span>
                        @else
                            <span class="text-gray-400 text-sm">-</span>
                        @endif
                    </div>
                @endscope
            </x-table>
        </div>
    @endif
</div>
