<?php

namespace App\Imports;

use App\Models\CertificationModule;
use App\Models\Competency;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CertificationModuleImport implements ToModel, WithHeadingRow, WithValidation
{
    private function parseCompetencyCode(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }
        if (str_contains($raw, ' - ')) {
            return trim(explode(' - ', $raw, 2)[0]);
        }
        return $raw;
    }

    public function model(array $row)
    {
        $competencyLabel = $row['competency'] ?? null;
        $competencyId = null;
        $code = $this->parseCompetencyCode(is_string($competencyLabel) ? $competencyLabel : null);
        if ($code) {
            $competencyId = Competency::query()->where('code', $code)->value('id');
            if ($competencyId) {
                $competency = Competency::query()->select('code', 'name')->find($competencyId);
                if ($competency) {
                    $competencyLabel = trim((string) $competency->code . ' - ' . (string) $competency->name);
                }
            }
        }

        return new CertificationModule([
            'code' => $row['code'] ?? null,
            'module_title' => $row['module_title'] ?? null,
            'competency_id' => $competencyId ? (int) $competencyId : null,
            'competency' => $competencyLabel,
            'level' => $row['level'] ?? null,
            'group_certification' => $row['group_certification'] ?? null,
            'points_per_module' => isset($row['points_per_module']) ? (int) $row['points_per_module'] : 0,
            'new_gex' => isset($row['new_gex']) ? (float) $row['new_gex'] : 0.0,
            'duration' => isset($row['duration_minutes']) ? (int) $row['duration_minutes'] : 0,
            'theory_passing_score' => isset($row['theory_passing_score']) ? (float) $row['theory_passing_score'] : 0.0,
            'practical_passing_score' => isset($row['practical_passing_score']) ? (float) $row['practical_passing_score'] : 0.0,
            'major_component' => $row['major_component'] ?? null,
            'mach_model' => $row['mach_model'] ?? null,
            'is_active' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'module_title' => 'required|string|max:255',
            'competency' => 'required|string|max:255',
            'level' => 'required|in:Basic,Intermediate,Advanced',
            'group_certification' => 'required|in:ENGINE,MACHINING,PPT AND PPM',
            'points_per_module' => 'required|integer|min:0',
            'new_gex' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'theory_passing_score' => 'required|numeric|min:0|max:100',
            'practical_passing_score' => 'required|numeric|min:0|max:100',
            'major_component' => 'nullable|string',
            'mach_model' => 'nullable|string',
        ];
    }
}
