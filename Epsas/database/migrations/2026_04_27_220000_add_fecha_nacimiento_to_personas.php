<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
ALTER TABLE personas
  ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE;
SQL);
        } else {
            // For SQLite and other databases
            if (Schema::hasTable('personas') && !Schema::hasColumn('personas', 'fecha_nacimiento')) {
                Schema::table('personas', function ($table) {
                    $table->date('fecha_nacimiento')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
ALTER TABLE personas
  DROP COLUMN IF EXISTS fecha_nacimiento;
SQL);
        } else {
            if (Schema::hasTable('personas') && Schema::hasColumn('personas', 'fecha_nacimiento')) {
                Schema::table('personas', function ($table) {
                    $table->dropColumn('fecha_nacimiento');
                });
            }
        }
    }
};

