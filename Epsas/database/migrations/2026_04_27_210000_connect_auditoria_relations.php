<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // PostgreSQL-specific advanced migration
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(file_get_contents(database_path('sql/schema.sql')));
            DB::unprepared(file_get_contents(database_path('sql/triggers.sql')));
            DB::unprepared(file_get_contents(database_path('sql/rls.sql')));
        }
        // SQLite doesn't need these advanced features for development
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(file_get_contents(database_path('sql/cleanup.sql')));
        }
    }
};
