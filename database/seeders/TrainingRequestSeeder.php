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
        // Only SPV can create training requests
        $spv = User::where('role', 'spv')->first() ?: User::first();

        // Guard: if no users exist, bail out gracefully
        if (!$spv) {
            return;
        }

        // Resolve target users for the requests
        $employee   = User::where('role', 'employee')->first() ?: $spv;
        $instructor = User::where('role', 'instructor')->first() ?: $spv;
        $leader     = User::where('role', 'leader')->first() ?: $spv;

        // Get competencies for mapping
        $competencies = Competency::all()->keyBy('name');

        $rows = [
            [
                'user_id'    => $employee->id,
                'competency' => 'Safety',
                'reason'     => 'Improve safety awareness on site',
                'status'     => TrainingRequest::STATUS_PENDING,
            ],
            [
                'user_id'    => $instructor->id,
                'competency' => 'Welding',
                'reason'     => 'Upskill welding techniques for production line',
                'status'     => TrainingRequest::STATUS_APPROVED,
            ],
            [
                'user_id'    => $leader->id,
                'competency' => 'Leadership',
                'reason'     => 'Develop frontline leadership capabilities',
                'status'     => TrainingRequest::STATUS_REJECTED,
            ],
            [
                'user_id'    => $employee->id,
                'competency' => 'Quality',
                'reason'     => 'Enhance quality control skills',
                'status'     => TrainingRequest::STATUS_PENDING,
            ],
            [
                'user_id'    => $instructor->id,
                'competency' => 'Maintenance',
                'reason'     => 'Learn new maintenance procedures',
                'status'     => TrainingRequest::STATUS_APPROVED,
            ],
            [
                'user_id'    => $leader->id,
                'competency' => 'Production',
                'reason'     => 'Increase production efficiency',
                'status'     => TrainingRequest::STATUS_REJECTED,
            ],
            [
                'user_id'    => $employee->id,
                'competency' => 'Logistics',
                'reason'     => 'Streamline logistics operations',
                'status'     => TrainingRequest::STATUS_PENDING,
            ],
            [
                'user_id'    => $instructor->id,
                'competency' => 'IT',
                'reason'     => 'Upgrade IT knowledge',
                'status'     => TrainingRequest::STATUS_APPROVED,
            ],
            [
                'user_id'    => $leader->id,
                'competency' => 'HR',
                'reason'     => 'Boost HR management skills',
                'status'     => TrainingRequest::STATUS_REJECTED,
            ],
            [
                'user_id'    => $employee->id,
                'competency' => 'Finance',
                'reason'     => 'Strengthen financial analysis',
                'status'     => TrainingRequest::STATUS_PENDING,
            ],
            [
                'user_id'    => $instructor->id,
                'competency' => 'Safety',
                'reason'     => 'Safety refresher for instructors',
                'status'     => TrainingRequest::STATUS_APPROVED,
            ],
            [
                'user_id'    => $leader->id,
                'competency' => 'Welding',
                'reason'     => 'Welding best practices for leaders',
                'status'     => TrainingRequest::STATUS_REJECTED,
            ],
            [
                'user_id'    => $employee->id,
                'competency' => 'Leadership',
                'reason'     => 'Aspiring to be a team leader',
                'status'     => TrainingRequest::STATUS_PENDING,
            ],
            [
                'user_id'    => $instructor->id,
                'competency' => 'Quality',
                'reason'     => 'Quality training for instructors',
                'status'     => TrainingRequest::STATUS_APPROVED,
            ],
            [
                'user_id'    => $leader->id,
                'competency' => 'Maintenance',
                'reason'     => 'Advanced maintenance for leaders',
                'status'     => TrainingRequest::STATUS_REJECTED,
            ],
        ];

        foreach ($rows as $data) {
            // Find competency_id based on competency name
            $competency = $competencies->get($data['competency']);
            if (!$competency) {
                continue; // Skip if competency not found
            }

            TrainingRequest::updateOrCreate(
                [
                    'created_by' => $spv->id,
                    'user_id'    => $data['user_id'],
                    'competency_id' => $competency->id,
                    'reason'     => $data['reason'],
                ],
                [
                    'status' => $data['status'],
                ]
            );
        }
    }
}
