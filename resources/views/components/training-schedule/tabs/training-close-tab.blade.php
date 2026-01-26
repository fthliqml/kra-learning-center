<div class="space-y-4" x-data="{ tempScores: @entangle('tempScores') }">
    @if ($training)
        @php
            $isLms = strtoupper($training->type ?? '') === 'LMS';
            $isDone = in_array(strtolower($training->status ?? ''), ['done', 'approved', 'rejected']);
            $attendanceMin = 75;
            $theoryMin = $training->module->theory_passing_score ?? null;
            // Practical minimum is now stored as letter grade (A-E)
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
                                    (Post-Test)</span><span>&ge;
                                    {{ rtrim(rtrim(number_format((float) $theoryMin, 2, '.', ''), '0'), '.') }}</span></span>
                        @endif
                        @if ($practicalMin !== null)
                            <span class="inline-flex items-center gap-1"><span
                                    class="font-semibold">Practical</span><span>&ge;
                                    {{ strtoupper($practicalMin) }}</span></span>
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

        @if (($missingPretestCount ?? 0) > 0 || ($missingPosttestCount ?? 0) > 0)
            <div class="p-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-sm">
                @if (($missingPretestCount ?? 0) > 0)
                    <div>Pre-test belum selesai (belum dikerjakan / masih review) oleh {{ $missingPretestCount }}
                        peserta.</div>
                @endif
                @if (($missingPosttestCount ?? 0) > 0)
                    <div>Post-test belum selesai (belum dikerjakan / masih review) oleh {{ $missingPosttestCount }}
                        peserta.</div>
                @endif
            </div>
        @endif

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

                {{-- Custom cell untuk Pretest Score --}}
                @scope('cell_pretest_score', $assessment, $testReviewStatus, $training)
                    <div class="flex flex-col items-center gap-1">
                        @php
                            $trainingDone = $assessment->training_done ?? false;
                            $isLmsType = !empty($assessment->is_lms);
                            $reviewStatus = $testReviewStatus[$assessment->employee_id] ?? [];
                            $pretestNeedReview = $reviewStatus['pretest_need_review'] ?? false;
                            $pretestAttempted = (bool) ($tempScores[$assessment->id]['pretest_attempted'] ?? true);
                        @endphp
                        @if ($isLmsType)
                            {{-- LMS: Pretest is read-only, synced from course pretest --}}
                            <div tabindex="-1"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                {{ $assessment->temp_pretest ?? '-' }}</div>
                            @if (!$pretestAttempted)
                                <span class="badge badge-warning badge-xs">Belum dikerjakan</span>
                            @endif
                        @else
                            {{-- Non-LMS: Pretest is editable unless training is done --}}
                            @if ($pretestNeedReview)
                                <a href="{{ route('test-review.answers', ['training' => $training->id, 'user' => $assessment->employee_id]) }}"
                                    class="badge badge-warning badge-xs cursor-pointer hover:badge-outline gap-1"
                                    wire:navigate title="Click to review">
                                    Need Review
                                </a>
                            @endif
                            <div tabindex="-1"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none {{ $pretestNeedReview ? 'border-warning' : '' }}">
                                {{ $assessment->temp_pretest ?? 0 }}</div>
                            @if (!$pretestAttempted && !$pretestNeedReview)
                                <span class="badge badge-warning badge-xs">Belum dikerjakan</span>
                            @endif
                        @endif
                    </div>
                @endscope

                {{-- Custom cell untuk Post-Test Score --}}
                @scope('cell_posttest_score', $assessment, $testReviewStatus, $training)
                    <div class="flex flex-col items-center gap-1">
                        @php
                            $trainingDone = $assessment->training_done ?? false;
                            $reviewStatus = $testReviewStatus[$assessment->employee_id] ?? [];
                            $posttestNeedReview = $reviewStatus['posttest_need_review'] ?? false;
                            $posttestAttempted = (bool) ($tempScores[$assessment->id]['posttest_attempted'] ?? true);
                        @endphp
                        @if (!empty($assessment->is_lms))
                            <div tabindex="-1"
                                class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                {{ $assessment->temp_posttest ?? '-' }}</div>
                            @if (!$posttestAttempted)
                                <span class="badge badge-warning badge-xs">Belum dikerjakan</span>
                            @endif
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
                                <div tabindex="-1"
                                    class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none {{ $posttestNeedReview ? 'border-warning' : '' }}">
                                    {{ $assessment->temp_posttest ?? 0 }}</div>
                            @endif
                            @if (!$posttestAttempted && !$posttestNeedReview)
                                <span class="badge badge-warning badge-xs">Belum dikerjakan</span>
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
                                @php
                                    $score = $assessment->practical_score;
                                    $grade = null;
                                    if ($score !== null) {
                                        if ($score >= 90) {
                                            $grade = 'A';
                                        } elseif ($score >= 81) {
                                            $grade = 'B';
                                        } elseif ($score >= 71) {
                                            $grade = 'C';
                                        } elseif ($score >= 61) {
                                            $grade = 'D';
                                        } else {
                                            $grade = 'E';
                                        }
                                    }
                                @endphp
                                <div tabindex="-1"
                                    class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded bg-gray-50 opacity-60 select-none">
                                    {{ $grade ?? '-' }}</div>
                            @else
                                <select wire:model.live="tempScores.{{ $assessment->id }}.practical_grade"
                                    class="w-24 px-2 py-1 text-sm text-center border border-gray-300 rounded outline-none focus:ring-1 focus:ring-primary focus:border-primary bg-white">
                                    <option value="">-</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                </select>
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

        <x-modal wire:model="confirmCloseModal" title="Confirm Close Training" separator box-class="max-w-md">
            <div class="space-y-3">
                <p class="text-sm text-gray-700">
                    Anda akan menutup training ini. Peserta yang belum menyelesaikan pre-test / post-test akan tetap
                    berstatus <span class="font-semibold">Failed</span>.
                </p>

                @if (($missingPretestCount ?? 0) > 0 || ($missingPosttestCount ?? 0) > 0)
                    <div class="p-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-sm">
                        @if (!empty($missingTestParticipants ?? []))
                            <div class="mt-2 max-h-40 overflow-auto space-y-1">
                                @foreach ($missingTestParticipants ?? [] as $row)
                                    @php
                                        $missing = $row['missing'] ?? [];
                                        $parts = [];
                                        if (in_array('pretest', $missing, true)) {
                                            $parts[] = 'belum pre-test';
                                        }
                                        if (in_array('posttest', $missing, true)) {
                                            $parts[] = 'belum post-test';
                                        }
                                        $missingText = implode(' dan ', $parts);
                                    @endphp
                                    <div class="text-sm">
                                        <span class="font-medium">{{ $row['name'] ?? 'Unknown' }}</span>:
                                        {{ $missingText }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-3 rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm">
                        Semua peserta sudah menyelesaikan pre-test dan post-test.
                    </div>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <x-button label="Cancel" wire:click="$set('confirmCloseModal', false)" class="btn-ghost" />
                    <x-button label="Yes, Close Training" wire:click="confirmCloseTraining" class="btn btn-primary" />
                </div>
            </div>
        </x-modal>
    @else
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
            <p class="text-sm text-gray-600">Training data not found.</p>
        </div>
    @endif
</div>
