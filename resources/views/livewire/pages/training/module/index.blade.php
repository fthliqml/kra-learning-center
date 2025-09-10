<div>
    @livewire('components.confirm-dialog')

    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-10
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training Module
        </h1>

        <div class="flex gap-2 w-full justify-between lg:justify-end">
            <x-ui.button variant="primary" wire:click="openCreateModal" wire:target="openCreateModal"
                wire:loading.attr="readonly">
                <span wire:loading.remove wire:target="openCreateModal" class="flex items-center gap-2">
                    <x-icon name="o-plus" class="size-4" />
                    Add
                </span>
                <span wire:loading wire:target="openCreateModal">
                    <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                </span>
            </x-ui.button>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$modules" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3">
            {{-- Custom cell untuk kolom Action --}}
            @scope('cell_action', $module)
                <div class="flex gap-2 justify-center">
                    <!-- Detail -->
                    <x-button icon="o-eye" class="text-white btn-circle btn-ghost p-2 bg-info" spinner
                        wire:click="openPreviewModal({{ $module->id }})" />

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
                :error="$errors->first('formData.title')" :readonly="$mode === 'preview'" />

            <x-select label="Group Competency" wire:model.defer="formData.group_comp" :options="$groupOptions" class="!pl-0"
                option-value="value" option-label="label" placeholder="Select Group Competency" :error="$errors->first('formData.group_comp')"
                :disabled="$mode === 'preview'" />

            <x-textarea label="Objective" placeholder="Describe the training objectives..."
                wire:model.defer="formData.objective" :error="$errors->first('formData.objective')" :readonly="$mode === 'preview'" />

            <x-textarea label="Training Content" placeholder="Outline the main topics..."
                wire:model.defer="formData.training_content" :error="$errors->first('formData.training_content')" :readonly="$mode === 'preview'" />

            <x-input label="Method" placeholder="Describe the development concept..." wire:model.defer="formData.method"
                :error="$errors->first('formData.method')" :readonly="$mode === 'preview'" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Duration" type="number" wire:model.defer="formData.duration" placeholder="6 Hours"
                    min="1" step="0.5" :error="$errors->first('formData.duration')" :readonly="$mode === 'preview'" />

                <x-input label="Frequency" type="number" wire:model.defer="formData.frequency" placeholder="15 Days"
                    min="1" :error="$errors->first('formData.frequency')" :readonly="$mode === 'preview'" />
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
