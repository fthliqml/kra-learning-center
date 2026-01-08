<?php

namespace App\Livewire\Pages\Training;

use App\Models\Training;
use App\Models\User;
use App\Services\TrainingCertificateService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

class Approval extends Component
{
    use WithPagination, Toast;

    public $modal = false;
    public $selectedId = null;
    public $activeTab = 'information';

    public $search = '';
    public $filter = 'All';

    public array $formData = [
        'training_name' => '',
        'type' => '',
        'created_at' => '',
    ];

    public $groupOptions = [
        ['value' => 'done', 'label' => 'Done (Ready for Approval)'],
        ['value' => 'approved', 'label' => 'Approved'],
        ['value' => 'rejected', 'label' => 'Rejected'],
    ];

    public function mount(): void
    {
        // No need for user searchable since we don't have create mode
    }

    // Reset pagination only when relevant filters change
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12 !md:w-[8%]'],
            ['key' => 'training_name', 'label' => 'Training Name', 'class' => '!md:w-[50%]'],
            ['key' => 'date', 'label' => 'Date', 'class' => '!text-center !md:w-[12%]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center !md:w-[14%]'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center !md:w-[10%]'],
        ];
    }

    public function participantHeaders(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-[50px]'],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => 'w-[100px]'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-[150px]'],
            ['key' => 'section', 'label' => 'Section', 'class' => 'w-[150px]'],
            ['key' => 'attendance', 'label' => 'Attendance', 'class' => '!text-center w-[100px]'],
            ['key' => 'theory_score', 'label' => 'Theory Score', 'class' => '!text-center w-[100px]'],
            ['key' => 'practice_score', 'label' => 'Practice Score', 'class' => '!text-center w-[100px]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center w-[80px]'],
            ['key' => 'certificate', 'label' => 'Certificate', 'class' => '!text-center w-[140px]'],
        ];
    }

    public function getParticipantsProperty()
    {
        if (!$this->selectedId) {
            return collect();
        }

        $training = Training::with([
            'sessions',
            'attendances.employee',
            'assessments.employee',
        ])->find($this->selectedId);

        if (!$training) {
            return collect();
        }

        // For LMS type, participants come from assessments (no physical attendance)
        // For other types (IN, OUT), participants come from attendances
        if (strtoupper($training->type) === 'LMS') {
            // LMS: Get participants from assessments
            $participants = $training->assessments->map(function ($assessment, $index) use ($training) {
                $employee = $assessment->employee;

                if (!$employee) {
                    return null;
                }

                // For LMS, no physical attendance - use course progress or assessment data
                $theoryScore = $assessment->posttest_score;
                $practiceScore = $assessment->practical_score;
                $attendancePercentage = $assessment->attendance_percentage;
                $status = $assessment->status ?? 'pending';
                $certificatePath = $assessment->certificate_path;

                return (object) [
                    'no' => $index + 1,
                    'nrp' => $employee->nrp ?? '-',
                    'name' => $employee->name ?? '-',
                    'section' => $employee->section ?? '-',
                    'attendance' => $attendancePercentage !== null ? round($attendancePercentage) . '%' : '-',
                    'attendance_raw' => $attendancePercentage,
                    'theory_score' => $theoryScore,
                    'practice_score' => $practiceScore,
                    'status' => $status,
                    'certificate_path' => $certificatePath,
                    'employee_id' => $employee->id,
                    'assessment_id' => $assessment->id,
                    'training_status' => $training->status,
                ];
            })->filter()->values();

            return $participants;
        }

        // For IN/OUT types: Get unique participants from attendances
        $participants = $training->attendances->unique('employee_id')->map(function ($attendance, $index) use ($training) {
            $employee = $attendance->employee;

            // Get assessment for this employee
            $assessment = $training->assessments->firstWhere('employee_id', $employee->id);

            // Determine status
            $status = $assessment ? $assessment->status : 'pending';
            $theoryScore = $assessment ? $assessment->posttest_score : null;
            $practiceScore = $assessment ? $assessment->practical_score : null;
            $attendancePercentage = $assessment ? $assessment->attendance_percentage : null;
            $certificatePath = $assessment ? $assessment->certificate_path : null;
            $assessmentId = $assessment ? $assessment->id : null;

            return (object) [
                'no' => $index + 1,
                'nrp' => $employee->nrp ?? '-',
                'name' => $employee->name ?? '-',
                'section' => $employee->section ?? '-',
                'attendance' => $attendancePercentage !== null ? round($attendancePercentage) . '%' : '-',
                'attendance_raw' => $attendancePercentage,
                'theory_score' => $theoryScore,
                'practice_score' => $practiceScore,
                'status' => $status,
                'certificate_path' => $certificatePath,
                'employee_id' => $employee->id,
                'assessment_id' => $assessmentId,
                'training_status' => $training->status,
            ];
        });

        return $participants->values();
    }

    public function approvals()
    {
        $query = Training::query()
            ->select('trainings.*')
            ->whereIn('status', ['done', 'approved', 'rejected'])
            ->distinct();

        // Filter by search
        if ($this->search) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%")
                    ->orWhereHas('competency', function ($cq) use ($term) {
                        $cq->where('type', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%");
                    })
                    ->orWhereHas('module.competency', function ($cq) use ($term) {
                        $cq->where('type', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%");
                    })
                    ->orWhereHas('course.competency', function ($cq) use ($term) {
                        $cq->where('type', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%");
                    });
            });
        }

        // Filter by actual status
        if ($this->filter && strtolower($this->filter) !== 'all') {
            $filterStatus = strtolower($this->filter);
            $query->where('status', $filterStatus);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->through(function ($training) {
                // Keep status as is - 'done' means closed and waiting for approval
                // Once approved, status will be 'approved' and certificates generated
                return (object) [
                    'id' => $training->id,
                    'training_name' => $training->name,
                    'type' => $training->type ?? '-',
                    'group_comp' => $training->group_comp ?? '-',
                    'start_date' => $training->start_date,
                    'end_date' => $training->end_date,
                    'status' => $training->status,
                    'actual_status' => $training->status,
                ];
            });
    }

    public function render()
    {
        return view('pages.training.training-approval', [
            'headers' => $this->headers(),
            'approvals' => $this->approvals(),
        ]);
    }

    public function openDetailModal(int $id): void
    {
        $training = Training::find($id);

        if (!$training) {
            return;
        }

        // Show actual status - 'done' means closed and ready for approval
        $this->selectedId = $training->id;
        $this->activeTab = 'information'; // Reset to information tab
        $this->formData = [
            'training_name' => $training->name,
            'type' => $training->type ?? '-',
            'group_comp' => $training->group_comp ?? '-',
            'start_date' => $training->start_date ? $training->start_date->format('d F Y') : '-',
            'end_date' => $training->end_date ? $training->end_date->format('d F Y') : '-',
            'created_at' => $training->created_at->format('d F Y'),
            'status' => $training->status,
            'actual_status' => $training->status,
        ];
        $this->modal = true;
        $this->resetValidation();
    }

    /**
     * Determine if the current authenticated user can moderate (approve/reject)
     */
    protected function canModerate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Only section head from LID can moderate
        return $user->hasPosition('section_head')
            && strtolower(trim($user->section ?? '')) === 'lid';
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

        $training = Training::find($this->selectedId);

        if (!$training) {
            $this->error('Training not found.', position: 'toast-top toast-center');
            return;
        }

        // Update status to approved
        $training->update([
            'status' => 'approved',
        ]);

        // Generate certificates for passed participants
        $certificateService = new TrainingCertificateService();
        $certificatesGenerated = $certificateService->generateCertificatesForTraining($training);

        $this->formData['status'] = 'approved';

        // Notify other components that training status changed
        $this->dispatch('training-closed', ['id' => $training->id, 'status' => 'approved']);

        if ($certificatesGenerated > 0) {
            $this->success("Training approved successfully. {$certificatesGenerated} certificate(s) generated.", position: 'toast-top toast-center');
        } else {
            $this->success('Training approved successfully', position: 'toast-top toast-center');
        }

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

        $training = Training::find($this->selectedId);

        if (!$training) {
            $this->error('Training not found.', position: 'toast-top toast-center');
            return;
        }

        // Update status to rejected
        $training->update(['status' => 'rejected']);

        // Hapus semua sertifikat yang sudah pernah digenerate untuk training ini
        $certificateService = new TrainingCertificateService();
        $assessmentsWithCertificate = $training->assessments()
            ->whereNotNull('certificate_path')
            ->get();

        foreach ($assessmentsWithCertificate as $assessment) {
            if (!empty($assessment->certificate_path)) {
                $certificateService->deleteCertificate($assessment->certificate_path);
            }
            $assessment->certificate_path = null;
            $assessment->save();
        }

        $this->formData['status'] = 'rejected';

        // Notify other components that training status changed
        $this->dispatch('training-closed', ['id' => $training->id, 'status' => 'rejected']);

        $this->error('Training rejected', position: 'toast-top toast-center');

        $this->modal = false;
    }
}
