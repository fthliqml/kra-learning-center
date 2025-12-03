<?php

namespace App\Imports;

use App\Models\Competency;
use App\Models\CompetencyValue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CompetencyValueImport implements ToModel, WithHeadingRow, WithValidation
{
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    private array $validPositions = [
        'Division Head',
        'Department Head',
        'Section Head',
        'Foreman',
        'Staff',
    ];

    /**
     * Normalize heading keys for flexible matching.
     */
    private function normalizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $key));
    }

    /**
     * Get value from row using flexible key matching.
     */
    private function getValue(array $row, array $possibleKeys): ?string
    {
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);
            foreach ($possibleKeys as $possibleKey) {
                if ($this->normalizeKey($possibleKey) === $normalizedKey) {
                    return trim((string) $value);
                }
            }
        }
        return null;
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $competencyCode = $this->getValue($row, ['competency_code', 'competencycode', 'code', 'id']);
        $position = $this->getValue($row, ['position', 'pos', 'jabatan']);
        $bobot = $this->getValue($row, ['bobot', 'weight']);
        $value = $this->getValue($row, ['value', 'val', 'nilai']);

        // Skip empty rows
        if (empty($competencyCode) || empty($position) || empty($bobot) || empty($value)) {
            $this->skipped++;
            return null;
        }

        // Normalize values - convert to string in case Excel sends numeric
        // Handle percentage: Excel converts "5%" to 0.05, so convert back to "5%"
        $bobot = trim((string) $bobot);
        if (is_numeric($bobot) && floatval($bobot) > 0 && floatval($bobot) < 1) {
            // Looks like a decimal percentage (0.05 = 5%)
            $bobot = round(floatval($bobot) * 100) . '%';
        }
        $position = trim($position);
        $value = (int) $value;

        // Validate position
        if (!in_array($position, $this->validPositions)) {
            $this->skipped++;
            return null;
        }

        // Validate value (1-10)
        if ($value < 1 || $value > 10) {
            $this->skipped++;
            return null;
        }

        // Find competency by code
        $competency = Competency::where('code', $competencyCode)->first();
        if (!$competency) {
            $this->skipped++;
            return null;
        }

        // Check if competency value with same competency_id and position already exists
        $existing = CompetencyValue::where('competency_id', $competency->id)
            ->where('position', $position)
            ->first();

        if ($existing) {
            // Update existing
            $existing->update([
                'bobot' => $bobot,
                'value' => $value,
            ]);
            $this->updated++;
            return null;
        }

        // Create new
        $this->created++;

        return new CompetencyValue([
            'competency_id' => $competency->id,
            'position' => $position,
            'bobot' => $bobot,
            'value' => $value,
        ]);
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            '*.competency_code' => 'nullable|max:50',
            '*.code' => 'nullable|max:50',
            '*.position' => 'nullable|max:255',
            '*.bobot' => 'nullable|max:50',
            '*.value' => 'nullable',
        ];
    }
}
