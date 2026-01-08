<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Trainer;
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
            // Normalize row keys and values
            $trainerType = trim((string)($row['trainer_type'] ?? 'external'));
            $name = trim((string)($row['name'] ?? ''));
            $institution = trim((string)($row['institution'] ?? ''));
            // Support multiple possible column names for competencies
            $competenciesRaw = (string)($row['competency_codes_comma_separated'] ?? $row['competencies'] ?? $row['competency_codes'] ?? '');

            if ($name === '') {
                $this->skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($trainerType, $name, $institution) {
                    $isInternal = strtolower($trainerType) === 'internal';

                    if ($isInternal) {
                        // For internal trainer, find existing user by exact name
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
                        $trainer->name = null; // Clear name field for internal trainer
                        $trainer->save();
                    } else {
                        // For external trainer, find by name and null user_id
                        $trainer = Trainer::firstOrNew([
                            'name' => $name,
                            'user_id' => null
                        ]);
                        $isNewTrainer = !$trainer->exists;
                        $trainer->institution = $institution;
                        $trainer->save();
                    }

                    // Count per trainer-row created/updated
                    $isNewTrainer ? $this->created++ : $this->updated++;
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
            'trainer_type' => ['required', 'string', 'in:internal,external,Internal,External'],
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
        ];
    }
}
