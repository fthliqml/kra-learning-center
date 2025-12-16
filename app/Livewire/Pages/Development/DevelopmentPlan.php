<?php

namespace App\Livewire\Pages\Development;

use App\Models\Competency;
use App\Models\MentoringPlan;
use App\Models\ProjectPlan;
use App\Models\SelfLearningPlan;
use App\Models\Training;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\User;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;

class DevelopmentPlan extends Component
{
    use Toast;

    // Modal states
    public $addModal = false;
    public $activeTab = 'training';

    // Edit mode
    public $isEdit = false;

    // Filter by year
    public $selectedYear;

    // Training Plan form (multiple rows)
    public $trainingPlans = [
        ['id' => null, 'group' => '', 'competency_id' => '', 'status' => null],
        ['id' => null, 'group' => '', 'competency_id' => '', 'status' => null],
        ['id' => null, 'group' => '', 'competency_id' => '', 'status' => null],
    ];

    // Self Learning Plans (multiple)
    public $selfLearningPlans = [
        ['id' => null, 'title' => '', 'objective' => '', 'mentor_id' => '', 'start_date' => '', 'end_date' => '', 'status' => null],
    ];

    // Mentoring Plans (multiple)
    public $mentoringPlans = [
        ['id' => null, 'mentor_id' => '', 'objective' => '', 'method' => '', 'frequency' => '', 'duration' => '', 'status' => null],
    ];

    // Project Plans (multiple)
    public $projectPlans = [
        ['id' => null, 'name' => '', 'objective' => '', 'mentor_id' => '', 'status' => null],
    ];

    // Legacy single form properties (for backward compatibility)
    public $selfLearning = [
        'title' => '',
        'objective' => '',
        'mentor_id' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    public $mentoring = [
        'mentor_id' => '',
        'objective' => '',
        'method' => '',
        'frequency' => '',
        'duration' => '',
    ];

    public $project = [
        'name' => '',
        'objective' => '',
        'mentor_id' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    // Options
    public $typeOptions = [
        ['value' => 'BMC', 'label' => 'BMC'],
        ['value' => 'BC', 'label' => 'BC'],
        ['value' => 'MMP', 'label' => 'MMP'],
        ['value' => 'LC', 'label' => 'LC'],
        ['value' => 'MDP', 'label' => 'MDP'],
        ['value' => 'TOC', 'label' => 'TOC'],
    ];

    public $methodOptions = [
        ['value' => 'online', 'label' => 'Online'],
        ['value' => 'offline', 'label' => 'Offline'],
        ['value' => 'hybrid', 'label' => 'Hybrid'],
    ];

    public function mount()
    {
        // Default to current year (as string for datepicker compatibility)
        $this->selectedYear = (string) now()->year;
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->addModal = true;
    }

    public function openEditModal()
    {
        $this->resetForm();
        $this->isEdit = true;
        $this->activeTab = 'training';
        $this->loadExistingPlans();
        $this->addModal = true;
    }

    private function loadExistingPlans()
    {
        $userId = Auth::id();
        $year = (int) $this->selectedYear;

        // Load Training Plans
        $trainingPlans = TrainingPlan::with('competency')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->get();

        $this->trainingPlans = [];
        foreach ($trainingPlans as $plan) {
            $this->trainingPlans[] = [
                'id' => $plan->id,
                'group' => $plan->competency->type ?? '',
                'competency_id' => $plan->competency_id,
                'status' => $plan->status,
            ];
        }
        // Add empty rows if less than 3
        while (count($this->trainingPlans) < 3) {
            $this->trainingPlans[] = ['id' => null, 'group' => '', 'competency_id' => '', 'status' => null];
        }

        // Load Self Learning Plans
        $selfLearningPlans = SelfLearningPlan::where('user_id', $userId)
            ->where('year', $year)
            ->get();

        $this->selfLearningPlans = [];
        foreach ($selfLearningPlans as $plan) {
            $this->selfLearningPlans[] = [
                'id' => $plan->id,
                'title' => $plan->title,
                'objective' => $plan->objective,
                'mentor_id' => $plan->mentor_id,
                'start_date' => $plan->start_date?->format('Y-m-d'),
                'end_date' => $plan->end_date?->format('Y-m-d'),
                'status' => $plan->status,
            ];
        }
        // Add empty row if empty
        if (empty($this->selfLearningPlans)) {
            $this->selfLearningPlans[] = [
                'id' => null,
                'title' => '',
                'objective' => '',
                'mentor_id' => '',
                'start_date' => '',
                'end_date' => '',
                'status' => null
            ];
        }

        // Load Mentoring Plans
        $mentoringPlans = MentoringPlan::where('user_id', $userId)
            ->where('year', $year)
            ->get();

        $this->mentoringPlans = [];
        foreach ($mentoringPlans as $plan) {
            $this->mentoringPlans[] = [
                'id' => $plan->id,
                'mentor_id' => $plan->mentor_id,
                'objective' => $plan->objective,
                'method' => $plan->method,
                'frequency' => $plan->frequency,
                'duration' => $plan->duration,
                'status' => $plan->status,
            ];
        }
        if (empty($this->mentoringPlans)) {
            $this->mentoringPlans[] = [
                'id' => null,
                'mentor_id' => '',
                'objective' => '',
                'method' => '',
                'frequency' => '',
                'duration' => '',
                'status' => null
            ];
        }

        // Load Project Plans
        $projectPlans = ProjectPlan::where('user_id', $userId)
            ->where('year', $year)
            ->get();

        $this->projectPlans = [];
        foreach ($projectPlans as $plan) {
            $this->projectPlans[] = [
                'id' => $plan->id,
                'name' => $plan->name,
                'objective' => $plan->objective,
                'mentor_id' => $plan->mentor_id,
                'status' => $plan->status,
            ];
        }
        if (empty($this->projectPlans)) {
            $this->projectPlans[] = [
                'id' => null,
                'name' => '',
                'objective' => '',
                'mentor_id' => '',
                'status' => null
            ];
        }
    }

    public function closeAddModal()
    {
        $this->addModal = false;
        $this->resetForm();
    }

    // Add new row methods
    public function addTrainingRow()
    {
        $this->trainingPlans[] = ['id' => null, 'group' => '', 'competency_id' => '', 'status' => null];
    }

    public function addSelfLearningRow()
    {
        $this->selfLearningPlans[] = ['id' => null, 'title' => '', 'objective' => '', 'mentor_id' => '', 'start_date' => '', 'end_date' => '', 'status' => null];
    }

    public function addMentoringRow()
    {
        $this->mentoringPlans[] = ['id' => null, 'mentor_id' => '', 'objective' => '', 'method' => '', 'frequency' => '', 'duration' => '', 'status' => null];
    }

    public function addProjectRow()
    {
        $this->projectPlans[] = ['id' => null, 'name' => '', 'objective' => '', 'mentor_id' => '', 'status' => null];
    }

    // Remove row methods
    public function removeTrainingRow($index)
    {
        if (count($this->trainingPlans) > 1) {
            $plan = $this->trainingPlans[$index];
            // If it has an ID, delete from database
            if (!empty($plan['id'])) {
                $existing = TrainingPlan::find($plan['id']);
                if ($existing && $existing->canEdit()) {
                    $existing->delete();
                }
            }
            unset($this->trainingPlans[$index]);
            $this->trainingPlans = array_values($this->trainingPlans);
        }
    }

    public function removeSelfLearningRow($index)
    {
        if (count($this->selfLearningPlans) > 1) {
            $plan = $this->selfLearningPlans[$index];
            if (!empty($plan['id'])) {
                $existing = SelfLearningPlan::find($plan['id']);
                if ($existing && $existing->canEdit()) {
                    $existing->delete();
                }
            }
            unset($this->selfLearningPlans[$index]);
            $this->selfLearningPlans = array_values($this->selfLearningPlans);
        }
    }

    public function removeMentoringRow($index)
    {
        if (count($this->mentoringPlans) > 1) {
            $plan = $this->mentoringPlans[$index];
            if (!empty($plan['id'])) {
                $existing = MentoringPlan::find($plan['id']);
                if ($existing && $existing->canEdit()) {
                    $existing->delete();
                }
            }
            unset($this->mentoringPlans[$index]);
            $this->mentoringPlans = array_values($this->mentoringPlans);
        }
    }

    public function removeProjectRow($index)
    {
        if (count($this->projectPlans) > 1) {
            $plan = $this->projectPlans[$index];
            if (!empty($plan['id'])) {
                $existing = ProjectPlan::find($plan['id']);
                if ($existing && $existing->canEdit()) {
                    $existing->delete();
                }
            }
            unset($this->projectPlans[$index]);
            $this->projectPlans = array_values($this->projectPlans);
        }
    }

    // Delete methods
    public function deleteTrainingPlan($id)
    {
        $plan = TrainingPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan || !$plan->canEdit()) {
            $this->error('This plan cannot be deleted', position: 'toast-top toast-center');
            return;
        }

        $plan->delete();
        $this->success('Training plan deleted successfully', position: 'toast-top toast-center');
    }

    public function deleteSelfLearningPlan($id)
    {
        $plan = SelfLearningPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan || !$plan->canEdit()) {
            $this->error('This plan cannot be deleted', position: 'toast-top toast-center');
            return;
        }

        $plan->delete();
        $this->success('Self learning plan deleted successfully', position: 'toast-top toast-center');
    }

    public function deleteMentoringPlan($id)
    {
        $plan = MentoringPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan || !$plan->canEdit()) {
            $this->error('This plan cannot be deleted', position: 'toast-top toast-center');
            return;
        }

        $plan->delete();
        $this->success('Mentoring plan deleted successfully', position: 'toast-top toast-center');
    }

    public function deleteProjectPlan($id)
    {
        $plan = ProjectPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan || !$plan->canEdit()) {
            $this->error('This plan cannot be deleted', position: 'toast-top toast-center');
            return;
        }

        $plan->delete();
        $this->success('Project plan deleted successfully', position: 'toast-top toast-center');
    }

    public function resetForm()
    {
        $this->activeTab = 'training';
        $this->isEdit = false;
        $this->trainingPlans = [
            ['id' => null, 'group' => '', 'competency_id' => '', 'status' => null],
            ['id' => null, 'group' => '', 'competency_id' => '', 'status' => null],
            ['id' => null, 'group' => '', 'competency_id' => '', 'status' => null],
        ];
        $this->selfLearningPlans = [
            ['id' => null, 'title' => '', 'objective' => '', 'mentor_id' => '', 'start_date' => '', 'end_date' => '', 'status' => null],
        ];
        $this->mentoringPlans = [
            ['id' => null, 'mentor_id' => '', 'objective' => '', 'method' => '', 'frequency' => '', 'duration' => '', 'status' => null],
        ];
        $this->projectPlans = [
            ['id' => null, 'name' => '', 'objective' => '', 'mentor_id' => '', 'status' => null],
        ];
        // Legacy
        $this->selfLearning = [
            'title' => '',
            'objective' => '',
            'mentor_id' => '',
            'start_date' => '',
            'end_date' => '',
        ];
        $this->mentoring = [
            'mentor_id' => '',
            'objective' => '',
            'method' => '',
            'frequency' => '',
            'duration' => '',
        ];
        $this->project = [
            'name' => '',
            'objective' => '',
            'mentor_id' => '',
            'start_date' => '',
            'end_date' => '',
        ];
    }

    public function getCompetenciesByType($type)
    {
        if (empty($type)) {
            return [];
        }

        return Competency::where('type', $type)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->name])
            ->toArray();
    }

    public function save()
    {
        $user = Auth::user();

        try {
            // Save all tabs at once
            $this->saveTrainingPlans($user);
            $this->saveSelfLearningPlan($user);
            $this->saveMentoringPlan($user);
            $this->saveProjectPlan($user);

            $this->closeAddModal();
            $this->success('Development plan saved successfully!', position: 'toast-top toast-center');
        } catch (\Exception $e) {
            $this->error('Failed to save: ' . $e->getMessage(), position: 'toast-top toast-center');
        }
    }

    public function saveDraft()
    {
        $user = Auth::user();

        try {
            // Save all tabs at once as draft
            $this->saveTrainingPlans($user, true);
            $this->saveSelfLearningPlan($user, true);
            $this->saveMentoringPlan($user, true);
            $this->saveProjectPlan($user, true);

            $this->closeAddModal();
            $this->success('Development plan draft saved successfully!', position: 'toast-top toast-center');
        } catch (\Exception $e) {
            $this->error('Failed to save draft: ' . $e->getMessage(), position: 'toast-top toast-center');
        }
    }

    private function saveTrainingPlans($user, $isDraft = false)
    {
        $status = $isDraft ? 'draft' : 'pending_spv';

        foreach ($this->trainingPlans as $plan) {
            if (!empty($plan['competency_id'])) {
                if (!empty($plan['id'])) {
                    // Update existing plan
                    $existingPlan = TrainingPlan::find($plan['id']);
                    if ($existingPlan && $existingPlan->canEdit()) {
                        $existingPlan->update([
                            'competency_id' => $plan['competency_id'],
                            'status' => $status,
                            'year' => (int) $this->selectedYear,
                        ]);
                    }
                } else {
                    // Create new plan
                    TrainingPlan::create([
                        'user_id' => $user->id,
                        'competency_id' => $plan['competency_id'],
                        'status' => $status,
                        'year' => (int) $this->selectedYear,
                    ]);
                }
            }
        }
    }

    private function saveSelfLearningPlan($user, $isDraft = false)
    {
        $status = $isDraft ? 'draft' : 'pending_spv';

        foreach ($this->selfLearningPlans as $plan) {
            // Skip empty rows
            if (empty($plan['title']) && empty($plan['objective'])) {
                continue;
            }

            $data = [
                'mentor_id' => $plan['mentor_id'] ?: null,
                'title' => $plan['title'] ?: 'Draft',
                'objective' => $plan['objective'] ?: '',
                'start_date' => $plan['start_date'] ?: now(),
                'end_date' => $plan['end_date'] ?: now(),
                'status' => $status,
                'year' => (int) $this->selectedYear,
            ];

            if (!empty($plan['id'])) {
                // Update existing
                $existingPlan = SelfLearningPlan::find($plan['id']);
                if ($existingPlan && $existingPlan->canEdit()) {
                    $existingPlan->update($data);
                }
            } else {
                // Create new
                SelfLearningPlan::create(array_merge(['user_id' => $user->id], $data));
            }
        }
    }

    private function saveMentoringPlan($user, $isDraft = false)
    {
        $status = $isDraft ? 'draft' : 'pending_spv';

        foreach ($this->mentoringPlans as $plan) {
            // Skip empty rows
            if (empty($plan['objective']) && empty($plan['mentor_id'])) {
                continue;
            }

            $data = [
                'mentor_id' => $plan['mentor_id'] ?: null,
                'objective' => $plan['objective'] ?: '',
                'method' => $plan['method'] ?: '',
                'frequency' => $plan['frequency'] ?: 0,
                'duration' => $plan['duration'] ?: 0,
                'status' => $status,
                'year' => (int) $this->selectedYear,
            ];

            if (!empty($plan['id'])) {
                $existingPlan = MentoringPlan::find($plan['id']);
                if ($existingPlan && $existingPlan->canEdit()) {
                    $existingPlan->update($data);
                }
            } else {
                MentoringPlan::create(array_merge(['user_id' => $user->id], $data));
            }
        }
    }

    private function saveProjectPlan($user, $isDraft = false)
    {
        $status = $isDraft ? 'draft' : 'pending_spv';

        foreach ($this->projectPlans as $plan) {
            // Skip empty rows
            if (empty($plan['name']) && empty($plan['objective'])) {
                continue;
            }

            $data = [
                'mentor_id' => $plan['mentor_id'] ?: null,
                'name' => $plan['name'] ?: 'Draft',
                'objective' => $plan['objective'] ?: '',
                'status' => $status,
                'year' => (int) $this->selectedYear,
            ];

            if (!empty($plan['id'])) {
                $existingPlan = ProjectPlan::find($plan['id']);
                if ($existingPlan && $existingPlan->canEdit()) {
                    $existingPlan->update($data);
                }
            } else {
                ProjectPlan::create(array_merge(['user_id' => $user->id], $data));
            }
        }
    }

    public function render()
    {
        $user = Auth::user();

        // Get mentors (users with spv or leader role)
        $mentors = User::whereIn('role', ['spv', 'leader', 'admin'])
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
            ->toArray();

        // Parse year from datepicker (handles both "2025" and full date strings)
        $selectedYearInt = (int) $this->selectedYear;

        // Get user's development plans for selected year
        $trainingPlansData = TrainingPlan::with(['competency', 'approver'])
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->get();

        $selfLearningData = SelfLearningPlan::with(['mentor', 'approver'])
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->get();

        $mentoringData = MentoringPlan::with(['mentor', 'approver'])
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->get();

        $projectData = ProjectPlan::with(['mentor', 'approver'])
            ->where('user_id', $user->id)
            ->where('year', $selectedYearInt)
            ->get();

        // Statistics for selected year
        $trainingPlanCount = $trainingPlansData->count();
        $selfLearningCount = $selfLearningData->count();
        $mentoringCount = $mentoringData->count();
        $projectCount = $projectData->count();
        $totalPlans = $trainingPlanCount + $selfLearningCount + $mentoringCount + $projectCount;

        // Training Realization: Count training plans where user has completed training
        // with matching competency and passed status
        $trainingRealized = 0;
        foreach ($trainingPlansData as $plan) {
            if ($plan->isRealized()) {
                $trainingRealized++;
            }
        }

        // Self Learning, Mentoring, Project realization (based on approved status for now)
        // TODO: Implement actual realization tracking when features are available
        $selfLearningRealized = $selfLearningData->where('status', 'approved')->count();
        $mentoringRealized = $mentoringData->where('status', 'approved')->count();
        $projectRealized = $projectData->where('status', 'approved')->count();

        // Chart data
        $chartData = [
            $trainingPlanCount,
            $selfLearningCount,
            $mentoringCount,
            $projectCount,
        ];

        return view('pages.development.development-plan', [
            'user' => $user,
            'mentors' => $mentors,
            'trainingPlanCount' => $trainingPlanCount,
            'selfLearningCount' => $selfLearningCount,
            'mentoringCount' => $mentoringCount,
            'projectCount' => $projectCount,
            'totalPlans' => $totalPlans,
            'trainingPlansData' => $trainingPlansData,
            'selfLearningData' => $selfLearningData,
            'mentoringData' => $mentoringData,
            'projectData' => $projectData,
            'chartData' => $chartData,
            'trainingRealized' => $trainingRealized,
            'selfLearningRealized' => $selfLearningRealized,
            'mentoringRealized' => $mentoringRealized,
            'projectRealized' => $projectRealized,
        ]);
    }
}
