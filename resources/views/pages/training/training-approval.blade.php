<div>
    @livewire('components.confirm-dialog')

    {{-- Global loading overlay for Dept Head approval (certificate generation) --}}
    <div wire:loading.flex wire:target="approve"
        class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg px-6 py-4 flex items-center gap-3 max-w-sm mx-4">
            <x-icon name="o-arrow-path" class="size-6 text-primary animate-spin" />
            <div class="text-sm text-gray-800">
                <div class="font-semibold">Generating certificates</div>
                <div class="text-xs text-gray-500 mt-0.5">Please wait while training certificates are being
                    generated...</div>
            </div>
        </div>
    </div>

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

    {{-- Signature Info & Upload (for LID leaders) --}}
    @php
        $currentUser = auth()->user();
        $canUploadSignature =
            $currentUser &&
            method_exists($currentUser, 'hasPosition') &&
            (($currentUser->hasPosition('section_head') && strtolower($currentUser->section ?? '') === 'lid') ||
                ($currentUser->hasPosition('department_head') &&
                    ($currentUser->department ?? '') === 'Human Capital, General Service, Security & LID'));
        $currentSignature = $currentUser?->signature;
    @endphp

    @if ($canUploadSignature)
        <div class="mb-5 rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="px-4 pt-3 pb-2 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-gray-800">Digital Signature</div>
                    <div class="text-[11px] text-gray-500">Used on training certificates approved from this page.</div>
                </div>
            </div>

            <div class="px-4 py-3 space-y-3">
                @if ($currentSignature && $currentSignature->path)
                    <div class="flex items-start gap-3 p-3 rounded-md bg-emerald-50 border border-emerald-200">
                        <div class="mt-0.5">
                            <x-icon name="o-check-circle" class="size-5 text-emerald-500" />
                        </div>
                        <div class="text-xs text-emerald-800">
                            <div class="font-semibold">Digital signature is already uploaded.</div>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-emerald-700">Current signature preview:</span>
                                <img src="{{ asset('storage/' . $currentSignature->path) }}" alt="Signature preview"
                                    class="h-8 object-contain border border-emerald-200 bg-white px-2 py-1 rounded" />
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-start gap-3 p-3 rounded-md bg-amber-50 border border-amber-200">
                        <div class="mt-0.5">
                            <x-icon name="o-exclamation-triangle" class="size-5 text-amber-500" />
                        </div>
                        <div class="text-xs text-amber-800">
                            <div class="font-semibold">No digital signature uploaded yet.</div>
                            <div class="mt-1">Please upload your signature before approving training certificates.
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col md:flex-row items-start md:items-center gap-3 text-xs w-full">
                    <input type="file" wire:model="signatureFile" accept="image/*"
                        class="file-input file-input-sm file-input-bordered w-full md:flex-1" />
                    <x-ui.button type="button" variant="secondary" wire:click="uploadSignature"
                        wire:target="uploadSignature,signatureFile" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="uploadSignature" class="flex items-center gap-2">
                            <x-icon name="o-arrow-up-tray" class="size-3" />
                            Upload Signature
                        </span>
                        <span wire:loading wire:target="uploadSignature">
                            <x-icon name="o-arrow-path" class="size-3 animate-spin" />
                        </span>
                    </x-ui.button>
                </div>

                @error('signatureFile')
                    <div class="text-xs text-rose-600">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif

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
                        $isLevel1Approved = !empty($approval->section_head_signed_at);
                        $isLevel2Approved = !empty($approval->dept_head_signed_at);

                        if ($status === 'done' && !$isLevel1Approved) {
                            $classes = 'bg-amber-100 text-amber-700';
                            $statusLabel = 'Waiting Section Head Approval';
                        } elseif ($status === 'done' && $isLevel1Approved && !$isLevel2Approved) {
                            $classes = 'bg-blue-100 text-blue-700';
                            $statusLabel = 'Waiting Dept Head Approval';
                        } else {
                            $map = [
                                'pending' => ['bg-amber-100 text-amber-700', 'Pending'],
                                'approved' => ['bg-emerald-100 text-emerald-700', 'Approved'],
                                'rejected' => ['bg-rose-100 text-rose-700', 'Rejected'],
                                'done' => ['bg-blue-100 text-blue-700', 'Ready for Approval'],
                            ];
                            [$classes, $statusLabel] = $map[$status] ?? ['bg-gray-100 text-gray-700', ucfirst($status)];
                        }
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                        {{ $statusLabel }}
                    </span>
                @endscope

                {{-- Action --}}
                @scope('cell_action', $approval)
                    @php
                        $user = auth()->user();

                        $isDeptHead =
                            $user &&
                            method_exists($user, 'hasPosition') &&
                            $user->hasPosition('department_head') &&
                            ($user->department ?? '') === 'Human Capital, General Service, Security & LID';

                        $status = strtolower($approval->status ?? '');
                        $hasLevel1Approval = !empty($approval->section_head_signed_at ?? null);
                        $hasLevel2Approval = !empty($approval->dept_head_signed_at ?? null);

                        // Row-level Dept Head actions: training done, level 1 approved, not yet level 2
                        $showDeptHeadRowActions =
                            $isDeptHead && $status === 'done' && $hasLevel1Approval && !$hasLevel2Approval;
                    @endphp
                    <div class="flex justify-center gap-1">
                        <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner
                            wire:click="openDetailModal({{ $approval->id }})" />

                        @if ($showDeptHeadRowActions)
                            <x-button icon="o-x-mark"
                                class="btn-circle btn-ghost p-2 bg-rose-600 text-white hover:bg-rose-700" spinner
                                wire:click="reject({{ $approval->id }})" />

                            <x-button icon="o-check"
                                class="btn-circle btn-ghost p-2 bg-emerald-600 text-white hover:bg-emerald-700" spinner
                                wire:click="approve({{ $approval->id }})" />
                        @endif
                    </div>
                @endscope
            </x-table>
        </div>
    @endif

    {{-- Modal Training Approval --}}
    <x-modal wire:model="modal" title="Training Detail" separator box-class="max-w-5xl h-fit">
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
                    <x-input label="Training Name" :value="$formData['training_name'] ?? ''" class="focus-within:border-0"
                        :readonly="true" />

                    <div class="grid grid-cols-2 gap-4">
                        <x-input label="Type" :value="$formData['type'] ?? ''" class="focus-within:border-0" :readonly="true" />

                        <x-input label="Group/Competency" :value="$formData['group_comp'] ?? ''" class="focus-within:border-0"
                            :readonly="true" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-input label="Start Date" :value="$formData['start_date'] ?? ''" class="focus-within:border-0"
                            :readonly="true" />

                        <x-input label="End Date" :value="$formData['end_date'] ?? ''" class="focus-within:border-0"
                            :readonly="true" />
                    </div>

                    <div class="mt-3">
                        {{-- Status badge --}}
                        @php
                            $status = strtolower($formData['status'] ?? 'pending');
                            $isLevel1Approved = !empty($formData['section_head_signed_at'] ?? null);
                            $isLevel2Approved = !empty($formData['dept_head_signed_at'] ?? null);

                            if ($status === 'done' && !$isLevel1Approved) {
                                $classes = 'bg-amber-100 text-amber-700';
                                $statusLabel = 'Waiting Section Head Approval';
                            } elseif ($status === 'done' && $isLevel1Approved && !$isLevel2Approved) {
                                $classes = 'bg-blue-100 text-blue-700';
                                $statusLabel = 'Waiting Dept Head Approval';
                            } else {
                                $map = [
                                    'pending' => ['bg-amber-100 text-amber-700', 'Pending'],
                                    'approved' => ['bg-emerald-100 text-emerald-700', 'Approved'],
                                    'rejected' => ['bg-rose-100 text-rose-700', 'Rejected'],
                                    'done' => ['bg-blue-100 text-blue-700', 'Ready for Approval'],
                                ];
                                [$classes, $statusLabel] = $map[$status] ?? [
                                    'bg-gray-100 text-gray-700',
                                    ucfirst($status),
                                ];
                            }
                        @endphp
                        <div class="text-xs font-semibold">Status</div>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                            {{ $statusLabel }}
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
                                <div class="text-sm">{{ $participant->section }}</div>
                            @endscope

                            {{-- Attendance --}}
                            @scope('cell_attendance', $participant)
                                <div
                                    class="text-center text-sm font-semibold {{ ($participant->attendance_raw ?? 0) >= 75 ? 'text-emerald-600' : (($participant->attendance_raw ?? null) !== null ? 'text-rose-600' : 'text-gray-400') }}">
                                    {{ $participant->attendance }}
                                </div>
                            @endscope

                            {{-- Theory Score --}}
                            @scope('cell_theory_score', $participant)
                                <div
                                    class="text-center font-semibold {{ $participant->theory_score !== null ? 'text-primary' : 'text-gray-400' }}">
                                    {{ $participant->theory_score !== null ? number_format($participant->theory_score, 0) : '-' }}
                                </div>
                            @endscope

                            {{-- Practice Score --}}
                            @scope('cell_practice_score', $participant)
                                <div
                                    class="text-center font-semibold {{ $participant->practice_score !== null ? 'text-primary' : 'text-gray-400' }}">
                                    @if ($participant->practice_score !== null)
                                        @php
                                            $grade = match (true) {
                                                $participant->practice_score >= 90 => 'A',
                                                $participant->practice_score >= 81 => 'B',
                                                $participant->practice_score >= 71 => 'C',
                                                $participant->practice_score >= 61 => 'D',
                                                default => 'E',
                                            };
                                        @endphp
                                        {{ $grade }}
                                    @else
                                        -
                                    @endif
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
                                        // Ambil status training dari row peserta (lebih andal),
                                        // fallback ke formData jika belum ada
                                        $trainingStatus = strtolower(
                                            $participant->training_status ?? ($formData['status'] ?? ''),
                                        );
                                        $hasCertificate =
                                            $participantStatus === 'passed' &&
                                            !empty($participant->certificate_path) &&
                                            !empty($participant->assessment_id);

                                        // Generate certificate number format
                                        $certNumber = null;
                                        if ($hasCertificate && $participant->assessment_id) {
                                            $groupComp = $formData['group_comp'] ?? 'BMC';
                                            $certNumber =
                                                $groupComp .
                                                '/C/' .
                                                date('Y') .
                                                '/' .
                                                str_pad($participant->assessment_id, 4, '0', STR_PAD_LEFT);
                                        }
                                    @endphp
                                    @if ($trainingStatus === 'rejected')
                                        <span class="text-xs text-gray-400">-</span>
                                    @elseif ($hasCertificate && $certNumber)
                                        <a href="{{ route('certificate.training.view', $participant->assessment_id) }}"
                                            target="_blank"
                                            class="text-primary hover:text-primary/80 hover:underline text-xs font-medium transition-colors"
                                            title="View Certificate">
                                            {{ $certNumber }}
                                        </a>
                                    @elseif ($trainingStatus === 'done' && $participantStatus === 'passed')
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
                $user = auth()->user();
                $status = strtolower($formData['status'] ?? '');

                $isSectionHead =
                    $user &&
                    method_exists($user, 'hasPosition') &&
                    $user->hasPosition('section_head') &&
                    strtolower($user->section ?? '') === 'lid';

                $isDeptHead =
                    $user &&
                    method_exists($user, 'hasPosition') &&
                    $user->hasPosition('department_head') &&
                    ($user->department ?? '') === 'Human Capital, General Service, Security & LID';

                $hasLevel1Approval = !empty($formData['section_head_signed_at'] ?? null);
                $hasLevel2Approval = !empty($formData['dept_head_signed_at'] ?? null);

                // Level 1 actions: Section Head LID, training done, no approvals yet
                $showLevel1Actions = $isSectionHead && $status === 'done' && !$hasLevel1Approval && !$hasLevel2Approval;

                // Level 2 actions: Dept Head LID, training done, level 1 approved, not yet level 2
                $showLevel2Actions = $isDeptHead && $status === 'done' && $hasLevel1Approval && !$hasLevel2Approval;
            @endphp
            @if ($showLevel1Actions || $showLevel2Actions)
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
