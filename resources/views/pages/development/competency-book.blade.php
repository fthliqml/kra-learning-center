<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Competency Book
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">

            <div class="flex items-center justify-center gap-2">
                {{-- Dropdown Export / Import --}}
                <x-dropdown no-x-anchor right>
                    <x-slot:trigger>
                        <x-button class="btn-success h-10" wire:target="file" wire:loading.attr="disabled">
                            <span class="flex items-center gap-2" wire:loading.remove wire:target="file">
                                <x-icon name="o-clipboard-document-list" class="size-4" />
                                Excel
                            </span>
                            <span class="flex items-center gap-2" wire:loading wire:target="file">
                                <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                            </span>
                        </x-button>
                    </x-slot:trigger>

                    <x-menu-item title="Export" icon="o-arrow-down-on-square" wire:click.stop="export"
                        spinner="export" />

                    <label class="w-full cursor-pointer relative" wire:loading.class="opacity-60 pointer-events-none"
                        wire:target="file">
                        <x-menu-item title="Import" icon="o-arrow-up-on-square" />
                        <div class="absolute right-2 top-2" wire:loading wire:target="file">
                            <x-icon name="o-arrow-path" class="size-4 animate-spin text-gray-500" />
                        </div>
                        <input type="file" wire:model="file" class="hidden" />
                    </label>

                    <x-menu-item title="Download Template" icon="o-document-arrow-down"
                        wire:click.stop="downloadTemplate" spinner="downloadTemplate" />
                </x-dropdown>

                <!-- Add Button -->
                <x-ui.button variant="primary" wire:click="openCreateModal" wire:target="openCreateModal" class="h-10"
                    wire:loading.attr="readonly">
                    <span wire:loading.remove wire:target="openCreateModal" class="flex items-center gap-2">
                        <x-icon name="o-plus" class="size-4" />
                        Add
                    </span>
                    <span wire:loading wire:target="openCreateModal">
                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                    </span>
                </x-ui.button>

                <!-- Filter -->
                <x-select wire:model.live="filter" :options="$typeOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="5" :rows="10"
        targets="search,filter,save,deleteCompetency,file" />

    {{-- No Data State --}}
    @if ($competencies->isEmpty())
        <div wire:loading.remove wire:target="search,filter,save,delete-confirmed"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no competency records to display at the moment.
                </p>
            </div>
        </div>
    @endif

    {{-- Table --}}
    @if (!$competencies->isEmpty())
        <div wire:loading.remove wire:target="search,filter,save,delete-confirmed"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$competencies" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- No --}}
                @scope('cell_no', $competency, $competencies)
                    <div class="text-center">
                        {{ ($competencies->currentPage() - 1) * $competencies->perPage() + $loop->iteration }}
                    </div>
                @endscope

                {{-- ID --}}
                @scope('cell_code', $competency)
                    <div class="text-center font-mono text-sm">{{ $competency->code }}</div>
                @endscope

                {{-- Competency Name --}}
                @scope('cell_name', $competency)
                    <div class="truncate max-w-[50ch] xl:max-w-[60ch]">{{ $competency->name }}</div>
                @endscope

                {{-- Type --}}
                @scope('cell_type', $competency)
                    @php
                        $type = $competency->type ?? '-';
                        $classes =
                            [
                                'BMC' => 'bg-blue-100 text-blue-700',
                                'BC' => 'bg-emerald-100 text-emerald-700',
                                'MMP' => 'bg-amber-100 text-amber-700',
                                'LC' => 'bg-purple-100 text-purple-700',
                                'MDP' => 'bg-rose-100 text-rose-700',
                                'TOC' => 'bg-cyan-100 text-cyan-700',
                            ][$type] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <div class="flex justify-center">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                            {{ $type }}
                        </span>
                    </div>
                @endscope

                {{-- Action --}}
                @scope('cell_action', $competency)
                    <div class="flex gap-2 justify-center">
                        <!-- Details -->
                        <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner
                            wire:click="openDetailModal({{ $competency->id }})" />

                        <!-- Edit -->
                        <x-button icon="o-pencil-square" class="btn-circle btn-ghost p-2 bg-tetriary" spinner
                            wire:click="openEditModal({{ $competency->id }})" />

                        <!-- Delete -->
                        <x-button icon="o-trash" class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85"
                            spinner
                            wire:click="$dispatch('confirm', {
                                title: 'Are you sure you want to delete?',
                                text: 'This action is permanent and cannot be undone.',
                                action: 'deleteCompetency',
                                id: {{ $competency->id }}
                            })" />
                    </div>
                @endscope
            </x-table>
        </div>
    @endif

    {{-- Modal Add/Edit/View Competency --}}
    <x-modal wire:model="modal" :title="$mode === 'create' ? 'Add Competency' : ($mode === 'edit' ? 'Edit Competency' : 'Competency Detail')" separator box-class="max-w-xl h-fit">
        <x-form wire:submit="save" no-separator>
            <x-input label="Competency Name" wire:model="formData.name" placeholder="Enter competency name"
                :readonly="$mode === 'preview'" class="focus-within:border-0" />

            @if ($mode === 'preview')
                <x-input label="Type" :value="collect($typeOptions)->firstWhere('value', $formData['type'])['label'] ?? $formData['type']" readonly class="focus-within:border-0" />
            @else
                <x-select label="Type" wire:model="formData.type" :options="$typeOptions" option-value="value"
                    option-label="label" placeholder="Select type" class="focus-within:border-0" />
            @endif

            <x-textarea label="Description" wire:model="formData.description" placeholder="Enter description"
                :readonly="$mode === 'preview'" class="focus-within:border-0" rows="3" />

            <x-slot:actions>
                <x-ui.button @click="$wire.modal = false" type="button">Close</x-ui.button>
                @if ($mode !== 'preview')
                    <x-ui.button type="submit" variant="primary" class="btn-primary !text-white" spinner="save">
                        {{ $mode === 'create' ? 'Create' : 'Update' }}
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
