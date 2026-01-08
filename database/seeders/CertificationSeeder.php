<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\CertificationAttendance;
use App\Models\CertificationModule;
use App\Models\CertificationParticipant;
use App\Models\CertificationPoint;
use App\Models\CertificationScore;
use App\Models\CertificationSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $modules = CertificationModule::all();
    $employees = User::whereIn('position', ['employee', 'supervisor'])->get();

    if ($modules->isEmpty() || $employees->isEmpty()) {
      $this->command->warn('Make sure CertificationModule and User seeders run first!');
      return;
    }

    // 10 Completed Certifications (with full data)
    $completedCertifications = [
      ['name' => 'Welding Inspector Batch 2024-Q1', 'participants_count' => 8, 'days_ago' => 30],
      ['name' => 'ISO 9001 Lead Auditor Program 2024', 'participants_count' => 10, 'days_ago' => 45],
      ['name' => 'Forklift Operator Training Batch 15', 'participants_count' => 6, 'days_ago' => 60],
      ['name' => 'Electrical Safety Professional 2024', 'participants_count' => 7, 'days_ago' => 25],
      ['name' => 'Six Sigma Green Belt Batch 8', 'participants_count' => 12, 'days_ago' => 50],
      ['name' => 'Quality Control Fundamentals Q1', 'participants_count' => 9, 'days_ago' => 20],
      ['name' => 'Lean Six Sigma Black Belt 2024', 'participants_count' => 8, 'days_ago' => 15],
      ['name' => 'Maintenance & Reliability Professional Training', 'participants_count' => 10, 'days_ago' => 35],
      ['name' => 'ISO Internal Auditor Certification Batch 5', 'participants_count' => 7, 'days_ago' => 10],
      ['name' => 'Project Management Professional 2024-Q4', 'participants_count' => 11, 'days_ago' => 40],
    ];

    // 10 Other Certifications (random: approved, rejected, scheduled, cancelled)
    $otherCertifications = [
      ['name' => 'Equipment Maintenance Training Q3', 'participants_count' => 9],
      ['name' => 'Advanced Welding Certification Q4', 'participants_count' => 6],
      ['name' => 'Safety Management Certification Q1', 'participants_count' => 8],
      ['name' => 'NEBOSH Safety Certificate Program', 'participants_count' => 10],
      ['name' => 'Equipment Analysis Certification', 'participants_count' => 7],
      ['name' => 'Quality Control Specialist Batch 3', 'participants_count' => 5],
      ['name' => 'Production Management Certification 2024', 'participants_count' => 8],
      ['name' => 'Equipment Maintenance Training Q2', 'participants_count' => 6],
      ['name' => 'Advanced CNC Operator Program', 'participants_count' => 7],
      ['name' => 'Industrial Safety Expert 2024', 'participants_count' => 9],
    ];

    // Create 10 Completed Certifications
    foreach ($completedCertifications as $certData) {
      $module = $modules->random();
      $createdAt = Carbon::now()->subDays($certData['days_ago'] + rand(5, 10));
      $approvedAt = Carbon::now()->subDays($certData['days_ago']);

      $certification = Certification::create([
        'module_id' => $module->id,
        'name' => $certData['name'],
        'status' => 'completed',
        'approved_at' => $approvedAt,
        'created_at' => $createdAt,
        'updated_at' => $approvedAt,
      ]);

      $this->createCertificationData($certification, $module, $employees, $certData['participants_count'], $createdAt, false);
    }

    // Create 10 Other Certifications (random status)
    $otherStatuses = ['approved', 'rejected', 'scheduled', 'cancelled'];

    foreach ($otherCertifications as $certData) {
      $module = $modules->random();
      $status = $otherStatuses[array_rand($otherStatuses)];
      $createdAt = Carbon::now()->subDays(rand(5, 60));
      $approvedAt = $status === 'approved' ? Carbon::parse($createdAt)->addDays(rand(5, 15)) : null;

      $certification = Certification::create([
        'module_id' => $module->id,
        'name' => $certData['name'],
        'status' => $status,
        'approved_at' => $approvedAt,
        'created_at' => $createdAt,
        'updated_at' => $approvedAt ?? $createdAt,
      ]);

      // For approved and scheduled: create sessions and participants
      if (in_array($status, ['approved', 'scheduled'])) {
        $isScheduled = $status === 'scheduled';
        $this->createCertificationData($certification, $module, $employees, $certData['participants_count'], $createdAt, $isScheduled);
      }
      // For rejected and cancelled: no sessions or participants
    }
  }

  /**
   * Create sessions, participants, attendance, and scores for a certification
   */
  private function createCertificationData($certification, $module, $employees, $participantsCount, $createdAt, $isScheduled = false): void
  {
    // Create sessions
    $theoryDate = $isScheduled
      ? Carbon::now()->addDays(rand(10, 30))
      : Carbon::parse($createdAt)->addDays(rand(3, 7));

    $theorySession = CertificationSession::create([
      'certification_id' => $certification->id,
      'type' => 'theory',
      'date' => $theoryDate,
      'start_time' => '08:00:00',
      'end_time' => '12:00:00',
      'location' => 'Training Room ' . rand(1, 5),
    ]);

    $practicalDate = Carbon::parse($theoryDate)->addDays(rand(7, 14));
    $practicalSession = CertificationSession::create([
      'certification_id' => $certification->id,
      'type' => 'practical',
      'date' => $practicalDate,
      'start_time' => '13:00:00',
      'end_time' => '17:00:00',
      'location' => 'Workshop Area ' . rand(1, 3),
    ]);

    // Create participants
    $selectedEmployees = $employees->random(min($participantsCount, $employees->count()));
    $theoryPassingScore = $module->theory_passing_score ?? 70;
    $practicalPassingScore = $module->practical_passing_score ?? 70;

    foreach ($selectedEmployees as $employee) {
      if ($isScheduled) {
        // Scheduled: no scores yet, pending status
        CertificationParticipant::create([
          'certification_id' => $certification->id,
          'employee_id' => $employee->id,
          'final_status' => 'pending',
          'assigned_at' => Carbon::now()->subDays(rand(1, 5)),
        ]);
      } else {
        // Completed/Approved: create scores in certification_scores
        $theoryScore = rand(1, 100) <= 75 ? rand((int) $theoryPassingScore, 100) : rand(40, (int) $theoryPassingScore - 1);
        $practicalScore = rand(1, 100) <= 75 ? rand((int) $practicalPassingScore, 100) : rand(45, (int) $practicalPassingScore - 1);

        $theoryPassed = $theoryScore >= $theoryPassingScore;
        $practicalPassed = $practicalScore >= $practicalPassingScore;
        $finalStatus = ($theoryPassed && $practicalPassed) ? 'passed' : 'failed';

        // Earned points: if passed, get from module's points_per_module
        $earnedPoints = $finalStatus === 'passed' ? ($module->points_per_module ?? 0) : 0;

        $participant = CertificationParticipant::create([
          'certification_id' => $certification->id,
          'employee_id' => $employee->id,
          'final_status' => $finalStatus,
          'earned_points' => $earnedPoints,
          'assigned_at' => Carbon::parse($createdAt)->subDays(rand(1, 5)),
        ]);

        // Create scores in certification_scores table
        CertificationScore::create([
          'participant_id' => $participant->id,
          'session_id' => $theorySession->id,
          'score' => $theoryScore,
          'status' => $theoryPassed ? 'passed' : 'failed',
          'recorded_at' => Carbon::parse($theorySession->date)->addHours(4),
        ]);

        CertificationScore::create([
          'participant_id' => $participant->id,
          'session_id' => $practicalSession->id,
          'score' => $practicalScore,
          'status' => $practicalPassed ? 'passed' : 'failed',
          'recorded_at' => Carbon::parse($practicalSession->date)->addHours(4),
        ]);

        // If passed, add earned points to certification_points table (accumulated)
        if ($finalStatus === 'passed' && $earnedPoints > 0) {
          $certPoint = CertificationPoint::getOrCreateForEmployee($employee->id);
          $certPoint->addPoints($earnedPoints);
        }

        // Attendance records
        CertificationAttendance::create([
          'participant_id' => $participant->id,
          'session_id' => $theorySession->id,
          'status' => 'present',
          'absence_notes' => null,
          'recorded_at' => Carbon::parse($theorySession->date)->setTimeFromTimeString($theorySession->start_time),
        ]);

        CertificationAttendance::create([
          'participant_id' => $participant->id,
          'session_id' => $practicalSession->id,
          'status' => 'present',
          'absence_notes' => null,
          'recorded_at' => Carbon::parse($practicalSession->date)->setTimeFromTimeString($practicalSession->start_time),
        ]);
      }
    }
  }
}
