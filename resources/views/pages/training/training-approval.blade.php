<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training Approval
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">

            <div class="flex items-center justify-center gap-2">
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
    <x-skeletons.table :columns="5" :rows="10" targets="search,filter,approve,reject" />

    {{-- No Data State --}}
    @if ($approvals->isEmpty())
        <div wire:loading.remove wire:target="search,filter,approve,reject"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no training records to display at the moment.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove wire:target="search,filter,approve,reject"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$approvals" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- No --}}
                @scope('cell_no', $approval, $approvals)
                    {{ ($approvals->currentPage() - 1) * $approvals->perPage() + $loop->iteration }}
                @endscope

                {{-- Training Name --}}
                @scope('cell_training_name', $approval)
                    <div class="truncate max-w-[50ch] xl:max-w-[60ch]">{{ $approval->training_name ?? '-' }}</div>
                @endscope

                {{-- Date --}}
                @scope('cell_date', $approval)
                    <div class="text-sm">
                        {{ $approval->start_date ? \Carbon\Carbon::parse($approval->start_date)->format('d M Y') : '-' }}
                        @if ($approval->start_date && $approval->end_date && $approval->start_date != $approval->end_date)
                            <span class="text-gray-400">-</span>
                            {{ \Carbon\Carbon::parse($approval->end_date)->format('d M Y') }}
                        @endif
                    </div>
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
    @endif

    {{-- Modal Training Approval --}}
    <x-modal wire:model="modal" title="Training Request Detail" separator box-class="max-w-5xl h-fit">
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg mb-4 flex items-center gap-2">
            <p class="text-sm text-green-700">
                Participants marked as <span class="font-bold text-rose-600">Failed</span> will
                not receive a training certificate even if the training is approved.
            </p>
        </div>
        {{-- Tabs --}}
        <x-tabs wire:model="activeTab">
            <x-tab name="information" label="Information" icon="o-information-circle">
                <x-form no-separator>
                    <x-input label="Training Name" :value="$formData['training_name'] ?? ''" class="focus-within:border-0" :readonly="true" />

                    <div class="grid grid-cols-2 gap-4">
                        <x-input label="Type" :value="$formData['type'] ?? ''" class="focus-within:border-0" :readonly="true" />

                        <x-input label="Group/Competency" :value="$formData['group_comp'] ?? ''" class="focus-within:border-0"
                            :readonly="true" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-input label="Start Date" :value="$formData['start_date'] ?? ''" class="focus-within:border-0"
                            :readonly="true" />

                        <x-input label="End Date" :value="$formData['end_date'] ?? ''" class="focus-within:border-0" :readonly="true" />
                    </div>

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
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                            {{ ucfirst($status) }}
                        </span>
                    </div>
                </x-form>
            </x-tab>

            <x-tab name="participants" label="Participants" icon="o-user-group">
                <div class="overflow-x-auto">
                    @if ($this->participants->isEmpty())
                        <div class="flex flex-col items-center justify-center py-12 px-4 text-gray-500">
                            <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-sm">No participants found</p>
                        </div>
                    @else
                        <x-table :headers="$this->participantHeaders()" :rows="$this->participants" striped
                            class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3">
                            {{-- No --}}
                            @scope('cell_no', $participant)
                                <div class="text-center">{{ $participant->no ?? $loop->iteration }}</div>
                            @endscope

                            {{-- NRP --}}
                            @scope('cell_nrp', $participant)
                                <div class="font-mono text-sm">{{ $participant->nrp }}</div>
                            @endscope

                            {{-- Name --}}
                            @scope('cell_name', $participant)
                                <div class="truncate max-w-[200px]">{{ $participant->name }}</div>
                            @endscope

                            {{-- Section --}}
                            @scope('cell_section', $participant)
                                <div class="text-center text-sm">{{ $participant->section }}</div>
                            @endscope

                            {{-- Score --}}
                            @scope('cell_score', $participant)
                                <div
                                    class="text-center font-semibold {{ $participant->score_raw !== null ? 'text-primary' : 'text-gray-400' }}">
                                    {{ $participant->score }}
                                </div>
                            @endscope

                            {{-- Status --}}
                            @scope('cell_status', $participant)
                                <div class="flex justify-center">
                                    @php
                                        $participantStatus = strtolower($participant->status);
                                    @endphp
                                    @if ($participantStatus === 'passed')
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-700 gap-1">
                                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Passed
                                        </span>
                                    @elseif ($participantStatus === 'failed')
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-600 text-white border border-rose-700 gap-1 shadow-sm">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Failed
                                        </span>
                                    @elseif ($participantStatus === 'in_progress')
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">
                                            In Progress
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">
                                            {{ ucfirst(str_replace('_', ' ', $participant->status)) }}
                                        </span>
                                    @endif
                                </div>
                            @endscope

                            {{-- Certificate Download --}}
                            @scope('cell_certificate', $participant)
                                <div class="flex justify-center">
                                    @php
                                        $participantStatus = strtolower($participant->status);
                                        $hasCertificate =
                                            $participantStatus === 'passed' &&
                                            !empty($participant->certificate_path) &&
                                            !empty($participant->assessment_id);
                                    @endphp
                                    @if ($hasCertificate)
                                        <a href="{{ route('certificate.training.download', $participant->assessment_id) }}"
                                            target="_blank"
                                            class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-primary hover:bg-primary/90 rounded transition-colors gap-1"
                                            title="View Certificate">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </a>
                                    @elseif ($participantStatus === 'passed')
                                        <span class="text-xs text-amber-600 italic">Pending</span>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </div>
                            @endscope
                        </x-table>
                    @endif
                </div>
            </x-tab>
        </x-tabs>

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
    </x-modal>
</div>
