<?php

namespace App\Http\Controllers;

use App\Models\TrainingAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    /**
     * Download training certificate
     */
    public function downloadTrainingCertificate($assessmentId)
    {
        $assessment = TrainingAssessment::with(['training', 'employee'])->findOrFail($assessmentId);

        // Check if user is authorized to download
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Allow download if:
        // 1. User is the participant themselves
        // 2. User is a leader
        $canDownload = $user->id === $assessment->employee_id ||
            strtolower($user->role ?? '') === 'leader';

        if (!$canDownload) {
            abort(403, 'You are not authorized to download this certificate');
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
