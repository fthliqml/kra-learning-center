<div class="space-y-4">
    @if ($training)
        @php
            $typeUpper = strtoupper($training->type ?? '');
            $isDone = strtolower($training->status ?? '') === 'done';
        @endphp

        @if ($typeUpper === 'K-LEARN')
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-sm text-amber-700">
                    <strong>Note:</strong> K-Learn trainings are managed through the learning platform and cannot be
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
        <div class="rounded-lg border border-gray-200 shadow-sm overflow-x-auto" x-data="{ tempScores: @entangle('tempScores') }">
            <x-table :headers="$headers" :rows="$assessments" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>
                {{-- Custom cell untuk kolom Nomor --}}
                @scope('cell_no', $assessment)
                    {{ $assessment->no ?? $loop->iteration }}
                @endscope

                {{-- Custom cell untuk Pretest Score --}}
                @scope('cell_pretest_score', $assessment)
                    @php
                        $trainingDone = strtolower($training->status ?? '') === 'done';
                    @endphp
                    @if ($trainingDone)
                        <div class="text-center">{{ $assessment->pretest_score ?? '-' }}</div>
                    @else
                        <div class="flex justify-center">
                            <input type="number" min="0" max="100" step="0.1"
                                wire:model.live.debounce.300ms="tempScores.{{ $assessment->id }}.pretest_score"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                placeholder="0-100">
                        </div>
                    @endif
                @endscope

                {{-- Custom cell untuk Posttest Score --}}
                @scope('cell_posttest_score', $assessment)
                    @php
                        $trainingDone = strtolower($training->status ?? '') === 'done';
                    @endphp
                    @if ($trainingDone)
                        <div class="text-center">{{ $assessment->posttest_score ?? '-' }}</div>
                    @else
                        <div class="flex justify-center">
                            <input type="number" min="0" max="100" step="0.1"
                                wire:model.live.debounce.300ms="tempScores.{{ $assessment->id }}.posttest_score"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                placeholder="0-100">
                        </div>
                    @endif
                @endscope

                {{-- Custom cell untuk Practical Score --}}
                @scope('cell_practical_score', $assessment)
                    @php
                        $trainingDone = strtolower($training->status ?? '') === 'done';
                    @endphp
                    @if ($trainingDone)
                        <div class="text-center">{{ $assessment->practical_score ?? '-' }}</div>
                    @else
                        <div class="flex justify-center">
                            <input type="number" min="0" max="100" step="0.1"
                                wire:model.live.debounce.300ms="tempScores.{{ $assessment->id }}.practical_score"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                placeholder="0-100">
                        </div>
                    @endif
                @endscope

                {{-- Custom cell untuk Average Score --}}
                @scope('cell_average_score', $assessment)
                    <div class="text-center font-medium" x-data="{
                        get average() {
                            let posttest = parseFloat(tempScores[{{ $assessment->id }}]?.posttest_score) || 0;
                            let practical = parseFloat(tempScores[{{ $assessment->id }}]?.practical_score) || 0;
                            // Calculate average: (posttest + practical) / 2, treat empty as 0
                            let avg = (posttest + practical) / 2;
                            return Math.round(avg * 10) / 10;
                        }
                    }" x-text="average > 0 ? average : '-'">
                    </div>
                @endscope

                {{-- Custom cell untuk Status --}}
                @scope('cell_status', $assessment)
                    @php
                        $trainingDone = strtolower($training->status ?? '') === 'done';
                    @endphp
                    <div class="flex justify-center" x-data="{
                        get status() {
                            @if ($trainingDone) return '{{ $assessment->status }}';
                                @else
                                    let posttest = parseFloat(tempScores[{{ $assessment->id }}]?.posttest_score) || 0;
                                    let practical = parseFloat(tempScores[{{ $assessment->id }}]?.practical_score) || 0;
                                    // Calculate average: (posttest + practical) / 2, treat empty as 0
                                    let avg = (posttest + practical) / 2;
                                    if (avg === 0) return 'in_progress';
                                    return avg >= 60 ? 'passed' : 'failed'; @endif
                        }
                    }">
                        <span x-show="status === 'passed'" class="badge badge-success badge-sm">Passed</span>
                        <span x-show="status === 'failed'" class="badge badge-error badge-sm">Failed</span>
                        <span x-show="status === 'in_progress'" class="badge badge-warning badge-sm">In Progress</span>
                    </div>
                @endscope
            </x-table>
        </div>

        {{-- Action Buttons --}}
        @if (!$isDone && $typeUpper !== 'K-LEARN')
            <div class="flex justify-end gap-2 pt-4">
                <x-button wire:click="saveDraft" spinner="saveDraft" class="btn btn-outline btn-primary">
                    <x-icon name="o-document-text" class="w-5 h-5" />
                    Save Draft
                </x-button>
                <x-button wire:click="closeTraining" spinner="closeTraining" class="btn btn-primary">
                    <x-icon name="o-check-circle" class="w-5 h-5" />
                    Close Training
                </x-button>
            </div>
        @endif
    @else
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
            <p class="text-sm text-gray-600">Training data not found.</p>
        </div>
    @endif
</div>
