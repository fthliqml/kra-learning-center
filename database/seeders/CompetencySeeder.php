<?php

namespace Database\Seeders;

use App\Models\Competency;
use Illuminate\Database\Seeder;

class CompetencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $competencies = [
            // BMC - Basic Management Competency
            [
                'code' => 'BMC001',
                'name' => '5S Management',
                'type' => 'BMC',
                'description' => 'Understanding and implementation of 5S workplace organization methodology.',
            ],
            [
                'code' => 'BMC002',
                'name' => 'Quality Control Circle (QCC)',
                'type' => 'BMC',
                'description' => 'Knowledge of QCC activities and continuous improvement.',
            ],
            [
                'code' => 'BMC003',
                'name' => 'Safety Awareness',
                'type' => 'BMC',
                'description' => 'Understanding of workplace safety procedures and hazard identification.',
            ],
            [
                'code' => 'BMC004',
                'name' => 'Basic Problem Solving',
                'type' => 'BMC',
                'description' => 'Ability to identify and solve basic operational problems.',
            ],

            // BC - Behavioral Competency
            [
                'code' => 'BC001',
                'name' => 'Communication Skills',
                'type' => 'BC',
                'description' => 'Ability to communicate effectively with colleagues and stakeholders.',
            ],
            [
                'code' => 'BC002',
                'name' => 'Teamwork & Collaboration',
                'type' => 'BC',
                'description' => 'Ability to work effectively within a team environment.',
            ],
            [
                'code' => 'BC003',
                'name' => 'Integrity & Ethics',
                'type' => 'BC',
                'description' => 'Adherence to ethical standards and company values.',
            ],
            [
                'code' => 'BC004',
                'name' => 'Adaptability',
                'type' => 'BC',
                'description' => 'Ability to adapt to changes in work environment and tasks.',
            ],

            // MMP - Manufacturing Management Program
            [
                'code' => 'MMP001',
                'name' => 'Production Planning',
                'type' => 'MMP',
                'description' => 'Knowledge of production planning and scheduling.',
            ],
            [
                'code' => 'MMP002',
                'name' => 'Process Control',
                'type' => 'MMP',
                'description' => 'Understanding of manufacturing process control and optimization.',
            ],
            [
                'code' => 'MMP003',
                'name' => 'Equipment Maintenance',
                'type' => 'MMP',
                'description' => 'Knowledge of preventive and corrective maintenance procedures.',
            ],
            [
                'code' => 'MMP004',
                'name' => 'Quality Management System',
                'type' => 'MMP',
                'description' => 'Understanding of QMS principles and ISO standards.',
            ],

            // LC - Leadership Competency
            [
                'code' => 'LC001',
                'name' => 'Team Leadership',
                'type' => 'LC',
                'description' => 'Ability to lead and motivate team members.',
            ],
            [
                'code' => 'LC002',
                'name' => 'Decision Making',
                'type' => 'LC',
                'description' => 'Ability to make effective and timely decisions.',
            ],
            [
                'code' => 'LC003',
                'name' => 'Coaching & Mentoring',
                'type' => 'LC',
                'description' => 'Ability to coach and develop team members.',
            ],
            [
                'code' => 'LC004',
                'name' => 'Conflict Resolution',
                'type' => 'LC',
                'description' => 'Ability to resolve conflicts effectively.',
            ],

            // MDP - Management Development Program
            [
                'code' => 'MDP001',
                'name' => 'Strategic Planning',
                'type' => 'MDP',
                'description' => 'Ability to develop and execute strategic plans.',
            ],
            [
                'code' => 'MDP002',
                'name' => 'Performance Management',
                'type' => 'MDP',
                'description' => 'Ability to manage and evaluate team performance.',
            ],
            [
                'code' => 'MDP003',
                'name' => 'Resource Management',
                'type' => 'MDP',
                'description' => 'Ability to allocate and manage resources efficiently.',
            ],
            [
                'code' => 'MDP004',
                'name' => 'Change Management',
                'type' => 'MDP',
                'description' => 'Ability to manage organizational change effectively.',
            ],

            // TOC - Technical/Operational Competency
            [
                'code' => 'TOC001',
                'name' => 'Machine Operation',
                'type' => 'TOC',
                'description' => 'Skill in operating production machinery and equipment.',
            ],
            [
                'code' => 'TOC002',
                'name' => 'Technical Drawing',
                'type' => 'TOC',
                'description' => 'Ability to read and interpret technical drawings.',
            ],
            [
                'code' => 'TOC003',
                'name' => 'Troubleshooting',
                'type' => 'TOC',
                'description' => 'Ability to diagnose and troubleshoot equipment issues.',
            ],
            [
                'code' => 'TOC004',
                'name' => 'Product Knowledge',
                'type' => 'TOC',
                'description' => 'Understanding of company products and specifications.',
            ],
        ];

        foreach ($competencies as $competency) {
            Competency::create($competency);
        }
    }
}
