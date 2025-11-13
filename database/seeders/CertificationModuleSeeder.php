<?php

namespace Database\Seeders;

use App\Models\CertificationModule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CertificationModuleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $modules = [
      [
        'code' => 'CWI-001',
        'name' => 'Certified Welding Inspector',
        'level' => 'Advanced',
        'competency' => 'Welding Inspection',
        'points_per_module' => 50,
        'new_gex' => 2.5,
        'duration' => 40,
        'theory_passing_score' => 75.0,
        'practical_passing_score' => 80.0,
        'is_active' => true,
      ],
      [
        'code' => 'ISO-9001',
        'name' => 'ISO 9001:2015 Lead Auditor',
        'level' => 'Expert',
        'competency' => 'Quality Management',
        'points_per_module' => 60,
        'new_gex' => 3.0,
        'duration' => 32,
        'theory_passing_score' => 80.0,
        'practical_passing_score' => 85.0,
        'is_active' => true,
      ],
      [
        'code' => 'NEBOSH-IGC',
        'name' => 'NEBOSH International General Certificate',
        'level' => 'Intermediate',
        'competency' => 'Health & Safety',
        'points_per_module' => 45,
        'new_gex' => 2.0,
        'duration' => 48,
        'theory_passing_score' => 70.0,
        'practical_passing_score' => 75.0,
        'is_active' => true,
      ],
      [
        'code' => 'CMRP-001',
        'name' => 'Certified Maintenance & Reliability Professional',
        'level' => 'Advanced',
        'competency' => 'Maintenance Management',
        'points_per_module' => 55,
        'new_gex' => 2.8,
        'duration' => 36,
        'theory_passing_score' => 75.0,
        'practical_passing_score' => 80.0,
        'is_active' => true,
      ],
      [
        'code' => 'LSSBB-001',
        'name' => 'Lean Six Sigma Black Belt',
        'level' => 'Expert',
        'competency' => 'Process Improvement',
        'points_per_module' => 70,
        'new_gex' => 3.5,
        'duration' => 80,
        'theory_passing_score' => 80.0,
        'practical_passing_score' => 85.0,
        'is_active' => true,
      ],
      [
        'code' => 'FLO-001',
        'name' => 'Certified Forklift Operator',
        'level' => 'Basic',
        'competency' => 'Material Handling',
        'points_per_module' => 30,
        'new_gex' => 1.5,
        'duration' => 16,
        'theory_passing_score' => 70.0,
        'practical_passing_score' => 75.0,
        'is_active' => true,
      ],
      [
        'code' => 'ISO-INT',
        'name' => 'ISO 9001 Internal Auditor',
        'level' => 'Intermediate',
        'competency' => 'Internal Auditing',
        'points_per_module' => 40,
        'new_gex' => 2.0,
        'duration' => 24,
        'theory_passing_score' => 75.0,
        'practical_passing_score' => 80.0,
        'is_active' => true,
      ],
      [
        'code' => 'CESP-001',
        'name' => 'Certified Electrical Safety Professional',
        'level' => 'Advanced',
        'competency' => 'Electrical Safety',
        'points_per_module' => 50,
        'new_gex' => 2.5,
        'duration' => 40,
        'theory_passing_score' => 80.0,
        'practical_passing_score' => 85.0,
        'is_active' => true,
      ],
      [
        'code' => 'PMP-001',
        'name' => 'Project Management Professional',
        'level' => 'Expert',
        'competency' => 'Project Management',
        'points_per_module' => 65,
        'new_gex' => 3.2,
        'duration' => 60,
        'theory_passing_score' => 80.0,
        'practical_passing_score' => 85.0,
        'is_active' => true,
      ],
      [
        'code' => 'SSGB-001',
        'name' => 'Six Sigma Green Belt',
        'level' => 'Intermediate',
        'competency' => 'Quality Control',
        'points_per_module' => 50,
        'new_gex' => 2.5,
        'duration' => 40,
        'theory_passing_score' => 75.0,
        'practical_passing_score' => 80.0,
        'is_active' => true,
      ],
    ];

    foreach ($modules as $module) {
      CertificationModule::create($module);
    }
  }
}
