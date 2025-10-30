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

class SurveyPreview extends Component
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


    public function render()
    {
        return view('pages.survey.survey-preview', [
            'trainingName' => $this->trainingName,
            'questions' => $this->questions,
            'answers' => $this->answers,
            'surveyLevel' => $this->surveyLevel,
        ]);
    }
}
