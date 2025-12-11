<?php

namespace App\Livewire\Pages\Certification;

use App\Models\Certification;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

class CertificationApproval extends Component
{
    use WithPagination, Toast;

    public $modal = false;
    public $selectedId = null;
    public $activeTab = 'information';

    public $search = '';
    public $filter = 'All';

    public array $formData = [
        'certification_name' => '',
        'module_name' => '',
        'created_at' => '',
    ];

    public $groupOptions = [
        ['value' => 'Pending', 'label' => 'Pending'],
        ['value' => 'Approved', 'label' => 'Approved'],
        ['value' => 'Rejected', 'label' => 'Rejected'],
    ];

    public function mount(): void
    {
        // No need for user searchable since we don't have create mode
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12 !md:w-[8%]'],
            ['key' => 'certification_name', 'label' => 'Certification Name', 'class' => '!md:w-[50%]'],
            ['key' => 'date', 'label' => 'Date', 'class' => '!text-center !md:w-[12%]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center !md:w-[14%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center !md:w-[10%]'],
        ];
    }

    public function participantHeaders(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-[60px]'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => 'w-[120px]'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-[200px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => '!text-center w-[100px]'],
            ['key' => 'theory_score', 'label' => 'Theory', 'class' => '!text-center w-[80px]'],
            ['key' => 'practical_score', 'label' => 'Practical', 'class' => '!text-center w-[80px]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center w-[100px]'],
            ['key' => 'earned_point', 'label' => 'Point', 'class' => '!text-center w-[80px]'],
        ];
    }

    public function getParticipantsProperty()
    {
        if (!$this->selectedId) {
            return collect();
        }

        $certification = Certification::with([
            'certificationModule',
            'participants.employee',
            'participants.scores.session',
        ])->find($this->selectedId);

        if (!$certification) {
            return collect();
        }

        // Get passing scores from module
        $theoryPassingScore = $certification->certificationModule->theory_passing_score ?? 70;
        $practicalPassingScore = $certification->certificationModule->practical_passing_score ?? 75;
        $modulePoints = $certification->certificationModule->points_per_module ?? 0;

        return $certification->participants->map(function ($participant, $index) use ($certification, $theoryPassingScore, $practicalPassingScore, $modulePoints) {
            $employee = $participant->employee;
            $scores = $participant->scores;

            // Separate theory and practical scores
            $theoryScore = $scores->first(function ($score) {
                return $score->session && $score->session->type === 'theory';
            });

            $practicalScore = $scores->first(function ($score) {
                return $score->session && $score->session->type === 'practical';
            });

            // Determine overall status based on module passing scores
            // Both theory and practical must exist AND pass to overall pass
            $theoryPassed = $theoryScore && $theoryScore->score >= $theoryPassingScore;
            $practicalPassed = $practicalScore && $practicalScore->score >= $practicalPassingScore;

            // If either theory or practical is missing, status is failed
            $overallStatus = ($theoryScore && $practicalScore && $theoryPassed && $practicalPassed) ? 'passed' : 'failed';

            // Calculate earned points
            $earnedPoint = $overallStatus === 'passed' ? $modulePoints : 0;

            return (object) [
                'no' => $index + 1,
                'nrp' => $employee->nrp ?? '-',
                'name' => $employee->name ?? '-',
                'section' => $employee->section ?? '-',
                'theory_score' => $theoryScore ? number_format($theoryScore->score, 1) : '-',
                'practical_score' => $practicalScore ? number_format($practicalScore->score, 1) : '-',
                'status' => $overallStatus,
                'earned_point' => $earnedPoint,
                'theory_raw' => $theoryScore ? $theoryScore->score : null,
                'practical_raw' => $practicalScore ? $practicalScore->score : null,
                'theory_threshold' => $theoryPassingScore,
                'practical_threshold' => $practicalPassingScore,
            ];
        });
    }

    public function approvals()
    {
        $query = Certification::with('certificationModule')
            ->whereIn('status', ['completed', 'approved', 'rejected']);

        // Filter by search
        if ($this->search) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('certificationModule', function ($moduleQuery) use ($term) {
                        $moduleQuery->where('module_title', 'like', "%{$term}%")
                            ->orWhere('competency', 'like', "%{$term}%");
                    });
            });
        }

        // Filter by display status (completed = pending for display)
        if ($this->filter && strtolower($this->filter) !== 'all') {
            $filterStatus = strtolower($this->filter);

            if ($filterStatus === 'pending') {
                $query->where('status', 'completed');
            } else {
                $query->where('status', $filterStatus);
            }
        }

        return $query
            ->orderByRaw("CASE WHEN status = 'completed' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate(10)
            ->through(function ($certification) {
                // Map status: completed -> pending for display
                $displayStatus = $certification->status === 'completed' ? 'pending' : $certification->status;

                return (object) [
                    'id' => $certification->id,
                    'certification_name' => $certification->name,
                    'module_name' => $certification->certificationModule->module_title ?? '-',
                    'competency' => $certification->certificationModule->competency ?? '-',
                    'date' => $certification->created_at,
                    'status' => $displayStatus,
                    'actual_status' => $certification->status, // Store actual status for updates
                ];
            });
    }

    public function render()
    {
        return view('pages.certification.certification-approval', [
            'headers' => $this->headers(),
            'approvals' => $this->approvals(),
        ]);
    }

    public function openDetailModal(int $id): void
    {
        $certification = Certification::with('certificationModule')->find($id);

        if (!$certification) {
            return;
        }

        // Map status: completed -> pending for display
        $displayStatus = $certification->status === 'completed' ? 'pending' : $certification->status;

        $this->selectedId = $certification->id;
        $this->activeTab = 'information'; // Reset to information tab
        $this->formData = [
            'certification_name' => $certification->name,
            'module_name' => $certification->certificationModule->module_title ?? '-',
            'competency' => $certification->certificationModule->competency ?? '-',
            'created_at' => $certification->created_at->format('d F Y'),
            'status' => $displayStatus,
            'actual_status' => $certification->status,
        ];
        $this->modal = true;
        $this->resetValidation();
    }

    /**
     * Determine if the current authenticated user can moderate (approve/reject)
     */
    protected function canModerate(): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;
        // Only section head from LID can moderate
        return strtolower(trim($user->role ?? '')) === 'section_head' && strtolower(trim($user->section ?? '')) === 'lid';
    }

    /** Approve selected request */
    public function approve(): void
    {
        if (!$this->selectedId) {
            return;
        }
        if (!$this->canModerate()) {
            $this->error('Only LID leader can approve.', position: 'toast-top toast-center');
            return;
        }

        $certification = Certification::find($this->selectedId);

        if (!$certification) {
            $this->error('Certification not found.', position: 'toast-top toast-center');
            return;
        }

        // Update status to approved
        $certification->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->formData['status'] = 'approved';
        $this->success('Certification approved successfully', position: 'toast-top toast-center');

        $this->modal = false;
    }

    /** Reject selected request */
    public function reject(): void
    {
        if (!$this->selectedId) {
            return;
        }
        if (!$this->canModerate()) {
            $this->error('Only LID leader can reject.', position: 'toast-top toast-center');
            return;
        }

        $certification = Certification::find($this->selectedId);

        if (!$certification) {
            $this->error('Certification not found.', position: 'toast-top toast-center');
            return;
        }

        // Update status to rejected
        $certification->update(['status' => 'rejected']);

        $this->formData['status'] = 'rejected';
        $this->error('Certification rejected', position: 'toast-top toast-center');

        $this->modal = false;
    }
}
