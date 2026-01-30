<div>
  {{-- Header --}}
  <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
    <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
      IDP Tracker
    </h1>

    <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
      <div class="flex items-center justify-center gap-2">
        {{-- Filter by Stage --}}
        <x-select wire:model.live="filterStage" :options="$stageOptions" option-value="value" option-label="label"
          placeholder="All Stages"
          class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
          icon-right="o-funnel" />

        {{-- Filter by Section --}}
        <x-select wire:model.live="filterSection" :options="$sectionOptions" option-value="value" option-label="label"
          placeholder="All Sections"
          class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
          icon-right="o-building-office" />
      </div>

      {{-- Search --}}
      <x-search-input placeholder="Search employee name or NRP..." class="max-w-72"
        wire:model.live.debounce.600ms="search" />
    </div>
  </div>

  {{-- Info Banner --}}
  <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
    <x-icon name="o-information-circle" class="size-5 text-blue-500 mt-0.5" />
    <div class="text-sm text-blue-700">
      <div class="font-semibold">Read-Only Tracker</div>
      <div class="mt-1 text-blue-600">
        This page shows the approval status of all Individual Development Plans (IDP). You can track which approvals are
        pending and who needs to approve.
      </div>
    </div>
  </div>

  {{-- Skeleton Loading --}}
  <x-skeletons.table :columns="8" :rows="10" targets="search,filterStage,filterSection" />

  {{-- No Data State --}}
  @if ($employees->isEmpty())
    <div wire:loading.remove wire:target="search,filterStage,filterSection"
      class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
      <div class="flex flex-col items-center justify-center py-16 px-4">
        <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
        <p class="text-sm text-gray-500 text-center">
          There are no IDP records matching your filters.
        </p>
      </div>
    </div>
  @else
    {{-- Table --}}
    <div wire:loading.remove wire:target="search,filterStage,filterSection"
      class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
      <x-table :headers="$headers" :rows="$employees" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
        with-pagination>

        {{-- No --}}
        @scope('cell_no', $employee, $employees)
        {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
        @endscope

        {{-- Employee Name --}}
        @scope('cell_employee_name', $employee)
        <div class="truncate max-w-[30ch]">{{ $employee->employee_name ?? '-' }}</div>
        @endscope

        {{-- NRP --}}
        @scope('cell_employee_nrp', $employee)
        <div class="font-mono text-sm">{{ $employee->employee_nrp ?? '-' }}</div>
        @endscope

        {{-- Section --}}
        @scope('cell_section', $employee)
        <div class="text-sm">{{ $employee->section ?? '-' }}</div>
        @endscope

        {{-- Plan Count --}}
        @scope('cell_plan_count', $employee)
        <div class="text-center font-semibold">{{ $employee->plan_count }}</div>
        @endscope

        {{-- Current Stage --}}
        @scope('cell_current_stage', $employee)
        @php
          $stage = $employee->current_stage;
          $classes = match ($stage) {
            'Pending SPV/Section Head' => 'bg-amber-100 text-amber-700',
            'Pending Section Head LID' => 'bg-blue-100 text-blue-700',
            'Approved' => 'bg-emerald-100 text-emerald-700',
            'Rejected' => 'bg-rose-100 text-rose-700',
            default => 'bg-gray-100 text-gray-700',
          };
        @endphp
        <div class="flex justify-center">
          <span
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }} whitespace-nowrap">
            {{ $stage }}
          </span>
        </div>
        @endscope

        {{-- Pending Approver --}}
        @scope('cell_pending_approver', $employee)
        <div class="text-sm">{{ $employee->pending_approver }}</div>
        @endscope

        {{-- Days Pending --}}
        @scope('cell_days_pending', $employee)
        @if ($employee->days_pending !== null)
          @php
            $days = $employee->days_pending;
            $classes = match (true) {
              $days >= 7 => 'text-rose-600 font-bold',
              $days >= 3 => 'text-amber-600 font-semibold',
              default => 'text-gray-600',
            };
          @endphp
          <div class="text-center {{ $classes }}">
            {{ $days }} {{ $days === 1 ? 'day' : 'days' }}
          </div>
        @else
          <div class="text-center text-gray-400">-</div>
        @endif
        @endscope
      </x-table>
    </div>
  @endif
</div>