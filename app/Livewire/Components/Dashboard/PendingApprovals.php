<?php

namespace App\Livewire\Components\Dashboard;

use App\Models\Certification;
use App\Models\Request;
use App\Models\Training;
use App\Models\TrainingPlan;
use App\Models\SelfLearningPlan;
use App\Models\MentoringPlan;
use App\Models\ProjectPlan;
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

    // Get pending trainings (done = waiting for approval)
    $pendingTrainings = Training::where('status', 'done')
      ->latest()
      ->take(5)
      ->get();

    foreach ($pendingTrainings as $training) {
      $dateInfo = $training->start_date
        ? $training->start_date->format('d M Y')
        : 'No date';

      $this->items[] = [
        'title' => $training->name ?? 'Training',
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

    // Get pending IDP (Individual Development Plan) approvals (status pending_lid)
    $pendingIdpPlans = collect()
      ->merge(TrainingPlan::with('user')->where('status', 'pending_lid')->latest()->take(5)->get())
      ->merge(SelfLearningPlan::with('user')->where('status', 'pending_lid')->latest()->take(5)->get())
      ->merge(MentoringPlan::with('user')->where('status', 'pending_lid')->latest()->take(5)->get())
      ->merge(ProjectPlan::with('user')->where('status', 'pending_lid')->latest()->take(5)->get());

    foreach ($pendingIdpPlans as $plan) {
      $this->items[] = [
        'title' => 'Development Plan',
        'info' => $plan->user->name ?? 'Unknown',
        'type' => 'idp',
        'id' => $plan->id,
      ];
    }

    // Sort by most recent and limit total
    $this->items = array_slice($this->items, 0, 10);
  }

  public function gradient(string $type): string
  {
    return match ($type) {
      'idp' => 'from-green-400 to-green-200',
      'training_request' => 'from-blue-500 to-blue-300',
      'training' => 'from-blue-400 to-blue-200',
      'certification' => 'from-orange-400 to-yellow-300',
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

  public function getUrl(string $type, int $id): string
  {
    return match ($type) {
      'idp' => route('development-approval.index'),
      'training_request' => route('training-request.index'),
      'training' => route('training-approval.index'),
      'certification' => route('certification-approval.index'),
      default => '#',
    };
  }

  public function render()
  {
    return view('components.dashboard.pending-approvals');
  }
}
