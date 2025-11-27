<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-5 mb-5 lg:mb-9 items-center justify-between">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Data Trainer
        </h1>

        <div
            class="flex gap-3 flex-col w-full lg:w-auto items-center justify-center lg:justify-end md:gap-2 md:flex-row">

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

                {{-- Add Button --}}
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

                {{-- Filter --}}
                <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!min-w-[120px] !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />
            </div>

            {{-- Search --}}
            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="7" :rows="10" targets="search,filter,file,openCreateModal" />

    {{-- No Data State --}}
    @if ($trainers->isEmpty())
        <div wire:loading.remove wire:target="search,filter,file,openCreateModal"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no trainer records to display at the moment.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove wire:target="search,filter,file,openCreateModal"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$trainers" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- Custom cell untuk kolom Nomor --}}
                @scope('cell_no', $trainer)
                    {{ $trainer->no ?? $loop->iteration }}
                @endscope

                {{-- Custom cell untuk kolom Trainer Name --}}
                @scope('cell_name', $trainer)
                    {{ optional($trainer->user)->name ?? $trainer->name }}
                @endscope

                {{-- Custom cell untuk kolom Action --}}
                @scope('cell_action', $trainer)
                    <div class="flex gap-2 justify-center">

                        <!-- Details -->
                        <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner
                            wire:click="openDetailModal({{ $trainer->id }})" />

                        <!-- Edit -->
                        <x-button icon="o-pencil-square" class="btn-circle btn-ghost p-2 bg-tetriary" spinner
                            wire:click="openEditModal({{ $trainer->id }})" />

                        <!-- Delete -->
                        <x-button icon="o-trash" class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85"
                            spinner
                            wire:click="$dispatch('confirm', {
                            title: 'Are you sure you want to delete?',
                            text: 'This action is permanent and cannot be undone.',
                            action: 'deleteTrainer',
                            id: {{ $trainer->id }}
                        })" />
                    </div>
                @endscope
            </x-table>
        </div>

        {{-- Modal --}}
        <x-modal wire:model="modal" :title="$mode === 'create' ? 'Add Data Trainer' : ($mode === 'edit' ? 'Edit Data Trainer' : 'Preview Data Trainer')" separator box-class="max-w-3xl h-fit">

            <x-form wire:submit.prevent="save" no-separator>
                {{-- Trainer Type & Name --}}
                @if ($mode === 'preview')
                    <x-input label="Trainer Name" placeholder="Name of the trainer..." wire:model.defer="formData.name"
                        class="focus-within:border-0" :error="$errors->first('formData.name')" :readonly="true" />
                @elseif ($mode === 'create')
                    <x-choices label="Trainer Type" wire:model.live="formData.trainer_type" :options="[
                        ['value' => 'internal', 'label' => 'Internal'],
                        ['value' => 'external', 'label' => 'External'],
                    ]"
                        option-value="value" option-label="label" placeholder="Select trainer type..."
                        class="focus-within:border-0" :error="$errors->first('formData.trainer_type')" single clearable />

                    @if (($formData['trainer_type'] ?? 'internal') === 'internal')
                        <x-choices label="Trainer Name" wire:model.live="formData.user_id" :options="$trainersSearchable"
                            search-function="trainerSearch" debounce="300ms" option-value="id" option-label="name"
                            placeholder="Search trainer name..." class="focus-within:border-0" min-chars=2
                            hint="Type at least 2 chars" searchable single clearable
                            wire:change="checkDuplicateTrainer" />
                    @else
                        <x-input label="Trainer Name" placeholder="Name of the trainer..."
                            wire:model.live.debounce.500ms="formData.name" class="focus-within:border-0"
                            :error="$errors->first('formData.name')" />
                    @endif
                @elseif ($mode === 'edit')
                    @if (($formData['trainer_type'] ?? 'internal') === 'internal')
                        <x-choices label="Trainer Name" wire:model.live="formData.user_id" :options="$trainersSearchable"
                            search-function="trainerSearch" debounce="300ms" option-value="id" option-label="name"
                            placeholder="Search trainer name..." class="focus-within:border-0" min-chars=2
                            hint="Type at least 2 chars" searchable single clearable
                            wire:change="checkDuplicateTrainer" />
                    @else
                        <x-input label="Trainer Name" placeholder="Name of the trainer..."
                            wire:model.live.debounce.500ms="formData.name" class="focus-within:border-0"
                            :error="$errors->first('formData.name')" />
                    @endif
                @endif

                <x-input label="Institution" placeholder="Institution name..."
                    wire:model.defer="formData.institution" class="focus-within:border-0" :error="$errors->first('formData.institution')"
                    :readonly="$mode === 'preview'" />

                @if (!empty($duplicateWarning) && $mode === 'create')
                    <div class="bg-amber-50 border border-amber-200 rounded-md p-3">
                        <div class="flex items-start">
                            <x-icon name="o-exclamation-triangle"
                                class="w-5 h-5 text-amber-500 mr-2 mt-0.5 flex-shrink-0" />
                            <div>
                                <p class="text-sm text-amber-800 font-medium">Trainer Already Registered</p>
                                <p class="text-xs text-amber-600 mt-1">{{ $duplicateWarning }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-3">
                    <label class="label p-0">
                        <span class="label-text text-xs leading-[18px] font-semibold text-[#123456]">Competency</span>
                    </label>
                    @if ($mode === 'preview')
                        @foreach ($formData['competencies'] ?? [] as $i => $comp)
                            <x-input class="w-full" placeholder="Describe the competency..." :readonly="true"
                                :value="$comp" />
                        @endforeach
                    @else
                        @foreach ($formData['competencies'] ?? [''] as $i => $comp)
                            <div class="group">
                                <div class="relative">
                                    <x-input class="w-full pr-12 focus-within:border-0"
                                        placeholder="Describe the competency..."
                                        wire:model.defer="formData.competencies.{{ $i }}"
                                        :error="$errors->first('formData.competencies.' . $i)" />
                                    <button type="button" wire:click="removeCompetencyRow({{ $i }})"
                                        title="Remove competency" @disabled(count($formData['competencies'] ?? []) <= 1)
                                        class="absolute inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent
                                        transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:text-gray-300
                                        hover:bg-red-50 hover:text-red-600 active:scale-[0.95]
                                        focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1">
                                        <x-icon name="o-trash" class="size-4" />
                                        <span class="sr-only">Remove</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        @error('formData.competencies')
                            <div class="text-error text-xs mt-1">{{ $message }}</div>
                        @enderror

                        <div>
                            <x-ui.button type="button" variant="primary" size="sm" outline
                                wire:click="addCompetencyRow">
                                + Add Row
                            </x-ui.button>
                        </div>
                    @endif
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
