<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Trainer;
use App\Models\Competency;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class DataTrainerImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        // Skip header row handled by WithHeadingRow
        foreach ($collection as $row) {
            // Normalize row keys and values (Email is not used anymore)
            $name = trim((string)($row['name'] ?? ''));
            $institution = trim((string)($row['institution'] ?? ''));
            $competenciesRaw = (string)($row['competencies'] ?? '');

            if ($name === '') {
                $this->skipped++;
                continue;
            }

            // Parse competencies: split by comma/semicolon/newline
            $competencyNames = collect(preg_split('/[;,\n]+/', $competenciesRaw))
                ->map(fn($c) => trim((string)$c))
                ->filter()
                ->unique()
                ->values();

            try {
                DB::transaction(function () use ($name, $institution, $competencyNames) {
                    // Find existing user by exact name
                    $users = User::where('name', $name)->get();
                    if ($users->count() !== 1) {
                        // Skip if not found or ambiguous
                        throw new \RuntimeException('User not found or ambiguous for name: ' . $name);
                    }
                    $user = $users->first();

                    // Ensure trainer record exists for this user
                    $trainer = Trainer::firstOrNew(['user_id' => $user->id]);
                    $isNewTrainer = !$trainer->exists;
                    $trainer->institution = $institution;
                    $trainer->save();
                    // Count per trainer-row created/updated
                    $isNewTrainer ? $this->created++ : $this->updated++;

                    // Upsert competencies and sync pivot
                    $competencyIds = $competencyNames->map(function ($desc) {
                        $competency = Competency::firstOrCreate(['description' => $desc]);
                        return $competency->id;
                    })->all();

                    $trainer->competencies()->sync($competencyIds);
                });
            } catch (\Throwable $e) {
                Log::warning('DataTrainerImport row skipped', [
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);
                $this->skipped++;
                continue;
            }
        }
    }

    /**
     * Validation rules for each row (WithHeadingRow provides associative keys)
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'competencies' => ['nullable', 'string'],
        ];
    }
}
