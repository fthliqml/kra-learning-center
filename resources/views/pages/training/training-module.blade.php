<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-5 mb-5 lg:mb-9 items-center justify-between">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training Module
        </h1>

        <div
            class="flex gap-3 flex-col w-full lg:w-auto items-center justify-center lg:justify-end md:gap-2 md:flex-row">

            <div class="flex items-center justify-center gap-2">
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

                <x-ui.button variant="primary" wire:click="openCreateModal" wire:target="openCreateModal" class="h-10"
                    wire:loading.attr="readonly">
                    <span wire:loading.remove wire:target="openCreateModal" size="lg"
                        class="flex items-center gap-2">
                        <x-icon name="o-plus" class="size-4" />
                        Add
                    </span>
                    <span wire:loading wire:target="openCreateModal">
                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                    </span>
                </x-ui.button>

                {{-- Filter --}}
                <x-select wire:model.live="filter" :options="$groupCompOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!min-w-[120px] !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="5" :rows="10" targets="search,filter,file,openCreateModal" />

    {{-- No Data State --}}
    @if ($modules->isEmpty())
        <div wire:loading.remove wire:target="search,filter,file,openCreateModal"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no training module records to display at the moment.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove wire:target="search,filter,file,openCreateModal"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$modules" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- Custom cell untuk kolom Nomor --}}
                @scope('cell_no', $module)
                    {{ $module->no ?? $loop->iteration }}
                @endscope

                {{-- Custom cell untuk kolom Action --}}
                @scope('cell_action', $module)
                    <div class="flex gap-2 justify-center">
                        <!-- Detail -->
                        <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner
                            wire:click="openDetailModal({{ $module->id }})" />

                        <!-- Edit -->
                        <x-button icon="o-pencil-square" class="btn-circle btn-ghost p-2 bg-tetriary" spinner
                            wire:click="openEditModal({{ $module->id }})" />

                        <!-- Delete -->
                        <x-button icon="o-trash" class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85"
                            spinner
                            wire:click="$dispatch('confirm', {
                            title: 'Are you sure you want to delete?',
                            text: 'This action is permanent and cannot be undone.',
                            action: 'deleteModule',
                            id: {{ $module->id }}
                        })" />
                    </div>
                @endscope
            </x-table>
        </div>
    @endif

    {{-- Modal --}}
    <x-modal wire:model="modal" :title="$mode === 'create' ? 'Add Training Module' : ($mode === 'edit' ? 'Edit Training Module' : 'Preview Training Module')" separator box-class="max-w-3xl h-fit">

        <x-form wire:submit.prevent="save" no-separator>
            {{-- Title --}}
            <x-input label="Title" placeholder="Title of the training module..." wire:model.defer="formData.title"
                class="focus-within:border-0" :error="$errors->first('formData.title')" :readonly="$mode === 'preview'" />

            @if ($mode === 'preview')
                <x-input label="Competency" placeholder="Competency" wire:model="formData.competency_id"
                    class="focus-within:border-0" readonly />
            @else
                <x-choices label="Competency" wire:model.defer="formData.competency_id" :options="$competencyOptions"
                    option-value="value" option-label="label" placeholder="Select Competency" :error="$errors->first('formData.competency_id')" single
                    searchable class="focus-within:border-0" />
            @endif

            <x-textarea label="Objective" placeholder="Describe the training objectives..."
                class="focus-within:border-0" wire:model.defer="formData.objective" :error="$errors->first('formData.objective')"
                :readonly="$mode === 'preview'" />

            <x-textarea label="Training Content" placeholder="Outline the main topics..."
                class="focus-within:border-0" wire:model.defer="formData.training_content" :error="$errors->first('formData.training_content')"
                :readonly="$mode === 'preview'" />

            <x-input label="Method" placeholder="Describe the development concept..."
                wire:model.defer="formData.method" class="focus-within:border-0" :error="$errors->first('formData.method')"
                :readonly="$mode === 'preview'" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Duration" type="number" wire:model.defer="formData.duration" placeholder="6 Hours"
                    class="focus-within:border-0" min="1" step="0.5" :error="$errors->first('formData.duration')"
                    :readonly="$mode === 'preview'" />

                <x-input label="Frequency" type="number" wire:model.defer="formData.frequency"
                    placeholder="15 Days" class="focus-within:border-0" min="1" :error="$errors->first('formData.frequency')"
                    :readonly="$mode === 'preview'" />
            </div>

            {{-- Actions --}}
            <x-slot:actions>
                <x-ui.button @click="$wire.modal = false" type="button">
                    {{ $mode === 'preview' ? 'Close' : 'Cancel' }}
                </x-ui.button>

                @if ($mode !== 'preview')
                    <x-ui.button variant="primary" type="submit">
                        {{ $mode === 'create' ? 'Save' : 'Update' }}
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
