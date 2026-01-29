# Approval Tracker Implementation Plan

> **REQUIRED SUB-WORKFLOW:** Use `/execute-plan` workflow to implement this plan task-by-task.

**Goal:** Create Admin-only read-only tracker pages for Training and IDP approvals, allowing admins to see which approvals are pending and who needs to approve.

**Architecture:** 
- Two new Livewire components under `app/Livewire/Pages/Tracker/`
- New sidebar menu group "Trackers" visible only to admin role
- Read-only tables with filters (stage, department/section) and search capability
- Following existing patterns from `Training/Approval.php` and `Development/DevelopmentApproval.php`

**Tech Stack:** Laravel 11, Livewire 3, MaryUI, Blade Templates

**Design Document:** `docs/plans/2026-01-29-approval-tracker-design.md`

---

## Task 1: Add Sidebar Menu for Trackers

**Files:**
- Modify: `config/menu.php`

#### Step 1: Add Trackers menu group after Reports section

Add new menu group at line ~327 (before the closing bracket of 'sidebar' array):

```php
// Trackers [Admin Only]
[
    'id' => 'trackers',
    'label' => 'Trackers',
    'icon' => 'chart-bar-square',
    'href' => '#',
    'roles' => ['admin'],
    'submenu' => [
        [
            'label' => 'Training Tracker',
            'href' => '/trackers/training',
            'roles' => ['admin'],
        ],
        [
            'label' => 'IDP Tracker',
            'href' => '/trackers/idp',
            'roles' => ['admin'],
        ],
    ],
],
```

#### Step 2: Verify menu appears in sidebar

Run: `php artisan serve`
Login as admin user and check sidebar has "Trackers" menu with submenu items.

#### Step 3: Commit

```bash
git add config/menu.php
git commit -m "feat(trackers): add sidebar menu for approval trackers"
```

---

## Task 2: Add Routes for Tracker Pages

**Files:**
- Modify: `routes/web.php`

#### Step 1: Add use statements at top of file

```php
use App\Livewire\Pages\Tracker\TrainingTracker;
use App\Livewire\Pages\Tracker\IdpTracker;
```

#### Step 2: Add routes inside auth middleware group

Add after the Development routes (around line 137):

```php
// Trackers (Admin Only)
Route::get('/trackers/training', TrainingTracker::class)->name('trackers.training');
Route::get('/trackers/idp', IdpTracker::class)->name('trackers.idp');
```

#### Step 3: Commit

```bash
git add routes/web.php
git commit -m "feat(trackers): add routes for approval tracker pages"
```

---

## Task 3: Create Training Tracker Livewire Component

**Files:**
- Create: `app/Livewire/Pages/Tracker/TrainingTracker.php`

#### Step 1: Create the directory

```bash
mkdir -p app/Livewire/Pages/Tracker
```

#### Step 2: Create TrainingTracker.php

```php
<?php

namespace App\Livewire\Pages\Tracker;

use App\Models\Training;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12'],
            ['key' => 'training_name', 'label' => 'Training Name', 'class' => 'min-w-[200px]', 'sortable' => true],
            ['key' => 'request_date', 'label' => 'Request Date', 'class' => '!text-center w-[120px]', 'sortable' => true],
            ['key' => 'current_stage', 'label' => 'Current Stage', 'class' => '!text-center w-[200px]'],
            ['key' => 'pending_approver', 'label' => 'Pending Approver', 'class' => 'w-[180px]'],
            ['key' => 'days_pending', 'label' => 'Days Pending', 'class' => '!text-center w-[100px]', 'sortable' => true],
        ];
    }

    public function stageOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All Stages'],
            ['value' => 'pending_section_head', 'label' => 'Pending Section Head LID'],
            ['value' => 'pending_dept_head', 'label' => 'Pending Dept Head HC'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'rejected', 'label' => 'Rejected'],
        ];
    }

    public function sectionOptions(): array
    {
        // Get unique sections from employees who have training requests
        $sections = Training::query()
            ->join('training_attendances', 'trainings.id', '=', 'training_attendances.training_id')
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
        if ($this->filterSection !== 'all') {
            $query->whereHas('attendances.employee', function ($q) {
                $q->where('section', $this->filterSection);
            });
        }

        // Search
        if ($this->search) {
            $term = $this->search;
            $query->where('name', 'like', "%{$term}%");
        }

        // Default sort: pending trainings first, then by days pending (longest first)
        $query->orderByRaw("CASE 
            WHEN status = 'done' AND section_head_signed_at IS NULL THEN 0
            WHEN status = 'done' AND section_head_signed_at IS NOT NULL AND dept_head_signed_at IS NULL THEN 1
            ELSE 2
        END")
            ->orderBy('created_at', 'asc'); // Oldest first (longest pending)

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

            // Calculate days pending
            $daysPending = $pendingSince ? Carbon::parse($pendingSince)->diffInDays(now()) : null;

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
```

#### Step 3: Commit

```bash
git add app/Livewire/Pages/Tracker/TrainingTracker.php
git commit -m "feat(trackers): create TrainingTracker Livewire component"
```

---

## Task 4: Create Training Tracker Blade View

**Files:**
- Create: `resources/views/pages/tracker/training-tracker.blade.php`

#### Step 1: Create the directory

```bash
mkdir -p resources/views/pages/tracker
```

#### Step 2: Create training-tracker.blade.php

```blade
<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Training Tracker
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <div class="flex items-center justify-center gap-2">
                {{-- Filter by Stage --}}
                <x-select wire:model.live="filterStage" :options="$stageOptions" option-value="value" option-label="label"
                    placeholder="All Stages"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />

                {{-- Filter by Section --}}
                <x-select wire:model.live="filterSection" :options="$sectionOptions" option-value="value" option-label="label"
                    placeholder="All Sections"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-building-office" />
            </div>

            {{-- Search --}}
            <x-search-input placeholder="Search training name..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
        <x-icon name="o-information-circle" class="size-5 text-blue-500 mt-0.5" />
        <div class="text-sm text-blue-700">
            <div class="font-semibold">Read-Only Tracker</div>
            <div class="mt-1 text-blue-600">
                This page shows the approval status of all trainings. You can track which approvals are pending and who needs to approve.
            </div>
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="6" :rows="10" targets="search,filterStage,filterSection" />

    {{-- No Data State --}}
    @if ($trainings->isEmpty())
        <div wire:loading.remove wire:target="search,filterStage,filterSection"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no training records matching your filters.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove wire:target="search,filterStage,filterSection"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$trainings" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>

                {{-- No --}}
                @scope('cell_no', $training, $trainings)
                    {{ ($trainings->currentPage() - 1) * $trainings->perPage() + $loop->iteration }}
                @endscope

                {{-- Training Name --}}
                @scope('cell_training_name', $training)
                    <div class="truncate max-w-[50ch]">{{ $training->training_name ?? '-' }}</div>
                @endscope

                {{-- Request Date --}}
                @scope('cell_request_date', $training)
                    <div class="text-sm text-center">
                        {{ $training->request_date ? \Carbon\Carbon::parse($training->request_date)->format('d M Y') : '-' }}
                    </div>
                @endscope

                {{-- Current Stage --}}
                @scope('cell_current_stage', $training)
                    @php
                        $stage = $training->current_stage;
                        $classes = match($stage) {
                            'Pending Section Head LID' => 'bg-amber-100 text-amber-700',
                            'Pending Dept Head HC' => 'bg-blue-100 text-blue-700',
                            'Approved' => 'bg-emerald-100 text-emerald-700',
                            'Rejected' => 'bg-rose-100 text-rose-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <div class="flex justify-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }} whitespace-nowrap">
                            {{ $stage }}
                        </span>
                    </div>
                @endscope

                {{-- Pending Approver --}}
                @scope('cell_pending_approver', $training)
                    <div class="text-sm">{{ $training->pending_approver }}</div>
                @endscope

                {{-- Days Pending --}}
                @scope('cell_days_pending', $training)
                    @if ($training->days_pending !== null)
                        @php
                            $days = $training->days_pending;
                            $classes = match(true) {
                                $days >= 7 => 'text-rose-600 font-bold',
                                $days >= 3 => 'text-amber-600 font-semibold',
                                default => 'text-gray-600',
                            };
                        @endphp
                        <div class="text-center {{ $classes }}">
                            {{ $days }} {{ $days === 1 ? 'day' : 'days' }}
                        </div>
                    @else
                        <div class="text-center text-gray-400">-</div>
                    @endif
                @endscope
            </x-table>
        </div>
    @endif
</div>
```

#### Step 3: Commit

```bash
git add resources/views/pages/tracker/training-tracker.blade.php
git commit -m "feat(trackers): create Training Tracker blade view"
```

---

## Task 5: Create IDP Tracker Livewire Component

**Files:**
- Create: `app/Livewire/Pages/Tracker/IdpTracker.php`

#### Step 1: Create IdpTracker.php

```php
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
                ? Carbon::parse($planStatus['pending_since'])->diffInDays(now()) 
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
```

#### Step 2: Commit

```bash
git add app/Livewire/Pages/Tracker/IdpTracker.php
git commit -m "feat(trackers): create IdpTracker Livewire component"
```

---

## Task 6: Create IDP Tracker Blade View

**Files:**
- Create: `resources/views/pages/tracker/idp-tracker.blade.php`

#### Step 1: Create idp-tracker.blade.php

```blade
<div>
    {{-- Header --}}
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            IDP Tracker
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <div class="flex items-center justify-center gap-2">
                {{-- Filter by Stage --}}
                <x-select wire:model.live="filterStage" :options="$stageOptions" option-value="value" option-label="label"
                    placeholder="All Stages"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-funnel" />

                {{-- Filter by Section --}}
                <x-select wire:model.live="filterSection" :options="$sectionOptions" option-value="value" option-label="label"
                    placeholder="All Sections"
                    class="!w-fit !h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_select+div_svg]:!hidden"
                    icon-right="o-building-office" />
            </div>

            {{-- Search --}}
            <x-search-input placeholder="Search employee name or NRP..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
        <x-icon name="o-information-circle" class="size-5 text-blue-500 mt-0.5" />
        <div class="text-sm text-blue-700">
            <div class="font-semibold">Read-Only Tracker</div>
            <div class="mt-1 text-blue-600">
                This page shows the approval status of all Individual Development Plans (IDP). You can track which approvals are pending and who needs to approve.
            </div>
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <x-skeletons.table :columns="8" :rows="10" targets="search,filterStage,filterSection" />

    {{-- No Data State --}}
    @if ($employees->isEmpty())
        <div wire:loading.remove wire:target="search,filterStage,filterSection"
            class="rounded-lg border-2 border-dashed border-gray-300 p-2 overflow-x-auto">
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">No Data Available</h3>
                <p class="text-sm text-gray-500 text-center">
                    There are no IDP records matching your filters.
                </p>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div wire:loading.remove wire:target="search,filterStage,filterSection"
            class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
            <x-table :headers="$headers" :rows="$employees" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
                with-pagination>

                {{-- No --}}
                @scope('cell_no', $employee, $employees)
                    {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                @endscope

                {{-- Employee Name --}}
                @scope('cell_employee_name', $employee)
                    <div class="truncate max-w-[30ch]">{{ $employee->employee_name ?? '-' }}</div>
                @endscope

                {{-- NRP --}}
                @scope('cell_employee_nrp', $employee)
                    <div class="font-mono text-sm">{{ $employee->employee_nrp ?? '-' }}</div>
                @endscope

                {{-- Section --}}
                @scope('cell_section', $employee)
                    <div class="text-sm">{{ $employee->section ?? '-' }}</div>
                @endscope

                {{-- Plan Count --}}
                @scope('cell_plan_count', $employee)
                    <div class="text-center font-semibold">{{ $employee->plan_count }}</div>
                @endscope

                {{-- Current Stage --}}
                @scope('cell_current_stage', $employee)
                    @php
                        $stage = $employee->current_stage;
                        $classes = match($stage) {
                            'Pending SPV/Section Head' => 'bg-amber-100 text-amber-700',
                            'Pending Section Head LID' => 'bg-blue-100 text-blue-700',
                            'Approved' => 'bg-emerald-100 text-emerald-700',
                            'Rejected' => 'bg-rose-100 text-rose-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <div class="flex justify-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $classes }} whitespace-nowrap">
                            {{ $stage }}
                        </span>
                    </div>
                @endscope

                {{-- Pending Approver --}}
                @scope('cell_pending_approver', $employee)
                    <div class="text-sm">{{ $employee->pending_approver }}</div>
                @endscope

                {{-- Days Pending --}}
                @scope('cell_days_pending', $employee)
                    @if ($employee->days_pending !== null)
                        @php
                            $days = $employee->days_pending;
                            $classes = match(true) {
                                $days >= 7 => 'text-rose-600 font-bold',
                                $days >= 3 => 'text-amber-600 font-semibold',
                                default => 'text-gray-600',
                            };
                        @endphp
                        <div class="text-center {{ $classes }}">
                            {{ $days }} {{ $days === 1 ? 'day' : 'days' }}
                        </div>
                    @else
                        <div class="text-center text-gray-400">-</div>
                    @endif
                @endscope
            </x-table>
        </div>
    @endif
</div>
```

#### Step 2: Commit

```bash
git add resources/views/pages/tracker/idp-tracker.blade.php
git commit -m "feat(trackers): create IDP Tracker blade view"
```

---

## Task 7: Verify Implementation

#### Step 1: Clear caches and test

```bash
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

#### Step 2: Start development server

```bash
php artisan serve
```

#### Step 3: Test in browser

1. Login as admin user
2. Check sidebar for "Trackers" menu
3. Navigate to `/trackers/training` - verify table displays
4. Navigate to `/trackers/idp` - verify table displays
5. Test filters (stage, section)
6. Test search functionality
7. Verify "Days Pending" shows correct values with color coding

#### Step 4: Final commit

```bash
git add .
git commit -m "feat(trackers): complete approval tracker implementation"
```

---

## Summary

| Task | Description | Files |
|------|-------------|-------|
| 1 | Add sidebar menu | `config/menu.php` |
| 2 | Add routes | `routes/web.php` |
| 3 | Create TrainingTracker component | `app/Livewire/Pages/Tracker/TrainingTracker.php` |
| 4 | Create Training Tracker view | `resources/views/pages/tracker/training-tracker.blade.php` |
| 5 | Create IdpTracker component | `app/Livewire/Pages/Tracker/IdpTracker.php` |
| 6 | Create IDP Tracker view | `resources/views/pages/tracker/idp-tracker.blade.php` |
| 7 | Verify implementation | Manual testing |

**Total estimated time:** 2-3 hours
