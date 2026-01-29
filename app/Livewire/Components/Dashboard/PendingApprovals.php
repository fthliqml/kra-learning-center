<?php

namespace App\Livewire\Components\Dashboard;

use App\Models\Request;
use App\Models\Training;
use App\Models\TrainingPlan;
use App\Models\SelfLearningPlan;
use App\Models\MentoringPlan;
use App\Models\ProjectPlan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PendingApprovals extends Component
{
    public array $items = [];

    public function mount()
    {
        $this->loadPendingItems();
    }

    public function loadPendingItems()
    {
        $this->items = [];

        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Get pending training requests that are actionable for the current user
        $pendingRequests = $this->actionableTrainingRequests($user);

        foreach ($pendingRequests as $request) {
            $this->items[] = [
                'title' => 'Training Request',
                'info' => $request->user->name ?? 'Unknown',
                'type' => 'training_request',
                'id' => $request->id,
                'sort_at' => $request->updated_at ?? $request->created_at,
            ];
        }

        // Get trainings that are waiting for *this user's* next approval action
        $pendingTrainings = $this->actionableTrainings($user);

        foreach ($pendingTrainings as $training) {
            $dateInfo = $training->start_date
                ? $training->start_date->format('d M Y')
                : 'No date';

            $this->items[] = [
                'title' => $training->name ?? 'Training',
                'info' => $dateInfo,
                'type' => 'training',
                'id' => $training->id,
                'sort_at' => $training->updated_at ?? $training->created_at,
            ];
        }

        // Get pending IDP (Individual Development Plan) approvals that are actionable for the current user
        $pendingIdpPlans = $this->actionableIdpPlans($user);


        // Group IDP plans by user so each employee only appears once
        $idpByUser = [];
        foreach ($pendingIdpPlans as $plan) {
            $userId = $plan->user_id;
            if (!isset($idpByUser[$userId])) {
                $idpByUser[$userId] = $plan;
            } else {
                // Keep the most recently updated plan for sorting
                $existing = $idpByUser[$userId];
                $existingTime = $existing->updated_at ?? $existing->created_at;
                $planTime = $plan->updated_at ?? $plan->created_at;
                if (strtotime((string)$planTime) > strtotime((string)$existingTime)) {
                    $idpByUser[$userId] = $plan;
                }
            }
        }
        foreach ($idpByUser as $plan) {
            $this->items[] = [
                'title' => 'Development Plan',
                'info' => $plan->user->name ?? 'Unknown',
                'type' => 'idp',
                'id' => $plan->id,
                'sort_at' => $plan->updated_at ?? $plan->created_at,
            ];
        }

        // Sort by most recent and limit total
        usort($this->items, function ($a, $b) {
            $at = $a['sort_at'] ?? null;
            $bt = $b['sort_at'] ?? null;
            $as = $at ? strtotime((string) $at) : 0;
            $bs = $bt ? strtotime((string) $bt) : 0;
            return $bs <=> $as;
        });
        $this->items = array_values(array_slice($this->items, 0, 10));
    }

    private function isLidSectionHead(User $user): bool
    {
        return $user->hasPosition('section_head')
            && strtolower(trim($user->section ?? '')) === 'lid';
    }

    private function isLidDeptHead(User $user): bool
    {
        return $user->hasPosition('department_head')
            && trim((string) ($user->department ?? '')) === 'Human Capital, General Service, Security & LID';
    }

    private function actionableTrainings(User $user)
    {
        if ($this->isLidSectionHead($user)) {
            return Training::query()
                ->where('status', 'done')
                ->whereNull('section_head_signed_at')
                ->whereNull('dept_head_signed_at')
                ->latest()
                ->take(5)
                ->get();
        }

        if ($this->isLidDeptHead($user)) {
            return Training::query()
                ->where('status', 'done')
                ->whereNotNull('section_head_signed_at')
                ->whereNull('dept_head_signed_at')
                ->latest()
                ->take(5)
                ->get();
        }

        return collect();
    }

    private function actionableTrainingRequests(User $user)
    {
        $base = Request::query()->with('user')->where('status', 'pending');

        $userDept = strtolower(trim((string) ($user->department ?? '')));
        $userDiv = strtolower(trim((string) ($user->division ?? '')));

        // Dept Head area stage (same department as target user)
        if ($user->hasPosition('department_head') && $userDept !== '') {
            return $base
                ->where('approval_stage', Request::STAGE_DEPT_HEAD)
                ->whereHas('user', function ($q) use ($userDept) {
                    $q->whereRaw('LOWER(TRIM(department)) = ?', [$userDept]);
                })
                ->latest()
                ->take(5)
                ->get();
        }

        // Division Head area stage (same division as target user)
        if ($user->hasPosition('division_head') && $userDiv !== '') {
            return $base
                ->where('approval_stage', Request::STAGE_AREA_DIV_HEAD)
                ->whereHas('user', function ($q) use ($userDiv) {
                    $q->whereRaw('LOWER(TRIM(division)) = ?', [$userDiv]);
                })
                ->latest()
                ->take(5)
                ->get();
        }

        // Division Head LID final stage
        if ($user->hasPosition('division_head') && $userDiv === 'human capital, finance & general support') {
            return $base
                ->where('approval_stage', Request::STAGE_LID_DIV_HEAD)
                ->latest()
                ->take(5)
                ->get();
        }

        return collect();
    }

    private function actionableIdpPlans(User $user)
    {
        $expectedStatus = $this->expectedIdpStatusForUser($user);
        if ($expectedStatus === '') {
            return collect();
        }

        // Pull a small candidate set, then filter with the exact per-target permission checks.
        $candidates = collect()
            ->merge(TrainingPlan::with('user')->where('status', $expectedStatus)->latest()->take(20)->get())
            ->merge(SelfLearningPlan::with('user')->where('status', $expectedStatus)->latest()->take(20)->get())
            ->merge(MentoringPlan::with('user')->where('status', $expectedStatus)->latest()->take(20)->get())
            ->merge(ProjectPlan::with('user')->where('status', $expectedStatus)->latest()->take(20)->get());

        return $candidates
            ->filter(function ($plan) use ($expectedStatus) {
                $targetUserId = (int) ($plan->user_id ?? 0);
                return $targetUserId > 0 && $this->canApproveUserAtStatus($targetUserId, $expectedStatus);
            })
            ->values()
            ->take(5);
    }

    private function expectedIdpStatusForUser(User $user): string
    {
        $section = strtolower(trim((string) ($user->section ?? '')));

        if ($user->hasPosition('section_head') && $section === 'lid') {
            return 'pending_lid';
        }

        if ($user->hasPosition('supervisor')) {
            return 'pending_spv';
        }

        if ($user->hasPosition('section_head') && $section !== 'lid') {
            return 'pending_section_head';
        }

        if ($user->hasPosition('department_head') && $section !== 'lid') {
            return 'pending_dept_head';
        }

        return '';
    }

    private function isSupervisorArea(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user ? $user->hasPosition('supervisor') : false;
    }

    private function isSectionHeadArea(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $section = strtolower(trim($user->section ?? ''));
        return $user->hasPosition('section_head') && $section !== 'lid';
    }

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

    private function isLidApprover(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user ? ($user->hasPosition('section_head') && strtolower(trim($user->section ?? '')) === 'lid') : false;
    }

    private function existsSupervisorInUserArea(User $target): bool
    {
        $section = (string) ($target->section ?? '');
        $department = (string) ($target->department ?? '');

        if ($section !== '') {
            return User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
                ->where('section', $section)
                ->exists();
        }

        if ($department !== '') {
            return User::query()
                ->whereRaw('LOWER(TRIM(position)) = ?', ['supervisor'])
                ->where('department', $department)
                ->exists();
        }

        return false;
    }

    private function canApproveUserAtStatus(int $targetUserId, string $expectedStatus): bool
    {
        if ($expectedStatus === 'pending_lid') {
            return $this->isLidApprover();
        }

        if ($expectedStatus === 'pending_dept_head') {
            return $this->isDeptHeadArea();
        }

        if (!in_array($expectedStatus, ['pending_spv', 'pending_section_head'], true)) {
            return false;
        }

        $target = User::find($targetUserId);
        if (!$target) {
            return false;
        }

        $targetPosition = strtolower(trim($target->position ?? ''));

        if ($expectedStatus === 'pending_spv') {
            // Supervisor can only approve employee submissions (not supervisors).
            return $this->isSupervisorArea() && $targetPosition !== 'supervisor';
        }

        // Section Head approves:
        // - supervisor submissions always
        // - employee submissions only when no supervisor exists in that area
        if ($expectedStatus === 'pending_section_head' && $this->isSectionHeadArea()) {
            if ($targetPosition === 'supervisor') {
                return true;
            }

            return !$this->existsSupervisorInUserArea($target);
        }

        return false;
    }

    public function gradient(string $type): string
    {
        return match ($type) {
            'idp' => 'from-green-400 to-green-200',
            'training_request' => 'from-blue-500 to-blue-300',
            'training' => 'from-blue-400 to-blue-200',
            default => 'from-gray-300 to-gray-100',
        };
    }

    public function iconName(string $type): string
    {
        return match ($type) {
            'idp' => 'o-clipboard-document-list',
            'training_request' => 'o-document-text',
            'training' => 'o-academic-cap',
            default => 'o-document',
        };
    }

    public function getUrl(string $type, int $id): string
    {
        return match ($type) {
            // Add filter param for IDP (Development Plan)
            'idp' => route('development-approval.index', ['filter' => 'pending_lid']),
            'training_request' => route('training-request.index'),
            'training' => route('training-approval.index'),
            default => '#',
        };
    }

    public function render()
    {
        return view('components.dashboard.pending-approvals');
    }
}
