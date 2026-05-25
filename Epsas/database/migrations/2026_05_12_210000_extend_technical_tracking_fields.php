<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medidor_anomalias', function (Blueprint $table) {
            $table->decimal('monto_multa', 12, 2)->default(0)->after('evidencia_path');
            $table->foreignId('id_factura_multa')->nullable()->after('id_empleado')->constrained('facturas', 'id_factura')->nullOnDelete();
        });

        // PostgreSQL-specific column type changes
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ordenes_tecnicas ALTER COLUMN coord_x TYPE NUMERIC(10,7)');
            DB::statement('ALTER TABLE ordenes_tecnicas ALTER COLUMN coord_y TYPE NUMERIC(10,7)');
            DB::statement('ALTER TABLE incidencias_tecnicas ALTER COLUMN coord_x TYPE NUMERIC(10,7)');
            DB::statement('ALTER TABLE incidencias_tecnicas ALTER COLUMN coord_y TYPE NUMERIC(10,7)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE incidencias_tecnicas ALTER COLUMN coord_y TYPE NUMERIC(5,2)');
            DB::statement('ALTER TABLE incidencias_tecnicas ALTER COLUMN coord_x TYPE NUMERIC(5,2)');
            DB::statement('ALTER TABLE ordenes_tecnicas ALTER COLUMN coord_y TYPE NUMERIC(5,2)');
            DB::statement('ALTER TABLE ordenes_tecnicas ALTER COLUMN coord_x TYPE NUMERIC(5,2)');
        }

        Schema::table('medidor_anomalias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_factura_multa');
            $table->dropColumn('monto_multa');
        });
    }
};
