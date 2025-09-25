<?php

namespace App\Imports;

use App\Models\TrainingModule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TrainingModuleImport implements ToModel, WithHeadingRow, WithValidation
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

    /**
     * Rules untuk validasi setiap kolom
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'module_title' => 'required|string',
            'group_competency' => 'required|string',
            'objective' => 'nullable|string',
            'training_content' => 'nullable|string',
            'method' => 'nullable|string',
            'duration_hours' => 'required|numeric',
            'frequency_days' => 'required|numeric',
        ];
    }
}
