<?php

namespace App\Livewire;

use App\Models\Certification;
use App\Models\Request;
use App\Models\Training;
use Livewire\Component;

class PendingApprovals extends Component
{
  public array $items = [];

  public function mount()
  {
    $this->loadPendingItems();
  }

  public function loadPendingItems()
  {
    $this->items = [];

    // Get pending training requests
    $pendingRequests = Request::where('status', 'pending')
      ->latest()
      ->take(5)
      ->get();

    foreach ($pendingRequests as $request) {
      $this->items[] = [
        'title' => 'Training Request',
        'info' => $request->user->name ?? 'Unknown',
        'type' => 'training_request',
        'id' => $request->id,
      ];
    }

    // Get pending trainings
    $pendingTrainings = Training::where('status', 'pending')
      ->latest()
      ->take(5)
      ->get();

    foreach ($pendingTrainings as $training) {
      $dateInfo = $training->start_date
        ? $training->start_date->format('d M Y')
        : 'No date';

      $this->items[] = [
        'title' => $training->title ?? 'Training',
        'info' => $dateInfo,
        'type' => 'training',
        'id' => $training->id,
      ];
    }

    // Get pending certifications
    $pendingCertifications = Certification::where('status', 'pending')
      ->latest()
      ->take(5)
      ->get();

    foreach ($pendingCertifications as $cert) {
      $dateInfo = $cert->created_at
        ? $cert->created_at->format('d M Y')
        : 'No date';

      $this->items[] = [
        'title' => $cert->name ?? 'Certification',
        'info' => $dateInfo,
        'type' => 'certification',
        'id' => $cert->id,
      ];
    }

    // Sort by most recent and limit total
    $this->items = array_slice($this->items, 0, 10);
  }

  public function gradient(string $type): string
  {
    return match ($type) {
      'idp' => 'from-green-400 to-green-200',
      'training_request' => 'from-blue-400 to-blue-200',
      'training' => 'from-indigo-400 to-indigo-200',
      'certification' => 'from-amber-400 to-amber-200',
      default => 'from-gray-300 to-gray-100',
    };
  }

  public function iconName(string $type): string
  {
    return match ($type) {
      'idp' => 'o-clipboard-document-list',
      'training_request' => 'o-document-text',
      'training' => 'o-academic-cap',
      'certification' => 'o-check-badge',
      default => 'o-document',
    };
  }

  public function render()
  {
    return view('livewire.pending-approvals');
  }
}
