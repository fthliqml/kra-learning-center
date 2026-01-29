<?php

namespace App\Livewire\Pages\Tracker;

use App\Models\Training;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Training Tracker - Admin-only read-only view of training approval status
 *
 * Displays all trainings with their current approval stage, pending approver,
 * and days pending to help admins track stuck approvals.
 */
class TrainingTracker extends Component
{
  use WithPagination;

  public string $search = '';
  public string $filterStage = 'all';
  public string $filterSection = 'all';

  protected $queryString = [
    'search' => ['except' => ''],
    'filterStage' => ['except' => 'all'],
    'filterSection' => ['except' => 'all'],
  ];

  public function mount(): void
  {
    // Verify admin access
    $user = Auth::user();
    if (!$user || !$user->hasRole('admin')) {
      abort(403, 'Unauthorized');
    }
  }

  public function updatingSearch(): void
  {
    $this->resetPage();
  }

  public function updatingFilterStage(): void
  {
    $this->resetPage();
  }

  public function updatingFilterSection(): void
  {
    $this->resetPage();
  }

  public function headers(): array
  {
    return [
      ['key' => 'no', 'label' => 'No', 'class' => '!text-center !py-4 w-12'],
      ['key' => 'training_name', 'label' => 'Training Name', 'class' => '!py-4 min-w-[200px]', 'sortable' => true],
      ['key' => 'request_date', 'label' => 'Request Date', 'class' => '!text-center !py-4 w-[120px]', 'sortable' => true],
      ['key' => 'current_stage', 'label' => 'Current Stage', 'class' => '!text-center !py-4 w-[200px]'],
      ['key' => 'pending_approver', 'label' => 'Pending Approver', 'class' => '!py-4 w-[180px]'],
      ['key' => 'days_pending', 'label' => 'Days Pending', 'class' => '!text-center !py-4 w-[100px]', 'sortable' => true],
    ];
  }

  public function stageOptions(): array
  {
    return [
      ['value' => 'pending_section_head', 'label' => 'Pending Section Head LID'],
      ['value' => 'pending_dept_head', 'label' => 'Pending Dept Head HC'],
      ['value' => 'approved', 'label' => 'Approved'],
      ['value' => 'rejected', 'label' => 'Rejected'],
    ];
  }

  public function sectionOptions(): array
  {
    // Get unique sections from employees who have training requests
    // Join path: trainings -> training_sessions -> training_attendances -> users
    $sections = Training::query()
      ->join('training_sessions', 'trainings.id', '=', 'training_sessions.training_id')
      ->join('training_attendances', 'training_sessions.id', '=', 'training_attendances.session_id')
      ->join('users', 'training_attendances.employee_id', '=', 'users.id')
      ->whereIn('trainings.status', ['done', 'approved', 'rejected'])
      ->whereNotNull('users.section')
      ->where('users.section', '!=', '')
      ->select('users.section')
      ->distinct()
      ->orderBy('users.section')
      ->pluck('section')
      ->filter()
      ->values();

    $options = [['value' => 'all', 'label' => 'All Sections']];
    foreach ($sections as $section) {
      $options[] = ['value' => $section, 'label' => $section];
    }

    return $options;
  }

  protected function getSectionHeadLid(): ?User
  {
    return User::query()
      ->where('position', 'section_head')
      ->whereRaw("LOWER(section) = 'lid'")
      ->first();
  }

  protected function getDeptHeadHc(): ?User
  {
    return User::query()
      ->where('position', 'department_head')
      ->where('department', 'Human Capital, General Service, Security & LID')
      ->first();
  }

  public function getTrainingsProperty()
  {
    $query = Training::query()
      ->whereIn('status', ['done', 'approved', 'rejected']);

    // Filter by stage
    if ($this->filterStage !== 'all') {
      switch ($this->filterStage) {
        case 'pending_section_head':
          $query->where('status', 'done')
            ->whereNull('section_head_signed_at');
          break;
        case 'pending_dept_head':
          $query->where('status', 'done')
            ->whereNotNull('section_head_signed_at')
            ->whereNull('dept_head_signed_at');
          break;
        case 'approved':
          $query->where('status', 'approved');
          break;
        case 'rejected':
          $query->where('status', 'rejected');
          break;
      }
    }

    // Filter by section (participants' section)
    // Relationship path: sessions.attendances.employee
    if ($this->filterSection !== 'all') {
      $query->whereHas('sessions.attendances.employee', function ($q) {
        $q->where('section', $this->filterSection);
      });
    }

    // Search
    if ($this->search) {
      $term = $this->search;
      $query->where('name', 'like', "%{$term}%");
    }

    // Default sort: non-approved (pending) first sorted by request date oldest first
    // Approved/rejected items go at the bottom
    $query->orderByRaw("CASE 
            WHEN status = 'done' THEN 0
            WHEN status = 'approved' THEN 1
            WHEN status = 'rejected' THEN 2
            ELSE 3
        END")
      ->orderBy('created_at', 'asc'); // Request date oldest first (No 1 = oldest pending)

    $sectionHeadLid = $this->getSectionHeadLid();
    $deptHeadHc = $this->getDeptHeadHc();

    return $query->paginate(10)->through(function ($training) use ($sectionHeadLid, $deptHeadHc) {
      $status = strtolower($training->status);
      $hasSectionHeadApproval = !empty($training->section_head_signed_at);
      $hasDeptHeadApproval = !empty($training->dept_head_signed_at);

      // Determine current stage and pending approver
      if ($status === 'done' && !$hasSectionHeadApproval) {
        $currentStage = 'Pending Section Head LID';
        $pendingApprover = $sectionHeadLid?->name ?? 'Section Head LID';
        $pendingSince = $training->created_at;
      } elseif ($status === 'done' && $hasSectionHeadApproval && !$hasDeptHeadApproval) {
        $currentStage = 'Pending Dept Head HC';
        $pendingApprover = $deptHeadHc?->name ?? 'Dept Head HC';
        $pendingSince = $training->section_head_signed_at;
      } elseif ($status === 'approved') {
        $currentStage = 'Approved';
        $pendingApprover = '-';
        $pendingSince = null;
      } elseif ($status === 'rejected') {
        $currentStage = 'Rejected';
        $pendingApprover = '-';
        $pendingSince = null;
      } else {
        $currentStage = ucfirst($status);
        $pendingApprover = '-';
        $pendingSince = null;
      }

      // Calculate days pending (cast to int to get whole days)
      $daysPending = $pendingSince ? (int) Carbon::parse($pendingSince)->diffInDays(now()) : null;

      return (object) [
        'id' => $training->id,
        'training_name' => $training->name,
        'request_date' => $training->created_at,
        'current_stage' => $currentStage,
        'pending_approver' => $pendingApprover,
        'days_pending' => $daysPending,
        'status' => $status,
      ];
    });
  }

  public function render()
  {
    return view('pages.tracker.training-tracker', [
      'headers' => $this->headers(),
      'trainings' => $this->trainings,
      'stageOptions' => $this->stageOptions(),
      'sectionOptions' => $this->sectionOptions(),
    ]);
  }
}
