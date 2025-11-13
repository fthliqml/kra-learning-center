<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Certification Approval
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">

            <div class="flex items-center justify-center gap-2">
                <!-- Filter -->
                <x-select wire:model.live="filter" :options="$groupOptions" option-value="value" option-label="label"
                    placeholder="All"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.certification-approval-table />

    {{-- Table --}}
    <div wire:loading.remove wire:target="search,filter,approve,reject"
        class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$approvals" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
            with-pagination>
            {{-- No --}}
            @scope('cell_no', $approval)
                {{ $loop->iteration }}
            @endscope

            {{-- Certification Name --}}
            @scope('cell_certification_name', $approval)
                <div class="truncate max-w-[50ch] xl:max-w-[60ch]">{{ $approval->certification_name ?? '-' }}</div>
            @endscope

            {{-- Date --}}
            @scope('cell_date', $approval)
                <div class="text-sm">{{ \Carbon\Carbon::parse($approval->date)->format('d M Y') }}</div>
            @endscope

            {{-- Status --}}
            @scope('cell_status', $approval)
                @php
                    $status = strtolower($approval->status ?? 'pending');
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
            @scope('cell_action', $approval)
                <div class="flex justify-center">
                    <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner
                        wire:click="openDetailModal({{ $approval->id }})" />
                </div>
            @endscope
        </x-table>
    </div>

    {{-- Modal Certification Approval --}}
    <x-modal wire:model="modal" title="Certification Request Detail" separator box-class="max-w-3xl h-fit">
        <x-form no-separator>
            <x-input label="Certification Name" :value="$formData['certification_name'] ?? ''" class="focus-within:border-0" :readonly="true" />

            <x-input label="Module" :value="$formData['module_name'] ?? ''" class="focus-within:border-0" :readonly="true" />

            <x-input label="Date" :value="$formData['created_at'] ?? ''" class="focus-within:border-0" :readonly="true" />

            <x-input label="Competency" :value="$formData['competency'] ?? ''" class="focus-within:border-0" :readonly="true" />

            <div class="mt-3">
                {{-- Status badge --}}
                @php
                    $status = strtolower($formData['status'] ?? 'pending');
                    $classes =
                        [
                            'pending' => 'bg-amber-100 text-amber-700',
                            'approved' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-rose-100 text-rose-700',
                        ][$status] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <div class="text-xs font-semibold">Status</div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                    {{ ucfirst($status) }}
                </span>
            </div>

            <x-slot:actions>
                <x-ui.button @click="$wire.modal = false" type="button">Close</x-ui.button>
                @php
                    $canModerate =
                        auth()->check() &&
                        auth()->user()->hasRole('leader') &&
                        strtolower(auth()->user()->section ?? '') === 'lid';
                    $isPending = strtolower($formData['status'] ?? 'pending') === 'pending';
                @endphp
                @if ($canModerate && $isPending)
                    <x-ui.button variant="danger" type="button" wire:click="reject" wire:target="reject"
                        wire:loading.attr="disabled" class="bg-rose-600 hover:bg-rose-700 border-rose-600 text-white">
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
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
