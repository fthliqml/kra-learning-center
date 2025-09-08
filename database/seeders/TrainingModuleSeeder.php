<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('training_modules')->insert([
            [
                'title' => '5S Management',
                'group_comp' => 'BMC',
                'duration' => 8,
                'frequency' => 2,
            ],
            [
                'title' => '7 HABITS',
                'group_comp' => 'BC',
                'duration' => 8,
                'frequency' => 2,
            ],
            [
                'title' => '8 STEPS IMPROVEMENT',
                'group_comp' => 'BMC',
                'duration' => 12,
                'frequency' => 3,
            ],
            [
                'title' => 'ADVANCE ASSEMBLY OF AXLE ASSY',
                'group_comp' => 'MMP',
                'duration' => 16,
                'frequency' => 4,
            ],
            [
                'title' => 'ADVANCE ASSEMBLY OF COUNTER SHAFT TRANSMISSION',
                'group_comp' => 'MMP',
                'duration' => 16,
                'frequency' => 4,
            ],
            [
                'title' => 'ADVANCE ASSEMBLY OF DIFFERENTIAL',
                'group_comp' => 'MMP',
                'duration' => 16,
                'frequency' => 4,
            ],
            [
                'title' => 'ADVANCE ASSEMBLY OF ENGINE PART 2',
                'group_comp' => 'MMP',
                'duration' => 20,
                'frequency' => 5,
            ],
            [
                'title' => 'ADVANCE ASSEMBLY OF FINAL DRIVE DZ AND PC SERIES',
                'group_comp' => 'MMP',
                'duration' => 16,
                'frequency' => 4,
            ],
            [
                'title' => 'ADVANCE ASSEMBLY OF FINAL DRIVE GD SERIES',
                'group_comp' => 'MMP',
                'duration' => 16,
                'frequency' => 4,
            ],
            [
                'title' => 'ADVANCE ASSEMBLY OF POWER TRAIN MODUL',
                'group_comp' => 'MMP',
                'duration' => 16,
                'frequency' => 4,
            ],
        ]);
    }
}
