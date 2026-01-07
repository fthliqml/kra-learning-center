<div class="space-y-4" x-data="{ tempScores: @entangle('tempScores') }">
    @if ($training)
        @php
            $isLms = strtoupper($training->type ?? '') === 'LMS';
            $isDone = in_array(strtolower($training->status ?? ''), ['done', 'approved', 'rejected']);
            $attendanceMin = 75;
            $theoryMin = $training->module->theory_passing_score ?? null;
            $practicalMin = $training->module->practical_passing_score ?? null;
        @endphp

        @if (!$isDone)
            @if (!$isLms && ($theoryMin !== null || $practicalMin !== null))
                <div
                    class="p-3 md:p-4 rounded-lg border border-blue-200 bg-blue-50 text-xs md:text-sm text-blue-700 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                    <div class="font-medium">Minimum Passing Scores:</div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1"><span
                                class="font-semibold">Attendance</span><span>&ge;
                                {{ $attendanceMin }}%</span></span>
                        @if ($theoryMin !== null)
                            <span class="inline-flex items-center gap-1"><span class="font-semibold">Theory
                                    (Posttest)</span><span>&ge;
                                    {{ rtrim(rtrim(number_format((float) $theoryMin, 2, '.', ''), '0'), '.') }}</span></span>
                        @endif
                        @if ($practicalMin !== null)
                            <span class="inline-flex items-center gap-1"><span
                                    class="font-semibold">Practical</span><span>&ge;
                                    {{ rtrim(rtrim(number_format((float) $practicalMin, 2, '.', ''), '0'), '.') }}</span></span>
                        @endif
                    </div>
                </div>
            @endif
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

                {{-- Custom cell untuk Attendance Percentage --}}
                @scope('cell_attendance_percentage', $assessment)
                    <div class="flex justify-center">
                        @php
                            $attendancePercentage = $assessment->attendance_percentage ?? 0;
                            $colorClass =
                                $attendancePercentage >= 75
                                    ? 'text-green-600 font-semibold'
                                    : 'text-red-600 font-semibold';
                        @endphp
                        <span class="text-sm {{ $colorClass }}">{{ round($attendancePercentage) }}%</span>
                    </div>
                @endscope

                @if (!$isLms)
                    {{-- Custom cell untuk Pretest Score --}}
                    @scope('cell_pretest_score', $assessment, $testReviewStatus, $training)
                        <div class="flex flex-col items-center gap-1">
                            @php
                                $trainingDone = $assessment->training_done ?? false;
                                $reviewStatus = $testReviewStatus[$assessment->employee_id] ?? [];
                                $pretestNeedReview = $reviewStatus['pretest_need_review'] ?? false;
                            @endphp
                            @if ($pretestNeedReview)
                                <a href="{{ route('test-review.answers', ['training' => $training->id, 'user' => $assessment->employee_id]) }}"
                                    class="badge badge-warning badge-xs cursor-pointer hover:badge-outline gap-1"
                                    wire:navigate title="Click to review">
                                    Need Review
                                </a>
                            @endif
                            @if ($trainingDone)
                                <div tabindex="-1"
                                    class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                    {{ $assessment->pretest_score ?? '-' }}</div>
                            @else
                                <input type="number" min="0" max="100" step="0.1"
                                    wire:model.live="tempScores.{{ $assessment->id }}.pretest_score"
                                    class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded outline-none focus:ring-1 focus:ring-primary focus:border-primary {{ $pretestNeedReview ? 'border-warning' : '' }}"
                                    placeholder="0-100" {{ $pretestNeedReview ? 'readonly' : '' }}>
                            @endif
                        </div>
                    @endscope
                @endif

                {{-- Custom cell untuk Posttest Score --}}
                @scope('cell_posttest_score', $assessment, $testReviewStatus, $training)
                    <div class="flex flex-col items-center gap-1">
                        @php
                            $trainingDone = $assessment->training_done ?? false;
                            $reviewStatus = $testReviewStatus[$assessment->employee_id] ?? [];
                            $posttestNeedReview = $reviewStatus['posttest_need_review'] ?? false;
                        @endphp
                        @if (!empty($assessment->is_lms))
                            <div tabindex="-1"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                {{ $assessment->temp_posttest ?? '-' }}</div>
                        @else
                            @if ($posttestNeedReview)
                                <a href="{{ route('test-review.answers', ['training' => $training->id, 'user' => $assessment->employee_id]) }}"
                                    class="badge badge-warning badge-xs cursor-pointer hover:badge-outline gap-1"
                                    wire:navigate title="Click to review">
                                    Need Review
                                </a>
                            @endif
                            @if ($trainingDone)
                                <div tabindex="-1"
                                    class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                    {{ $assessment->posttest_score ?? '-' }}</div>
                            @else
                                <input type="number" min="0" max="100" step="0.1"
                                    wire:model.live="tempScores.{{ $assessment->id }}.posttest_score"
                                    class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded outline-none focus:ring-1 focus:ring-primary focus:border-primary {{ $posttestNeedReview ? 'border-warning' : '' }}"
                                    placeholder="0-100" {{ $posttestNeedReview ? 'readonly' : '' }}>
                            @endif
                        @endif
                    </div>
                @endscope

                @if (!$isLms)
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
                @endif

                @if ($isLms)
                    {{-- Custom cell untuk Progress --}}
                    @scope('cell_progress', $assessment)
                        <div class="flex justify-center">
                            <span class="text-sm text-gray-700">{{ (int) ($assessment->lms_progress ?? 0) }}%</span>
                        </div>
                    @endscope
                @endif

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

                @if (!$isLms)
                    {{-- Custom cell untuk Actions --}}
                    @scope('cell_actions', $assessment, $testReviewStatus, $training)
                        @php
                            $reviewStatus = $testReviewStatus[$assessment->employee_id] ?? [];
                            $needsReview =
                                ($reviewStatus['pretest_need_review'] ?? false) ||
                                ($reviewStatus['posttest_need_review'] ?? false);
                        @endphp
                        @if ($needsReview)
                            <div class="flex justify-center">
                                <a href="{{ route('test-review.answers', ['training' => $training->id, 'user' => $assessment->employee_id]) }}"
                                    class="btn btn-warning btn-xs gap-1" title="Review Test Answers" wire:navigate>
                                    <x-icon name="o-clipboard-document-check" class="size-3.5" />
                                    Review
                                </a>
                            </div>
                        @endif
                    @endscope
                @endif
            </x-table>
        </div>
    @else
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
            <p class="text-sm text-gray-600">Training data not found.</p>
        </div>
    @endif
</div>
