<?php

namespace App\Imports;

use App\Models\TrainingModule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrainingModuleImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new TrainingModule([
            'title' => $row['module_title'],
            'group_comp' => $row['group_competency'],
            'objective' => $row['objective'],
            'training_content' => $row['training_content'],
            'method' => $row['method'],
            'duration' => $row['duration_hours'],
            'frequency' => $row['frequency_days'],
        ]);
    }
}
