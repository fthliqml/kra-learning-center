<?php

namespace App\Livewire\Pages\Development;

use App\Models\Competency;
use App\Models\MentoringPlan;
use App\Models\ProjectPlan;
use App\Models\SelfLearningPlan;
use App\Models\TrainingPlan;
use App\Models\User;
use Livewire\Component;
use Mary\Traits\Toast;

class DevelopmentPlan extends Component
{
    use Toast;

    // Modal states
    public $addModal = false;
    public $activeTab = 'training';

    // Training Plan form (3 rows of group + competency)
    public $trainingPlans = [
        ['group' => '', 'competency_id' => ''],
        ['group' => '', 'competency_id' => ''],
        ['group' => '', 'competency_id' => ''],
    ];

    // Self Learning Plan form
    public $selfLearning = [
        'title' => '',
        'objective' => '',
        'mentor_id' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    // Mentoring Plan form
    public $mentoring = [
        'mentor_id' => '',
        'objective' => '',
        'method' => '',
        'frequency' => '',
        'duration' => '',
    ];

    // Project Assignment Plan form
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

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->addModal = true;
    }

    public function closeAddModal()
    {
        $this->addModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->activeTab = 'training';
        $this->trainingPlans = [
            ['group' => '', 'competency_id' => ''],
            ['group' => '', 'competency_id' => ''],
            ['group' => '', 'competency_id' => ''],
        ];
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
        $user = auth()->user();

        try {
            if ($this->activeTab === 'training') {
                $this->saveTrainingPlans($user);
            } elseif ($this->activeTab === 'self-learning') {
                $this->saveSelfLearningPlan($user);
            } elseif ($this->activeTab === 'mentoring') {
                $this->saveMentoringPlan($user);
            } elseif ($this->activeTab === 'project') {
                $this->saveProjectPlan($user);
            }

            $this->closeAddModal();
            $this->success('Development plan saved successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to save: ' . $e->getMessage());
        }
    }

    public function saveDraft()
    {
        $user = auth()->user();

        try {
            if ($this->activeTab === 'training') {
                $this->saveTrainingPlans($user, true);
            } elseif ($this->activeTab === 'self-learning') {
                $this->saveSelfLearningPlan($user, true);
            } elseif ($this->activeTab === 'mentoring') {
                $this->saveMentoringPlan($user, true);
            } elseif ($this->activeTab === 'project') {
                $this->saveProjectPlan($user, true);
            }

            $this->closeAddModal();
            $this->success('Development plan draft saved successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to save draft: ' . $e->getMessage());
        }
    }

    private function saveTrainingPlans($user, $isDraft = false)
    {
        foreach ($this->trainingPlans as $plan) {
            if (!empty($plan['competency_id'])) {
                TrainingPlan::create([
                    'user_id' => $user->id,
                    'competency_id' => $plan['competency_id'],
                ]);
            }
        }
    }

    private function saveSelfLearningPlan($user, $isDraft = false)
    {
        if (!$isDraft) {
            $this->validate([
                'selfLearning.title' => 'required|string|max:255',
                'selfLearning.objective' => 'required|string',
                'selfLearning.mentor_id' => 'required|exists:users,id',
                'selfLearning.start_date' => 'required|date',
                'selfLearning.end_date' => 'required|date|after_or_equal:selfLearning.start_date',
            ]);
        }

        SelfLearningPlan::create([
            'user_id' => $user->id,
            'mentor_id' => $this->selfLearning['mentor_id'] ?: null,
            'title' => $this->selfLearning['title'] ?: 'Draft',
            'objective' => $this->selfLearning['objective'] ?: '',
            'start_date' => $this->selfLearning['start_date'] ?: now(),
            'end_date' => $this->selfLearning['end_date'] ?: now(),
        ]);
    }

    private function saveMentoringPlan($user, $isDraft = false)
    {
        if (!$isDraft) {
            $this->validate([
                'mentoring.mentor_id' => 'required|exists:users,id',
                'mentoring.objective' => 'required|string',
                'mentoring.method' => 'required|string',
                'mentoring.frequency' => 'required|integer|min:1',
                'mentoring.duration' => 'required|integer|min:1',
            ]);
        }

        MentoringPlan::create([
            'user_id' => $user->id,
            'mentor_id' => $this->mentoring['mentor_id'] ?: null,
            'objective' => $this->mentoring['objective'] ?: '',
            'method' => $this->mentoring['method'] ?: '',
            'frequency' => $this->mentoring['frequency'] ?: 0,
            'duration' => $this->mentoring['duration'] ?: 0,
        ]);
    }

    private function saveProjectPlan($user, $isDraft = false)
    {
        if (!$isDraft) {
            $this->validate([
                'project.name' => 'required|string|max:255',
                'project.objective' => 'required|string',
                'project.mentor_id' => 'required|exists:users,id',
                'project.start_date' => 'required|date',
                'project.end_date' => 'required|date|after_or_equal:project.start_date',
            ]);
        }

        ProjectPlan::create([
            'user_id' => $user->id,
            'mentor_id' => $this->project['mentor_id'] ?: null,
            'objective' => $this->project['objective'] ?: '',
            'method' => $this->project['name'] ?: 'Draft',
            'frequency' => 1,
            'duration' => 1,
        ]);
    }

    public function render()
    {
        $user = auth()->user();

        // Get mentors (users with spv or leader role)
        $mentors = User::whereIn('role', ['spv', 'leader', 'admin'])
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
            ->toArray();

        return view('pages.development.development-plan', [
            'user' => $user,
            'mentors' => $mentors,
        ]);
    }
}
