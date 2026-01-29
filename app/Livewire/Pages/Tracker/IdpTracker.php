<?php

namespace App\Livewire\Pages\Tracker;

use App\Models\User;
use App\Models\TrainingPlan;
use App\Models\ProjectPlan;
use App\Models\SelfLearningPlan;
use App\Models\MentoringPlan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * IDP Tracker - Admin-only read-only view of IDP approval status
 *
 * Displays all employees with IDP plans and their current approval stage,
 * pending approver, and days pending to help admins track stuck approvals.
 */
class IdpTracker extends Component
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
      ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12'],
      ['key' => 'employee_name', 'label' => 'Employee Name', 'class' => 'min-w-[180px]', 'sortable' => true],
      ['key' => 'employee_nrp', 'label' => 'NRP', 'class' => 'w-[100px]'],
      ['key' => 'section', 'label' => 'Section', 'class' => 'w-[120px]'],
      ['key' => 'plan_count', 'label' => 'Plans', 'class' => '!text-center w-[80px]'],
      ['key' => 'current_stage', 'label' => 'Current Stage', 'class' => '!text-center w-[200px]'],
      ['key' => 'pending_approver', 'label' => 'Pending Approver', 'class' => 'w-[180px]'],
      ['key' => 'days_pending', 'label' => 'Days Pending', 'class' => '!text-center w-[100px]', 'sortable' => true],
    ];
  }

  public function stageOptions(): array
  {
    return [
      ['value' => 'all', 'label' => 'All Stages'],
      ['value' => 'pending_spv', 'label' => 'Pending SPV/Section Head'],
      ['value' => 'pending_lid', 'label' => 'Pending Section Head LID'],
      ['value' => 'approved', 'label' => 'Approved'],
      ['value' => 'rejected', 'label' => 'Rejected'],
    ];
  }

  public function sectionOptions(): array
  {
    // Get unique sections from users who have IDP plans
    $sections = User::query()
      ->where(function ($q) {
        $q->whereHas('trainingPlans')
          ->orWhereHas('projectPlans')
          ->orWhereHas('selfLearningPlans')
          ->orWhereHas('mentoringPlans');
      })
      ->whereNotNull('section')
      ->where('section', '!=', '')
      ->select('section')
      ->distinct()
      ->orderBy('section')
      ->pluck('section')
      ->filter()
      ->values();

    $options = [['value' => 'all', 'label' => 'All Sections']];
    foreach ($sections as $section) {
      $options[] = ['value' => $section, 'label' => $section];
    }

    return $options;
  }

  protected function getPlanStatusForUser(User $user): array
  {
    // Get all plans for user and determine their overall IDP status
    $plans = collect();

    // Collect all plan types
    $trainingPlans = $user->trainingPlans()->get();
    $projectPlans = $user->projectPlans()->get();
    $selfLearningPlans = $user->selfLearningPlans()->get();
    $mentoringPlans = $user->mentoringPlans()->get();

    $allPlans = $trainingPlans->merge($projectPlans)->merge($selfLearningPlans)->merge($mentoringPlans);

    if ($allPlans->isEmpty()) {
      return [
        'stage' => 'No Plans',
        'pending_approver' => '-',
        'pending_since' => null,
        'plan_count' => 0,
      ];
    }

    $planCount = $allPlans->count();

    // Group by status to find the "worst" status
    $statuses = $allPlans->pluck('status')->unique();

    // Priority: pending_spv > pending_lid > approved > rejected
    if ($statuses->contains('pending_spv') || $statuses->contains('pending_supervisor')) {
      $firstPendingPlan = $allPlans->first(fn($p) => in_array($p->status, ['pending_spv', 'pending_supervisor']));
      $supervisor = $this->getSupervisorForUser($user);
      return [
        'stage' => 'Pending SPV/Section Head',
        'pending_approver' => $supervisor?->name ?? 'Supervisor',
        'pending_since' => $firstPendingPlan?->created_at,
        'plan_count' => $planCount,
      ];
    }

    if ($statuses->contains('pending_lid') || $statuses->contains('pending_section_head_lid')) {
      $firstPendingPlan = $allPlans->first(fn($p) => in_array($p->status, ['pending_lid', 'pending_section_head_lid']));
      $lidHead = $this->getSectionHeadLid();
      return [
        'stage' => 'Pending Section Head LID',
        'pending_approver' => $lidHead?->name ?? 'Section Head LID',
        'pending_since' => $firstPendingPlan?->first_level_approved_at ?? $firstPendingPlan?->created_at,
        'plan_count' => $planCount,
      ];
    }

    if ($statuses->every(fn($s) => $s === 'approved')) {
      return [
        'stage' => 'Approved',
        'pending_approver' => '-',
        'pending_since' => null,
        'plan_count' => $planCount,
      ];
    }

    if ($statuses->contains('rejected')) {
      return [
        'stage' => 'Rejected',
        'pending_approver' => '-',
        'pending_since' => null,
        'plan_count' => $planCount,
      ];
    }

    return [
      'stage' => 'Unknown',
      'pending_approver' => '-',
      'pending_since' => null,
      'plan_count' => $planCount,
    ];
  }

  protected function getSupervisorForUser(User $user): ?User
  {
    // Find supervisor in same section
    return User::query()
      ->where('section', $user->section)
      ->where('position', 'supervisor')
      ->first();
  }

  protected function getSectionHeadLid(): ?User
  {
    return User::query()
      ->where('position', 'section_head')
      ->whereRaw("LOWER(section) = 'lid'")
      ->first();
  }

  public function getEmployeesProperty()
  {
    // Get users who have any IDP plans
    $query = User::query()
      ->where(function ($q) {
        $q->whereHas('trainingPlans')
          ->orWhereHas('projectPlans')
          ->orWhereHas('selfLearningPlans')
          ->orWhereHas('mentoringPlans');
      });

    // Filter by section
    if ($this->filterSection !== 'all') {
      $query->where('section', $this->filterSection);
    }

    // Search by name
    if ($this->search) {
      $term = $this->search;
      $query->where(function ($q) use ($term) {
        $q->where('name', 'like', "%{$term}%")
          ->orWhere('nrp', 'like', "%{$term}%");
      });
    }

    $users = $query->orderBy('name')->get();

    // Process each user to get their IDP status
    $processed = $users->map(function ($user) {
      $planStatus = $this->getPlanStatusForUser($user);

      $daysPending = $planStatus['pending_since']
        ? (int) Carbon::parse($planStatus['pending_since'])->diffInDays(now())
        : null;

      return (object) [
        'id' => $user->id,
        'employee_name' => $user->name,
        'employee_nrp' => $user->nrp,
        'section' => $user->section,
        'plan_count' => $planStatus['plan_count'],
        'current_stage' => $planStatus['stage'],
        'pending_approver' => $planStatus['pending_approver'],
        'days_pending' => $daysPending,
      ];
    });

    // Filter by stage
    if ($this->filterStage !== 'all') {
      $stageMap = [
        'pending_spv' => 'Pending SPV/Section Head',
        'pending_lid' => 'Pending Section Head LID',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
      ];
      $targetStage = $stageMap[$this->filterStage] ?? null;
      if ($targetStage) {
        $processed = $processed->filter(fn($p) => $p->current_stage === $targetStage);
      }
    }

    // Sort by days pending (longest first for pending items)
    $processed = $processed->sortByDesc(function ($item) {
      // Pending items first, sorted by days pending
      if (str_starts_with($item->current_stage, 'Pending')) {
        return 10000 + ($item->days_pending ?? 0);
      }
      return 0;
    })->values();

    // Manual pagination
    $page = $this->getPage();
    $perPage = 10;
    $total = $processed->count();
    $items = $processed->slice(($page - 1) * $perPage, $perPage)->values();

    return new \Illuminate\Pagination\LengthAwarePaginator(
      $items,
      $total,
      $perPage,
      $page,
      ['path' => request()->url(), 'query' => request()->query()]
    );
  }

  public function render()
  {
    return view('pages.tracker.idp-tracker', [
      'headers' => $this->headers(),
      'employees' => $this->employees,
      'stageOptions' => $this->stageOptions(),
      'sectionOptions' => $this->sectionOptions(),
    ]);
  }
}
