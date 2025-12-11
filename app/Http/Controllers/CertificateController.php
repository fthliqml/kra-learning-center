<?php

namespace App\Http\Controllers;

use App\Models\TrainingAssessment;
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
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Allow view if:
        // 1. User is the participant themselves
        // 2. User is in leadership position (section_head, dept_head, div_head, director)
        $canView = $user->id === $assessment->employee_id ||
            in_array(strtolower($user->role ?? ''), ['section_head', 'dept_head', 'div_head', 'director']);

        if (!$canView) {
            abort(403, 'You are not authorized to view this certificate');
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
