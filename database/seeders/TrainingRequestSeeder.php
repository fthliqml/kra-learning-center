<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\Request as TrainingRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingRequestSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Only SPV (supervisor position) can create training requests
    $spv = User::where('position', 'supervisor')->first() ?: User::first();

    // Guard: if no users exist, bail out gracefully
    if (!$spv) {
      return;
    }

    // Resolve target users for the requests
    $employee = User::where('position', 'employee')->first() ?: $spv;
    $instructor = User::whereHas('userRoles', fn($q) => $q->where('role', 'instructor'))->first() ?: $spv;
    $leader = User::where('position', 'section_head')->first() ?: $spv;

    // Get competencies from database or create them with proper code
    $competencyData = [
      'Safety' => 'BMC',
      'Welding' => 'BC',
      'Leadership' => 'LC',
      'Quality' => 'MMP',
      'Maintenance' => 'BC',
      'Production' => 'MMP',
      'Logistics' => 'MMP',
      'IT' => 'TOC',
      'HR' => 'MDP',
      'Finance' => 'MDP',
    ];
    $competencies = [];

    foreach ($competencyData as $name => $type) {
      $comp = Competency::where('name', $name)->first();
      if (!$comp) {
        $comp = Competency::create([
          'code' => Competency::generateCode($type),
          'name' => $name,
          'type' => $type,
          'description' => $name . ' competency',
        ]);
      }
      $competencies[$name] = $comp->id;
    }

    // If still no competencies, bail out
    if (empty($competencies)) {
      return;
    }    // Helper function to get competency_id by name (or first available)
    $getCompetencyId = function ($name) use ($competencies) {
      return $competencies[$name] ?? reset($competencies);
    };

    $rows = [
      [
        'user_id' => $employee->id,
        'competency_id' => $getCompetencyId('Safety'),
        'reason' => 'Improve safety awareness on site',
        'status' => TrainingRequest::STATUS_PENDING,
      ],
      [
        'user_id' => $instructor->id,
        'competency_id' => $getCompetencyId('Welding'),
        'reason' => 'Upskill welding techniques for production line',
        'status' => TrainingRequest::STATUS_APPROVED,
      ],
      [
        'user_id' => $leader->id,
        'competency_id' => $getCompetencyId('Leadership'),
        'reason' => 'Develop frontline leadership capabilities',
        'status' => TrainingRequest::STATUS_REJECTED,
      ],
      [
        'user_id' => $employee->id,
        'competency_id' => $getCompetencyId('Quality'),
        'reason' => 'Enhance quality control skills',
        'status' => TrainingRequest::STATUS_PENDING,
      ],
      [
        'user_id' => $instructor->id,
        'competency_id' => $getCompetencyId('Maintenance'),
        'reason' => 'Learn new maintenance procedures',
        'status' => TrainingRequest::STATUS_APPROVED,
      ],
      [
        'user_id' => $leader->id,
        'competency_id' => $getCompetencyId('Production'),
        'reason' => 'Increase production efficiency',
        'status' => TrainingRequest::STATUS_REJECTED,
      ],
      [
        'user_id' => $employee->id,
        'competency_id' => $getCompetencyId('Logistics'),
        'reason' => 'Streamline logistics operations',
        'status' => TrainingRequest::STATUS_PENDING,
      ],
      [
        'user_id' => $instructor->id,
        'competency_id' => $getCompetencyId('IT'),
        'reason' => 'Upgrade IT knowledge',
        'status' => TrainingRequest::STATUS_APPROVED,
      ],
      [
        'user_id' => $leader->id,
        'competency_id' => $getCompetencyId('HR'),
        'reason' => 'Boost HR management skills',
        'status' => TrainingRequest::STATUS_REJECTED,
      ],
      [
        'user_id' => $employee->id,
        'competency_id' => $getCompetencyId('Finance'),
        'reason' => 'Strengthen financial analysis',
        'status' => TrainingRequest::STATUS_PENDING,
      ],
      [
        'user_id' => $instructor->id,
        'competency_id' => $getCompetencyId('Safety'),
        'reason' => 'Safety refresher for instructors',
        'status' => TrainingRequest::STATUS_APPROVED,
      ],
      [
        'user_id' => $leader->id,
        'competency_id' => $getCompetencyId('Welding'),
        'reason' => 'Welding best practices for leaders',
        'status' => TrainingRequest::STATUS_REJECTED,
      ],
      [
        'user_id' => $employee->id,
        'competency_id' => $getCompetencyId('Leadership'),
        'reason' => 'Aspiring to be a team leader',
        'status' => TrainingRequest::STATUS_PENDING,
      ],
      [
        'user_id' => $instructor->id,
        'competency_id' => $getCompetencyId('Quality'),
        'reason' => 'Quality training for instructors',
        'status' => TrainingRequest::STATUS_APPROVED,
      ],
      [
        'user_id' => $leader->id,
        'competency_id' => $getCompetencyId('Maintenance'),
        'reason' => 'Advanced maintenance for leaders',
        'status' => TrainingRequest::STATUS_REJECTED,
      ],
    ];

    foreach ($rows as $data) {
      TrainingRequest::updateOrCreate(
        [
          'created_by' => $spv->id,
          'user_id' => $data['user_id'],
          'competency_id' => $data['competency_id'],
          'reason' => $data['reason'],
        ],
        [
          'status' => $data['status'],
        ]
      );
    }
  }
}
