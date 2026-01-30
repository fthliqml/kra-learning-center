# Design: Email Reminder Survey Level 1

**Date:** 2026-01-30  
**Status:** Approved

## Overview

Ketika training di-close, sistem akan otomatis mengirim email ke semua employee yang terdaftar di training tersebut. Email berisi notifikasi bahwa Survey Level 1 sudah dibuka dan link untuk mengakses survey.

## Trigger Flow

**Trigger Point:**
- Lokasi: `TrainingCloseTab.php` → method `closeTraining()`
- Setelah: `$surveyService->createSurveysOnClose($this->training)` berhasil (line ~1003)
- Dispatch job untuk mengirim email secara asynchronous

```
[Admin/Instructor clicks "Close Training"]
        ↓
[TrainingCloseTab::closeTraining()]
        ↓
[TrainingSurveyService::createSurveysOnClose()]
        ↓ (surveys created successfully)
[Dispatch SendSurveyNotificationJob] ← NEW
        ↓ (async via queue)
[SendSurveyNotificationJob processes]
        ↓
[Loop through all training participants]
        ↓
[Send SurveyReminderMail to each employee]
```

## Components to Create

### 1. Job: `SendSurveyNotificationJob`
- **Path:** `app/Jobs/SendSurveyNotificationJob.php`
- **Implements:** `ShouldQueue`
- **Parameters:** `Training $training`, `TrainingSurvey $survey`
- **Logic:**
  - Get all employees from `TrainingAssessment` for this training
  - Filter employees yang sudah punya `SurveyResponse` record (untuk survey ini)
  - Loop dan dispatch `SurveyReminderMail` ke masing-masing employee

### 2. Mailable: `SurveyReminderMail`
- **Path:** `app/Mail/SurveyReminderMail.php`
- **Parameters:** `User $employee`, `Training $training`, `TrainingSurvey $survey`
- **Template:** Blade view dengan HTML styling

### 3. Email View: `survey-reminder.blade.php`
- **Path:** `resources/views/emails/survey-reminder.blade.php`
- **Content:**
  - Header dengan logo/nama sistem
  - Greeting: "Halo [Nama Employee]"
  - Info: Training name, tanggal pelaksanaan, nama trainer
  - CTA Button: Link ke survey
  - Footer: Info kontak atau note

### 4. New Directories
- `app/Jobs/` (new)
- `app/Mail/` (new)
- `resources/views/emails/` (new)

## Email Template Design

**Link Survey:**
- Format: `{APP_URL}/survey/1/take/{surveyId}`
- Employee harus login terlebih dahulu untuk mengakses

**Visual Layout:**
```
┌─────────────────────────────────────────────────┐
│                                                 │
│   🎓 KRA Learning Center                       │
│                                                 │
├─────────────────────────────────────────────────┤
│                                                 │
│   Halo [Nama Employee],                        │
│                                                 │
│   Survey Level 1 untuk training berikut        │
│   telah dibuka dan menunggu tanggapan Anda:    │
│                                                 │
│   ┌───────────────────────────────────────┐    │
│   │  📚 [Nama Training]                   │    │
│   │  📅 [Tanggal Training]                │    │
│   │  👨‍🏫 [Nama Trainer]                    │    │
│   └───────────────────────────────────────┘    │
│                                                 │
│   Mohon segera isi survey untuk memberikan     │
│   feedback terhadap training yang telah        │
│   Anda ikuti.                                  │
│                                                 │
│        ┌─────────────────────────────────┐     │
│        │   Isi Survey Sekarang           │     │
│        └─────────────────────────────────┘     │
│                                                 │
│   Terima kasih atas partisipasi Anda.          │
│                                                 │
├─────────────────────────────────────────────────┤
│   © 2026 KRA Learning Center                   │
│   Email ini dikirim otomatis, mohon tidak      │
│   membalas email ini.                          │
└─────────────────────────────────────────────────┘
```

**Styling:**
- Background: Soft gray (`#f5f5f5`)
- Card: White dengan subtle shadow
- Button: Brand color (biru/hijau sesuai aplikasi)
- Responsive: Mobile-friendly

## Integration Point

```php
// Di TrainingCloseTab.php::closeTraining()
// Setelah line: $surveyService->createSurveysOnClose($this->training);

// Get the Level 1 survey that was just created
$survey = TrainingSurvey::where('training_id', $this->training->id)
    ->where('level', 1)
    ->first();

if ($survey) {
    SendSurveyNotificationJob::dispatch($this->training, $survey);
}
```

## Error Handling

1. **Email gagal terkirim ke satu employee** → Log error, lanjut ke employee berikutnya
2. **Employee tidak punya email** → Skip dengan log warning
3. **Queue worker not running** → Email tetap tersimpan di queue, terkirim saat worker aktif

## Queue Configuration

- **Queue name:** `emails` (atau `default`)
- **Retry:** 3x dengan exponential backoff
- **Timeout:** 60 seconds per job

## Logging

- **Success:** Log jumlah email terkirim
- **Failure:** Log detail error per employee

## Exception: LMS Training

- LMS training TIDAK membuat survey (existing behavior)
- Jadi email juga TIDAK dikirim untuk LMS training (konsisten)

## Summary

| Aspek | Detail |
|-------|--------|
| **Trigger** | Button "Close Training" di modal detail training |
| **Timing** | Otomatis setelah survey Level 1 berhasil dibuat |
| **Recipients** | Semua employee yang terdaftar di training |
| **Delivery** | Asynchronous via Laravel Queue |
| **Link** | `/survey/1/take/{surveyId}` (butuh login) |
| **Email Style** | HTML styled, clean & professional |
| **Exception** | LMS training tidak mendapat email |

## Files to Create

1. `app/Jobs/SendSurveyNotificationJob.php`
2. `app/Mail/SurveyReminderMail.php`
3. `resources/views/emails/survey-reminder.blade.php`

## Files to Modify

1. `app/Livewire/Components/TrainingSchedule/Tabs/TrainingCloseTab.php`
