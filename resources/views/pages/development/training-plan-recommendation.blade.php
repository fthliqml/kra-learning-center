<div>
    {{-- Header --}}
    <div class="flex flex-col gap-6 mb-8">
        {{-- Title & Actions --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h1 class="text-primary text-3xl lg:text-4xl font-bold">
                    Training Plan Recommendations
                </h1>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <x-input type="number" wire:model.live.debounce.500ms="selectedYear" icon="o-calendar"
                    class="h-10 sm:!w-32" min="2000" max="2100" />
            </div>
        </div>

        {{-- Filters Toolbar --}}
        <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                @php
                    $choicesEllipsis =
                        'focus-within:border-0 [&_.choices__inner]:!min-h-10 [&_.choices__inner]:!h-10 [&_.choices__inner]:!py-1 [&_.choices__inner]:!overflow-hidden [&_.choices__list--single]:!py-0 [&_.choices__list--single]:!overflow-hidden [&_.choices__list--single]:!whitespace-nowrap [&_.choices__list--single]:!text-ellipsis [&_.choices__item--selectable]:!overflow-hidden [&_.choices__item--selectable]:!whitespace-nowrap [&_.choices__item--selectable]:!text-ellipsis [&_.choices__item--selectable]:!max-w-full';
                @endphp

                <div class="w-full">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full items-end">
                        <div class="min-w-0">
                            <x-choices wire:key="dept-{{ $filterKey }}" label="Department"
                                wire:model.live="filterDepartment" :options="$departments" option-value="id"
                                option-label="name" placeholder="All Departments" class="{{ $choicesEllipsis }}" single
                                clearable searchable />
                        </div>
                        <div class="min-w-0">
                            <x-choices wire:key="sect-{{ $filterKey }}-{{ $filterDepartment }}" label="Section"
                                wire:model.live="filterSection" :options="$sections" option-value="id" option-label="name"
                                placeholder="All Sections" class="{{ $choicesEllipsis }}" single clearable searchable />
                        </div>
                        <div class="min-w-0">
                            <x-choices wire:key="emp-{{ $filterKey }}-{{ $filterDepartment }}-{{ $filterSection }}"
                                label="Employee" wire:model.live="selectedUserId" :options="$employeeOptions" option-value="value"
                                option-label="label" placeholder="Select employee" class="{{ $choicesEllipsis }}" single
                                clearable searchable />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!$selectedUser)
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-12 px-4">
                <x-icon name="o-user" class="w-10 h-10 text-gray-400 mb-3" />
                <h3 class="text-lg font-semibold text-gray-700 mb-1">Select an Employee</h3>
                <p class="text-sm text-gray-500 text-center">Pick an employee from the dropdown above to manage
                    recommendations.</p>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-4 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-base font-semibold text-gray-900 truncate">
                        {{ $selectedUser->name ?? '-' }}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">Employee details</div>
                </div>

                <div class="flex items-center gap-2 shrink-0">

                    <x-button label="Clear" class="btn-ghost btn-sm" wire:click="clearSelectedEmployee" />
                </div>
            </div>

            <div class="flex flex-wrap gap-2 text-xs mb-7">
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200">
                    <span class="text-gray-500 mr-1">NRP</span>
                    <span class="font-semibold">{{ $selectedUser->nrp ?? '-' }}</span>
                </span>
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200">
                    <span class="text-gray-500 mr-1">Section</span>
                    <span class="font-semibold">{{ $selectedUser->section ?? '-' }}</span>
                </span>
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200">
                    <span class="text-gray-500 mr-1">Department</span>
                    <span class="font-semibold">{{ $selectedUser->department ?? '-' }}</span>
                </span>
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    Year: {{ $selectedYear }}
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-7 gap-3">
                <noscript>
                    <div class="min-w-0 lg:col-span-7 text-sm text-red-600">JavaScript is required for this page.</div>
                </noscript>

                <div class="min-w-0 lg:col-span-2">
                    <x-choices label="Type" wire:model.live="recommendationType" :options="$recommendationTypeOptions"
                        option-value="value" option-label="label" placeholder="Select type"
                        class="{{ $choicesEllipsis }}" single searchable />
                </div>
                <div class="min-w-0 lg:col-span-1">
                    <x-choices label="Group" wire:model.live="group" :options="$typeOptions" option-value="value"
                        option-label="label" placeholder="Select group" class="{{ $choicesEllipsis }}" single clearable
                        searchable />
                </div>
                <div class="min-w-0 lg:col-span-4">
                    @if ($recommendationType === 'training_module')
                        <x-choices wire:key="rec-tm-{{ $recommendationType }}-{{ $group }}"
                            label="Training Module" wire:model.live="selectedId" :options="$this->getTrainingModulesByType($group)" option-value="value"
                            option-label="label" placeholder="Select training module" class="{{ $choicesEllipsis }}"
                            single clearable searchable />
                    @else
                        <x-choices wire:key="rec-comp-{{ $recommendationType }}-{{ $group }}"
                            label="Competency" wire:model.live="selectedId" :options="$this->getCompetenciesByType($group)" option-value="value"
                            option-label="label" placeholder="Select competency" class="{{ $choicesEllipsis }}" single
                            clearable searchable />
                    @endif
                </div>
            </div>

            <div>
                <x-button label="Add Recommendation" class="btn-primary btn-md w-full" spinner
                    wire:click="addRecommendation" />
            </div>

            <div
                class="rounded-lg border border-base-300 px-2 pb-4 pt-0 max-h-[55vh] overflow-x-auto overflow-y-auto relative bg-base-100 mt-10">
                @if (empty($recommendations))
                    <div class="text-sm text-gray-500 text-center py-6">No recommendations for this employee/year.
                    </div>
                @else
                    <table class="table table-zebra min-w-[720px] w-full text-sm table-fixed">
                        <thead>
                            <tr class="text-left text-base-content">
                                <th
                                    class="py-2 px-2 text-center w-20 sticky top-0 z-5 bg-base-100 border-b border-base-300">
                                    Type</th>
                                <th class="py-2 px-2 sticky top-0 z-5 bg-base-100 border-b border-base-300">
                                    Competency</th>
                                <th class="py-2 px-2 sticky top-0 z-5 bg-base-100 border-b border-base-300">
                                    Training Module</th>
                                <th
                                    class="py-2 px-2 text-center w-24 sticky top-0 z-5 bg-base-100 border-b border-base-300">
                                    Active</th>
                                <th
                                    class="py-2 px-2 text-center w-20 sticky top-0 z-5 bg-base-100 border-b border-base-300">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recommendations as $rec)
                                <tr>
                                    <td class="py-2 px-2 text-center">{{ $rec['type'] ?? '-' }}</td>
                                    <td class="py-2 px-2">
                                        <div class="truncate" title="{{ $rec['competency'] ?? '-' }}">
                                            {{ $rec['competency'] ?? '-' }}</div>
                                    </td>
                                    <td class="py-2 px-2">
                                        <div class="truncate" title="{{ $rec['training_module'] ?? '-' }}">
                                            {{ $rec['training_module'] ?? '-' }}</div>
                                    </td>
                                    <td class="py-2 px-2 text-center">
                                        <x-button label="{{ $rec['is_active'] ?? false ? 'Yes' : 'No' }}"
                                            class="btn-xs {{ $rec['is_active'] ?? false ? 'btn-success' : 'btn-ghost' }}"
                                            wire:click="toggleRecommendation({{ $rec['id'] }})" />
                                    </td>
                                    <td class="py-2 px-2 text-center">
                                        <x-button icon="o-trash" class="btn-circle btn-ghost text-red-600" spinner
                                            wire:click="deleteRecommendation({{ $rec['id'] }})" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif

</div>
