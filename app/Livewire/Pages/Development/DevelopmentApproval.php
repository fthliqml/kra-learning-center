<?php

namespace App\Livewire\Pages\Development;

use App\Models\MentoringPlan;
use App\Models\ProjectPlan;
use App\Models\SelfLearningPlan;
use App\Models\TrainingPlan;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

class DevelopmentApproval extends Component
{
    use WithPagination, Toast;

    public $search = '';
    public $filter = 'all';
    public $selectedYear;

    // Modal
    public $detailModal = false;
    public $rejectModal = false;
    public $selectedUserId = null;
    public $selectedUserData = null;
    public $rejectionReason = '';
    public $rejectingPlanType = null;
    public $rejectingPlanId = null;
    public $rejectAll = false;

    // User's plans for modal
    public $userTrainingPlans = [];
    public $userSelfLearningPlans = [];
    public $userMentoringPlans = [];
    public $userProjectPlans = [];

    public $filterOptions = [
        ['value' => 'all', 'label' => 'All'],
        ['value' => 'pending_spv', 'label' => 'Pending SPV'],
        ['value' => 'pending_leader', 'label' => 'Pending Leader'],
        ['value' => 'approved', 'label' => 'Approved'],
        ['value' => 'rejected', 'label' => 'Rejected'],
    ];

    public function mount()
    {
        $this->selectedYear = (string) now()->year;
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    /**
     * Check if current user is Supervisor in area (first-level approver type 1)
     */
    private function isSupervisorArea(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasPosition('supervisor');
    }

    /**
     * Check if current user is Dept Head in area (non-LID, first-level approver type 2)
     */
    private function isDeptHeadArea(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $section = strtolower(trim($user->section ?? ''));

        return $user->hasPosition('department_head') && $section !== 'lid';
    }

    /**
     * Check if current user is first-level approver in area (SPV or Dept Head area)
     */
    public function isSpv(): bool
    {
        return $this->isSupervisorArea() || $this->isDeptHeadArea();
    }

    /**
     * Check if current user is Leader LID
     */
    public function isLeaderLid(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user
            && $user->hasAnyPosition(['section_head', 'department_head', 'division_head', 'director'])
            && strtolower(trim($user->section ?? '')) === 'lid';
    }

    /**
     * Get current approval level based on user role
     */
    public function getApprovalLevel(): string
    {
        if ($this->isLeaderLid()) {
            return 'leader';
        } elseif ($this->isSpv()) {
            return 'spv';
        }
        return '';
    }

    public function headers(): array
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center w-12'],
            ['key' => 'name', 'label' => 'Employee Name', 'class' => ''],
            ['key' => 'nrp', 'label' => 'NRP', 'class' => '!text-center'],
            ['key' => 'section', 'label' => 'Section', 'class' => ''],
            ['key' => 'total_plans', 'label' => 'Total Plans', 'class' => '!text-center'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
            ['key' => 'action', 'label' => 'Action', 'class' => '!text-center'],
        ];
    }

    public function getApprovalDataProperty()
    {
        $year = (int) $this->selectedYear;
        $user = Auth::user();
        $approvalLevel = $this->getApprovalLevel();

        // Get users who have development plans for the selected year
        $query = User::query()
            ->where(function ($q) use ($year) {
                $q->whereHas('trainingPlans', fn($q) => $q->where('year', $year)->where('status', '!=', 'draft'))
                    ->orWhereHas('selfLearningPlans', fn($q) => $q->where('year', $year)->where('status', '!=', 'draft'))
                    ->orWhereHas('mentoringPlans', fn($q) => $q->where('year', $year)->where('status', '!=', 'draft'))
                    ->orWhereHas('projectPlans', fn($q) => $q->where('year', $year)->where('status', '!=', 'draft'));
            })
            ->withCount([
                'trainingPlans as training_count' => fn($q) => $q->where('year', $year)->where('status', '!=', 'draft'),
                'selfLearningPlans as self_learning_count' => fn($q) => $q->where('year', $year)->where('status', '!=', 'draft'),
                'mentoringPlans as mentoring_count' => fn($q) => $q->where('year', $year)->where('status', '!=', 'draft'),
                'projectPlans as project_count' => fn($q) => $q->where('year', $year)->where('status', '!=', 'draft'),
            ]);

        // SPV can only see employees in their section
        if ($approvalLevel === 'spv' && $user) {
            $query->where('section', $user->section)
                ->where('id', '!=', $user->id); // Exclude self
        }

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nrp', 'like', "%{$this->search}%")
                    ->orWhere('section', 'like', "%{$this->search}%");
            });
        }

        // Status filter
        if ($this->filter && $this->filter !== 'all') {
            $status = $this->filter;

            // Handle 'rejected' filter (both rejected_spv and rejected_leader)
            if ($status === 'rejected') {
                // Rejected at any level (SPV, Dept Head, or Leader)
                $rejectedStatuses = ['rejected_spv', 'rejected_dept_head', 'rejected_leader'];

                $query->where(function ($q) use ($year, $rejectedStatuses) {
                    $q->whereHas('trainingPlans', fn($sq) => $sq->where('year', $year)->whereIn('status', $rejectedStatuses))
                        ->orWhereHas('selfLearningPlans', fn($sq) => $sq->where('year', $year)->whereIn('status', $rejectedStatuses))
                        ->orWhereHas('mentoringPlans', fn($sq) => $sq->where('year', $year)->whereIn('status', $rejectedStatuses))
                        ->orWhereHas('projectPlans', fn($sq) => $sq->where('year', $year)->whereIn('status', $rejectedStatuses));
                });
            } else {
                $query->where(function ($q) use ($year, $status) {
                    // For 'pending_spv' filter, include both supervisor and dept head pending
                    if ($status === 'pending_spv') {
                        $statuses = ['pending_spv', 'pending_dept_head'];

                        $q->whereHas('trainingPlans', fn($sq) => $sq->where('year', $year)->whereIn('status', $statuses))
                            ->orWhereHas('selfLearningPlans', fn($sq) => $sq->where('year', $year)->whereIn('status', $statuses))
                            ->orWhereHas('mentoringPlans', fn($sq) => $sq->where('year', $year)->whereIn('status', $statuses))
                            ->orWhereHas('projectPlans', fn($sq) => $sq->where('year', $year)->whereIn('status', $statuses));
                    } else {
                        $q->whereHas('trainingPlans', fn($sq) => $sq->where('year', $year)->where('status', $status))
                            ->orWhereHas('selfLearningPlans', fn($sq) => $sq->where('year', $year)->where('status', $status))
                            ->orWhereHas('mentoringPlans', fn($sq) => $sq->where('year', $year)->where('status', $status))
                            ->orWhereHas('projectPlans', fn($sq) => $sq->where('year', $year)->where('status', $status));
                    }
                });
            }
        }

        return $query->paginate(10);
    }

    public function getUserStatus($user)
    {
        $year = (int) $this->selectedYear;

        $statuses = collect();

        // Get all statuses from all plan types
        $statuses = $statuses->merge(
            TrainingPlan::where('user_id', $user->id)->where('year', $year)->where('status', '!=', 'draft')->pluck('status')
        );
        $statuses = $statuses->merge(
            SelfLearningPlan::where('user_id', $user->id)->where('year', $year)->where('status', '!=', 'draft')->pluck('status')
        );
        $statuses = $statuses->merge(
            MentoringPlan::where('user_id', $user->id)->where('year', $year)->where('status', '!=', 'draft')->pluck('status')
        );
        $statuses = $statuses->merge(
            ProjectPlan::where('user_id', $user->id)->where('year', $year)->where('status', '!=', 'draft')->pluck('status')
        );

        // Determine overall status with priority
        if ($statuses->contains('rejected_leader') || $statuses->contains('rejected_dept_head') || $statuses->contains('rejected_spv')) {
            if ($statuses->contains('rejected_leader')) {
                return 'rejected_leader';
            }

            if ($statuses->contains('rejected_dept_head')) {
                return 'rejected_dept_head';
            }

            return 'rejected_spv';
        } elseif ($statuses->contains('pending_leader')) {
            return 'pending_leader';
        } elseif ($statuses->contains('pending_dept_head')) {
            return 'pending_dept_head';
        } elseif ($statuses->contains('pending_spv')) {
            return 'pending_spv';
        } elseif ($statuses->every(fn($s) => $s === 'approved')) {
            return 'approved';
        }

        return 'pending_spv';
    }

    public function openDetailModal($userId)
    {
        $this->selectedUserId = $userId;
        $year = (int) $this->selectedYear;

        $user = User::find($userId);
        $this->selectedUserData = [
            'name' => $user->name,
            'nrp' => $user->nrp,
            'section' => $user->section,
            'position' => $user->position,
        ];

        // Load user's plans
        $this->userTrainingPlans = TrainingPlan::with(['competency', 'spvApprover', 'leaderApprover'])
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('status', '!=', 'draft')
            ->get()
            ->toArray();

        $this->userSelfLearningPlans = SelfLearningPlan::with(['mentor', 'spvApprover', 'leaderApprover'])
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('status', '!=', 'draft')
            ->get()
            ->toArray();

        $this->userMentoringPlans = MentoringPlan::with(['mentor', 'spvApprover', 'leaderApprover'])
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('status', '!=', 'draft')
            ->get()
            ->toArray();

        $this->userProjectPlans = ProjectPlan::with(['mentor', 'spvApprover', 'leaderApprover'])
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('status', '!=', 'draft')
            ->get()
            ->toArray();

        $this->detailModal = true;
    }

    public function closeDetailModal()
    {
        $this->detailModal = false;
        $this->selectedUserId = null;
        $this->selectedUserData = null;
        $this->userTrainingPlans = [];
        $this->userSelfLearningPlans = [];
        $this->userMentoringPlans = [];
        $this->userProjectPlans = [];
    }

    /**
     * Get the expected pending status based on current user's approval level
     */
    private function getExpectedPendingStatus(): string
    {
        if ($this->isLeaderLid()) {
            return 'pending_leader';
        }

        if ($this->isSupervisorArea()) {
            return 'pending_spv';
        }

        if ($this->isDeptHeadArea()) {
            return 'pending_dept_head';
        }

        return '';
    }

    /**
     * Get the next status after approval
     */
    private function getNextApprovedStatus(): string
    {
        if ($this->isSpv()) {
            return 'pending_leader'; // After SPV approves, goes to Leader
        } elseif ($this->isLeaderLid()) {
            return 'approved'; // After Leader approves, fully approved
        }
        return '';
    }

    /**
     * Get the rejection status based on current user's role
     */
    private function getRejectionStatus(): string
    {
        if ($this->isSupervisorArea()) {
            return 'rejected_spv';
        }

        if ($this->isDeptHeadArea()) {
            return 'rejected_dept_head';
        }

        if ($this->isLeaderLid()) {
            return 'rejected_leader';
        }
        return '';
    }

    public function approveAll()
    {
        if (!$this->selectedUserId) return;

        $year = (int) $this->selectedYear;
        $approverId = Auth::id();
        $now = now();
        $expectedStatus = $this->getExpectedPendingStatus();
        $nextStatus = $this->getNextApprovedStatus();

        if (!$expectedStatus || !$nextStatus) {
            $this->error('You do not have permission to approve', position: 'toast-top toast-center');
            return;
        }

        $approvalFields = $this->isSpv()
            ? $this->buildFirstLevelApprovalFields($nextStatus, $approverId, $now)
            : ['status' => $nextStatus, 'leader_approved_by' => $approverId, 'leader_approved_at' => $now];

        TrainingPlan::where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->where('status', $expectedStatus)
            ->update($approvalFields);

        SelfLearningPlan::where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->where('status', $expectedStatus)
            ->update($approvalFields);

        MentoringPlan::where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->where('status', $expectedStatus)
            ->update($approvalFields);

        ProjectPlan::where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->where('status', $expectedStatus)
            ->update($approvalFields);

        $this->closeDetailModal();

        $message = $this->isSpv()
            ? 'All plans approved. Waiting for Leader LID approval.'
            : 'All development plans fully approved!';
        $this->success($message, position: 'toast-top toast-center');
    }

    public function openRejectModal($type = null, $id = null, $all = false)
    {
        $this->rejectingPlanType = $type;
        $this->rejectingPlanId = $id;
        $this->rejectAll = $all;
        $this->rejectionReason = '';
        $this->rejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->rejectModal = false;
        $this->rejectingPlanType = null;
        $this->rejectingPlanId = null;
        $this->rejectAll = false;
        $this->rejectionReason = '';
    }

    public function confirmReject()
    {
        $this->validate([
            'rejectionReason' => 'required|min:10',
        ], [
            'rejectionReason.required' => 'Please provide a reason for rejection.',
            'rejectionReason.min' => 'Rejection reason must be at least 10 characters.',
        ]);

        if ($this->rejectAll) {
            $this->executeRejectAll();
        } else {
            $this->executeRejectPlan($this->rejectingPlanType, $this->rejectingPlanId);
        }

        $this->closeRejectModal();
    }

    private function executeRejectAll()
    {
        if (!$this->selectedUserId) return;

        $year = (int) $this->selectedYear;
        $approverId = Auth::id();
        $now = now();
        $expectedStatus = $this->getExpectedPendingStatus();
        $rejectionStatus = $this->getRejectionStatus();

        if (!$expectedStatus || !$rejectionStatus) {
            $this->error('You do not have permission to reject', position: 'toast-top toast-center');
            return;
        }

        $rejectionFields = [
            'status' => $rejectionStatus,
            'rejection_reason' => $this->rejectionReason,
        ];

        if ($this->isSpv()) {
            $firstLevelFields = $this->buildFirstLevelApprovalFields($rejectionStatus, $approverId, $now);
            unset($firstLevelFields['status']);
            $rejectionFields = array_merge($rejectionFields, $firstLevelFields);
        } else {
            $rejectionFields['leader_approved_by'] = $approverId;
            $rejectionFields['leader_approved_at'] = $now;
        }

        TrainingPlan::where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->where('status', $expectedStatus)
            ->update($rejectionFields);

        SelfLearningPlan::where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->where('status', $expectedStatus)
            ->update($rejectionFields);

        MentoringPlan::where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->where('status', $expectedStatus)
            ->update($rejectionFields);

        ProjectPlan::where('user_id', $this->selectedUserId)
            ->where('year', $year)
            ->where('status', $expectedStatus)
            ->update($rejectionFields);

        $this->closeDetailModal();
        $this->error('All development plans rejected. Employee must revise and resubmit.', position: 'toast-top toast-center');
    }

    public function approvePlan($type, $id)
    {
        $model = $this->getPlanModel($type);
        if (!$model) return;

        $plan = $model::find($id);
        $expectedStatus = $this->getExpectedPendingStatus();
        $nextStatus = $this->getNextApprovedStatus();

        if (!$plan || $plan->status !== $expectedStatus) {
            $this->error('Plan cannot be approved at this stage', position: 'toast-top toast-center');
            return;
        }

        $updateData = ['status' => $nextStatus];

        if ($this->isSpv()) {
            $firstLevelFields = $this->buildFirstLevelApprovalFields($nextStatus, Auth::id(), now());
            unset($firstLevelFields['status']);
            $updateData = array_merge($updateData, $firstLevelFields);
        } else {
            $updateData['leader_approved_by'] = Auth::id();
            $updateData['leader_approved_at'] = now();
        }

        $plan->update($updateData);

        // Refresh modal data
        $this->openDetailModal($this->selectedUserId);

        $message = $nextStatus === 'approved'
            ? 'Plan fully approved!'
            : 'Plan approved. Waiting for Leader LID approval.';
        $this->success($message, position: 'toast-top toast-center');
    }

    public function rejectPlan($type, $id)
    {
        // Open reject modal for single plan
        $this->openRejectModal($type, $id, false);
    }

    private function executeRejectPlan($type, $id)
    {
        $model = $this->getPlanModel($type);
        if (!$model) return;

        $plan = $model::find($id);
        $expectedStatus = $this->getExpectedPendingStatus();
        $rejectionStatus = $this->getRejectionStatus();

        if (!$plan || $plan->status !== $expectedStatus) {
            $this->error('Plan cannot be rejected at this stage', position: 'toast-top toast-center');
            return;
        }

        $updateData = [
            'status' => $rejectionStatus,
            'rejection_reason' => $this->rejectionReason,
        ];

        if ($this->isSpv()) {
            $firstLevelFields = $this->buildFirstLevelApprovalFields($rejectionStatus, Auth::id(), now());
            unset($firstLevelFields['status']);
            $updateData = array_merge($updateData, $firstLevelFields);
        } else {
            $updateData['leader_approved_by'] = Auth::id();
            $updateData['leader_approved_at'] = now();
        }

        $plan->update($updateData);

        // Refresh modal data
        $this->openDetailModal($this->selectedUserId);
        $this->error('Plan rejected. Employee must revise and resubmit.', position: 'toast-top toast-center');
    }

    /**
     * Check if current user can approve/reject the given plan
     */
    public function canApprovePlan($plan): bool
    {
        $expectedStatus = $this->getExpectedPendingStatus();
        return $plan['status'] === $expectedStatus;
    }

    private function getPlanModel($type)
    {
        return match ($type) {
            'training' => TrainingPlan::class,
            'self-learning' => SelfLearningPlan::class,
            'mentoring' => MentoringPlan::class,
            'project' => ProjectPlan::class,
            default => null,
        };
    }

    /**
     * Build first-level (area) approval fields depending on whether
     * the current user is Supervisor area or Dept Head area.
     */
    private function buildFirstLevelApprovalFields(string $status, int $approverId, $timestamp): array
    {
        $fields = ['status' => $status];

        if ($this->isSupervisorArea()) {
            $fields['spv_approved_by'] = $approverId;
            $fields['spv_approved_at'] = $timestamp;
        } elseif ($this->isDeptHeadArea()) {
            $fields['dept_head_approved_by'] = $approverId;
            $fields['dept_head_approved_at'] = $timestamp;
        }

        return $fields;
    }

    /**
     * Check if there are any plans pending approval for current user
     */
    public function hasPendingPlans(): bool
    {
        $expectedStatus = $this->getExpectedPendingStatus();

        return count(array_filter($this->userTrainingPlans, fn($p) => $p['status'] === $expectedStatus)) > 0
            || count(array_filter($this->userSelfLearningPlans, fn($p) => $p['status'] === $expectedStatus)) > 0
            || count(array_filter($this->userMentoringPlans, fn($p) => $p['status'] === $expectedStatus)) > 0
            || count(array_filter($this->userProjectPlans, fn($p) => $p['status'] === $expectedStatus)) > 0;
    }

    public function render()
    {
        return view('pages.development.development-approval', [
            'headers' => $this->headers(),
            'approvalData' => $this->approvalData,
            'approvalLevel' => $this->getApprovalLevel(),
        ]);
    }
}
