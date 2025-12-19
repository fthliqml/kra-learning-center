<div>
    @livewire('components.confirm-dialog')

    {{-- Success Alert (auto hide) --}}
    @if (!empty($flash))
        <div x-data x-init="setTimeout(() => $wire.set('flash', null), 2500)" class="fixed top-4 inset-x-0 z-[60] flex justify-center">
            @php
                $isError = ($flash['type'] ?? '') === 'error';
                $alertClass = $isError ? 'alert-error' : 'alert-success';
                $icon = $isError ? 'o-x-circle' : 'o-check-badge';
            @endphp
            <x-alert :icon="$icon" :class="" class="shadow-lg {{ $alertClass }}">
                {{ $flash['message'] ?? ($isError ? 'Failed' : 'Success') }}
            </x-alert>
        </div>
    @endif

    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training Requests
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">

            <div class="flex items-center justify-center gap-2">
                @if (auth()->check() && auth()->user()->hasPosition('supervisor'))
                    <!-- Add Button (SPV only) -->
                    <x-ui.button variant="primary" wire:click="openCreateModal" wire:target="openCreateModal"
                        class="h-10" wire:loading.attr="readonly">
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
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="6" :rows="10" targets="search,filter,approve,reject" />

    {{-- No Data State --}}
    @if ($requests->isEmpty())
        <div wire:loading.remove wire:target="search,filter,approve,reject"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no training request records to display at the moment.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove wire:target="search,filter,approve,reject"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
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
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs font-medium">
                        {{ $request->section ?? '-' }}
                    </span>
                @endscope

                {{-- Status --}}
                @scope('cell_status', $request)
                    @php
                        $status = strtolower($request->status ?? 'pending');
                        $classes =
                            [
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
    @endif

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

            @if ($mode === 'preview')
                <x-input label="Group Competency" placeholder="Auto filled from competency"
                    wire:model.defer="formData.group_comp" class="focus-within:border-0" :readonly="true" />
                <x-input label="Competency" :value="$formData['competency_name'] ?? ''" class="focus-within:border-0" :readonly="true" />
            @else
                <x-choices label="Group Competency" wire:model.live="selectedGroupComp" :options="$groupCompOptions"
                    option-value="value" option-label="label" placeholder="Select group competency first..."
                    class="focus-within:border-0" searchable single clearable />

                <x-choices label="Competency" wire:model.live="formData.competency_id" :options="$competencies"
                    option-value="id" option-label="name" :placeholder="$selectedGroupComp ? 'Select competency...' : 'Please select group competency first'" class="focus-within:border-0"
                    :hint="$selectedGroupComp
                        ? 'Type at least 2 chars'
                        : 'Select group competency to enable this field'" searchable single />
            @endif

            @if ($mode === 'preview')
                <x-input label="Reason" placeholder="Enter reason..." wire:model.defer="formData.reason"
                    class="focus-within:border-0" :error="$errors->first('formData.reason')" :readonly="true" />
            @else
                <x-input label="Reason" placeholder="Enter reason..." wire:model.defer="formData.reason"
                    class="focus-within:border-0" :error="$errors->first('formData.reason')" />
            @endif

            <div class="mt-3">
                {{-- Status badge in detail mode --}}
                @if ($mode === 'preview')
                    @php
                        $status = strtolower(
                            $formData['status'] ?? ($requests->firstWhere('id', $selectedId)->status ?? 'pending'),
                        );
                        $classes =
                            [
                                'pending' => 'bg-amber-100 text-amber-700',
                                'approved' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-rose-100 text-rose-700',
                            ][$status] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <div class="text-xs font-semibold">Status</div>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                        {{ ucfirst($status) }}
                    </span>
                @endif
            </div>

            <x-slot:actions>
                <x-ui.button @click="$wire.modal = false"
                    type="button">{{ $mode === 'preview' ? 'Close' : 'Cancel' }}</x-ui.button>
                @if ($mode !== 'preview')
                    <x-ui.button variant="primary" type="submit" wire:target="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Save</span>
                        <span wire:loading wire:target="save">
                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                        </span>
                    </x-ui.button>
                @else
                    @php
                        $user = auth()->user();
                        $canModerate =
                            $user &&
                            method_exists($user, 'hasPosition') &&
                            $user->hasPosition('section_head') &&
                            strtolower($user->section ?? '') === 'lid';
                        $isPending = strtolower($formData['status'] ?? 'pending') === 'pending';
                    @endphp
                    @if ($canModerate && $isPending)
                        <x-ui.button variant="danger" type="button" wire:click="reject" wire:target="reject"
                            wire:loading.attr="disabled"
                            class="bg-rose-600 hover:bg-rose-700 border-rose-600 text-white">
                            <span wire:loading.remove wire:target="reject" class="flex items-center gap-2">
                                <x-icon name="o-x-mark" class="size-4" />
                                Reject
                            </span>
                            <span wire:loading wire:target="reject">
                                <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                            </span>
                        </x-ui.button>
                        <x-ui.button variant="success" type="button" wire:click="approve" wire:target="approve"
                            wire:loading.attr="disabled"
                            class="bg-emerald-600 hover:bg-emerald-700 border-emerald-600 text-white">
                            <span wire:loading.remove wire:target="approve" class="flex items-center gap-2">
                                <x-icon name="o-check" class="size-4" />
                                Approve
                            </span>
                            <span wire:loading wire:target="approve">
                                <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                            </span>
                        </x-ui.button>
                    @endif
                @endif
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
