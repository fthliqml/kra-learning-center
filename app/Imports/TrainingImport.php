<?php

namespace App\Imports;

use App\Models\Training;
use App\Models\TrainingSession;
use App\Models\TrainingAssessment;
use App\Models\Course;
use App\Models\Trainer;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TrainingImport implements ToCollection, WithHeadingRow
{
  private array $errors = [];
  private int $rowNumberOffset = 2; // heading row = 1

  public function collection(Collection $rows)
  {
    DB::beginTransaction();
    try {
      foreach ($rows as $index => $row) {
        $excelRow = $index + $this->rowNumberOffset; // human row number
        // Skip completely empty rows
        if ($row->filter(fn($v) => trim((string) $v) !== '')->isEmpty()) {
          continue;
        }

        $type = strtoupper(trim((string) ($row['type'] ?? '')));
        $groupComp = trim((string) ($row['group_comp'] ?? ''));
        $startDate = trim((string) ($row['start_date'] ?? ''));
        $endDate = trim((string) ($row['end_date'] ?? ''));
        $participantsRaw = trim((string) ($row['participants'] ?? ''));
        $courseTitle = trim((string) ($row['course_title_k_learn'] ?? ''));
        $trainingName = trim((string) ($row['training_name'] ?? ''));
        $trainerName = trim((string) ($row['trainer_name'] ?? ''));
        $roomName = trim((string) ($row['room_name'] ?? ''));
        $roomLocation = trim((string) ($row['room_location'] ?? ''));
        $startTime = trim((string) ($row['start_time'] ?? ''));
        $endTime = trim((string) ($row['end_time'] ?? ''));

        // Basic required validations
        $rowErrors = [];
        if (!in_array($type, ['IN', 'OUT', 'K-LEARN'])) {
          $rowErrors[] = "Row {$excelRow}: Invalid Type '{$type}'";
        }
        if ($groupComp === '') {
          $rowErrors[] = "Row {$excelRow}: Group Comp required";
        }
        if ($startDate === '' || !$this->isValidDate($startDate)) {
          $rowErrors[] = "Row {$excelRow}: Start Date invalid";
        }
        if ($endDate === '' || !$this->isValidDate($endDate)) {
          $rowErrors[] = "Row {$excelRow}: End Date invalid";
        }
        // Participants required
        if ($participantsRaw === '') {
          $rowErrors[] = "Row {$excelRow}: Participants required";
        }

        $courseId = null;
        if ($type === 'K-LEARN') {
          if ($courseTitle === '') {
            $rowErrors[] = "Row {$excelRow}: Course Title (K-LEARN) required for K-LEARN";
          } else {
            $course = Course::where('title', $courseTitle)->first();
            if (!$course) {
              $rowErrors[] = "Row {$excelRow}: Course Title '{$courseTitle}' not found";
            } else {
              $courseId = $course->id;
            }
          }
          // Non K-Learn specific fields may be blank
        } else { // IN / OUT
          if ($trainingName === '') {
            $rowErrors[] = "Row {$excelRow}: Training Name required";
          }
          if ($trainerName === '') {
            $rowErrors[] = "Row {$excelRow}: Trainer Name required";
          }
          if ($roomName === '') {
            $rowErrors[] = "Row {$excelRow}: Room Name required";
          }
          if ($roomLocation === '') {
            $rowErrors[] = "Row {$excelRow}: Room Location required";
          }
          if ($startTime === '' || !$this->isValidTime($startTime)) {
            $rowErrors[] = "Row {$excelRow}: Start Time invalid (HH:MM)";
          }
          if ($endTime === '' || !$this->isValidTime($endTime)) {
            $rowErrors[] = "Row {$excelRow}: End Time invalid (HH:MM)";
          } elseif ($this->isValidTime($startTime) && $this->isValidTime($endTime) && !$this->isChronological($startTime, $endTime)) {
            $rowErrors[] = "Row {$excelRow}: End Time must be after Start Time";
          }
        }

        // If basic errors accumulated, store and continue (we will fail at end)
        if (!empty($rowErrors)) {
          $this->errors = array_merge($this->errors, $rowErrors);
          continue;
        }

        // Participants resolution (fail if any unknown)
        $participantNames = collect(preg_split('/,/', $participantsRaw))
          ->map(fn($n) => trim($n))
          ->filter();
        $users = User::whereIn('name', $participantNames)->get();
        if ($users->count() !== $participantNames->count()) {
          $missing = $participantNames->diff($users->pluck('name'));
          $this->errors[] = "Row {$excelRow}: Unknown participant(s): " . $missing->implode(', ');
          continue;
        }

        $trainerId = null;
        if ($type !== 'K-LEARN') {
          $trainer = Trainer::where('name', $trainerName)
            ->orWhereHas('user', fn($q) => $q->where('name', $trainerName))
            ->first();
          if (!$trainer) {
            $this->errors[] = "Row {$excelRow}: Trainer '{$trainerName}' not found";
            continue;
          }
          $trainerId = $trainer->id;
        }

        // All validations passed; create training
        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');
        $training = Training::create([
          'name' => $type === 'K-LEARN' ? ($courseTitle ?: 'K-Learn') : $trainingName,
          'type' => $type,
          'group_comp' => $groupComp,
          'start_date' => $start,
          'end_date' => $end,
          'course_id' => $type === 'K-LEARN' ? $courseId : null,
        ]);

        // Sessions
        $period = CarbonPeriod::create($start, $end);
        $day = 1;
        foreach ($period as $dateObj) {
          TrainingSession::create([
            'training_id' => $training->id,
            'day_number' => $day,
            'date' => $dateObj->format('Y-m-d'),
            'trainer_id' => $type === 'K-LEARN' ? null : $trainerId,
            'room_name' => $type === 'K-LEARN' ? ($roomName ?: null) : $roomName,
            'room_location' => $type === 'K-LEARN' ? ($roomLocation ?: null) : $roomLocation,
            'start_time' => $type === 'K-LEARN' ? null : $startTime,
            'end_time' => $type === 'K-LEARN' ? null : $endTime,
          ]);
          $day++;
        }

        // Participants (assessments)
        foreach ($users as $u) {
          TrainingAssessment::create([
            'training_id' => $training->id,
            'employee_id' => $u->id,
          ]);
        }
      }

      if (!empty($this->errors)) {
        throw ValidationException::withMessages(['import' => $this->errors]);
      }

      DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();
      if ($e instanceof ValidationException) {
        throw $e; // bubble up
      }
      throw ValidationException::withMessages(['import' => ['Unexpected import error: ' . $e->getMessage()]]);
    }
  }

  public function getErrors(): array
  {
    return $this->errors;
  }

  private function isValidDate(string $v): bool
  {
    try {
      Carbon::parse($v);
      return true;
    } catch (\Throwable $e) {
      return false;
    }
  }
  private function isValidTime(string $v): bool
  {
    return preg_match('/^\d{2}:\d{2}$/', $v) === 1;
  }
  private function isChronological(string $start, string $end): bool
  {
    return strtotime($end) > strtotime($start);
  }
}
