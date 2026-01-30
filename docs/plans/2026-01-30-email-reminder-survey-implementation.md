# Email Reminder Survey Level 1 - Implementation Plan

> **REQUIRED SUB-WORKFLOW:** Use `/execute-plan` workflow to implement this plan task-by-task.

**Goal:** Automatically send email notifications to training participants when training is closed, informing them that Survey Level 1 is ready to be filled.

**Architecture:** Create a queued job that dispatches after survey creation. The job loops through all training participants and sends a styled HTML email with a direct link to the survey.

**Tech Stack:** Laravel Mail, Laravel Queue, Blade templates for email

---

## Task 1: Create Mailable Class

**Files:**
- Create: `app/Mail/SurveyReminderMail.php`

#### Step 1: Create the Mail directory and Mailable class

Create file `app/Mail/SurveyReminderMail.php`:

```php
<?php

namespace App\Mail;

use App\Models\Training;
use App\Models\TrainingSurvey;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $employee;
    public Training $training;
    public TrainingSurvey $survey;
    public string $surveyUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $employee, Training $training, TrainingSurvey $survey)
    {
        $this->employee = $employee;
        $this->training = $training;
        $this->survey = $survey;
        $this->surveyUrl = url("/survey/1/take/{$survey->id}");
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Survey Level 1 - ' . $this->training->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.survey-reminder',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
```

#### Step 2: Verify file created correctly

Run: `php artisan tinker --execute="echo class_exists('App\Mail\SurveyReminderMail') ? 'OK' : 'FAIL';"`

Expected: `OK`

#### Step 3: Commit

```bash
git add app/Mail/SurveyReminderMail.php
git commit -m "feat: add SurveyReminderMail mailable class"
```

---

## Task 2: Create Email Blade Template

**Files:**
- Create: `resources/views/emails/survey-reminder.blade.php`

#### Step 1: Create the emails directory and template

Create file `resources/views/emails/survey-reminder.blade.php`:

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Level 1</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 32px 40px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                🎓 KRA Learning Center
                            </h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Halo <strong>{{ $employee->name }}</strong>,
                            </p>

                            <p style="margin: 0 0 24px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Survey Level 1 untuk training berikut telah dibuka dan menunggu tanggapan Anda:
                            </p>

                            {{-- Training Info Card --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; border-left: 4px solid #3b82f6; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #6b7280;">
                                            📚 <strong style="color: #1f2937;">Training</strong>
                                        </p>
                                        <p style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1e3a8a;">
                                            {{ $training->name }}
                                        </p>

                                        <p style="margin: 0 0 8px 0; font-size: 14px; color: #6b7280;">
                                            📅 <strong>Tanggal:</strong>
                                            @if($training->start_date && $training->end_date)
                                                {{ $training->start_date->format('d M Y') }}
                                                @if($training->start_date->ne($training->end_date))
                                                    - {{ $training->end_date->format('d M Y') }}
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </p>

                                        @php
                                            $trainerNames = $training->sessions()
                                                ->with('trainer.user')
                                                ->get()
                                                ->pluck('trainer')
                                                ->filter()
                                                ->map(fn($t) => $t->name ?? $t->user?->name)
                                                ->filter()
                                                ->unique()
                                                ->values()
                                                ->implode(', ');
                                        @endphp
                                        @if($trainerNames)
                                        <p style="margin: 0; font-size: 14px; color: #6b7280;">
                                            👨‍🏫 <strong>Trainer:</strong> {{ $trainerNames }}
                                        </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 32px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Mohon segera isi survey untuk memberikan feedback terhadap training yang telah Anda ikuti.
                            </p>

                            {{-- CTA Button --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 8px; background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);">
                                        <a href="{{ $surveyUrl }}" target="_blank" style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">
                                            Isi Survey Sekarang
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 32px 0 0 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Terima kasih atas partisipasi Anda.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; text-align: center;">
                                © {{ date('Y') }} KRA Learning Center
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #9ca3af; text-align: center;">
                                Email ini dikirim secara otomatis, mohon tidak membalas email ini.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
```

#### Step 2: Verify blade template exists

Run: `ls -la resources/views/emails/survey-reminder.blade.php`

Expected: File exists with content

#### Step 3: Commit

```bash
git add resources/views/emails/survey-reminder.blade.php
git commit -m "feat: add email template for survey reminder"
```

---

## Task 3: Create Queued Job

**Files:**
- Create: `app/Jobs/SendSurveyNotificationJob.php`

#### Step 1: Create the Jobs directory and job class

Create file `app/Jobs/SendSurveyNotificationJob.php`:

```php
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
```

#### Step 2: Verify job class is valid

Run: `php artisan tinker --execute="echo class_exists('App\Jobs\SendSurveyNotificationJob') ? 'OK' : 'FAIL';"`

Expected: `OK`

#### Step 3: Commit

```bash
git add app/Jobs/SendSurveyNotificationJob.php
git commit -m "feat: add queued job for sending survey notification emails"
```

---

## Task 4: Integrate Job Dispatch into Training Close Flow

**Files:**
- Modify: `app/Livewire/Components/TrainingSchedule/Tabs/TrainingCloseTab.php` (around line 1000-1010)

#### Step 1: Add import statements at top of file

Open `app/Livewire/Components/TrainingSchedule/Tabs/TrainingCloseTab.php` and add these imports after the existing imports (around line 5-12):

```php
use App\Jobs\SendSurveyNotificationJob;
use App\Models\TrainingSurvey;
```

#### Step 2: Modify closeTraining() method to dispatch job

Find the section in `closeTraining()` method around line 1000-1005 that looks like:

```php
// Auto-create Level 1 and Level 3 surveys with default templates (except LMS - no surveys)
if (!$isLms) {
    $surveyService = new TrainingSurveyService();
    $surveyService->createSurveysOnClose($this->training);
}
```

Replace it with:

```php
// Auto-create Level 1 and Level 3 surveys with default templates (except LMS - no surveys)
if (!$isLms) {
    $surveyService = new TrainingSurveyService();
    $surveyService->createSurveysOnClose($this->training);

    // Dispatch email notification for Survey Level 1
    $level1Survey = TrainingSurvey::where('training_id', $this->training->id)
        ->where('level', 1)
        ->first();

    if ($level1Survey) {
        SendSurveyNotificationJob::dispatch($this->training, $level1Survey);
    }
}
```

#### Step 3: Verify syntax is correct

Run: `php -l app/Livewire/Components/TrainingSchedule/Tabs/TrainingCloseTab.php`

Expected: `No syntax errors detected`

#### Step 4: Commit

```bash
git add app/Livewire/Components/TrainingSchedule/Tabs/TrainingCloseTab.php
git commit -m "feat: dispatch email notification job when training is closed"
```

---

## Task 5: Manual Testing

**Prerequisites:**
- Queue worker running OR use sync driver for testing
- Valid MAIL_* configuration in `.env`

#### Step 1: Configure mail for testing (optional - use log driver for safe testing)

Check current mail driver:

```bash
grep "^MAIL_MAILER" .env
```

For testing with log driver (emails written to `storage/logs/laravel.log`):
- Temporarily set `MAIL_MAILER=log` in `.env`

#### Step 2: Test the flow

1. Login as admin or instructor
2. Open Training Schedule
3. Click on a training that is ready to close (has participants, scores filled)
4. Click "Close Training"
5. Check logs for email sending:

```bash
grep "SendSurveyNotificationJob" storage/logs/laravel.log | tail -20
```

Expected: Log entries showing emails sent or logged

#### Step 3: Verify email content (if using log driver)

```bash
grep -A 50 "survey-reminder" storage/logs/laravel.log | tail -60
```

Expected: HTML email content in logs

#### Step 4: Final commit (if any fixes needed)

```bash
git add -A
git commit -m "chore: finalize email reminder feature"
```

---

## Summary

| Task | File | Action |
|------|------|--------|
| 1 | `app/Mail/SurveyReminderMail.php` | Create Mailable class |
| 2 | `resources/views/emails/survey-reminder.blade.php` | Create email template |
| 3 | `app/Jobs/SendSurveyNotificationJob.php` | Create queued job |
| 4 | `app/Livewire/Components/TrainingSchedule/Tabs/TrainingCloseTab.php` | Add job dispatch |
| 5 | Manual testing | Verify end-to-end |

**Total estimated time:** 15-25 minutes
