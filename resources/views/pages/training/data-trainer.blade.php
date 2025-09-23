<div>
    @livewire('components.confirm-dialog')

    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Data Trainer
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">

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
                    <span wire:loading.remove wire:target="openCreateModal" class="flex items-center gap-2">
                        <x-icon name="o-plus" class="size-4" />
                        Add
                    </span>
                    <span wire:loading wire:target="openCreateModal">
                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                    </span>
                </x-ui.button>

                <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                    placeholder="Filter"
                    class="!w-30 !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Cari trainer..." class="max-w-72" wire:model.live="search" />
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
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
                    <x-button icon="o-trash" class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85" spinner
                        wire:click="$dispatch('confirm', {
                            title: 'Yakin mau hapus?',
                            text: 'Data yang sudah dihapus tidak bisa dikembalikan!',
                            action: 'deleteTrainer',
                            id: {{ $trainer->id }}
                        })" />
                </div>
            @endscope
        </x-table>
    </div>

    <x-modal wire:model="modal" :title="$mode === 'create' ? 'Add Data Trainer' : ($mode === 'edit' ? 'Edit Data Trainer' : 'Preview Data Trainer')" separator box-class="max-w-3xl h-fit">

        <x-form wire:submit.prevent="save" no-separator>
            {{-- Trainer Type & Name --}}
            @if ($mode === 'preview')
                <x-input label="Trainer Name" placeholder="Name of the trainer..." wire:model.defer="formData.name"
                    class="focus-within:border-0" :error="$errors->first('formData.name')" :readonly="true" />
            @elseif ($mode === 'create')
                <div class="grid gap-3 md:grid-cols-2">
                    <x-select label="Trainer Type" wire:model.live="formData.trainer_type" :options="[
                        ['value' => 'internal', 'label' => 'Internal'],
                        ['value' => 'external', 'label' => 'External'],
                    ]"
                        option-value="value" option-label="label" placeholder="Select Type" :error="$errors->first('formData.trainer_type')" />

                    @if (($formData['trainer_type'] ?? 'internal') === 'internal')
                        <div class="relative" x-data="{ showDropdown: @entangle('filteredUsers').live }" x-on:click.outside="$wire.filteredUsers = []">
                            <x-input label="Trainer Name" placeholder="Type trainer name..."
                                wire:model.live.debounce.300ms="trainerNameSearch" class="focus-within:border-0"
                                :error="$errors->first('formData.user_id')" autocomplete="off" />

                            @if (!empty($trainerNameSearch) && empty($formData['user_id']))
                                <div
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    @if (!empty($filteredUsers))
                                        @foreach ($filteredUsers as $user)
                                            <div class="px-4 py-2 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150"
                                                wire:click="selectTrainer({{ $user['value'] }}, '{{ addslashes($user['label']) }}')">
                                                <span class="text-gray-900">{{ $user['label'] }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="px-4 py-2 text-gray-500 text-sm">
                                            <div class="flex items-center mb-2">
                                                <x-icon name="o-exclamation-circle"
                                                    class="w-4 h-4 mr-2 text-amber-500" />
                                                No trainers found with name "{{ $trainerNameSearch }}"
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                Try searching with different keywords or add as external trainer
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <x-input label="Trainer Name" placeholder="Name of the trainer..."
                            wire:model.live.debounce.500ms="formData.name" class="focus-within:border-0"
                            :error="$errors->first('formData.name')" />
                    @endif
                </div>
            @elseif ($mode === 'edit')
                @if (($formData['trainer_type'] ?? 'internal') === 'internal')
                    <div class="relative" x-data="{ showDropdown: @entangle('filteredUsers').live }" x-on:click.outside="$wire.filteredUsers = []">
                        <x-input label="Trainer Name" placeholder="Type trainer name..."
                            wire:model.live.debounce.300ms="trainerNameSearch" class="focus-within:border-0"
                            :error="$errors->first('formData.user_id')" autocomplete="off" />

                        @if (!empty($trainerNameSearch) && empty($formData['user_id']))
                            <div
                                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                @if (!empty($filteredUsers))
                                    @foreach ($filteredUsers as $user)
                                        <div class="px-4 py-2 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150"
                                            wire:click="selectTrainer({{ $user['value'] }}, '{{ addslashes($user['label']) }}')">
                                            <span class="text-gray-900">{{ $user['label'] }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="px-4 py-2 text-gray-500 text-sm">
                                        <div class="flex items-center mb-2">
                                            <x-icon name="o-exclamation-circle" class="w-4 h-4 mr-2 text-amber-500" />
                                            No trainers found with name "{{ $trainerNameSearch }}"
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            Try searching with different keywords or add as external trainer
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <x-input label="Trainer Name" placeholder="Name of the trainer..."
                        wire:model.live.debounce.500ms="formData.name" class="focus-within:border-0"
                        :error="$errors->first('formData.name')" />
                @endif
            @endif

            <x-input label="Institution" placeholder="Institution name..." wire:model.defer="formData.institution"
                class="focus-within:border-0" :error="$errors->first('formData.institution')" :readonly="$mode === 'preview'" />

            @if (!empty($duplicateWarning) && $mode === 'create')
                <div class="bg-amber-50 border border-amber-200 rounded-md p-3">
                    <div class="flex items-start">
                        <x-icon name="o-exclamation-triangle"
                            class="w-5 h-5 text-amber-500 mr-2 mt-0.5 flex-shrink-0" />
                        <div>
                            <p class="text-sm text-amber-800 font-medium">Trainer Sudah Terdaftar</p>
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
                        <div class="grid grid-cols-1 md:grid-cols-12 items-start gap-2">
                            <div class="md:col-span-11">
                                <x-input class="w-full focus-within:border-0" placeholder="Describe the competency..."
                                    wire:model.defer="formData.competencies.{{ $i }}" :error="$errors->first('formData.competencies.' . $i)" />
                            </div>
                            <div class="md:col-span-1">
                                <x-ui.button type="button"
                                    class="w-full bg-danger text-white hover:bg-red-400 hover:text-white border-0 shadow-none"
                                    size="lg" wire:click="removeCompetencyRow({{ $i }})"
                                    :disabled="count($formData['competencies'] ?? []) <= 1">
                                    <x-icon name="o-trash" class="size-5" />
                                </x-ui.button>
                            </div>
                        </div>
                    @endforeach

                    <div>
                        <x-ui.button type="button" variant="primary" size="sm" outline
                            wire:click="addCompetencyRow">
                            + Add Row
                        </x-ui.button>
                        @error('formData.competencies')
                            <div class="text-error text-sm mt-1">{{ $message }}</div>
                        @enderror
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
