<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training Requests
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">

            <div class="flex items-center justify-center gap-2">
                <!-- Dropdown Export / Import -->
                {{-- <x-dropdown no-x-anchor right>
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
                </x-dropdown> --}}

                @if (auth()->check() && auth()->user()->hasRole('spv'))
                    <!-- Add Button (SPV only) -->
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
                @endif

                <!-- Filter -->
                <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!w-30 !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live="search" />
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$requests" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
            with-pagination>
            {{-- No --}}
            @scope('cell_no', $request)
                {{ $loop->iteration }}
            @endscope

            {{-- User --}}
            @scope('cell_user', $request)
                <div class="truncate max-w-[40ch] xl:max-w-[52ch]">{{ $request->user_name ?? '-' }}</div>
            @endscope

            {{-- Section --}}
            @scope('cell_section', $request)
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs font-medium">
                    {{ $request->section ?? '-' }}
                </span>
            @endscope

            {{-- Status --}}
            @scope('cell_status', $request)
                @php
                    $status = strtolower($request->status ?? 'pending');
                    $classes = [
                        'pending' => 'bg-amber-100 text-amber-700',
                        'approved' => 'bg-emerald-100 text-emerald-700',
                        'rejected' => 'bg-rose-100 text-rose-700',
                    ][$status] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                    {{ ucfirst($status) }}
                </span>
            @endscope

            {{-- Action --}}
            @scope('cell_action', $request)
                <div class="flex justify-center">
                    <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner
                        wire:click="openDetailModal({{ $request->id }})" />
                </div>
            @endscope
        </x-table>
    </div>

    {{-- Modal Training Request --}}
    <x-modal wire:model="modal" :title="$mode === 'create' ? 'Add Training Request' : 'Training Request Detail'" separator box-class="max-w-3xl h-fit">
        <x-form wire:submit.prevent="save" no-separator>
            @if ($mode === 'preview')
                <x-input label="Name" :value="$formData['user_name'] ?? ''" class="focus-within:border-0" :readonly="true" />
            @else
                <x-choices label="Name" wire:model.live="formData.user_id" :options="$usersSearchable"
                    search-function="userSearch" debounce="300ms" option-value="id" option-label="name"
                    placeholder="Search user name..." class="focus-within:border-0" min-chars=2
                    hint="Type at least 2 chars" searchable single clearable />
            @endif

            <x-input label="Section" placeholder="Auto filled from user" wire:model.defer="formData.section"
                class="focus-within:border-0" :readonly="true" />

            <x-input label="Competency" placeholder="Enter competency..." wire:model.defer="formData.competency"
                class="focus-within:border-0" :error="$errors->first('formData.competency')" :readonly="$mode === 'preview'" />

            <x-input label="Reason" placeholder="Enter reason..." wire:model.defer="formData.reason"
                class="focus-within:border-0" :error="$errors->first('formData.reason')" :readonly="$mode === 'preview'" />

            <div class="mt-3">
                {{-- Status badge in detail mode --}}
                @if ($mode === 'preview')
                    @php
                        $status = strtolower($formData['status'] ?? ($requests->firstWhere('id', $selectedId)->status ?? 'pending'));
                        $classes = [
                            'pending' => 'bg-amber-100 text-amber-700',
                            'approved' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-rose-100 text-rose-700',
                        ][$status] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <div class="text-xs font-semibold">Status</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                        {{ ucfirst($status) }}
                    </span>
                @endif
            </div>

            <x-slot:actions class="mt-5">
                <x-ui.button @click="$wire.modal = false" type="button">{{ $mode === 'preview' ? 'Close' : 'Cancel' }}</x-ui.button>
                @if ($mode !== 'preview')
                    <x-ui.button variant="primary" type="submit" wire:target="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Save</span>
                        <span wire:loading wire:target="save">
                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                        </span>
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
