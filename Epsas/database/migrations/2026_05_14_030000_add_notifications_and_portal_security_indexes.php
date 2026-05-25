<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ordenes_pago_estado_revisado ON ordenes_pago (estado, revisado_en DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ordenes_pago_estado_updated ON ordenes_pago (estado, updated_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_lecturas_created_at ON lecturas (created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_socios_numero_exact ON socios (numero_socio)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_personas_ci_exact ON personas (cedula_identidad)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medidores_serie_exact ON medidores (numero_serie)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_medidores_serie_exact');
        DB::statement('DROP INDEX IF EXISTS idx_personas_ci_exact');
        DB::statement('DROP INDEX IF EXISTS idx_socios_numero_exact');
        DB::statement('DROP INDEX IF EXISTS idx_lecturas_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_ordenes_pago_estado_updated');
        DB::statement('DROP INDEX IF EXISTS idx_ordenes_pago_estado_revisado');
    }
};
