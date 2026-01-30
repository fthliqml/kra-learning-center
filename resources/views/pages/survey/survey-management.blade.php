<div>
    {{-- Header (mimic Training Requests layout) --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <div class="flex flex-col gap-2">
            <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
                Survey Management
            </h1>
            <span class="badge badge-primary badge-soft">Level {{ $surveyLevel }}</span>
        </div>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <div class="flex items-center justify-center gap-2">
                <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Empty State --}}
    @if ($surveys->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no survey records matching your filters.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$surveys" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                @scope('cell_no', $survey)
                    {{ $survey->no ?? $loop->iteration }}
                @endscope

                @scope('cell_status', $survey)
                    @php
                        $status = $survey->status ?? 'incomplete';

                        $label = match ($status) {
                            'draft' => 'Draft',
                            'completed' => 'Completed',
                            // NOTE: 'incomplete' means survey is published/in progress (not all participants completed yet)
                            'incomplete' => 'In Progress',
                            default => str($status)->title(),
                        };
                        // Map to badge classes (no variant prop)
                        $badgeClass = match ($status) {
                            'draft' => 'badge-warning',
                            'completed' => 'badge-primary bg-primary/95',
                            'incomplete' => 'badge-primary badge-soft',
                            default => 'badge-ghost',
                        };
                    @endphp
                    <x-badge :value="$label" :class="$badgeClass . ' whitespace-nowrap'" />
                @endscope

                @scope('cell_action', $survey)
                    <div class="flex gap-2 justify-center">
                        @if (auth()->check() && ($canExport ?? false))
                            <x-button icon="o-arrow-down-on-square" class="btn-circle btn-success text-white p-2"
                                wire:click="exportSurvey({{ (int) $survey->id }})" wire:loading.attr="disabled"
                                wire:target="exportSurvey({{ (int) $survey->id }})" />
                        @endif
                        <a href="{{ route('survey.edit', ['level' => $this->surveyLevel, 'surveyId' => $survey->id]) }}">
                            <x-button icon="o-pencil-square" class="btn-circle btn-ghost bg-tetriary p-2" />
                        </a>
                    </div>
                @endscope
            </x-table>
        </div>
    @endif
</div>