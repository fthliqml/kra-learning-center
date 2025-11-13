<div>
    @livewire('components.confirm-dialog')

    <!-- Alerts -->
        <div x-data="{show:false,type:'success',message:''}"
         @notify.window="type=$event.detail.type||'success';message=$event.detail.message||'';show=true;setTimeout(()=>show=false,3000)"
         x-show="show" x-transition
            class="fixed top-4 left-1/2 -translate-x-1/2 z-50">
        <div class="flex items-center gap-2 text-white px-4 py-3 rounded shadow-lg"
             :class="{
                 'bg-green-600': type==='success',
                 'bg-red-600': type==='error',
                 'bg-blue-600': type==='info'
             }">
            <template x-if="type==='success'"><x-icon name="o-check-circle" class="size-5" /></template>
            <template x-if="type==='error'"><x-icon name="o-x-circle" class="size-5" /></template>
            <template x-if="type==='info'"><x-icon name="o-information-circle" class="size-5" /></template>
            <span x-text="message"></span>
        </div>
    </div>

    <!-- Header -->
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Certification Module
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">

            <div class="flex items-center justify-center gap-2">
                <!-- Dropdown for Excel actions -->
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

                <!-- Button Add Module -->
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

                <!-- Filter -->
                <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!w-30 !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                    icon-right="o-funnel" />
            </div>

            <!-- Search -->
            <x-search-input placeholder="Search..." class="max-w-md" wire:model.live="search" />
        </div>
    </div>

    <!-- Table -->
    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$modules" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3" with-pagination>
            <!-- No -->
            @scope('cell_no', $module)
                {{ $module->no ?? $loop->iteration }}
            @endscope

            <!-- Code -->
            @scope('cell_code', $module)
                <div class="font-semibold text-gray-800">{{ $module->code }}</div>
            @endscope

            <!-- Level -->
            @scope('cell_level', $module)
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs font-medium">{{ $module->level }}</span>
            @endscope

            <!-- Competency -->
            @scope('cell_competency', $module)
                <div class="truncate max-w-[28ch]" title="{{ $module->competency }}">{{ $module->competency }}</div>
            @endscope

            <!-- Point -->
            @scope('cell_point', $module)
                <span class="text-sm font-semibold text-indigo-600">{{ $module->points_per_module }}</span>
            @endscope

            <!-- New GEX -->
            @scope('cell_new_gex', $module)
                <span class="text-sm font-medium">{{ number_format($module->new_gex, 2) }}</span>
            @endscope

            <!-- Duration -->
            @scope('cell_duration', $module)
                <span class="text-sm">{{ $module->duration }} min</span>
            @endscope

            <!-- Action -->
            @scope('cell_action', $module)
                <div class="flex gap-2 justify-center">
                    <!-- Detail -->
                    <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner wire:click="openDetailModal({{ $module->id }})" />
                    <!-- Edit -->
                    <x-button icon="o-pencil-square" class="btn-circle btn-ghost p-2 bg-tetriary" spinner wire:click="openEditModal({{ $module->id }})" />
                    <!-- Delete -->
                    <x-button icon="o-trash" class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85" spinner
                        wire:click="$dispatch('confirm', { title: 'Delete module?', text: 'This action is permanent.', action: 'deleteModule', id: {{ $module->id }} })" />
                </div>
            @endscope
        </x-table>
    </div>

    <!-- Modal -->
    <x-modal wire:model="modal" :title="$mode === 'create' ? 'Add Certification Module' : ($mode === 'edit' ? 'Edit Certification Module' : 'Preview Certification Module')" separator box-class="max-w-3xl h-fit">

        <x-form wire:submit.prevent="save" no-separator>
            <x-input label="Competency" placeholder="Enter competency title" wire:model.defer="form.competency"
                class="focus-within:border-0" :error="$errors->first('form.competency')" :readonly="$mode === 'preview'" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Code" placeholder="BIP_1" wire:model.defer="form.code"
                    class="focus-within:border-0" :error="$errors->first('form.code')" :readonly="$mode === 'preview'" />

                @if ($mode === 'preview')
                    <x-input label="Level" wire:model="form.level" readonly class="focus-within:border-0" />
                @else
                    <x-select label="Level" wire:model.defer="form.level" :options="$groupOptions"
                        option-value="value" option-label="label" placeholder="Select level"
                        :error="$errors->first('form.level')" />
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if ($mode === 'preview')
                    <x-input label="Group Certification" wire:model="form.group_certification" readonly class="focus-within:border-0" />
                @else
                    <x-select label="Group Certification" wire:model.defer="form.group_certification" :options="$groupCertificationOptions"
                        option-value="value" option-label="label" placeholder="Select group"
                        :error="$errors->first('form.group_certification')" />
                @endif

                <x-input label="Point" type="number" min="0" wire:model.defer="form.points_per_module"
                    class="focus-within:border-0" :error="$errors->first('form.points_per_module')"
                    :readonly="$mode === 'preview'" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="New Gex" type="number" step="0.01" min="0" wire:model.defer="form.new_gex"
                    class="focus-within:border-0" :error="$errors->first('form.new_gex')" :readonly="$mode === 'preview'" />

                <x-input label="Duration (minutes)" type="number" min="1" wire:model.defer="form.duration"
                    class="focus-within:border-0" :error="$errors->first('form.duration')" :readonly="$mode === 'preview'" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Theory Passing Score (%)" type="number" step="0.01" min="0" max="100"
                    wire:model.defer="form.theory_passing_score"
                    class="focus-within:border-0" :error="$errors->first('form.theory_passing_score')" :readonly="$mode === 'preview'" />

                <x-input label="Practical Passing Score (%)" type="number" step="0.01" min="0" max="100"
                    wire:model.defer="form.practical_passing_score"
                    class="focus-within:border-0" :error="$errors->first('form.practical_passing_score')" :readonly="$mode === 'preview'" />
            </div>

            <x-textarea label="Major Component" placeholder="ALL INNER & OUTER PARTS" class="focus-within:border-0"
                wire:model.defer="form.major_component" :error="$errors->first('form.major_component')" :readonly="$mode === 'preview'" />

            <x-textarea label="Mach Model" placeholder="ALL UNIT MODEL" class="focus-within:border-0"
                wire:model.defer="form.mach_model" :error="$errors->first('form.mach_model')" :readonly="$mode === 'preview'" />

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
