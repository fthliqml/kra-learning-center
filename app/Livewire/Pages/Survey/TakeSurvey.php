<?php
namespace App\Livewire\Pages\Survey;

use Illuminate\Support\Facades\Auth;
use App\Models\SurveyResponse;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\TrainingSurvey;
use Livewire\Component;
use Mary\Traits\Toast;
use App\Services\SurveyAnswersValidator;

class TakeSurvey extends Component
{
    use Toast;

    protected $currentUser = null;
    public array $errorQuestionIndexes = [];

    public $surveyLevel = 1;
    public $surveyId = 1;
    public $questions = [];
    public $trainingName = '';
    public $answers = [];

    public function mount($level, $surveyId)
    {
        $this->surveyLevel = (int) $level;
        $this->surveyId = (int) $surveyId;
        $this->questions = SurveyQuestion::with('options')
            ->where('training_survey_id', $this->surveyId)
            ->orderBy('order')
            ->get();

        // Get training name from survey
        $survey = TrainingSurvey::with('training')->find($this->surveyId);
        $this->trainingName = $survey?->training?->name ?? '';

        // Initialize answers if already exist
        $this->currentUser = Auth::user();
        $this->hydrateAnswersFromDb();
    }

    /**
     * Hydrate $answers dari tabel survey_answers jika response sudah ada
     */
    protected function hydrateAnswersFromDb()
    {
        if (!$this->currentUser)
            return;
        $response = SurveyResponse::where('survey_id', $this->surveyId)
            ->where('employee_id', $this->currentUser->id)
            ->first();
        if (!$response)
            return;
        $answers = [];
        $answerRows = \App\Models\SurveyAnswer::where('response_id', $response->id)->get();
        foreach ($answerRows as $row) {
            if ($row->selected_option_id) {
                $answers[$row->question_id] = (string) $row->selected_option_id;
            } elseif ($row->essay_answer !== null) {
                $answers[$row->question_id] = $row->essay_answer;
            }
        }
        $this->answers = $answers;
    }

    /**
     * Dipanggil dari modal konfirmasi sebelum submit.
     */
    public function confirmAndSubmit()
    {
        $this->submit();
    }

    public function submit()
    {
        $this->currentUser = Auth::user();

        $validator = new SurveyAnswersValidator();
        $questionsArr = is_array($this->questions) ? $this->questions : $this->questions->toArray();
        $result = $validator->validate($questionsArr, $this->answers);
        $errors = $result['errors'];
        $this->errorQuestionIndexes = $result['errorQuestionIndexes'];
        if (!empty($errors)) {
            $bulletLines = collect($errors)->take(6)->map(fn($e) => '• ' . $e);
            $display = $bulletLines->implode("\n");
            if (count($errors) > 6) {
                $display .= "\n..." . (count($errors) - 6) . " more questions unanswered";
            }
            $htmlMessage = "<div style=\"white-space:pre-line; text-align:left\"><strong>Please answer all the following questions:</strong>\n" . e($display) . '</div>';
            $this->error(
                $htmlMessage,
                timeout: 10000,
                position: 'toast-top toast-center'
            );
            return;
        }

        // Reset error highlights on success
        $this->errorQuestionIndexes = [];

        // Save answers to SurveyResponse and SurveyAnswer
        if (!$this->currentUser) {
            $this->error('User not found.', timeout: 6000, position: 'toast-top toast-center');
            return;
        }
        $response = SurveyResponse::firstOrCreate([
            'survey_id' => $this->surveyId,
            'employee_id' => $this->currentUser->id,
        ]);
        $response->is_completed = true;
        $response->save();

        // Hapus jawaban lama
        SurveyAnswer::where('response_id', $response->id)->delete();

        // Simpan jawaban baru
        foreach ($this->questions as $q) {
            $qid = (string) $q->id;
            $ans = $this->answers[$qid] ?? null;
            if ($q->question_type === 'multiple') {
                SurveyAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $qid,
                    'selected_option_id' => is_numeric($ans) ? $ans : null,
                    'essay_answer' => null,
                ]);
            } else if ($q->question_type === 'essay') {
                SurveyAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $qid,
                    'selected_option_id' => null,
                    'essay_answer' => is_string($ans) ? $ans : null,
                ]);
            }
        }

        // After submit: if all expected employees have completed responses, mark survey as completed
        $surveyModel = TrainingSurvey::with('training.assessments')->find($this->surveyId);
        if ($surveyModel && $surveyModel->training) {
            $expectedCount = $surveyModel->training->assessments()->count();
            if ($expectedCount > 0) {
                $completedCount = SurveyResponse::where('survey_id', $surveyModel->id)
                    ->where('is_completed', true)
                    ->count();

                if ($completedCount >= $expectedCount) {
                    if ($surveyModel->status !== TrainingSurvey::STATUS_COMPLETED) {
                        $surveyModel->status = TrainingSurvey::STATUS_COMPLETED;
                        $surveyModel->save();
                    }
                } else {
                    // Optional: if was marked completed before but now not all complete, set back to incomplete
                    if ($surveyModel->status === TrainingSurvey::STATUS_COMPLETED) {
                        $surveyModel->status = TrainingSurvey::STATUS_INCOMPLETE;
                        $surveyModel->save();
                    }
                }
            }
        }

        $this->success(
            'Survey answers have been saved and submitted successfully',
            timeout: 4000,
            position: 'toast-top toast-center'
        );

        // Redirect back to Survey Employee list for this level
        return redirect()->route('survey.index', ['level' => $this->surveyLevel]);
    }

    public function render()
    {
        return view('pages.survey.take-survey', [
            'trainingName' => $this->trainingName,
        ]);
    }
}
