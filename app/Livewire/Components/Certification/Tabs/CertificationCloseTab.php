<?php

namespace App\Livewire\Components\Certification\Tabs;

use App\Models\Certification;
use App\Models\CertificationParticipant;
use App\Models\CertificationScore;
use App\Models\CertificationSession;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;
use Carbon\Carbon;

class CertificationCloseTab extends Component
{
    use Toast;

    public int $certificationId;
    public $certification; // Certification model instance
    public array $sessions = []; // simplified session info for display
    public array $rows = []; // participant base rows
    public bool $isClosed = false;
    public string $search = '';
    public bool $saving = false;
    public array $tempScores = []; // temp input scores keyed by participant_id

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
        $this->certification = Certification::with(['certificationModule', 'sessions', 'participants'])->find($this->certificationId);
        if (!$this->certification) {
            $this->error('Certification not found.', position: 'toast-top toast-center');
            return;
        }
        $this->isClosed = strtolower($this->certification->status ?? '') === 'done';

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

        $this->rows = [];
        $this->tempScores = [];
        foreach ($participants as $p) {
            $theoryScore = $p->scores->first(fn($sc) => $sc->session_id === $theoryId);
            $practicalScore = $p->scores->first(fn($sc) => $sc->session_id === $practicalId);
            $this->rows[] = [
                'participant_id' => $p->id,
                'employee_id' => $p->employee_id,
                'name' => $p->employee?->name ?? 'Unknown',
                'theory_session_id' => $theoryId,
                'practical_session_id' => $practicalId,
                'theory' => $theoryScore?->score,
                'practical' => $practicalScore?->score,
                'note' => null,
            ];
            $this->tempScores[$p->id] = [
                'theory' => $theoryScore?->score,
                'practical' => $practicalScore?->score,
                'note' => null,
            ];
        }
    }

    public function updatedSearch(): void
    {
        // Could implement filtering; for now trigger render
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
                foreach ($this->rows as $row) {
                    $pid = $row['participant_id'];
                    $vals = $this->tempScores[$pid] ?? [];
                    if ($row['theory_session_id'] && ($vals['theory'] !== null && $vals['theory'] !== '')) {
                        CertificationScore::updateOrCreate([
                            'participant_id' => $pid,
                            'session_id' => $row['theory_session_id'],
                        ], [
                            'score' => (float)$vals['theory'],
                            'status' => 'passed',
                            'recorded_at' => Carbon::now(),
                        ]);
                    }
                    if ($row['practical_session_id'] && ($vals['practical'] !== null && $vals['practical'] !== '')) {
                        CertificationScore::updateOrCreate([
                            'participant_id' => $pid,
                            'session_id' => $row['practical_session_id'],
                        ], [
                            'score' => (float)$vals['practical'],
                            'status' => 'passed',
                            'recorded_at' => Carbon::now(),
                        ]);
                    }
                }
            });
            $this->success('Draft saved.', position: 'toast-top toast-center');
            $this->loadData();
        } catch (\Throwable $e) {
            $this->error('Failed to save draft.', position: 'toast-top toast-center');
        }
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
        // Ensure all participants have both scores filled
        $incomplete = collect($this->tempScores)->filter(fn($v) => ($v['theory'] === null || $v['theory'] === '') || ($v['practical'] === null || $v['practical'] === ''));
        if ($incomplete->isNotEmpty()) {
            $this->error('All participants must have theory and practical scores before closing.', position: 'toast-top toast-center');
            return;
        }
        // Persist any unsaved scores first
        $this->saveDraft();
        try {
            $this->certification->status = 'done';
            $this->certification->approved_at = Carbon::now();
            $this->certification->save();
            $this->isClosed = true;
            $this->success('Certification closed successfully.', position: 'toast-top toast-center');
            $this->dispatch('certification-updated', id: $this->certification->id);
            $this->dispatch('close-modal');
        } catch (\Throwable $e) {
            $this->error('Failed to close certification.', position: 'toast-top toast-center');
        }
    }

    public function render()
    {
        return view('components.certification.tabs.certification-close-tab', [
            'sessions' => $this->sessions,
            'rows' => $this->filteredRows(),
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
        $theoryPassingScore = $this->certification?->certificationModule->theory_passing_score ?? 0;
        $practicalPassingScore = $this->certification?->certificationModule->practical_passing_score ?? 0;
        $modulePoints = $this->certification?->certificationModule->points_per_module ?? 0;
        $out = [];
        foreach ($this->rows as $index => $r) {
            $vals = $this->tempScores[$r['participant_id']] ?? ['theory' => null, 'practical' => null, 'note' => null];
            $theory = $vals['theory'];
            $practical = $vals['practical'];
            // Determine participant status ONLY from persisted scores (current temp mirrors loaded scores until saved)
            $hasTheory = $theory !== null && $theory !== '';
            $hasPractical = $practical !== null && $practical !== '';
            if (!$hasTheory || !$hasPractical) {
                $status = 'pending';
            } else {
                $theoryPassed = (float)$theory >= $theoryPassingScore;
                $practicalPassed = (float)$practical >= $practicalPassingScore;
                $status = ($theoryPassed && $practicalPassed) ? 'passed' : 'failed';
            }
            $earnedPoint = $status === 'passed' ? $modulePoints : 0;
            $row = [
                'id' => $r['participant_id'],
                'no' => $index + 1,
                'employee_name' => $r['name'],
                'theory_score' => $theory,
                'practical_score' => $practical,
                'status' => $status,
                'earned_point' => $earnedPoint,
                'note' => $vals['note'] ?? null,
                'participant_id' => $r['participant_id'],
                'cert_done' => $this->isClosed,
            ];
            if ($search && stripos($row['employee_name'], $search) === false) {
                continue;
            }
            $out[] = $row; // use associative array for blade compatibility
        }
        return collect($out);
    }
}
