<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certification_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('certification_modules', 'competency_id')) {
                $table->unsignedBigInteger('competency_id')->nullable()->after('competency');
                $table->index('competency_id', 'certification_modules_competency_id_index');
                $table->foreign('competency_id', 'certification_modules_competency_id_fk')
                    ->references('id')
                    ->on('competency')
                    ->nullOnDelete();
            }
        });

        // Backfill competency_id using existing competency string (expects format "CODE - Name" or "CODE").
        // Keep it defensive: only update rows where competency_id is NULL.
        try {
            $rows = DB::table('certification_modules')
                ->select('id', 'competency')
                ->whereNull('competency_id')
                ->whereNotNull('competency')
                ->get();

            foreach ($rows as $row) {
                $raw = trim((string) $row->competency);
                if ($raw === '') {
                    continue;
                }

                $code = $raw;
                if (str_contains($raw, ' - ')) {
                    $code = trim(explode(' - ', $raw, 2)[0]);
                }

                if ($code === '') {
                    continue;
                }

                $competencyId = DB::table('competency')->where('code', $code)->value('id');
                if ($competencyId) {
                    DB::table('certification_modules')
                        ->where('id', $row->id)
                        ->update(['competency_id' => $competencyId]);
                }
            }
        } catch (Throwable $e) {
            // Do not fail migration if backfill cannot run in a specific environment.
        }
    }

    public function down(): void
    {
        Schema::table('certification_modules', function (Blueprint $table) {
            if (Schema::hasColumn('certification_modules', 'competency_id')) {
                $table->dropForeign('certification_modules_competency_id_fk');
                $table->dropIndex('certification_modules_competency_id_index');
                $table->dropColumn('competency_id');
            }
        });
    }
};
