<?php

namespace App\Livewire\Pages\Training;

use App\Models\Training;
use App\Models\Signature;
use App\Models\User;
use App\Services\TrainingCertificateService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use Illuminate\Support\Collection;

class Approval extends Component
{
  use WithPagination, Toast, WithFileUploads;

  public $modal = false;
  public $selectedId = null;
  public $activeTab = 'information';

  public $search = '';
  public $filter = 'All';
  public $filterType = 'All';

  /** @var array<int, int|string> */
  public array $selectedApprovalIds = [];

  public $signatureFile;

  public array $formData = [
    'training_name' => '',
    'type' => '',
    'created_at' => '',
  ];

  public $groupOptions = [
    ['value' => 'done', 'label' => 'Need Approve'],
    ['value' => 'approved', 'label' => 'Approved'],
    ['value' => 'rejected', 'label' => 'Rejected'],
  ];

  // Reset pagination only when relevant filters change
  public function updatingSearch(): void
  {
    $this->resetPage();
    $this->selectedApprovalIds = [];
  }

  public function updatingFilter(): void
  {
    $this->resetPage();
    $this->selectedApprovalIds = [];
  }

  public function updatingFilterType(): void
  {
    $this->resetPage();
    $this->selectedApprovalIds = [];
  }

  public function headers(): array
  {
    $isDeptHeadOnly = $this->canSignAsDeptHead() && !$this->canSignAsSectionHead();

    $headers = [];
    if ($isDeptHeadOnly) {
      $headers[] = ['key' => 'select', 'label' => '', 'class' => '!text-center w-10'];
    } else {
      $headers[] = ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12 !md:w-[8%]'];
    }
    $headers = array_merge($headers, [
      ['key' => 'training_name', 'label' => 'Training Name', 'class' => '!md:w-[42%]'],
      ['key' => 'type', 'label' => 'Type', 'class' => '!text-center !md:w-[10%]'],
      ['key' => 'date', 'label' => 'Date', 'class' => '!text-center !md:w-[14%]'],
      ['key' => 'status', 'label' => 'Status', 'class' => '!text-center !md:w-[14%]'],
      ['key' => 'action', 'label' => 'Action', 'class' => '!text-center !md:w-[10%]'],
    ]);
    return $headers;
  }

  protected function isEligibleForDeptHeadApproval(object $approvalRow): bool
  {
    $status = strtolower((string) ($approvalRow->status ?? ''));
    $hasLevel1Approval = !empty($approvalRow->section_head_signed_at ?? null);
    $hasLevel2Approval = !empty($approvalRow->dept_head_signed_at ?? null);

    return $this->canSignAsDeptHead() && $status === 'done' && $hasLevel1Approval && !$hasLevel2Approval;
  }

  public function approveSelected(): void
  {
    /** @var User|null $user */
    $user = Auth::user();
    if (!$user) {
      return;
    }

    if (!$this->canSignAsDeptHead() || $this->canSignAsSectionHead()) {
      $this->error('Only LID Department Head can bulk approve.', position: 'toast-top toast-center');
      return;
    }

    if (!$this->ensureApproverHasSignature($user)) {
      return;
    }

    $ids = collect($this->selectedApprovalIds)
      ->map(fn($v) => (int) $v)
      ->filter(fn($v) => $v > 0)
      ->unique()
      ->values();

    if ($ids->isEmpty()) {
      $this->error('Please select at least one training to approve.', position: 'toast-top toast-center');
      return;
    }

    $certificateService = new TrainingCertificateService();
    $approvedCount = 0;
    $skippedCount = 0;
    $certificatesGeneratedTotal = 0;

    foreach ($ids as $trainingId) {
      $training = Training::find($trainingId);

      if (!$training) {
        $skippedCount++;
        continue;
      }

      $status = strtolower((string) $training->status);
      if ($status !== 'done' || !$training->section_head_signed_at || $training->dept_head_signed_at) {
        $skippedCount++;
        continue;
      }

      $training->update([
        'dept_head_signed_by' => $user->id,
        'dept_head_signed_at' => now(),
        'status' => 'approved',
      ]);

      $certificatesGeneratedTotal += (int) $certificateService->generateCertificatesForTraining($training);

      $this->dispatch('training-closed', ['id' => $training->id, 'status' => 'approved']);
      $approvedCount++;
    }

    $this->selectedApprovalIds = [];

    if ($approvedCount === 0) {
      $this->error('No trainings were approved.', position: 'toast-top toast-center');
      return;
    }

    $extra = $skippedCount > 0 ? " Skipped {$skippedCount}." : '';
    if ($certificatesGeneratedTotal > 0) {
      $this->success("Approved {$approvedCount} training(s). {$certificatesGeneratedTotal} certificate(s) generated." . $extra, position: 'toast-top toast-center');
    } else {
      $this->success("Approved {$approvedCount} training(s)." . $extra, position: 'toast-top toast-center');
    }
  }

  public function rejectSelected(): void
  {
    $user = Auth::user();
    if (!$user) {
      return;
    }

    if (!$this->canSignAsDeptHead() || $this->canSignAsSectionHead()) {
      $this->error('Only LID Department Head can bulk reject.', position: 'toast-top toast-center');
      return;
    }

    $ids = collect($this->selectedApprovalIds)
      ->map(fn($v) => (int) $v)
      ->filter(fn($v) => $v > 0)
      ->unique()
      ->values();

    if ($ids->isEmpty()) {
      $this->error('Please select at least one training to reject.', position: 'toast-top toast-center');
      return;
    }

    $rejectedCount = 0;
    $skippedCount = 0;
    $certificateService = new TrainingCertificateService();

    foreach ($ids as $trainingId) {
      $training = Training::find($trainingId);
      if (!$training) {
        $skippedCount++;
        continue;
      }
      $status = strtolower((string) $training->status);
      if ($status !== 'done' || !$training->section_head_signed_at || $training->dept_head_signed_at) {
        $skippedCount++;
        continue;
      }
      $training->update([
        'status' => 'rejected',
        'dept_head_signed_by' => $user->id,
        'dept_head_signed_at' => now(),
      ]);

      // Hapus semua sertifikat yang sudah pernah digenerate untuk training ini
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

      $this->dispatch('training-closed', ['id' => $training->id, 'status' => 'rejected']);
      $rejectedCount++;
    }

    $this->selectedApprovalIds = [];

    if ($rejectedCount === 0) {
      $this->error('No trainings were rejected.', position: 'toast-top toast-center');
      return;
    }
    $extra = $skippedCount > 0 ? " Skipped {$skippedCount}." : '';
    $this->error("Rejected {$rejectedCount} training(s)." . $extra, position: 'toast-top toast-center');
  }

  public function approveAll(): void
  {
    /** @var User|null $user */
    $user = Auth::user();
    if (!$user) {
      return;
    }

    if (!$this->canSignAsDeptHead() || $this->canSignAsSectionHead()) {
      $this->error('Only LID Department Head can bulk approve.', position: 'toast-top toast-center');
      return;
    }

    if (!$this->ensureApproverHasSignature($user)) {
      return;
    }

    // Respect current status filter: only DONE queue is eligible
    $filterStatus = strtolower((string) ($this->filter ?? 'all'));
    if ($filterStatus !== 'all' && $filterStatus !== 'done') {
      $this->error('Approve All only applies to trainings waiting for approval (DONE).', position: 'toast-top toast-center');
      return;
    }

    $query = Training::query()
      ->where('status', 'done')
      ->whereNotNull('section_head_signed_at')
      ->whereNull('dept_head_signed_at');

    if ($this->filterType && strtolower((string) $this->filterType) !== 'all') {
      $query->where('type', $this->filterType);
    }

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

    $certificateService = new TrainingCertificateService();
    $approvedCount = 0;
    $certificatesGeneratedTotal = 0;

    $query->orderBy('id')->chunkById(50, function ($trainings) use ($user, $certificateService, &$approvedCount, &$certificatesGeneratedTotal) {
      foreach ($trainings as $training) {
        if (!$training->section_head_signed_at || $training->dept_head_signed_at) {
          continue;
        }

        $training->update([
          'dept_head_signed_by' => $user->id,
          'dept_head_signed_at' => now(),
          'status' => 'approved',
        ]);

        $certificatesGeneratedTotal += (int) $certificateService->generateCertificatesForTraining($training);
        $this->dispatch('training-closed', ['id' => $training->id, 'status' => 'approved']);
        $approvedCount++;
      }
    });

    $this->selectedApprovalIds = [];

    if ($approvedCount === 0) {
      $this->error('No trainings were approved.', position: 'toast-top toast-center');
      return;
    }

    if ($certificatesGeneratedTotal > 0) {
      $this->success("Approved {$approvedCount} training(s). {$certificatesGeneratedTotal} certificate(s) generated.", position: 'toast-top toast-center');
    } else {
      $this->success("Approved {$approvedCount} training(s).", position: 'toast-top toast-center');
    }
  }

  public function typeOptions(): array
  {
    $isSectionHead = $this->canSignAsSectionHead();
    $isDeptHead = $this->canSignAsDeptHead();

    $query = Training::query()
      ->select('type')
      ->whereIn('status', ['done', 'approved', 'rejected'])
      ->whereNotNull('type')
      ->where('type', '!=', '')
      ->distinct();

    if ($isDeptHead && !$isSectionHead) {
      $query->where(function ($q) {
        $q->whereIn('status', ['approved', 'rejected'])
          ->orWhere(function ($qq) {
            $qq->where('status', 'done')
              ->whereNotNull('section_head_signed_at')
              ->whereNull('dept_head_signed_at');
          });
      });
    }

    /** @var Collection<int, string> $types */
    $types = $query
      ->orderBy('type')
      ->pluck('type')
      ->filter(fn($t) => is_string($t) && trim($t) !== '')
      ->values();

    return $types
      ->map(fn($t) => ['value' => $t, 'label' => strtoupper($t)])
      ->all();
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
    $isSectionHead = $this->canSignAsSectionHead();
    $isDeptHead = $this->canSignAsDeptHead();

    $query = Training::query()
      ->select('trainings.*')
      ->whereIn('status', ['done', 'approved', 'rejected'])
      ->distinct();

    // Role-based visibility:
    // - Section Head LID: can see everything (done/approved/rejected)
    // - Dept Head LID: can see history (approved/rejected) + queue already approved by Section Head
    if ($isDeptHead && !$isSectionHead) {
      $query->where(function ($q) {
        $q->whereIn('status', ['approved', 'rejected'])
          ->orWhere(function ($qq) {
            $qq->where('status', 'done')
              ->whereNotNull('section_head_signed_at')
              ->whereNull('dept_head_signed_at');
          });
      });
    }

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

      if ($filterStatus === 'done') {
        // Re-apply the role-relevant "done" queue condition
        if ($isDeptHead && !$isSectionHead) {
          $query->where('status', 'done')
            ->whereNotNull('section_head_signed_at')
            ->whereNull('dept_head_signed_at');
        } else {
          $query->where('status', 'done');
        }
      } else {
        $query->where('status', $filterStatus);
      }
    }

    // Filter by training type
    if ($this->filterType && strtolower($this->filterType) !== 'all') {
      $query->where('type', $this->filterType);
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
          'section_head_signed_at' => $training->section_head_signed_at,
          'dept_head_signed_at' => $training->dept_head_signed_at,
        ];
      });
  }

  public function render()
  {
    return view('pages.training.training-approval', [
      'headers' => $this->headers(),
      'approvals' => $this->approvals(),
      'typeOptions' => $this->typeOptions(),
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
      'section_head_signed_at' => $training->section_head_signed_at
        ? $training->section_head_signed_at->format('d F Y H:i')
        : null,
      'dept_head_signed_at' => $training->dept_head_signed_at
        ? $training->dept_head_signed_at->format('d F Y H:i')
        : null,
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
    return strtolower(trim($user->position ?? '')) === 'section_head'
      && strtolower(trim($user->section ?? '')) === 'lid';
  }

  /** Determine if current user can sign as LID Section Head (used for upload permission) */
  protected function canSignAsSectionHead(): bool
  {
    $user = Auth::user();
    if (!$user) {
      return false;
    }

    return strtolower(trim($user->position ?? '')) === 'section_head'
      && strtolower(trim($user->section ?? '')) === 'lid';
  }

  /** Determine if current user can sign as LID Department Head (used for upload permission) */
  protected function canSignAsDeptHead(): bool
  {
    $user = Auth::user();
    if (!$user) {
      return false;
    }

    return strtolower(trim($user->position ?? '')) === 'department_head'
      && trim($user->department ?? '') === 'Human Capital, General Service, Security & LID';
  }

  /**
   * Approver must upload a signature before approving.
   */
  protected function ensureApproverHasSignature(User $user): bool
  {
    $user->loadMissing('signature');
    $path = $user->signature?->path;

    if (empty($path)) {
      $this->error('Please upload your digital signature before approving.', position: 'toast-top toast-center');
      return false;
    }

    return true;
  }

  public function uploadSignature(): void
  {
    $user = Auth::user();

    if (!$user) {
      return;
    }

    if (!$this->canSignAsSectionHead() && !$this->canSignAsDeptHead()) {
      $this->error('Only LID Section Head or LID Department Head can upload a signature.', position: 'toast-top toast-center');
      return;
    }

    $this->validate(
      [
        'signatureFile' => 'required|image|max:2048', // max 2MB
      ],
      [
        'signatureFile.required' => 'Signature is required.',
        'signatureFile.image' => 'Signature must be an image file.',
        'signatureFile.max' => 'Signature must be 2MB or less.',
      ],
      [
        'signatureFile' => 'signature',
      ]
    );

    $path = $this->signatureFile->store('signatures', 'public');

    Signature::updateOrCreate(
      ['user_id' => $user->id],
      ['path' => $path]
    );

    $this->signatureFile = null;

    $this->success('Signature uploaded successfully.', position: 'toast-top toast-center');
  }

  #[\Livewire\Attributes\On('deleteSignatureConfirmed')]
  public function deleteSignatureConfirmed(): void
  {
    $user = Auth::user();

    if (!$user) {
      $this->dispatch('confirm-done');
      return;
    }

    if (!$this->canSignAsSectionHead() && !$this->canSignAsDeptHead()) {
      $this->error('Only LID Section Head or LID Department Head can delete a signature.', position: 'toast-top toast-center');
      $this->dispatch('confirm-done');
      return;
    }

    $signature = Signature::where('user_id', $user->id)->first();

    if (!$signature) {
      $this->error('No signature found to delete.', position: 'toast-top toast-center');
      $this->dispatch('confirm-done');
      return;
    }

    // Delete file from storage
    if ($signature->path && \Storage::disk('public')->exists($signature->path)) {
      \Storage::disk('public')->delete($signature->path);
    }

    // Delete record
    $signature->delete();

    $this->dispatch('confirm-done');
    $this->success('Signature deleted successfully.', position: 'toast-top toast-center');
  }

  /** Approve selected request */
  public function approve(?int $id = null): void
  {
    /** @var User|null $user */
    $user = Auth::user();

    if (!$user) {
      return;
    }

    $trainingId = $id ?? $this->selectedId;

    if (!$trainingId) {
      return;
    }

    $training = Training::find($trainingId);

    if (!$training) {
      $this->error('Training not found.', position: 'toast-top toast-center');
      return;
    }

    $status = strtolower($training->status);

    if ($status !== 'done') {
      $this->error('Only trainings with status DONE can be approved.', position: 'toast-top toast-center');
      return;
    }

    // Level 1 approval: Section Head LID
    if ($this->canSignAsSectionHead() && !$training->section_head_signed_at && !$training->dept_head_signed_at) {
      if (!$this->ensureApproverHasSignature($user)) {
        return;
      }

      $training->update([
        'section_head_signed_by' => $user->id,
        'section_head_signed_at' => now(),
      ]);

      $training->refresh();

      $this->formData['status'] = $training->status; // still 'done'
      $this->formData['section_head_signed_at'] = $training->section_head_signed_at
        ? $training->section_head_signed_at->format('d F Y H:i')
        : null;

      $this->success('Level 1 approval completed. Waiting for Department Head approval.', position: 'toast-top toast-center');

      $this->modal = false;
      return;
    }

    // Level 2 approval: Department Head LID (after Section Head approved)
    if ($this->canSignAsDeptHead() && $training->section_head_signed_at && !$training->dept_head_signed_at) {
      if (!$this->ensureApproverHasSignature($user)) {
        return;
      }

      $training->update([
        'dept_head_signed_by' => $user->id,
        'dept_head_signed_at' => now(),
        'status' => 'approved',
      ]);

      // Generate certificates for passed participants at final approval
      $certificateService = new TrainingCertificateService();
      $certificatesGenerated = $certificateService->generateCertificatesForTraining($training);

      $training->refresh();

      $this->formData['status'] = 'approved';
      $this->formData['dept_head_signed_at'] = $training->dept_head_signed_at
        ? $training->dept_head_signed_at->format('d F Y H:i')
        : null;

      // Notify other components that training status changed
      $this->dispatch('training-closed', ['id' => $training->id, 'status' => 'approved']);

      if ($certificatesGenerated > 0) {
        $this->success("Training fully approved. {$certificatesGenerated} certificate(s) generated.", position: 'toast-top toast-center');
      } else {
        $this->success('Training fully approved.', position: 'toast-top toast-center');
      }

      $this->modal = false;
      return;
    }

    $this->error('You are not allowed to approve at this stage.', position: 'toast-top toast-center');
  }

  /** Reject selected request */
  public function reject(?int $id = null): void
  {
    /** @var User|null $user */
    $user = Auth::user();

    if (!$user) {
      return;
    }

    $trainingId = $id ?? $this->selectedId;

    if (!$trainingId) {
      return;
    }

    $training = Training::find($trainingId);

    if (!$training) {
      $this->error('Training not found.', position: 'toast-top toast-center');
      return;
    }

    $status = strtolower($training->status);

    if ($status !== 'done') {
      $this->error('Only trainings with status DONE can be rejected.', position: 'toast-top toast-center');
      return;
    }

    // Level 1 reject: Section Head LID before any approval
    if ($this->canSignAsSectionHead() && !$training->section_head_signed_at && !$training->dept_head_signed_at) {
      $training->update([
        'status' => 'rejected',
        'section_head_signed_by' => $user->id,
        'section_head_signed_at' => now(),
      ]);
    }
    // Level 2 reject: Department Head LID after Section Head approval
    elseif ($this->canSignAsDeptHead() && $training->section_head_signed_at && !$training->dept_head_signed_at) {
      $training->update([
        'status' => 'rejected',
        'dept_head_signed_by' => $user->id,
        'dept_head_signed_at' => now(),
      ]);
    } else {
      $this->error('You are not allowed to reject at this stage.', position: 'toast-top toast-center');
      return;
    }

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

    $training->refresh();

    $this->formData['status'] = 'rejected';
    $this->formData['dept_head_signed_at'] = $training->dept_head_signed_at
      ? $training->dept_head_signed_at->format('d F Y H:i')
      : null;

    // Notify other components that training status changed
    $this->dispatch('training-closed', ['id' => $training->id, 'status' => 'rejected']);

    $this->error('Training rejected', position: 'toast-top toast-center');

    $this->modal = false;
  }
}
