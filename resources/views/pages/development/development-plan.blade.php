<div>
    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-5 mb-5 lg:mb-9 items-center justify-between">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start whitespace-nowrap">
            Development Plan
        </h1>

        <div class="flex items-center justify-center gap-3">
            {{-- Year Filter --}}
            <x-input type="number" wire:model.live.debounce.500ms="selectedYear" icon="o-calendar" class="!w-32"
                min="2000" max="2100" />

            {{-- Show when any plan exists and is editable --}}
            @php
                $hasEditablePlans =
                    $trainingPlansData->contains(fn($p) => $p->canEdit()) ||
                    $selfLearningData->contains(fn($p) => $p->canEdit()) ||
                    $mentoringData->contains(fn($p) => $p->canEdit()) ||
                    $projectData->contains(fn($p) => $p->canEdit());

                $hasAnyPlans =
                    $trainingPlansData->count() > 0 ||
                    $selfLearningData->count() > 0 ||
                    $mentoringData->count() > 0 ||
                    $projectData->count() > 0;

                $isCurrentYear = (int) $selectedYear === (int) now()->year;
                $canAddPlan = $isCurrentYear && !$hasAnyPlans;
            @endphp

            <!-- Add Button  -->

        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.development-plan targets="selectedYear" />

    {{-- Main Content --}}
    <div wire:loading.remove wire:target="selectedYear">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            {{-- Training Plans --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-blue-100">
                        <x-icon name="o-academic-cap" class="size-6 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800">{{ $trainingPlanCount }}</p>
                        <p class="text-xs text-gray-500">Training Plans</p>
                    </div>
                </div>
            </div>

            {{-- Self Learning --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-green-100">
                        <x-icon name="o-book-open" class="size-6 text-green-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800">{{ $selfLearningCount }}</p>
                        <p class="text-xs text-gray-500">Self Learning</p>
                    </div>
                </div>
            </div>

            {{-- Mentoring --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-purple-100">
                        <x-icon name="o-user-group" class="size-6 text-purple-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800">{{ $mentoringCount }}</p>
                        <p class="text-xs text-gray-500">Mentoring</p>
                    </div>
                </div>
            </div>

            {{-- Project Assignment --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-amber-100">
                        <x-icon name="o-briefcase" class="size-6 text-amber-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800">{{ $projectCount }}</p>
                        <p class="text-xs text-gray-500">Projects</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column - Personal Info & Chart --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Personal Information Card --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <div class="flex items-center gap-4 mb-4">
                        {{-- Avatar --}}
                        <div
                            class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 overflow-hidden">
                            @if ($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
                                    class="w-full h-full object-cover">
                            @else
                                <span class="text-primary text-2xl font-bold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">{{ $user->name }}</h2>
                            <p class="text-sm text-gray-500">{{ $user->NRP }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Section</span>
                            <span class="font-medium text-gray-800">{{ $user->section ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Position</span>
                            <span class="font-medium text-gray-800">{{ $user->position ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Realization Card --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Realization</h3>
                    <div class="space-y-4">
                        {{-- Training --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-blue-100">
                                    <x-icon name="o-academic-cap" class="size-4 text-blue-600" />
                                </div>
                                <span class="text-sm text-gray-600">Training</span>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold text-gray-800">{{ $trainingRealized }}</span>
                                <span class="text-sm text-gray-500">/ {{ $trainingPlanCount }}</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full"
                                style="width: {{ $trainingPlanCount > 0 ? ($trainingRealized / $trainingPlanCount) * 100 : 0 }}%">
                            </div>
                        </div>

                        {{-- Self Learning --}}
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-green-100">
                                    <x-icon name="o-book-open" class="size-4 text-green-600" />
                                </div>
                                <span class="text-sm text-gray-600">Self Learning</span>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold text-gray-800">{{ $selfLearningRealized }}</span>
                                <span class="text-sm text-gray-500">/ {{ $selfLearningCount }}</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full"
                                style="width: {{ $selfLearningCount > 0 ? ($selfLearningRealized / $selfLearningCount) * 100 : 0 }}%">
                            </div>
                        </div>

                        {{-- Mentoring --}}
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-purple-100">
                                    <x-icon name="o-user-group" class="size-4 text-purple-600" />
                                </div>
                                <span class="text-sm text-gray-600">Mentoring</span>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold text-gray-800">{{ $mentoringRealized }}</span>
                                <span class="text-sm text-gray-500">/ {{ $mentoringCount }}</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full"
                                style="width: {{ $mentoringCount > 0 ? ($mentoringRealized / $mentoringCount) * 100 : 0 }}%">
                            </div>
                        </div>

                        {{-- Project --}}
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-amber-100">
                                    <x-icon name="o-briefcase" class="size-4 text-amber-600" />
                                </div>
                                <span class="text-sm text-gray-600">Project</span>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold text-gray-800">{{ $projectRealized }}</span>
                                <span class="text-sm text-gray-500">/ {{ $projectCount }}</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-amber-500 h-2 rounded-full"
                                style="width: {{ $projectCount > 0 ? ($projectRealized / $projectCount) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column - Plans by Category --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Training Plans --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 max-h-[60vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <x-icon name="o-academic-cap" class="size-5 text-blue-600" />
                            Training Plans
                        </h3>
                        <div class="flex items-center gap-2">
                            @if ($isCurrentYear && $trainingPlansData->count() == 0)
                                <x-ui.button variant="primary" size="sm" wire:click="openAddModal('training')"
                                    wire:loading.attr="disabled" class="h-8">
                                    <span wire:loading.remove wire:target="openAddModal('training')"
                                        class="flex items-center gap-1.5">
                                        <x-icon name="o-plus" class="size-4" />
                                        Add
                                    </span>
                                    <span wire:loading wire:target="openAddModal('training')">
                                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                    </span>
                                </x-ui.button>
                            @endif
                            @if ($canEditTraining && $trainingPlansData->count() > 0)
                                <x-ui.button variant="ghost" size="sm" wire:click="openEditModal('training')"
                                    wire:loading.attr="disabled" class="h-8">
                                    <span wire:loading.remove wire:target="openEditModal('training')"
                                        class="flex items-center gap-1.5">
                                        <x-icon name="o-pencil" class="size-4" />
                                        Edit
                                    </span>
                                    <span wire:loading wire:target="openEditModal('training')">
                                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                    </span>
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                    @if ($trainingPlansData->count() > 0)
                        <div class="space-y-3">
                            @foreach ($trainingPlansData as $plan)
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800">
                                                {{ $plan->competency->name ?? 'N/A' }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $plan->competency->type ?? '-' }}</p>
                                        </div>
                                        <div class="flex items-center gap-5">
                                            {{-- Realization Status - Only show if approved --}}
                                            @if ($plan->status === 'approved')
                                                @include('pages.development.partials.realization-badge', [
                                                    'status' => $plan->getRealizationStatus(),
                                                ])
                                            @endif

                                            @include('pages.development.partials.status-badge', [
                                                'status' => $plan->status,
                                            ])
                                        </div>
                                    </div>
                                    @if ($plan->rejection_reason && $plan->isRejected())
                                        <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm">
                                            <span class="text-red-600 font-medium">Rejection Reason:</span>
                                            <p class="text-red-700 mt-1">{{ $plan->rejection_reason }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">No training plans for this year</p>
                    @endif
                </div>

                {{-- Self Learning --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 max-h-[60vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <x-icon name="o-book-open" class="size-5 text-green-600" />
                            Self Learning
                        </h3>
                        <div class="flex items-center gap-2">
                            @if ($isCurrentYear && $selfLearningData->count() == 0)
                                <x-ui.button variant="primary" size="sm"
                                    wire:click="openAddModal('self_learning')" wire:loading.attr="disabled"
                                    class="h-8">
                                    <span wire:loading.remove wire:target="openAddModal('self_learning')"
                                        class="flex items-center gap-1.5">
                                        <x-icon name="o-plus" class="size-4" />
                                        Add
                                    </span>
                                    <span wire:loading wire:target="openAddModal('self_learning')">
                                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                    </span>
                                </x-ui.button>
                            @endif
                            @if ($canEditSelfLearning && $selfLearningData->count() > 0)
                                <x-ui.button variant="ghost" size="sm"
                                    wire:click="openEditModal('self_learning')" wire:loading.attr="disabled"
                                    class="h-8">
                                    <span wire:loading.remove wire:target="openEditModal('self_learning')"
                                        class="flex items-center gap-1.5">
                                        <x-icon name="o-pencil" class="size-4" />
                                        Edit
                                    </span>
                                    <span wire:loading wire:target="openEditModal('self_learning')">
                                        <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                    </span>
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                    @if ($selfLearningData->count() > 0)
                        <div class="space-y-3">
                            @foreach ($selfLearningData as $plan)
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800">{{ $plan->title }}</p>
                                            <p class="text-xs text-gray-500">Mentor: {{ $plan->mentor->name ?? '-' }}
                                                |
                                                {{ $plan->start_date?->format('d M Y') }} -
                                                {{ $plan->end_date?->format('d M Y') }}</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @include('pages.development.partials.status-badge', [
                                                'status' => $plan->status,
                                            ])
                                        </div>
                                    </div>
                                    @if ($plan->rejection_reason && $plan->isRejected())
                                        <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm">
                                            <span class="text-red-600 font-medium">Rejection Reason:</span>
                                            <p class="text-red-700 mt-1">{{ $plan->rejection_reason }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">No self learning plans for this year</p>
                    @endif
                </div>

                {{-- Mentoring & Projects (2 columns) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Mentoring --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6 max-h-[60vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <x-icon name="o-user-group" class="size-5 text-purple-600" />
                                Mentoring
                            </h3>
                            <div class="flex items-center gap-2">
                                @if ($isCurrentYear && $mentoringData->count() == 0)
                                    <x-ui.button variant="primary" size="sm"
                                        wire:click="openAddModal('mentoring')" wire:loading.attr="disabled"
                                        class="h-8">
                                        <span wire:loading.remove wire:target="openAddModal('mentoring')"
                                            class="flex items-center gap-1.5">
                                            <x-icon name="o-plus" class="size-4" />
                                            Add
                                        </span>
                                        <span wire:loading wire:target="openAddModal('mentoring')">
                                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                        </span>
                                    </x-ui.button>
                                @endif
                                @if ($canEditMentoring && $mentoringData->count() > 0)
                                    <x-ui.button variant="ghost" size="sm"
                                        wire:click="openEditModal('mentoring')" wire:loading.attr="disabled"
                                        class="h-8">
                                        <span wire:loading.remove wire:target="openEditModal('mentoring')"
                                            class="flex items-center gap-1.5">
                                            <x-icon name="o-pencil" class="size-4" />
                                            Edit
                                        </span>
                                        <span wire:loading wire:target="openEditModal('mentoring')">
                                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                        </span>
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>
                        @if ($mentoringData->count() > 0)
                            <div class="space-y-3">
                                @foreach ($mentoringData as $plan)
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="font-medium text-gray-800 text-sm truncate flex-1">
                                                {{ $plan->mentor->name ?? '-' }}</p>
                                            @include('pages.development.partials.status-badge', [
                                                'status' => $plan->status,
                                            ])
                                        </div>
                                        <p class="text-xs text-gray-500">{{ $plan->objective }}</p>
                                        @if ($plan->rejection_reason && $plan->isRejected())
                                            <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs">
                                                <span class="text-red-600 font-medium">Rejection:</span>
                                                <p class="text-red-700 mt-1">{{ $plan->rejection_reason }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">No mentoring plans for this year</p>
                        @endif
                    </div>

                    {{-- Projects --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6 max-h-[60vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <x-icon name="o-briefcase" class="size-5 text-amber-600" />
                                Projects
                            </h3>
                            <div class="flex items-center gap-2">
                                @if ($isCurrentYear && $projectData->count() == 0)
                                    <x-ui.button variant="primary" size="sm"
                                        wire:click="openAddModal('project')" wire:loading.attr="disabled"
                                        class="h-8">
                                        <span wire:loading.remove wire:target="openAddModal('project')"
                                            class="flex items-center gap-1.5">
                                            <x-icon name="o-plus" class="size-4" />
                                            Add
                                        </span>
                                        <span wire:loading wire:target="openAddModal('project')">
                                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                        </span>
                                    </x-ui.button>
                                @endif
                                @if ($canEditProject && $projectData->count() > 0)
                                    <x-ui.button variant="ghost" size="sm" wire:click="openEditModal('project')"
                                        wire:loading.attr="disabled" class="h-8">
                                        <span wire:loading.remove wire:target="openEditModal('project')"
                                            class="flex items-center gap-1.5">
                                            <x-icon name="o-pencil" class="size-4" />
                                            Edit
                                        </span>
                                        <span wire:loading wire:target="openEditModal('project')">
                                            <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                                        </span>
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>
                        @if ($projectData->count() > 0)
                            <div class="space-y-3">
                                @foreach ($projectData as $plan)
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="font-medium text-gray-800 text-sm truncate flex-1">
                                                {{ $plan->name }}</p>
                                            @include('pages.development.partials.status-badge', [
                                                'status' => $plan->status,
                                            ])
                                        </div>
                                        <p class="text-xs text-gray-500">{{ $plan->objective ?? '-' }}</p>
                                        @if ($plan->rejection_reason && $plan->isRejected())
                                            <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs">
                                                <span class="text-red-600 font-medium">Rejection:</span>
                                                <p class="text-red-700 mt-1">{{ $plan->rejection_reason }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">No project assignments for this year</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add/Edit Modal --}}
    <x-modal wire:model="addModal" :title="$isEdit ? 'Edit Development Plan' : 'Add Development Plan'" separator box-class="max-w-3xl h-fit !overflow-visible">
        <div class="max-h-[70vh] overflow-y-auto pr-1">
            <x-form wire:submit="save" no-separator>
                {{-- Show category being added/edited --}}
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700">
                        {{ $isEdit ? 'Editing' : 'Adding' }}:
                        @if ($editingCategory === 'training')
                            Training Plans
                        @elseif($editingCategory === 'self_learning')
                            Self Learning
                        @elseif($editingCategory === 'mentoring')
                            Mentoring
                        @elseif($editingCategory === 'project')
                            Project Assignment
                        @endif
                    </h4>
                </div>

                {{-- Training Tab --}}
                @if ($activeTab === 'training')
                    <div class="space-y-4">
                        @foreach ($trainingPlans as $index => $plan)
                            <div class="grid grid-cols-12 gap-4 items-end relative">
                                <div class="col-span-4 relative overflow-visible">
                                    <x-choices label="{{ $index === 0 ? 'Group Competency' : '' }}"
                                        wire:model.live="trainingPlans.{{ $index }}.group" :options="$typeOptions"
                                        option-value="value" option-label="label" placeholder="Select group"
                                        class="focus-within:border-0 [&_.choices__list--dropdown]:absolute [&_.choices__list--dropdown]:z-[9999] [&_.choices__list--dropdown]:!max-h-60"
                                        single />
                                </div>
                                <div
                                    class="{{ count($trainingPlans) > 1 ? 'col-span-7' : 'col-span-8' }} relative overflow-visible">
                                    <x-choices label="{{ $index === 0 ? 'Competency' : '' }}"
                                        wire:model="trainingPlans.{{ $index }}.competency_id" :options="$this->getCompetenciesByType($trainingPlans[$index]['group'] ?? '')"
                                        option-value="value" option-label="label" placeholder="Select competency"
                                        class="focus-within:border-0 [&_.choices__list--dropdown]:absolute [&_.choices__list--dropdown]:z-[9999] [&_.choices__list--dropdown]:!max-h-60"
                                        single />
                                </div>
                                @if (count($trainingPlans) > 1)
                                    <div class="col-span-1">
                                        <button type="button" wire:click="removeTrainingRow({{ $index }})"
                                            class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                            <x-icon name="o-trash" class="size-4" />
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        <button type="button" wire:click="addTrainingRow"
                            class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <x-icon name="o-plus" class="size-4" />
                            Add Training
                        </button>
                    </div>
                @endif

                {{-- Self Learning Tab --}}
                @if ($activeTab === 'self-learning')
                    <div class="space-y-6">
                        @foreach ($selfLearningPlans as $index => $plan)
                            <div class="p-4 border border-gray-200 rounded-lg space-y-4 relative">
                                @if (count($selfLearningPlans) > 1)
                                    <button type="button" wire:click="removeSelfLearningRow({{ $index }})"
                                        class="absolute top-2 right-2 p-1.5 text-red-500 hover:bg-red-50 rounded-lg">
                                        <x-icon name="o-trash" class="size-4" />
                                    </button>
                                @endif
                                <x-input label="Title" wire:model="selfLearningPlans.{{ $index }}.title"
                                    placeholder="Enter title" class="focus-within:border-0" />

                                <x-textarea label="Objective"
                                    wire:model="selfLearningPlans.{{ $index }}.objective"
                                    placeholder="Enter objective" class="focus-within:border-0" rows="2" />

                                {{-- Tidak perlu input mentor untuk Self Learning --}}

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <x-datepicker wire:model="selfLearningPlans.{{ $index }}.start_date"
                                        label="Date In" icon="o-calendar" placeholder="Select date"
                                        class="focus-within:border-0" :config="['altInput' => true, 'altFormat' => 'd M Y']" />
                                    <x-datepicker wire:model="selfLearningPlans.{{ $index }}.end_date"
                                        label="Date Out" icon="o-calendar" placeholder="Select date"
                                        class="focus-within:border-0" :config="['altInput' => true, 'altFormat' => 'd M Y']" />
                                </div>
                            </div>
                        @endforeach
                        <button type="button" wire:click="addSelfLearningRow"
                            class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <x-icon name="o-plus" class="size-4" />
                            Add Self Learning
                        </button>
                    </div>
                @endif

                {{-- Mentoring Tab --}}
                @if ($activeTab === 'mentoring')
                    <div class="space-y-6">
                        @foreach ($mentoringPlans as $index => $plan)
                            <div class="p-4 border border-gray-200 rounded-lg space-y-4 relative">
                                @if (count($mentoringPlans) > 1)
                                    <button type="button" wire:click="removeMentoringRow({{ $index }})"
                                        class="absolute top-2 right-2 p-1.5 text-red-500 hover:bg-red-50 rounded-lg">
                                        <x-icon name="o-trash" class="size-4" />
                                    </button>
                                @endif
                                <x-choices label="Mentor/Superior"
                                    wire:model="mentoringPlans.{{ $index }}.mentor_id" :options="$mentors"
                                    option-value="value" option-label="label" placeholder="Select mentor"
                                    class="focus-within:border-0" single />

                                <x-textarea label="Objective"
                                    wire:model="mentoringPlans.{{ $index }}.objective"
                                    placeholder="Enter objective" class="focus-within:border-0" rows="2" />

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <x-choices label="Method" wire:model="mentoringPlans.{{ $index }}.method"
                                        :options="$methodOptions" option-value="value" option-label="label"
                                        placeholder="Select method" class="focus-within:border-0" single />

                                    <x-input label="Frequency"
                                        wire:model="mentoringPlans.{{ $index }}.frequency" type="number"
                                        placeholder="Enter frequency" class="focus-within:border-0" readonly />

                                    <x-input label="Duration (minutes)"
                                        wire:model="mentoringPlans.{{ $index }}.duration" type="number"
                                        placeholder="Enter duration in minutes" class="focus-within:border-0"
                                        helper="Duration should be entered in minutes" />
                                </div>

                                <div class="space-y-3">
                                    <label class="label p-0">
                                        <span class="label-text text-xs leading-[18px] font-semibold text-[#123456]">
                                            Month
                                        </span>
                                    </label>

                                    <div class="space-y-2">
                                        @php
                                            $planMonths = $plan['plan_months'] ?? [];
                                        @endphp
                                        @foreach ($planMonths as $mIndex => $month)
                                            <div class="w-full relative">
                                                <x-datepicker
                                                    wire:model="mentoringPlans.{{ $index }}.plan_months.{{ $mIndex }}"
                                                    icon="o-calendar" class="w-full focus-within:border-0 pr-9"
                                                    :config="[
                                                        'altInput' => true,
                                                        'plugins' => [
                                                            [
                                                                'monthSelectPlugin' => [
                                                                    'dateFormat' => 'Y-m',
                                                                    'altFormat' => 'F Y',
                                                                ],
                                                            ],
                                                        ],
                                                    ]" />
                                                @if (count($planMonths) > 2)
                                                    <button type="button"
                                                        wire:click="removeMentoringMonth({{ $index }}, {{ $mIndex }})"
                                                        class="absolute inset-y-0 right-0 my-auto p-1 px-2 text-red-500 hover:bg-red-50 rounded-md flex items-center justify-center">
                                                        <x-icon name="o-trash" class="size-4" />
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach

                                        <button type="button" wire:click="addMentoringMonth({{ $index }})"
                                            class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                            <x-icon name="o-plus" class="size-3" />
                                            Add Month Plan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <button type="button" wire:click="addMentoringRow"
                            class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <x-icon name="o-plus" class="size-4" />
                            Add Mentoring
                        </button>
                    </div>
                @endif

                {{-- Project Assignment Tab --}}
                @if ($activeTab === 'project')
                    <div class="space-y-6">
                        @foreach ($projectPlans as $index => $plan)
                            <div class="p-4 border border-gray-200 rounded-lg space-y-4 relative">
                                @if (count($projectPlans) > 1)
                                    <button type="button" wire:click="removeProjectRow({{ $index }})"
                                        class="absolute top-2 right-2 p-1.5 text-red-500 hover:bg-red-50 rounded-lg">
                                        <x-icon name="o-trash" class="size-4" />
                                    </button>
                                @endif
                                <x-input label="Project Name" wire:model="projectPlans.{{ $index }}.name"
                                    placeholder="Enter project name" class="focus-within:border-0" />

                                <x-textarea label="Objective" wire:model="projectPlans.{{ $index }}.objective"
                                    placeholder="Enter objective" class="focus-within:border-0" rows="2" />

                                <x-choices label="Mentor/Superior"
                                    wire:model="projectPlans.{{ $index }}.mentor_id" :options="$mentors"
                                    option-value="value" option-label="label" placeholder="Select mentor"
                                    class="focus-within:border-0" single />
                            </div>
                        @endforeach
                        <button type="button" wire:click="addProjectRow"
                            class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <x-icon name="o-plus" class="size-4" />
                            Add Project
                        </button>
                    </div>
                @endif

                <x-slot:actions class="mt-5">
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <x-ui.button @click="$wire.closeAddModal()" type="button">Cancel</x-ui.button>
                        </div>
                        <div class="flex gap-2">
                            @if (!$isEdit)
                                <x-ui.button type="button" wire:click="saveDraft" class="btn-outline"
                                    spinner="saveDraft">
                                    Save Draft
                                </x-ui.button>
                            @endif
                            <x-ui.button type="submit" variant="primary" class="btn-primary !text-white"
                                spinner="save">
                                {{ $isEdit ? 'Update' : 'Save' }}
                            </x-ui.button>
                        </div>
                    </div>
                </x-slot:actions>
            </x-form>
        </div>
    </x-modal>
</div>
