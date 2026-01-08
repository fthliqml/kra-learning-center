<?php

namespace App\Services;

use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\User;
use App\Models\CompetencyMatrix;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TrainingCertificateService
{
    /**
     * Generate certificate for a passed participant
     *
     * @param Training $training
     * @param User $employee
     * @param TrainingAssessment $assessment
     * @return string|null Certificate path on success, null on failure
     */
    public function generateCertificate(Training $training, User $employee, TrainingAssessment $assessment): ?string
    {
        try {
            // Check if participant passed
            if ($assessment->status !== 'passed') {
                return null;
            }

            // Template paths (PNG)
            $template1Path = storage_path('app/private/template/Template-Certificate-1.png');
            $template2Path = storage_path('app/private/template/Template-Certificate-2.png');

            if (!file_exists($template1Path) || !file_exists($template2Path)) {
                Log::error("Certificate template not found");
                return null;
            }

            // Create FPDF instance
            $pdf = new \FPDF('L', 'mm', 'A4');

            // === PAGE 1: Certificate ===
            $pdf->AddPage();
            // Add background image (full page)
            $pdf->Image($template1Path, 0, 0, 297, 210);

            // Add certificate number (top right, after "NOMOR SERTIFIKAT")
            $groupComp = $training->group_comp ?? 'BMC';
            $certificateNumber = $groupComp . '/C/' . date('Y') . '/' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT);
            $pdf->SetFont('Times', 'I', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(245.5, 13);
            $pdf->Cell(45, 5, $certificateNumber, 0, 0, 'L');

            // Add participant name (after "Nama/Name :")
            $pdf->SetFont('Times', 'B', 14);
            $pdf->SetXY(156, 85);
            $pdf->Cell(120, 6, strtoupper($employee->name ?? '-'), 0, 0, 'L');

            // Add training name (after "Telah Mengikuti/Had joined :")
            $pdf->SetXY(156, 104.5);
            $pdf->Cell(120, 6, strtoupper($training->name ?? '-'), 0, 0, 'L');

            // Add period dates (after "Periode/Period :")
            $startDateRaw = $training->start_date ? Carbon::parse($training->start_date) : null;
            $endDateRaw = $training->end_date ? Carbon::parse($training->end_date) : null;

            $startDate = $startDateRaw ? $startDateRaw->format('d M Y') : '-';
            $endDate = $endDateRaw ? $endDateRaw->format('d M Y') : '-';

            // If training only 1 day (start == end), don't show "s.d."
            if ($startDateRaw && $endDateRaw && $startDateRaw->isSameDay($endDateRaw)) {
                $periodText = $startDate;
            } else {
                $periodText = $startDate . ' s.d. ' . $endDate;
            }
            $pdf->SetXY(156, 123.3);
            $pdf->Cell(120, 6, $periodText, 0, 0, 'L');

            // Add issue location and date (Balikpapan, dd Month YYYY) - right side
            $issueDate = Carbon::now()->format('d F Y');
            $pdf->SetFont('Times', '', 11);
            $pdf->SetXY(172, 150);
            $pdf->Cell(95, 5, 'Balikpapan, ' . $issueDate, 0, 0, 'R');

            // Add LID Section Head & Dept Head signatures/names on first page
            $lidSectionHead = $this->getLidSectionHead();
            $lidDeptHead = $this->getLidDepartmentHead();

            // Dept Head area LID
            if ($lidDeptHead) {
                $pdf->SetFont('Times', 'BU', 10);
                $pdf->SetXY(79.5, 183);
                $pdf->Cell(70, 5, $lidDeptHead->name, 0, 0, 'C');
            }

            // Section Head LID
            if ($lidSectionHead) {
                $pdf->SetFont('Times', 'BU', 10);
                $pdf->SetXY(207.5, 183);
                $pdf->Cell(70, 5, $lidSectionHead->name, 0, 0, 'C');
            }

            // === PAGE 2: Daftar Nilai ===
            $pdf->AddPage();
            // Add background image (full page)
            $pdf->Image($template2Path, 0, 0, 297, 210);

            // Add training material name (Materi Training column)
            $pdf->SetFont('Times', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(40, 79);
            $pdf->Cell(94, 20, $training->name ?? '', 0, 0, 'C');

            // Add theory score in words (Teori - Huruf column)
            if ($assessment->posttest_score !== null) {
                $theoryWords = $this->getScoreInWords($assessment->posttest_score);
                $pdf->SetFont('Times', 'I', 11);
                $pdf->SetXY(136.5, 79);
                $pdf->Cell(43, 20, $theoryWords, 0, 0, 'C');
            }

            // Add theory score number (Teori - Angka column)
            if ($assessment->posttest_score !== null) {
                $pdf->SetFont('Times', '', 11);
                $pdf->SetXY(180.2, 79);
                $pdf->Cell(20, 20, number_format($assessment->posttest_score, 0), 0, 0, 'C');
            }

            // Add practical score grade letter (Praktik - Angka column)
            if ($assessment->practical_score !== null) {
                $practicalGrade = $this->getGrade($assessment->practical_score);
                $pdf->SetXY(200, 79);
                $pdf->Cell(20, 20, $practicalGrade, 0, 0, 'C');
            }

            // Add practical score range (Praktik - Range Angka column)
            if ($assessment->practical_score !== null) {
                $rangeText = $this->getScoreRange($assessment->practical_score);
                $pdf->SetXY(225, 79);
                $pdf->Cell(30, 20, $rangeText, 0, 0, 'C');
            }

            // Add average score (Nilai Rata-Rata Peserta)
            $avgScore = $this->calculateAverageScore($assessment);
            $pdf->SetXY(180.2, 133);
            // Tampilkan dengan 2 desimal (tanpa pembulatan ke bilangan bulat)
            $pdf->Cell(20, 6, number_format($avgScore, 2), 0, 0, 'C');

            // Add class average (Nilai Rata-Rata Kelas)
            $classAvg = $this->calculateClassAverage($training);
            $pdf->SetXY(180.2, 142.7);
            $pdf->Cell(20, 6, number_format($classAvg, 2), 0, 0, 'C');

            // Add instructor name (bottom right)
            $firstSessionWithTrainer = $training->sessions()
                ->whereHas('trainer')
                ->with(['trainer.user'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->first();

            $instructorName = 'Instructor';
            if ($firstSessionWithTrainer && $firstSessionWithTrainer->trainer) {
                $instructorName = $firstSessionWithTrainer->trainer->name
                    ?? ($firstSessionWithTrainer->trainer->user->name ?? 'Instructor');
            }
            $pdf->SetFont('Times', 'BU', 10);
            $pdf->SetXY(201.6, 180);
            $pdf->Cell(60, 5, $instructorName, 0, 0, 'C');

            // Generate unique filename
            $fileName = 'certificate_' . $training->id . '_' . $employee->id . '_' . time() . '.pdf';
            $filePath = 'certificates/' . $fileName;

            // Save to storage
            $pdfContent = $pdf->Output('S'); // Output as string
            Storage::put($filePath, $pdfContent);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Certificate generation failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return null;
        }
    }

    /**
     * Calculate average score from assessment (Nilai Rata-Rata Peserta)
     *
     * Rumus:
     *   (posttest_score + max_practical_score) / 2
     * di mana max_practical_score adalah batas atas range nilai praktik
     * (A:100, B:90, C:80, D:70, E:60).
     */
    protected function calculateAverageScore(TrainingAssessment $assessment): float
    {
        $posttest = $assessment->posttest_score;
        $practical = $assessment->practical_score;

        // Jika tidak ada kedua-duanya
        if ($posttest === null && $practical === null) {
            return 0;
        }

        // Jika hanya ada posttest
        if ($posttest !== null && $practical === null) {
            return (float) $posttest;
        }

        // Jika hanya ada praktik
        if ($posttest === null && $practical !== null) {
            $maxPracticalOnly = $this->getMaxPracticalFromScore($practical);
            return (float) $maxPracticalOnly;
        }

        // Keduanya ada: gunakan rumus (posttest + max_practical) / 2
        $maxPractical = $this->getMaxPracticalFromScore($practical ?? 0.0);
        if ($maxPractical <= 0) {
            return 0;
        }

        return ($posttest + $maxPractical) / 2.0;
    }

    /**
     * Get maximum practical score upper-bound based on numeric practical score.
     *
     * Mapping mengikuti range di getScoreRange():
     *  - >= 90 => 100
     *  - >= 81 => 90
     *  - >= 71 => 80
     *  - >= 61 => 70
     *  - else  => 60
     */
    protected function getMaxPracticalFromScore(float $score): float
    {
        if ($score >= 90) return 100;
        if ($score >= 81) return 90;
        if ($score >= 71) return 80;
        if ($score >= 61) return 70;
        return 60;
    }

    /**
     * Calculate true class average for a training.
     *
     * Rata-rata diambil dari seluruh assessment pada training tersebut
     * yang memiliki minimal salah satu nilai (posttest atau praktik),
     * dengan nilai per peserta dihitung via calculateAverageScore().
     */
    protected function calculateClassAverage(Training $training): float
    {
        $assessments = TrainingAssessment::where('training_id', $training->id)->get();

        $sum = 0.0;
        $count = 0;

        foreach ($assessments as $assessment) {
            // Lewati peserta yang sama sekali belum punya nilai
            if ($assessment->posttest_score === null && $assessment->practical_score === null) {
                continue;
            }

            $avg = $this->calculateAverageScore($assessment);
            $sum += $avg;
            $count++;
        }

        return $count > 0 ? $sum / $count : 0.0;
    }

    /**
     * Get LID Section Head user (section = LID, position = section_head)
     */
    protected function getLidSectionHead(): ?User
    {
        return User::where('position', 'section_head')
            ->whereRaw('UPPER(COALESCE(section, "")) = ?', ['LID'])
            ->first();
    }

    /**
     * Get Department Head for LID area
     *
     * Assumes department name "Human Capital, General Service, Security & LID"
     * as seeded in UserSeeder.
     */
    protected function getLidDepartmentHead(): ?User
    {
        return User::where('position', 'department_head')
            ->where('department', 'Human Capital, General Service, Security & LID')
            ->first();
    }

    /**
     * Get grade letter from score
     */
    protected function getGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 81) return 'B';
        if ($score >= 71) return 'C';
        if ($score >= 61) return 'D';
        return 'E';
    }

    /**
     * Get score range text
     */
    protected function getScoreRange(float $score): string
    {
        if ($score >= 90) return '90-100';
        if ($score >= 81) return '81-90';
        if ($score >= 71) return '71-80';
        if ($score >= 61) return '61-70';
        return '0-60';
    }

    /**
     * Get score in Indonesian words
     */
    protected function getScoreInWords(float $score): string
    {
        $words = [
            0 => 'Nol',
            1 => 'Satu',
            2 => 'Dua',
            3 => 'Tiga',
            4 => 'Empat',
            5 => 'Lima',
            6 => 'Enam',
            7 => 'Tujuh',
            8 => 'Delapan',
            9 => 'Sembilan',
            10 => 'Sepuluh',
            11 => 'Sebelas'
        ];

        $score = (int) $score;

        if ($score <= 11) {
            return $words[$score];
        } elseif ($score < 20) {
            return $words[$score - 10] . ' Belas';
        } elseif ($score < 100) {
            $tens = (int) ($score / 10);
            $units = $score % 10;
            $result = $words[$tens] . ' Puluh';
            if ($units > 0) {
                $result .= ' ' . $words[$units];
            }
            return $result;
        } elseif ($score == 100) {
            return 'Seratus';
        }

        return (string) $score;
    }

    /**
     * Generate certificates for all passed participants
     *
     * @param Training $training
     * @return int Number of certificates generated
     */
    public function generateCertificatesForTraining(Training $training): int
    {
        $count = 0;

        // Resolve competency for this training (supports direct, module-based, and LMS/course-based mapping)
        $competencyId = $this->resolveCompetencyId($training);

        // Get all passed participants
        $passedAssessments = TrainingAssessment::where('training_id', $training->id)
            ->where('status', 'passed')
            ->whereNull('certificate_path') // Only generate if not already generated
            ->with('employee')
            ->get();

        foreach ($passedAssessments as $assessment) {
            $employee = $assessment->employee;

            if (!$employee) {
                Log::warning("Assessment ID {$assessment->id} has no employee, skipping");
                continue;
            }

            $certificatePath = $this->generateCertificate($training, $employee, $assessment);

            if ($certificatePath) {
                // Update assessment with certificate path
                $assessment->update([
                    'certificate_path' => $certificatePath
                ]);

                // Ensure competency matrix entry exists for this employee & competency
                if ($competencyId && $employee) {
                    CompetencyMatrix::firstOrCreate([
                        'competency_id' => $competencyId,
                        'employees_trained_id' => $employee->id,
                    ]);
                }
                $count++;
            } else {
                Log::error("Failed to generate certificate for Assessment ID {$assessment->id}");
            }
        }

        return $count;
    }

    /**
     * Resolve competency_id for a training, supporting direct, module, and course mapping.
     */
    protected function resolveCompetencyId(Training $training): ?int
    {
        if (!empty($training->competency_id)) {
            return $training->competency_id;
        }

        // In-house or unspecified type: prefer module competency
        $type = strtoupper((string) ($training->type ?? ''));

        if ($type === 'IN' || $type === '') {
            if ($training->module && !empty($training->module->competency_id)) {
                return $training->module->competency_id;
            }
        }

        // LMS type: prefer course competency
        if ($type === 'LMS' || $type === '') {
            if ($training->course && !empty($training->course->competency_id)) {
                return $training->course->competency_id;
            }
        }

        return null;
    }

    /**
     * Delete certificate file
     *
     * @param string $certificatePath
     * @return bool
     */
    public function deleteCertificate(string $certificatePath): bool
    {
        if (Storage::exists($certificatePath)) {
            return Storage::delete($certificatePath);
        }
        return false;
    }
}
