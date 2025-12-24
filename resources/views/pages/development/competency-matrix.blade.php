<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-5 mb-5 lg:mb-9 items-center justify-between">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start whitespace-nowrap">
            Competency Matrix
        </h1>

        <div
            class="flex gap-3 flex-col w-full lg:w-auto items-center justify-center lg:justify-end md:gap-2 md:flex-row">

            <div class="flex items-center justify-center gap-2">
                <!-- Filter -->
                <x-select wire:model.live="filterType" :options="$typeOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!min-w-[120px] !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="5" :rows="10" targets="search,filterType" />

    {{-- No Data State --}}
    @if ($competencies->isEmpty())
        <div wire:loading.remove wire:target="search,filterType"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no competency matrix records to display at the moment.
                </p>
            </div>
        </div>
    @endif

    {{-- Table --}}
    @if (!$competencies->isEmpty())
        <div wire:loading.remove wire:target="search,filterType"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$competencies" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- No --}}
                @scope('cell_no', $competency, $competencies)
                    <div class="text-center">
                        {{ ($competencies->currentPage() - 1) * $competencies->perPage() + $loop->iteration }}
                    </div>
                @endscope

                {{-- ID (Code) --}}
                @scope('cell_code', $competency)
                    <div class="text-center font-mono text-sm text-primary">{{ $competency->code }}</div>
                @endscope

                {{-- Competency Name --}}
                @scope('cell_name', $competency)
                    <div class="truncate max-w-[30ch] xl:max-w-[50ch]">{{ $competency->name }}</div>
                @endscope

                {{-- Employees Trained Count --}}
                @scope('cell_employees_trained_count', $competency)
                    <div class="text-center">{{ $competency->employees_trained_count }}</div>
                @endscope

                {{-- Action --}}
                @scope('cell_action', $competency)
                    <div class="flex gap-2 justify-center">
                        <!-- View Detail -->
                        <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner
                            wire:click="openDetailModal({{ $competency->id }})" />
                    </div>
                @endscope
            </x-table>
        </div>
    @endif

    {{-- Detail Modal --}}
    <x-modal wire:model="detailModal" title="Competency Matrix Details" separator box-class="max-w-xl h-fit">
        @if ($selectedCompetency)
            <x-form no-separator>
                <x-input label="ID" :value="$selectedCompetency->code" readonly class="focus-within:border-0" />

                <x-input label="Competency" :value="$selectedCompetency->name" readonly class="focus-within:border-0" />

                <x-input label="Type" :value="$selectedCompetency->type" readonly class="focus-within:border-0" />

                @if (count($employeesTrained) > 0)
                    <x-textarea label="Employees Trained" wire:model="employeesTrainedText" readonly
                        class="focus-within:border-0" rows="8" />
                @else
                    <x-input label="Employees Trained" value="No employees trained yet" readonly
                        class="focus-within:border-0 italic" />
                @endif

                <x-slot:actions>
                    <x-ui.button @click="$wire.closeDetailModal()" type="button">Close</x-ui.button>
                </x-slot:actions>
            </x-form>
        @endif
    </x-modal>
</div>
