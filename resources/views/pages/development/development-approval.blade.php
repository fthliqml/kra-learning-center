<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <div>
            <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
                Development Approval
            </h1>
        </div>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <div class="flex items-center justify-center gap-2">
                {{-- Year Filter --}}
                <x-input type="number" wire:model.live.debounce.500ms="selectedYear" icon="o-calendar" class="!w-32"
                    min="2000" max="2100" />

                {{-- Status Filter --}}
                <x-select wire:model.live="filter" :options="$filterOptions" option-value="value" option-label="label"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />
            </div>

            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="7" :rows="10"
        targets="search,filter,selectedYear,approveAll,rejectAll,approvePlan,rejectPlan" />

    {{-- No Data State --}}
    @if ($approvalData->isEmpty())
        <div wire:loading.remove wire:target="search,filter,selectedYear,approveAll,rejectAll,approvePlan,rejectPlan"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no development plans to approve for {{ $selectedYear }}.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove wire:target="search,filter,selectedYear,approveAll,rejectAll,approvePlan,rejectPlan"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$approvalData" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- No --}}
                @scope('cell_no', $user, $approvalData)
                    {{ ($approvalData->currentPage() - 1) * $approvalData->perPage() + $loop->iteration }}
                @endscope

                {{-- Employee Name --}}
                @scope('cell_name', $user)
                    <div class="truncate max-w-[30ch]">{{ $user->name ?? '-' }}</div>
                @endscope

                {{-- NRP --}}
                @scope('cell_nrp', $user)
                    <div class="text-center">{{ $user->nrp ?? '-' }}</div>
                @endscope

                {{-- Section --}}
                @scope('cell_section', $user)
                    <div class="truncate max-w-[20ch]">{{ $user->section ?? '-' }}</div>
                @endscope

                {{-- Total Plans --}}
                @scope('cell_total_plans', $user)
                    <div class="text-center">
                        {{ ($user->training_count ?? 0) + ($user->self_learning_count ?? 0) + ($user->mentoring_count ?? 0) + ($user->project_count ?? 0) }}
                    </div>
                @endscope

                {{-- Status --}}
                @scope('cell_status', $user)
                    @php
                        $status = $this->getUserStatus($user);
                        $classes = match ($status) {
                            'pending_spv', 'pending_section_head', 'pending_dept_head' => 'bg-amber-100 text-amber-700',
                            'pending_lid' => 'bg-blue-100 text-blue-700',
                            'approved' => 'bg-emerald-100 text-emerald-700',
                            'rejected_spv', 'rejected_dept_head', 'rejected_lid' => 'bg-rose-100 text-rose-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        $label = match ($status) {
                            'pending_spv' => 'Pending Supervisor Approval',
                            'pending_section_head' => 'Pending Section Head Approval',
                            'pending_dept_head' => 'Pending Dept Head Approval',
                            'pending_lid' => 'Pending LID Approval',
                            'rejected_spv' => 'Rejected by Supervisor',
                            'rejected_dept_head' => 'Rejected by Dept Head',
                            'rejected_lid' => 'Rejected by LID',
                            'approved' => 'Approved',
                            default => 'Pending',
                        };
                    @endphp
                    <div class="flex justify-center">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }}">
                            {{ $label }}
                        </span>
                    </div>
                @endscope

                {{-- Action --}}
                @scope('cell_action', $user)
                    <div class="flex justify-center">
                        <x-button icon="o-eye" class="btn-circle btn-ghost p-2 bg-info text-white" spinner
                            wire:click="openDetailModal({{ $user->id }})" />
                    </div>
                @endscope
            </x-table>
        </div>
    @endif

    {{-- Detail Modal --}}
    <x-modal wire:model="detailModal" title="Development Plan Details" separator box-class="max-w-4xl h-fit">
        @if ($selectedUserData)
            {{-- User Info --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Name</span>
                        <p class="font-semibold text-gray-800">{{ $selectedUserData['name'] ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">NRP</span>
                        <p class="font-semibold text-gray-800">{{ $selectedUserData['nrp'] ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Section</span>
                        <p class="font-semibold text-gray-800">{{ $selectedUserData['section'] ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Position</span>
                        <p class="font-semibold text-gray-800">{{ $selectedUserData['position'] ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Plans List --}}
            <div class="space-y-4 max-h-[60vh] overflow-y-auto">
                {{-- Training Plans --}}
                @if (count($userTrainingPlans) > 0)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 flex items-center gap-2 mb-3">
                            <x-icon name="o-academic-cap" class="size-5 text-blue-600" />
                            Training Plans ({{ count($userTrainingPlans) }})
                        </h4>
                        <div class="space-y-3">
                            @foreach ($userTrainingPlans as $plan)
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 space-y-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="font-semibold text-gray-900 text-sm md:text-base">
                                                    {{ $plan['competency']['name'] ?? 'N/A' }}
                                                </p>
                                                @include('pages.development.partials.status-badge', [
                                                    'status' => $plan['status'],
                                                ])
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                                <div class="space-y-0.5">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Group</span>
                                                    <span
                                                        class="block text-gray-800">{{ $plan['competency']['type'] ?? '-' }}</span>
                                                </div>
                                            </div>
                                            {{-- Show rejection reason if rejected --}}
                                            @if (
                                                !empty($plan['rejection_reason']) &&
                                                    in_array($plan['status'], ['rejected_spv', 'rejected_dept_head', 'rejected_lid']))
                                                <div class="text-sm bg-red-50 p-2 rounded border border-red-200">
                                                    <span class="text-red-600 font-medium">Rejection Reason:</span>
                                                    <p class="text-red-700 mt-1">{{ $plan['rejection_reason'] }}</p>
                                                </div>
                                            @endif
                                            {{-- Show approval info --}}
                                            @if (!empty($plan['spv_approver']))
                                                <div class="text-xs text-gray-500">
                                                    SPV Approved by: {{ $plan['spv_approver']['name'] ?? '-' }}
                                                </div>
                                            @endif
                                            @if (!empty($plan['leader_approver']))
                                                <div class="text-xs text-gray-500">
                                                    Leader Approved by: {{ $plan['leader_approver']['name'] ?? '-' }}
                                                </div>
                                            @endif
                                        </div>
                                        @if ($this->canApprovePlan($plan))
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <x-button icon="o-x-mark"
                                                    class="btn-sm !bg-red-500 hover:!bg-red-600 !text-white !border-red-500"
                                                    wire:click="rejectPlan('training', {{ $plan['id'] }})"
                                                    title="Reject" />
                                                <x-button icon="o-check"
                                                    class="btn-sm !bg-green-500 hover:!bg-green-600 !text-white !border-green-500"
                                                    wire:click="approvePlan('training', {{ $plan['id'] }})"
                                                    title="Approve" />
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Self Learning Plans --}}
                @if (count($userSelfLearningPlans) > 0)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 flex items-center gap-2 mb-3">
                            <x-icon name="o-book-open" class="size-5 text-green-600" />
                            Self Learning Plans ({{ count($userSelfLearningPlans) }})
                        </h4>
                        <div class="space-y-3">
                            @foreach ($userSelfLearningPlans as $plan)
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 space-y-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="font-semibold text-gray-900 text-sm md:text-base">
                                                    {{ $plan['title'] ?? 'N/A' }}
                                                </p>
                                                @include('pages.development.partials.status-badge', [
                                                    'status' => $plan['status'],
                                                ])
                                            </div>
                                            @if (!empty($plan['objective']))
                                                <div class="space-y-1">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Objective</span>
                                                    <p class="text-sm text-gray-800 leading-snug">
                                                        {{ $plan['objective'] }}
                                                    </p>
                                                </div>
                                            @endif
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                                <div class="space-y-0.5">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Start
                                                        Date</span>
                                                    <span class="block text-gray-800">
                                                        {{ $plan['start_date'] ? \Carbon\Carbon::parse($plan['start_date'])->format('d M Y') : '-' }}
                                                    </span>
                                                </div>
                                                <div class="space-y-0.5">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">End
                                                        Date</span>
                                                    <span class="block text-gray-800">
                                                        {{ $plan['end_date'] ? \Carbon\Carbon::parse($plan['end_date'])->format('d M Y') : '-' }}
                                                    </span>
                                                </div>
                                            </div>
                                            {{-- Show rejection reason if rejected --}}
                                            @if (
                                                !empty($plan['rejection_reason']) &&
                                                    in_array($plan['status'], ['rejected_spv', 'rejected_dept_head', 'rejected_lid']))
                                                <div class="text-sm bg-red-50 p-2 rounded border border-red-200">
                                                    <span class="text-red-600 font-medium">Rejection Reason:</span>
                                                    <p class="text-red-700 mt-1">{{ $plan['rejection_reason'] }}</p>
                                                </div>
                                            @endif
                                            {{-- Show approval info --}}
                                            @if (!empty($plan['spv_approver']))
                                                <div class="text-xs text-gray-500">
                                                    SPV Approved by: {{ $plan['spv_approver']['name'] ?? '-' }}
                                                </div>
                                            @endif
                                            @if (!empty($plan['leader_approver']))
                                                <div class="text-xs text-gray-500">
                                                    Leader Approved by: {{ $plan['leader_approver']['name'] ?? '-' }}
                                                </div>
                                            @endif
                                        </div>
                                        @if ($this->canApprovePlan($plan))
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <x-button icon="o-x-mark"
                                                    class="btn-sm !bg-red-500 hover:!bg-red-600 !text-white !border-red-500"
                                                    wire:click="rejectPlan('self-learning', {{ $plan['id'] }})"
                                                    title="Reject" />
                                                <x-button icon="o-check"
                                                    class="btn-sm !bg-green-500 hover:!bg-green-600 !text-white !border-green-500"
                                                    wire:click="approvePlan('self-learning', {{ $plan['id'] }})"
                                                    title="Approve" />
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Mentoring Plans --}}
                @if (count($userMentoringPlans) > 0)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 flex items-center gap-2 mb-3">
                            <x-icon name="o-user-group" class="size-5 text-purple-600" />
                            Mentoring Plans ({{ count($userMentoringPlans) }})
                        </h4>
                        <div class="space-y-3">
                            @foreach ($userMentoringPlans as $plan)
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 space-y-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="font-semibold text-gray-900 text-sm md:text-base">Mentoring
                                                    Session</p>
                                                @include('pages.development.partials.status-badge', [
                                                    'status' => $plan['status'],
                                                ])
                                            </div>
                                            @if (!empty($plan['objective']))
                                                <div class="space-y-1">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Objective</span>
                                                    <p class="text-sm text-gray-800 leading-snug">
                                                        {{ $plan['objective'] }}
                                                    </p>
                                                </div>
                                            @endif
                                            @if (!empty($plan['plan_months']) && is_array($plan['plan_months']))
                                                <div class="space-y-1">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Plan
                                                        Months</span>
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        @foreach ($plan['plan_months'] as $month)
                                                            @php
                                                                try {
                                                                    $label = \Carbon\Carbon::createFromFormat(
                                                                        'Y-m-d',
                                                                        $month . '-01',
                                                                    )->format('M Y');
                                                                } catch (\Exception $e) {
                                                                    $label = $month;
                                                                }
                                                            @endphp
                                                            <span
                                                                class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded-full text-xs">
                                                                {{ $label }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                                <div class="space-y-0.5">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Mentor</span>
                                                    <span
                                                        class="block text-gray-800">{{ $plan['mentor']['name'] ?? '-' }}</span>
                                                </div>
                                                <div class="space-y-0.5">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Method</span>
                                                    <span
                                                        class="block text-gray-800">{{ ucfirst($plan['method'] ?? '-') }}</span>
                                                </div>
                                                <div class="space-y-0.5">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Frequency</span>
                                                    <span
                                                        class="block text-gray-800">{{ $plan['frequency'] ?? '-' }}x</span>
                                                </div>
                                                <div class="space-y-0.5">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Duration</span>
                                                    <span class="block text-gray-800">{{ $plan['duration'] ?? '-' }}
                                                        mins</span>
                                                </div>
                                            </div>
                                            {{-- Show rejection reason if rejected --}}
                                            @if (
                                                !empty($plan['rejection_reason']) &&
                                                    in_array($plan['status'], ['rejected_spv', 'rejected_dept_head', 'rejected_lid']))
                                                <div class="text-sm bg-red-50 p-2 rounded border border-red-200">
                                                    <span class="text-red-600 font-medium">Rejection Reason:</span>
                                                    <p class="text-red-700 mt-1">{{ $plan['rejection_reason'] }}</p>
                                                </div>
                                            @endif
                                            {{-- Show approval info --}}
                                            @if (!empty($plan['spv_approver']))
                                                <div class="text-xs text-gray-500">
                                                    SPV Approved by: {{ $plan['spv_approver']['name'] ?? '-' }}
                                                </div>
                                            @endif
                                            @if (!empty($plan['leader_approver']))
                                                <div class="text-xs text-gray-500">
                                                    Leader Approved by: {{ $plan['leader_approver']['name'] ?? '-' }}
                                                </div>
                                            @endif
                                        </div>
                                        @if ($this->canApprovePlan($plan))
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <x-button icon="o-x-mark"
                                                    class="btn-sm !bg-red-500 hover:!bg-red-600 !text-white !border-red-500"
                                                    wire:click="rejectPlan('mentoring', {{ $plan['id'] }})"
                                                    title="Reject" />
                                                <x-button icon="o-check"
                                                    class="btn-sm !bg-green-500 hover:!bg-green-600 !text-white !border-green-500"
                                                    wire:click="approvePlan('mentoring', {{ $plan['id'] }})"
                                                    title="Approve" />
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Project Plans --}}
                @if (count($userProjectPlans) > 0)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 flex items-center gap-2 mb-3">
                            <x-icon name="o-briefcase" class="size-5 text-amber-600" />
                            Project Plans ({{ count($userProjectPlans) }})
                        </h4>
                        <div class="space-y-3">
                            @foreach ($userProjectPlans as $plan)
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 space-y-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="font-semibold text-gray-900 text-sm md:text-base">
                                                    {{ $plan['name'] ?? 'N/A' }}
                                                </p>
                                                @include('pages.development.partials.status-badge', [
                                                    'status' => $plan['status'],
                                                ])
                                            </div>
                                            @if (!empty($plan['objective']))
                                                <div class="space-y-1">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Objective</span>
                                                    <p class="text-sm text-gray-800 leading-snug">
                                                        {{ $plan['objective'] }}
                                                    </p>
                                                </div>
                                            @endif
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                                <div class="space-y-0.5">
                                                    <span
                                                        class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Mentor</span>
                                                    <span
                                                        class="block text-gray-800">{{ $plan['mentor']['name'] ?? '-' }}</span>
                                                </div>
                                            </div>
                                            {{-- Show rejection reason if rejected --}}
                                            @if (
                                                !empty($plan['rejection_reason']) &&
                                                    in_array($plan['status'], ['rejected_spv', 'rejected_dept_head', 'rejected_lid']))
                                                <div class="text-sm bg-red-50 p-2 rounded border border-red-200">
                                                    <span class="text-red-600 font-medium">Rejection Reason:</span>
                                                    <p class="text-red-700 mt-1">{{ $plan['rejection_reason'] }}</p>
                                                </div>
                                            @endif
                                            {{-- Show approval info --}}
                                            @if (!empty($plan['spv_approver']))
                                                <div class="text-xs text-gray-500">
                                                    SPV Approved by: {{ $plan['spv_approver']['name'] ?? '-' }}
                                                </div>
                                            @endif
                                            @if (!empty($plan['leader_approver']))
                                                <div class="text-xs text-gray-500">
                                                    Leader Approved by: {{ $plan['leader_approver']['name'] ?? '-' }}
                                                </div>
                                            @endif
                                        </div>
                                        @if ($this->canApprovePlan($plan))
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <x-button icon="o-x-mark"
                                                    class="btn-sm !bg-red-500 hover:!bg-red-600 !text-white !border-red-500"
                                                    wire:click="rejectPlan('project', {{ $plan['id'] }})"
                                                    title="Reject" />
                                                <x-button icon="o-check"
                                                    class="btn-sm !bg-green-500 hover:!bg-green-600 !text-white !border-green-500"
                                                    wire:click="approvePlan('project', {{ $plan['id'] }})"
                                                    title="Approve" />
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Footer Actions --}}
            <x-slot:actions>
                <div class="flex items-center justify-between w-full">
                    <x-ui.button @click="$wire.closeDetailModal()" type="button">Close</x-ui.button>

                    @if ($this->hasPendingPlans())
                        <div class="flex gap-2">
                            <x-ui.button wire:click="openRejectModal(null, null, true)"
                                class="!bg-red-500 hover:!bg-red-600 !text-white !border-red-500">
                                <x-icon name="o-x-mark" class="size-4" />
                                Reject All
                            </x-ui.button>
                            <x-ui.button wire:click="approveAll"
                                class="!bg-green-500 hover:!bg-green-600 !text-white !border-green-500"
                                spinner="approveAll">
                                <x-icon name="o-check" class="size-4" />
                                Approve All
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            </x-slot:actions>
        @endif
    </x-modal>

    {{-- Reject Reason Modal --}}
    <x-modal wire:model="rejectModal" title="Rejection Reason" separator box-class="max-w-md">
        <x-form wire:submit="confirmReject" no-separator>
            <p class="text-sm text-gray-600 mb-4">
                Please provide a reason for rejecting {{ $rejectAll ? 'all plans' : 'this plan' }}.
                The employee will need to revise and resubmit based on this feedback.
            </p>

            <x-textarea label="Rejection Reason" wire:model="rejectionReason"
                placeholder="Enter the reason for rejection..." rows="4" class="focus-within:border-0"
                required />

            @error('rejectionReason')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror

            <x-slot:actions>
                <x-ui.button @click="$wire.closeRejectModal()" type="button">Cancel</x-ui.button>
                <x-ui.button type="submit" class="!bg-red-500 hover:!bg-red-600 !text-white !border-red-500"
                    spinner="confirmReject">
                    <x-icon name="o-x-mark" class="size-4" />
                    Confirm Reject
                </x-ui.button>
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
