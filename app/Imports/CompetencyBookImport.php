<?php

namespace App\Imports;

use App\Models\Competency;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CompetencyBookImport implements ToModel, WithHeadingRow, WithValidation
{
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    private array $validTypes = ['BMC', 'BC', 'MMP', 'LC', 'MDP', 'TOC'];

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
        $name = $this->getValue($row, ['competency_name', 'competencyname', 'name', 'competency']);
        $type = $this->getValue($row, ['type', 'type_bmcbcmmplcmdptoc', 'competency_type']);
        $description = $this->getValue($row, ['description', 'desc']);

        // Skip empty rows
        if (empty($name) || empty($type)) {
            $this->skipped++;
            return null;
        }

        // Normalize type to uppercase
        $type = strtoupper(trim($type));

        // Validate type
        if (!in_array($type, $this->validTypes)) {
            $this->skipped++;
            return null;
        }

        // Check if competency with same name already exists
        $existing = Competency::where('name', $name)->first();

        if ($existing) {
            // Update existing
            $existing->update([
                'type' => $type,
                'description' => $description ?? $existing->description,
            ]);
            $this->updated++;
            return null;
        }

        // Create new with generated code
        $code = Competency::generateCode($type);

        $this->created++;

        return new Competency([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'description' => $description ?? '',
        ]);
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            '*.competency_name' => 'nullable|string|max:255',
            '*.name' => 'nullable|string|max:255',
            '*.type' => 'nullable|string',
            '*.description' => 'nullable|string|max:1000',
        ];
    }
}
