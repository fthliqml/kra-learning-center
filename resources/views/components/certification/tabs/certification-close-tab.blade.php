<div class="space-y-4" x-data="{ tempScores: @entangle('tempScores') }">
    @if ($certification)
        @php
            $isDone = $isClosed;
            $theoryMin = $certification->certificationModule->theory_passing_score ?? null;
            $practicalMin = $certification->certificationModule->practical_passing_score ?? null;
            $pointsPerModule = $certification->certificationModule->points_per_module ?? null;
        @endphp
        @if ($isDone)
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700"><strong>Certification Closed:</strong> This certification has been
                    completed and marked as done.</p>
            </div>
        @elseif($theoryMin !== null || $practicalMin !== null)
            <div
                class="p-3 md:p-4 rounded-lg border border-blue-200 bg-blue-50 text-xs md:text-sm text-blue-700 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                <div class="font-medium">Minimum Passing Scores:</div>
                <div class="flex flex-wrap items-center gap-3">
                    @if ($theoryMin !== null)
                        <span class="inline-flex items-center gap-1"><span class="font-semibold">Theory</span><span>&ge;
                                {{ rtrim(rtrim(number_format((float) $theoryMin, 2, '.', ''), '0'), '.') }}</span></span>
                    @endif
                    @if ($practicalMin !== null)
                        <span class="inline-flex items-center gap-1"><span
                                class="font-semibold">Practical</span><span>&ge;
                                {{ rtrim(rtrim(number_format((float) $practicalMin, 2, '.', ''), '0'), '.') }}</span></span>
                    @endif
                    @if ($pointsPerModule !== null)
                        <span class="inline-flex items-center gap-1"><span class="font-semibold">Points Awarded if
                                Passed</span><span>{{ $pointsPerModule }}</span></span>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Participant Scores</h3>
            <x-search-input placeholder="Search participant..." class="max-w-xs" wire:model.live="search" />
        </div>

        <div class="rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
            <x-table :headers="$headers" :rows="$rows" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3">
                @scope('cell_no', $row)
                    @php $get = fn($k,$d=null) => is_array($row) ? ($row[$k] ?? $d) : (is_object($row) ? ($row->{$k} ?? $d) : $d); @endphp
                    {{ $get('no', $loop->iteration) }}
                @endscope
                @scope('cell_employee_name', $row)
                    @php $get = fn($k,$d=null) => is_array($row) ? ($row[$k] ?? $d) : (is_object($row) ? ($row->{$k} ?? $d) : $d); @endphp
                    <div class="font-medium text-gray-800">{{ $get('employee_name', $get('name', '-')) }}</div>
                @endscope
                @scope('cell_theory_score', $row)
                    <div class="flex justify-center">
                        @php $get = fn($k,$d=null) => is_array($row) ? ($row[$k] ?? $d) : (is_object($row) ? ($row->{$k} ?? $d) : $d); @endphp
                        @if ($get('cert_done'))
                            <div tabindex="-1"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                {{ $get('theory_score', '-') }}</div>
                        @else
                            <input type="number" min="0" max="100" step="0.1"
                                wire:model.live="tempScores.{{ $get('participant_id') }}.theory"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                placeholder="0-100">
                        @endif
                    </div>
                @endscope
                @scope('cell_practical_score', $row)
                    <div class="flex justify-center">
                        @php $get = fn($k,$d=null) => is_array($row) ? ($row[$k] ?? $d) : (is_object($row) ? ($row->{$k} ?? $d) : $d); @endphp
                        @if ($get('cert_done'))
                            <div tabindex="-1"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                {{ $get('practical_score', '-') }}</div>
                        @else
                            <input type="number" min="0" max="100" step="0.1"
                                wire:model.live="tempScores.{{ $get('participant_id') }}.practical"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                placeholder="0-100">
                        @endif
                    </div>
                @endscope
                @scope('cell_status', $row)
                    @php
                        $get = fn($k, $d = null) => is_array($row)
                            ? $row[$k] ?? $d
                            : (is_object($row)
                                ? $row->{$k} ?? $d
                                : $d);
                        $status = $get('status', 'pending');
                    @endphp
                    <div class="flex justify-center">
                        @if ($status === 'passed')
                            <span class="badge badge-success badge-sm">Passed</span>
                        @elseif($status === 'failed')
                            <span class="badge badge-error badge-sm">Failed</span>
                        @elseif($status === 'in_progress')
                            <span class="badge badge-warning badge-sm">In Progress</span>
                        @else
                            <span class="badge badge-neutral badge-sm">Pending</span>
                        @endif
                    </div>
                @endscope
                @scope('cell_earned_point', $row)
                    @php $get = fn($k,$d=null) => is_array($row) ? ($row[$k] ?? $d) : (is_object($row) ? ($row->{$k} ?? $d) : $d); @endphp
                    <div class="text-center font-medium">{{ $get('earned_point', 0) }}</div>
                @endscope
                @scope('cell_note', $row)
                    @php $get = fn($k,$d=null) => is_array($row) ? ($row[$k] ?? $d) : (is_object($row) ? ($row->{$k} ?? $d) : $d); @endphp
                    @if ($get('cert_done'))
                        <div class="text-xs text-gray-600">{{ $get('note', '—') }}</div>
                    @else
                        <input type="text" maxlength="255"
                            wire:model.live.debounce.300ms="tempScores.{{ $get('participant_id') }}.note"
                            placeholder="Optional note"
                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded outline-none focus:ring-1 focus:ring-primary focus:border-primary" />
                    @endif
                @endscope
            </x-table>
        </div>
    @else
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
            <p class="text-sm text-gray-600">Certification data not found.</p>
        </div>
    @endif
</div>
