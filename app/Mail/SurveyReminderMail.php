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

/**
 * Email sent to training participants when Survey Level 1 is ready.
 * 
 * This email is dispatched via queue when training is closed.
 */
class SurveyReminderMail extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * The employee receiving the email.
   */
  public User $employee;

  /**
   * The training that was closed.
   */
  public Training $training;

  /**
   * The survey to be filled.
   */
  public TrainingSurvey $survey;

  /**
   * The URL to access the survey.
   */
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
