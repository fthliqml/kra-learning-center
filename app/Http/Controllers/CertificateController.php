<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use App\Models\TrainingSurvey;
use App\Models\TrainingAssessment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    /**
     * View training certificate (inline in browser)
     */
    public function viewTrainingCertificate($assessmentId)
    {
        $assessment = TrainingAssessment::with(['training', 'employee'])->findOrFail($assessmentId);

        // Check if user is authorized to view
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Allow view if:
        // 1. User is the participant themselves
        // 2. User is in leadership position (section_head, dept_head, div_head, director)
        $canView = $user->id === $assessment->employee_id ||
            $user->hasAnyPosition(['section_head', 'department_head', 'division_head', 'director']);

        if (!$canView) {
            abort(403, 'You are not authorized to view this certificate');
        }

        // Gate: participants must complete Survey level 1 before viewing their own certificate
        if ($user->id === $assessment->employee_id) {
            $surveyId = TrainingSurvey::query()
                ->where('training_id', $assessment->training_id)
                ->where('level', 1)
                ->value('id');

            // If no Survey Level 1 is configured for this training, don't block certificate viewing.
            $isSurveyComplete = !$surveyId || SurveyResponse::query()
                ->where('survey_id', $surveyId)
                ->where('employee_id', $user->id)
                ->where('is_completed', true)
                ->exists();

            if (!$isSurveyComplete) {
                abort(403, 'Please complete Survey Level 1 before viewing your certificate.');
            }
        }

        // Check if certificate exists
        if (!$assessment->certificate_path || !Storage::exists($assessment->certificate_path)) {
            abort(404, 'Certificate not found');
        }

        // Display the certificate inline (view in browser)
        $fileName = 'Certificate_' . $assessment->employee->name . '_' . $assessment->training->name . '.pdf';
        $fileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $fileName); // Sanitize filename

        return response()->file(Storage::path($assessment->certificate_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ]);
    }
}
