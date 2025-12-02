<?php

namespace App\Livewire\Components\Training\Tabs;

use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;

class TrainingCloseTab extends Component
{
    use Toast, WithPagination;

    public $trainingId;
    public $training;
    public $search = '';
    public $tempScores = []; // Temporary scores before saving    // Add validation rules for tempScores to validate on update
    protected function rules()
    {
        $rules = [];
        foreach ($this->tempScores as $id => $scores) {
            $rules["tempScores.{$id}.pretest_score"] = 'nullable|numeric|min:0|max:100';
            $rules["tempScores.{$id}.posttest_score"] = 'nullable|numeric|min:0|max:100';
            $rules["tempScores.{$id}.practical_score"] = 'nullable|numeric|min:0|max:100';
        }
        return $rules;
    }

    public function mount($trainingId)
    {
        $this->trainingId = $trainingId;
        $this->training = Training::with(['assessments.employee', 'sessions'])->find($trainingId);

        // Load existing scores into temp array
        $this->loadTempScores();
    }

    public function loadTempScores()
    {
        $assessments = TrainingAssessment::where('training_id', $this->trainingId)->get();
        foreach ($assessments as $assessment) {
            $this->tempScores[$assessment->id] = [
                'pretest_score' => $assessment->pretest_score,
                'posttest_score' => $assessment->posttest_score,
                'practical_score' => $assessment->practical_score,
            ];
        }
    }

    public function updated($property): void
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }
    public function headers()
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center'],
            ['key' => 'employee_name', 'label' => 'Employee Name', 'class' => 'min-w-[150px]'],
            ['key' => 'pretest_score', 'label' => 'Pretest Score', 'class' => '!text-center min-w-[120px]'],
            ['key' => 'posttest_score', 'label' => 'Posttest Score', 'class' => '!text-center min-w-[120px]'],
            ['key' => 'practical_score', 'label' => 'Practical Score', 'class' => '!text-center min-w-[120px]'],
            ['key' => 'average_score', 'label' => 'Average', 'class' => '!text-center'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center'],
        ];
    }

    public function assessments()
    {
        if (!$this->training) {
            return collect();
        }

        $query = TrainingAssessment::query()
            ->with('employee')
            ->where('training_id', $this->trainingId)
            ->when($this->search, function ($q) {
                $q->whereHas('employee', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'asc');

        $paginator = $query->paginate(10)->onEachSide(1);

        return $paginator->through(function ($assessment, $index) use ($paginator) {
            $start = $paginator->firstItem() ?? 0;
            $assessment->no = $start + $index;
            $assessment->employee_name = $assessment->employee->name ?? '-';

            // Ensure tempScores exists for this assessment
            if (!isset($this->tempScores[$assessment->id])) {
                $this->tempScores[$assessment->id] = [
                    'pretest_score' => $assessment->pretest_score,
                    'posttest_score' => $assessment->posttest_score,
                    'practical_score' => $assessment->practical_score,
                ];
            }

            // Use temp scores
            $pretestScore = $this->tempScores[$assessment->id]['pretest_score'];
            $posttestScore = $this->tempScores[$assessment->id]['posttest_score'];
            $practicalScore = $this->tempScores[$assessment->id]['practical_score'];

            // Attach temp scores to assessment for display
            $assessment->temp_pretest = $pretestScore;
            $assessment->temp_posttest = $posttestScore;
            $assessment->temp_practical = $practicalScore;

            // Calculate average score (only posttest and practical, exclude pretest) - 1 decimal place
            $scores = [];
            if ($posttestScore !== null && $posttestScore !== '') {
                $scores[] = (float) $posttestScore;
            }
            if ($practicalScore !== null && $practicalScore !== '') {
                $scores[] = (float) $practicalScore;
            }

            $assessment->average_score = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;

            // Calculate temp status
            if (count($scores) > 0) {
                $average = array_sum($scores) / count($scores);
                $assessment->temp_status = $average >= 60 ? 'passed' : 'failed';
            } else {
                $assessment->temp_status = $assessment->status ?? 'in_progress';
            }

            // Expose training done flag directly on assessment for blade scopes (avoid scope isolation issues)
            $assessment->training_done = strtolower($this->training->status ?? '') === 'done';

            return $assessment;
        });
    }

    public function saveDraft()
    {
        try {
            DB::transaction(function () {
                foreach ($this->tempScores as $assessmentId => $scores) {
                    $assessment = TrainingAssessment::find($assessmentId);
                    if (!$assessment)
                        continue;

                    $assessment->pretest_score = $scores['pretest_score'];
                    $assessment->posttest_score = $scores['posttest_score'];
                    $assessment->practical_score = $scores['practical_score'];

                    // Auto-determine status based on average score (only posttest and practical)
                    // Treat empty/null as 0
                    $posttest = (float) ($assessment->posttest_score ?? 0);
                    $practical = (float) ($assessment->practical_score ?? 0);
                    $average = ($posttest + $practical) / 2;

                    if ($average > 0) {
                        $assessment->status = $average >= 60 ? 'passed' : 'failed';
                    } else {
                        $assessment->status = 'in_progress';
                    }

                    $assessment->save();
                }
            });

            $this->success('Draft saved successfully.', position: 'toast-top toast-center');
        } catch (\Throwable $e) {
            $this->error('Failed to save draft.', position: 'toast-top toast-center');
        }
    }

    public function closeTraining()
    {
        if (!$this->training) {
            $this->error('Training not found.', position: 'toast-top toast-center');
            return;
        }

        // Check if training type is LMS
        $typeUpper = strtoupper($this->training->type ?? '');
        if ($typeUpper === 'LMS') {
            $this->error('LMS trainings cannot be closed manually.', position: 'toast-top toast-center');
            return;
        }

        // Check if all attendance records are filled
        $sessionsWithMissingAttendance = [];
        $sessions = $this->training->sessions()->with('attendances')->get();

        // Get all unique employees from training assessments
        $totalEmployees = TrainingAssessment::where('training_id', $this->trainingId)->count();

        foreach ($sessions as $index => $session) {
            // Count only present and absent status, exclude pending
            $filledAttendances = $session->attendances()
                ->whereIn('status', ['present', 'absent'])
                ->count();

            if ($filledAttendances < $totalEmployees) {
                $sessionsWithMissingAttendance[] = 'Day ' . ($index + 1);
            }
        }

        if (!empty($sessionsWithMissingAttendance)) {
            $this->error('All attendance records must be completed before closing the training. Missing attendance on: ' . implode(', ', $sessionsWithMissingAttendance), position: 'toast-top toast-center');
            return;
        }

        // Check if all assessments have both posttest and practical scores filled
        $allHaveScores = true;

        foreach ($this->tempScores as $assessmentId => $scores) {
            $posttest = $scores['posttest_score'];
            $practical = $scores['practical_score'];

            if ($posttest === null || $posttest === '' || $practical === null || $practical === '') {
                $allHaveScores = false;
                break;
            }
        }

        if (!$allHaveScores) {
            $this->error('All employees must have both posttest and practical scores completed before closing the training.', position: 'toast-top toast-center');
            return;
        }

        try {
            DB::transaction(function () {
                // Save all temp scores to database
                foreach ($this->tempScores as $assessmentId => $scores) {
                    $assessment = TrainingAssessment::find($assessmentId);
                    if (!$assessment)
                        continue;

                    $assessment->pretest_score = $scores['pretest_score'];
                    $assessment->posttest_score = $scores['posttest_score'];
                    $assessment->practical_score = $scores['practical_score'];

                    // Calculate status based on average (only posttest and practical)
                    // Both must be filled as per validation, no need to check for null
                    $posttest = (float) $assessment->posttest_score;
                    $practical = (float) $assessment->practical_score;
                    $average = ($posttest + $practical) / 2;

                    $assessment->status = $average >= 60 ? 'passed' : 'failed';

                    $assessment->save();
                }

                // Update training status to done
                $this->training->status = 'done';
                $this->training->save();
            });

            $this->success('Training has been closed successfully.', position: 'toast-top toast-center');
            $this->dispatch('training-closed', ['id' => $this->training->id]);
            $this->dispatch('close-modal');
        } catch (\Throwable $e) {
            $this->error('Failed to close training.', position: 'toast-top toast-center');
        }
    }

    public function placeholder()
    {
        return view('components.skeletons.training-close-tab');
    }

    public function render()
    {
        return view('components.training.tabs.training-close-tab', [
            'assessments' => $this->assessments(),
            'headers' => $this->headers(),
            'training' => $this->training,
        ]);
    }
}
