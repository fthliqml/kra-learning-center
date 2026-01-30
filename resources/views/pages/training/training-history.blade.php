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
                    class="!min-w-[120px] !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <div class="rounded-lg border border-gray-200 shadow-all">
        <x-skeletons.training-history-table />
    </div>

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
                    @if ($search || $filter)
                        Try adjusting your search or filter to find what you're looking for.
                    @else
                        You don't have any training history yet.
                    @endif
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
                    {{ $history->no ?? $loop->iteration }}
                @endscope

                {{-- Custom cell untuk kolom Type --}}
                @scope('cell_type', $history)
                    @php
                        $badgeClass = match ($history->type) {
                            'In-House' => 'border-green-500 bg-green-50 text-green-700',
                            'Out-House' => 'border-amber-500 bg-amber-50 text-amber-700',
                            'LMS' => 'border-indigo-500 bg-indigo-50 text-indigo-700',
                            'Blended' => 'border-purple-500 bg-purple-50 text-purple-700',
                            default => 'border-primary bg-[#E4F3FF] text-primary',
                        };
                    @endphp
                    <div class="flex justify-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs border {{ $badgeClass }} whitespace-nowrap">
                            {{ $history->type }}
                        </span>
                    </div>
                @endscope

                {{-- Custom cell untuk kolom Status --}}
                @scope('cell_status', $history)
                    <div class="flex justify-center">
                        @if ($history->status === null)
                            <span class="badge badge-ghost badge-sm whitespace-nowrap">Not Assessed</span>
                        @elseif($history->status === 'passed')
                            <span class="badge badge-success badge-sm whitespace-nowrap">Passed</span>
                        @elseif($history->status === 'failed')
                            <span class="badge badge-error badge-sm whitespace-nowrap">Failed</span>
                        @else
                            <span class="badge badge-warning badge-sm whitespace-nowrap">In Progress</span>
                        @endif
                    </div>
                @endscope

                {{-- Custom cell untuk kolom Certificate --}}
                @scope('cell_certificate', $history)
                    <div class="flex justify-center">
                        @if ($history->status === 'passed' && !empty($history->certificate_path) && !empty($history->assessment_id))
                            @if (!empty($history->survey_level1_id) && empty($history->survey_level1_completed))
                                <span class="text-gray-600 text-sm">
                                    Isi <a
                                        href="{{ route('survey.take', ['level' => 1, 'surveyId' => $history->survey_level1_id]) }}"
                                        class="text-blue-600 hover:text-blue-800 underline" rel="noopener">
                                        Survey Level 1
                                    </a> untuk melihat certificate.
                                </span>
                            @else
                                <a href="{{ route('certificate.training.view', $history->assessment_id) }}"
                                    class="text-blue-600 hover:text-blue-800 underline text-sm" target="_blank"
                                    rel="noopener">
                                    View Certificate
                                </a>
                            @endif
                        @else
                            <span class="text-gray-400 text-sm">-</span>
                        @endif
                    </div>
                @endscope
            </x-table>
        </div>
    @endif
</div>
