<div>
    @livewire('components.confirm-dialog')

    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training Module
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
                    <span wire:loading.remove wire:target="openCreateModal" size="lg"
                        class="flex items-center gap-2">
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

            <x-search-input placeholder="Cari modul..." class="max-w-md" wire:model.live="search" />
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$modules" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
            with-pagination>
            {{-- Custom cell untuk kolom Nomor --}}
            @scope('cell_no', $module)
                {{ $module->no ?? $loop->iteration }}
            @endscope

            {{-- Custom cell untuk kolom Action --}}
            @scope('cell_action', $module)
                <div class="flex gap-2 justify-center">

                    <!-- Edit -->
                    <x-button icon="o-pencil-square" class="btn-circle btn-ghost p-2 bg-tetriary" spinner
                        wire:click="openEditModal({{ $module->id }})" />

                    <!-- Delete -->
                    <x-button icon="o-trash" class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85" spinner
                        wire:click="$dispatch('confirm', {
                            title: 'Yakin mau hapus?',
                            text: 'Data yang sudah dihapus tidak bisa dikembalikan!',
                            action: 'deleteModule',
                            id: {{ $module->id }}
                        })" />
                </div>
            @endscope

        </x-table>
    </div>

    <x-modal wire:model="modal" :title="$mode === 'create' ? 'Add Training Module' : ($mode === 'edit' ? 'Edit Training Module' : 'Preview Training Module')" separator box-class="max-w-3xl h-fit">

        <x-form wire:submit.prevent="save" no-separator>
            {{-- Title --}}
            <x-input label="Title" placeholder="Title of the training module..." wire:model.defer="formData.title"
                class="focus-within:border-0" :error="$errors->first('formData.title')" :readonly="$mode === 'preview'" />

            <x-select label="Group Competency" wire:model.defer="formData.group_comp" :options="$groupOptions"
                option-value="value" option-label="label" placeholder="Select Group Competency" :error="$errors->first('formData.group_comp')"
                :disabled="$mode === 'preview'" />

            <x-textarea label="Objective" placeholder="Describe the training objectives..."
                class="focus-within:border-0" wire:model.defer="formData.objective" :error="$errors->first('formData.objective')"
                :readonly="$mode === 'preview'" />

            <x-textarea label="Training Content" placeholder="Outline the main topics..." class="focus-within:border-0"
                wire:model.defer="formData.training_content" :error="$errors->first('formData.training_content')" :readonly="$mode === 'preview'" />

            <x-input label="Method" placeholder="Describe the development concept..." wire:model.defer="formData.method"
                class="focus-within:border-0" :error="$errors->first('formData.method')" :readonly="$mode === 'preview'" />

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
