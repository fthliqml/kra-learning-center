<div>
    <div class="w-full flex flex-col lg:flex-row gap-4 mb-6 items-center justify-between">
        <div class="flex flex-col gap-2">
            <h1 class="text-primary text-2xl sm:text-3xl font-bold text-center lg:text-start">
                Pending Survey 1
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center lg:text-start">
                Total pending: <span
                    class="font-semibold text-gray-900 dark:text-white">{{ (int) ($totalPending ?? 0) }}</span>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.500ms="search" />
        </div>
    </div>

    @php
        $pendingCount = (int) ($totalPending ?? 0);
    @endphp

    @if ($pendingCount <= 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-10">
            <div class="flex flex-col items-center justify-center text-center gap-4">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full">
                    <x-mary-icon name="o-check-circle" class="w-9 h-9 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">All caught up!</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada peserta yang pending Survey 1.</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-button label="Back to Dashboard" class="btn-primary" />
                    </a>
                    <a href="{{ route('survey.index', ['level' => 1]) }}" wire:navigate>
                        <x-button label="Go to Survey 1" class="btn-ghost" />
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$rows" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                @scope('cell_response_status', $row)
                    @php
                        $status = $row->response_status ?? 'not_filled';
                        $label = match ($status) {
                            'filled' => 'Filled',
                            'in_progress' => 'In Progress',
                            default => 'Not Filled',
                        };
                        $badgeClass = match ($status) {
                            'filled' => 'badge-success',
                            'in_progress' => 'badge-warning',
                            default => 'badge-error',
                        };
                    @endphp

                    <span class="badge {{ $badgeClass }}">
                        {{ $label }}
                    </span>
                @endscope

                @scope('cell_employee_nrp', $row)
                    <span class="whitespace-nowrap">{{ $row->employee_nrp ?? '-' }}</span>
                @endscope
            </x-table>
        </div>
    @endif
</div>
