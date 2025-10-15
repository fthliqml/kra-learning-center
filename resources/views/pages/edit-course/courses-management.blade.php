<div>
    @livewire('components.confirm-dialog')

    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-6 lg:gap-5 mb-5 lg:mb-9 items-start lg:items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start w-fit shrink-0">K-Learn Management</h1>
        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row flex-1">
            <div class="flex items-center gap-2">
                <x-ui.button variant="primary" size="lg" href="{{ route('add-course.index') }}"
                    class="relative overflow-hidden transition-all duration-200 ease-out shadow-all-sm hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 ring-1 ring-primary/25 hover:ring-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60">
                    <x-icon name="o-plus" />
                    Add
                </x-ui.button>
                <x-button type="button" icon="o-funnel" wire:click="openFilters"
                    class="btn-outline hover:border-primary transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 active:translate-y-0">
                    Filters
                </x-button>
                <div class="flex flex-wrap justify-center gap-1">
                    @if ($filterGroup)
                        <span class="badge badge-primary badge-soft text-xs">Group: {{ $filterGroup }}</span>
                    @endif
                    @if ($filterStatus)
                        <span class="badge badge-warning badge-soft text-xs">Status: {{ ucfirst($filterStatus) }}</span>
                    @endif
                    @if (!$filterGroup && !$filterStatus)
                        <span class="text-[11px] italic text-gray-400">No filters applied</span>
                    @endif
                </div>
            </div>
            <x-search-input placeholder="Search..." class="max-w-md" wire:model.live="search" />
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$courses" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
            with-pagination>
            {{-- Custom cell untuk kolom Nomor --}}
            @scope('cell_no', $course)
                {{ $course->no ?? $loop->iteration }}
            @endscope

            {{-- Group Comp from related training --}}
            @scope('cell_group_comp', $course)
                {{ $course->group_comp ?? '-' }}
            @endscope

            {{-- Status badge --}}
            @scope('cell_status', $course)
                @php
                    $status = $course->status ?? 'inactive';
                    // Map to badge classes (no variant prop)
                    $badgeClass = match ($status) {
                        'draft' => 'badge-warning',
                        'assigned' => 'badge-primary bg-primary/95',
                        'inactive' => 'badge primary badge-soft',
                    };
                @endphp
                <x-badge :value="str($status)->title()" :class="$badgeClass" />
            @endscope

            {{-- Custom cell untuk kolom Action --}}
            @scope('cell_action', $course)
                <div class="flex gap-2 justify-center">
                    <!-- Edit -->
                    <x-button tooltip-left="Edit Course" icon="o-pencil-square"
                        class="btn-circle btn-ghost p-2 bg-tetriary hover:opacity-85" spinner wire:navigate
                        href="{{ route('edit-course.index', ['course' => $course->id]) }}" />
                    <!-- Delete -->
                    <x-button icon="o-trash" tooltip-right="Delete Course"
                        class="btn-circle btn-ghost p-2 bg-danger text-white hover:opacity-85" spinner
                        wire:click="$dispatch('confirm', {
                            title: 'Are you sure you want to delete?',
                            text: 'This action is permanent and cannot be undone.',
                            action: 'deleteCourse',
                            id: {{ $course->id }}
                        })" />
                </div>
            @endscope
        </x-table>
    </div>

    {{-- Filter Modal --}}
    @if ($showFilterModal)
        <div class="fixed inset-0 z-[999] flex items-center justify-center bg-black/40 p-4"
            wire:keydown.escape="closeFilters">
            <div class="bg-base-100 w-full max-w-md rounded-lg shadow-xl ring-1 ring-base-300/50 p-6 space-y-5"
                wire:click.outside="closeFilters">
                <div class="flex items-start justify-between">
                    <h2 class="text-lg font-semibold">Filters</h2>
                    <button type="button" class="btn btn-sm btn-ghost" wire:click="closeFilters"><x-icon
                            name="o-x-mark" class="size-5" /></button>
                </div>
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold tracking-wide uppercase text-base-content/60">Group
                            Comp</label>
                        <x-select wire:model="filterGroup" :options="$groupOptions" option-value="value" option-label="label"
                            placeholder="All Groups" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold tracking-wide uppercase text-base-content/60">Status</label>
                        <x-select wire:model="filterStatus" :options="[
                            ['value' => 'draft', 'label' => 'Draft'],
                            ['value' => 'inactive', 'label' => 'Inactive'],
                            ['value' => 'assigned', 'label' => 'Assigned'],
                        ]" option-value="value" option-label="label"
                            placeholder="All Statuses" />
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <x-button type="button" class="btn-ghost text-error" wire:click="clearFilters"
                        icon="o-arrow-path">Reset</x-button>
                    <div class="flex gap-2">
                        <x-button type="button" class="btn-ghost" wire:click="closeFilters">Cancel</x-button>
                        <x-button type="button" class="btn-primary" wire:click="applyFilters"
                            icon="o-check">Apply</x-button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
