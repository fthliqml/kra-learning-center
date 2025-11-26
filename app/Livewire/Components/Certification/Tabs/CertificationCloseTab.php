<?php

namespace App\Livewire\Components\Certification\Tabs;

use App\Models\Certification;
use App\Models\CertificationParticipant;
use App\Models\CertificationScore;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Mary\Traits\Toast;

class CertificationCloseTab extends Component
{
    use Toast;

    public int $certificationId;
    public $certification;
    public array $sessions = [];
    public array $participantsData = []; // Renamed from $rows to avoid conflict
    public bool $isClosed = false;
    public string $search = '';
    public array $tempScores = [];

    protected $listeners = [
        'cert-close-save-draft' => 'saveDraft',
        'cert-close-close' => 'closeCertification',
    ];

    public function mount($certificationId)
    {
        $this->certificationId = (int) $certificationId;
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->certification = Certification::with(['certificationModule', 'participants'])->find($this->certificationId);
        if (!$this->certification) {
            $this->error('Certification not found.', position: 'toast-top toast-center');
            return;
        }
        $this->isClosed = in_array(strtolower($this->certification->status ?? ''), ['done', 'completed']);

        $sessions = $this->certification->sessions->sortBy('date')->values();
        $this->sessions = $sessions->map(function ($s) {
            return [
                'id' => $s->id,
                'type' => strtoupper($s->type ?? ''),
                'date' => $s->date ? Carbon::parse($s->date)->format('d M Y') : null,
                'start_time' => $s->start_time ? Carbon::parse($s->start_time)->format('H:i') : null,
                'end_time' => $s->end_time ? Carbon::parse($s->end_time)->format('H:i') : null,
                'location' => $s->location,
            ];
        })->toArray();

        $theorySession = $sessions->first(fn($s) => strtoupper($s->type ?? '') === 'THEORY');
        $practicalSession = $sessions->first(fn($s) => strtoupper($s->type ?? '') === 'PRACTICAL');
        $theoryId = $theorySession?->id;
        $practicalId = $practicalSession?->id;

        $participants = CertificationParticipant::with(['employee', 'scores' => function ($q) use ($theoryId, $practicalId) {
            $q->when($theoryId, fn($qq) => $qq->orWhere('session_id', $theoryId))
                ->when($practicalId, fn($qq) => $qq->orWhere('session_id', $practicalId));
        }])->where('certification_id', $this->certificationId)->get();

        $this->participantsData = [];
        $this->tempScores = [];
        foreach ($participants as $p) {
            $theoryScore = $p->scores->first(fn($sc) => $sc->session_id === $theoryId);
            $practicalScore = $p->scores->first(fn($sc) => $sc->session_id === $practicalId);
            $this->participantsData[] = [
                'participant_id' => $p->id,
                'employee_id' => $p->employee_id,
                'name' => $p->employee?->name ?? 'Unknown',
                'theory_session_id' => $theoryId,
                'practical_session_id' => $practicalId,
                'theory' => $theoryScore?->score,
                'practical' => $practicalScore?->score,
                'note' => null,
                // Don't include status here - will be calculated in filteredRows()
            ];
            $this->tempScores[$p->id] = [
                'theory' => $theoryScore?->score,
                'practical' => $practicalScore?->score,
                'note' => null,
            ];
        }
    }

    private function validateTemp(): void
    {
        $rules = [];
        foreach ($this->tempScores as $pid => $vals) {
            $rules["tempScores.$pid.theory"] = 'nullable|numeric|min:0|max:100';
            $rules["tempScores.$pid.practical"] = 'nullable|numeric|min:0|max:100';
            $rules["tempScores.$pid.note"] = 'nullable|string|max:255';
        }
        $this->validate($rules, [
            'numeric' => ':attribute must be a number.',
            'min' => ':attribute must be at least :min.',
            'max' => ':attribute may not be greater than :max.',
        ], [
            'tempScores.*.theory' => 'Theory score',
            'tempScores.*.practical' => 'Practical score',
            'tempScores.*.note' => 'Note',
        ]);
    }

    public function saveDraft(): void
    {
        if ($this->isClosed) return;

        $this->validateTemp();

        try {
            DB::transaction(function () {
                $module = $this->certification->certificationModule;
                $theoryPassingScore = $module->theory_passing_score ?? 0;
                $practicalPassingScore = $module->practical_passing_score ?? 0;
                $pointsPerModule = $module->points_per_module ?? 0;

                foreach ($this->participantsData as $row) {
                    $participantId = $row['participant_id'];
                    $scores = $this->tempScores[$participantId] ?? [];

                    $theoryScore = $this->getNumericScore($scores['theory'] ?? null);
                    $practicalScore = $this->getNumericScore($scores['practical'] ?? null);

                    $theoryStatus = $this->saveSessionScore(
                        $participantId,
                        $row['theory_session_id'],
                        $theoryScore,
                        $theoryPassingScore
                    );

                    $practicalStatus = $this->saveSessionScore(
                        $participantId,
                        $row['practical_session_id'],
                        $practicalScore,
                        $practicalPassingScore
                    );

                    $this->updateParticipantStatus(
                        $participantId,
                        $theoryStatus,
                        $practicalStatus,
                        $row['theory_session_id'],
                        $row['practical_session_id'],
                        $pointsPerModule
                    );
                }
            });

            $this->success('Draft saved.', position: 'toast-top toast-center');
            $this->loadData();
        } catch (\Throwable $e) {
            Log::error('Failed to save draft: ' . $e->getMessage());
            $this->error('Failed to save draft.', position: 'toast-top toast-center');
        }
    }

    private function getNumericScore($value): ?float
    {
        if ($value === null || $value === '') return null;
        return is_numeric($value) ? (float)$value : null;
    }

    private function saveSessionScore(int $participantId, ?int $sessionId, ?float $score, float $passingScore): ?string
    {
        if (!$sessionId || $score === null) return null;

        $status = $score >= $passingScore ? 'passed' : 'failed';

        CertificationScore::updateOrCreate(
            [
                'participant_id' => $participantId,
                'session_id' => $sessionId,
            ],
            [
                'score' => $score,
                'status' => $status,
                'recorded_at' => Carbon::now(),
            ]
        );

        return $status;
    }

    private function updateParticipantStatus(
        int $participantId,
        ?string $theoryStatus,
        ?string $practicalStatus,
        ?int $theorySessionId,
        ?int $practicalSessionId,
        int $pointsPerModule
    ): void {
        $finalStatus = 'pending';
        $earnedPoints = 0;

        // Both sessions exist
        if ($theorySessionId && $practicalSessionId) {
            if ($theoryStatus === 'passed' && $practicalStatus === 'passed') {
                $finalStatus = 'passed';
                $earnedPoints = $pointsPerModule;
            } elseif ($theoryStatus === 'failed' || $practicalStatus === 'failed') {
                $finalStatus = 'failed';
            }
        }
        // Only theory session
        elseif ($theorySessionId && !$practicalSessionId && $theoryStatus) {
            $finalStatus = $theoryStatus;
            $earnedPoints = $theoryStatus === 'passed' ? $pointsPerModule : 0;
        }
        // Only practical session
        elseif (!$theorySessionId && $practicalSessionId && $practicalStatus) {
            $finalStatus = $practicalStatus;
            $earnedPoints = $practicalStatus === 'passed' ? $pointsPerModule : 0;
        }

        CertificationParticipant::where('id', $participantId)->update([
            'final_status' => $finalStatus,
            'earned_points' => $earnedPoints,
        ]);
    }

    public function closeCertification(): void
    {
        if (!$this->certification) {
            $this->error('Certification not found.', position: 'toast-top toast-center');
            return;
        }

        if ($this->isClosed) {
            $this->error('Certification already closed.', position: 'toast-top toast-center');
            return;
        }

        $incomplete = $this->validateAllScoresFilled();

        if (!empty($incomplete)) {
            $message = $this->formatIncompleteMessage($incomplete);
            $this->error($message, position: 'toast-top toast-center');
            return;
        }

        $this->saveDraft();

        try {
            $this->certification->update([
                'status' => 'completed',
                'approved_at' => Carbon::now(),
            ]);

            $this->isClosed = true;
            $this->success('Certification closed successfully.', position: 'toast-top toast-center');
            $this->dispatch('certification-updated', id: $this->certification->id);
            $this->dispatch('close-modal');
        } catch (\Throwable $e) {
            Log::error('Failed to close certification: ' . $e->getMessage());
            $this->error('Failed to close certification.', position: 'toast-top toast-center');
        }
    }

    private function validateAllScoresFilled(): array
    {
        $incomplete = [];

        foreach ($this->rows as $row) {
            $scores = $this->tempScores[$row['participant_id']] ?? [];
            $hasTheorySession = !empty($row['theory_session_id']);
            $hasPracticalSession = !empty($row['practical_session_id']);

            if ($hasTheorySession && !is_numeric($scores['theory'] ?? null)) {
                $incomplete[] = $row['name'] . ' (theory)';
            }

            if ($hasPracticalSession && !is_numeric($scores['practical'] ?? null)) {
                $incomplete[] = $row['name'] . ' (practical)';
            }
        }

        return $incomplete;
    }

    private function formatIncompleteMessage(array $incomplete): string
    {
        $names = implode(', ', array_slice($incomplete, 0, 3));

        if (count($incomplete) > 3) {
            $names .= ' and ' . (count($incomplete) - 3) . ' more';
        }

        return "Missing scores for: {$names}";
    }
    public function getRowsProperty()
    {
        return $this->filteredRows();
    }

    public function render()
    {
        return view('components.certification.tabs.certification-close-tab', [
            'sessions' => $this->sessions,
            'rows' => $this->rows, // This will call getRowsProperty()
            'headers' => $this->headers(),
            'isClosed' => $this->isClosed,
            'certification' => $this->certification,
        ]);
    }

    private function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'employee_name', 'label' => 'Employee Name', 'class' => 'min-w-[150px]'],
            ['key' => 'theory_score', 'label' => 'Theory Score', 'class' => '!text-center min-w-[120px]'],
            ['key' => 'practical_score', 'label' => 'Practical Score', 'class' => '!text-center min-w-[120px]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center min-w-[110px]'],
            ['key' => 'earned_point', 'label' => 'Point', 'class' => '!text-center min-w-[90px]'],
            ['key' => 'note', 'label' => 'Note', 'class' => 'min-w-[160px]'],
        ];
    }

    private function filteredRows()
    {
        $search = trim($this->search);
        $module = $this->certification?->certificationModule;
        $theoryPassingScore = $module->theory_passing_score ?? 0;
        $practicalPassingScore = $module->practical_passing_score ?? 0;
        $modulePoints = $module->points_per_module ?? 0;

        $filtered = [];

        foreach ($this->participantsData as $index => $participant) {
            $scores = $this->tempScores[$participant['participant_id']] ?? [];
            $theoryScore = $scores['theory'] ?? null;
            $practicalScore = $scores['practical'] ?? null;

            $hasTheorySession = !empty($participant['theory_session_id']);
            $hasPracticalSession = !empty($participant['practical_session_id']);

            $status = $this->calculateStatus(
                $theoryScore,
                $practicalScore,
                $hasTheorySession,
                $hasPracticalSession,
                $theoryPassingScore,
                $practicalPassingScore
            );

            $earnedPoint = $status === 'passed' ? $modulePoints : 0;

            // Create clean row data - don't include original participant data to avoid merge issues
            $row = [
                'id' => $participant['participant_id'],
                'no' => $index + 1,
                'employee_name' => $participant['name'],
                'theory_score' => $theoryScore,
                'practical_score' => $practicalScore,
                'status' => $status, // This is the calculated status
                'earned_point' => $earnedPoint,
                'note' => $scores['note'] ?? null,
                'participant_id' => $participant['participant_id'],
                'cert_done' => $this->isClosed,
                // Don't include these to avoid confusion:
                // 'theory_session_id', 'practical_session_id', 'final_status', 'earned_points'
            ];

            if ($search && stripos($row['employee_name'], $search) === false) {
                continue;
            }

            $filtered[] = $row;
        }

        return collect($filtered);
    }

    private function calculateStatus(
        $theoryScore,
        $practicalScore,
        bool $hasTheorySession,
        bool $hasPracticalSession,
        float $theoryPassingScore,
        float $practicalPassingScore
    ): string {
        $hasTheoryScore = $hasTheorySession ? is_numeric($theoryScore) : true;
        $hasPracticalScore = $hasPracticalSession ? is_numeric($practicalScore) : true;

        if (!$hasTheoryScore || !$hasPracticalScore) {
            return 'pending';
        }

        $theoryValue = (float)$theoryScore;
        $practicalValue = (float)$practicalScore;
        $theoryPassed = $hasTheorySession ? ($theoryValue >= $theoryPassingScore) : true;
        $practicalPassed = $hasPracticalSession ? ($practicalValue >= $practicalPassingScore) : true;

        return ($theoryPassed && $practicalPassed) ? 'passed' : 'failed';
    }
}
