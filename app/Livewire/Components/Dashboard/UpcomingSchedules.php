<?php

namespace App\Livewire\Components\Dashboard;

use App\Models\Certification;
use App\Models\Training;
use Carbon\Carbon;
use Livewire\Component;

class UpcomingSchedules extends Component
{
  public array $items = [];
  public ?int $employeeId = null; // Optional: filter by employee

  public function mount(?int $employeeId = null)
  {
    $this->employeeId = $employeeId;
    $this->loadUpcomingSchedules();
  }

  public function loadUpcomingSchedules()
  {
    $this->items = [];
    $now = Carbon::now();
    $todayStart = $now->copy()->startOfDay();

    // Get upcoming trainings (max 5)
    // Include trainings happening today (start_date can be midnight) and ongoing range trainings that overlap today.
    $trainingsQuery = Training::query()
      ->whereNotNull('start_date')
      ->where(function ($q) use ($todayStart) {
        $q->where('start_date', '>=', $todayStart)
          ->orWhere(function ($qq) use ($todayStart) {
            $qq->whereNotNull('end_date')
              ->where('start_date', '<', $todayStart)
              ->where('end_date', '>=', $todayStart);
          });
      });

    // If employeeId is provided, filter trainings where they are a participant
    if ($this->employeeId) {
      $trainingsQuery->whereHas('assessments', function ($q) {
        $q->where('employee_id', $this->employeeId);
      });
    }

    $upcomingTrainings = $trainingsQuery
      ->orderBy('start_date', 'asc')
      ->take(5)
      ->get();

    foreach ($upcomingTrainings as $training) {
      $effectiveDate = $training->start_date;

      // If training is ongoing today (range overlaps today), show today's date instead of the original start date.
      if (
        $training->start_date
        && $training->start_date->lt($todayStart)
        && $training->end_date
        && $training->end_date->gte($todayStart)
      ) {
        $effectiveDate = $todayStart;
      }

      $dateInfo = $effectiveDate
        ? $effectiveDate->format('d M Y')
        : 'No date';

      $this->items[] = [
        'title' => $training->name ?? 'Training',
        'info' => $dateInfo,
        'type' => 'training',
        'id' => $training->id,
        'date' => $effectiveDate,
      ];
    }

    // Get upcoming certifications (max 5)
    $certificationsQuery = Certification::where('status', 'scheduled')->with('sessions');

    // If employeeId is provided, filter certifications where they are a participant
    if ($this->employeeId) {
      $certificationsQuery->whereHas('participants', function ($q) {
        $q->where('employee_id', $this->employeeId);
      });
    }

    $upcomingCertifications = $certificationsQuery
      ->get()
      ->filter(function ($cert) use ($todayStart) {
        $firstSession = $cert->sessions->first();
        if (!$firstSession || empty($firstSession->date)) {
          return false;
        }
        $sessionDate = Carbon::parse($firstSession->date);
        return $sessionDate->startOfDay()->greaterThanOrEqualTo($todayStart);
      })
      ->take(5);

    foreach ($upcomingCertifications as $cert) {
      $firstSession = $cert->sessions->first();
      $sessionDate = $firstSession && $firstSession->date ? Carbon::parse($firstSession->date) : null;
      $dateInfo = $sessionDate ? $sessionDate->format('d M Y') : 'No date';

      $this->items[] = [
        'title' => $cert->name ?? 'Certification',
        'info' => $dateInfo,
        'type' => 'certification',
        'id' => $cert->id,
        'date' => $sessionDate,
      ];
    }

    // Sort by date and limit to 10 items
    $this->items = collect($this->items)
      ->sortBy('date')
      ->take(10)
      ->values()
      ->toArray();
  }

  public function gradient(string $type): string
  {
    return match ($type) {
      'training' => 'from-blue-400 to-blue-200',
      'certification' => 'from-orange-400 to-yellow-300',
      default => 'from-gray-300 to-gray-100',
    };
  }

  public function iconName(string $type): string
  {
    return match ($type) {
      'training' => 'o-academic-cap',
      'certification' => 'o-check-badge',
      default => 'o-calendar',
    };
  }

  public function getUrl(string $type): string
  {
    return match ($type) {
      'training' => route('training-schedule.index'),
      'certification' => route('certification-schedule.index'),
      default => '#',
    };
  }

  public function render()
  {
    return view('components.dashboard.upcoming-schedules');
  }
}
