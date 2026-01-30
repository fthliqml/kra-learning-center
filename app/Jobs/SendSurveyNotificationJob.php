<?php

namespace App\Jobs;

use App\Mail\SurveyReminderMail;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Models\TrainingSurvey;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job to send survey reminder emails to training participants.
 * 
 * Dispatched when training is closed and Survey Level 1 is created.
 * Sends email to all employees registered in the training.
 */
class SendSurveyNotificationJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * The number of times the job may be attempted.
   */
  public int $tries = 3;

  /**
   * The number of seconds to wait before retrying.
   */
  public int $backoff = 60;

  /**
   * The training instance.
   */
  protected Training $training;

  /**
   * The survey instance.
   */
  protected TrainingSurvey $survey;

  /**
   * Create a new job instance.
   */
  public function __construct(Training $training, TrainingSurvey $survey)
  {
    $this->training = $training;
    $this->survey = $survey;
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $successCount = 0;
    $failCount = 0;

    // Get all employees registered in this training
    $employeeIds = TrainingAssessment::where('training_id', $this->training->id)
      ->pluck('employee_id')
      ->toArray();

    if (empty($employeeIds)) {
      Log::info("SendSurveyNotificationJob: No employees found for training ID {$this->training->id}");
      return;
    }

    $employees = User::whereIn('id', $employeeIds)->get();

    foreach ($employees as $employee) {
      try {
        // Skip if employee has no email
        if (empty($employee->email)) {
          Log::warning("SendSurveyNotificationJob: Employee {$employee->id} ({$employee->name}) has no email, skipping");
          $failCount++;
          continue;
        }

        // Send the email
        Mail::to($employee->email)->send(
          new SurveyReminderMail($employee, $this->training, $this->survey)
        );

        $successCount++;
        Log::info("SendSurveyNotificationJob: Email sent to {$employee->email} for training '{$this->training->name}'");

      } catch (\Throwable $e) {
        $failCount++;
        Log::error("SendSurveyNotificationJob: Failed to send email to {$employee->email}: " . $e->getMessage());
      }
    }

    Log::info("SendSurveyNotificationJob: Completed for training '{$this->training->name}'. Success: {$successCount}, Failed: {$failCount}");
  }
}
