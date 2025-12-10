<div class="space-y-4" x-data="{ tempScores: @entangle('tempScores') }">
    @if ($training)
        @php
            $typeUpper = strtoupper($training->type ?? '');
            $isDone = strtolower($training->status ?? '') === 'done';
        @endphp

        @if ($typeUpper === 'LMS')
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-sm text-amber-700">
                    <strong>Note:</strong> LMS trainings are managed through the learning platform and cannot be
                    closed
                    manually from here.
                </p>
            </div>
        @elseif ($isDone)
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700">
                    <strong>Training Closed:</strong> This training has been completed and marked as done.
                </p>
            </div>
        @endif

        {{-- Search --}}
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Employee Assessments</h3>
            <x-search-input placeholder="Search employee..." class="max-w-xs" wire:model.live="search" />
        </div>

        {{-- Table --}}
        <div class="rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
            <x-table :headers="$headers" :rows="$assessments" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- Custom cell untuk kolom Nomor --}}
                @scope('cell_no', $assessment)
                    {{ $assessment->no ?? $loop->iteration }}
                @endscope

                {{-- Custom cell untuk Posttest Score --}}
                @scope('cell_posttest_score', $assessment)
                    <div class="flex justify-center">
                        @php $trainingDone = $assessment->training_done ?? false; @endphp
                        @if ($trainingDone)
                            <div tabindex="-1"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                {{ $assessment->posttest_score ?? '-' }}</div>
                        @else
                            <input type="number" min="0" max="100" step="0.1"
                                wire:model.live="tempScores.{{ $assessment->id }}.posttest_score"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                placeholder="0-100">
                        @endif
                    </div>
                @endscope

                {{-- Custom cell untuk Practical Score --}}
                @scope('cell_practical_score', $assessment)
                    <div class="flex justify-center">
                        @php $trainingDone = $assessment->training_done ?? false; @endphp
                        @if ($trainingDone)
                            <div tabindex="-1"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                {{ $assessment->practical_score ?? '-' }}</div>
                        @else
                            <input type="number" min="0" max="100" step="0.1"
                                wire:model.live="tempScores.{{ $assessment->id }}.practical_score"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                placeholder="0-100">
                        @endif
                    </div>
                @endscope

                {{-- Custom cell untuk Status --}}
                @scope('cell_status', $assessment)
                    @php
                        $status = $assessment->temp_status ?? 'pending';
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
            </x-table>
        </div>
    @else
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
            <p class="text-sm text-gray-600">Training data not found.</p>
        </div>
    @endif
</div>
