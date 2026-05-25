<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only create indexes if the tables exist (they might not in SQLite development)
        if (Schema::hasTable('facturas')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_fecha_emision_id ON facturas (fecha_emision DESC, id_factura DESC)');
        }
        if (Schema::hasTable('socios')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_socios_created_at ON socios (created_at DESC)');
        }
        if (Schema::hasTable('medidores')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_medidores_socio_estado ON medidores (id_socio, estado)');
        }
        if (Schema::hasTable('periodos_facturacion')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_periodos_fecha_inicio ON periodos_facturacion (fecha_inicio DESC)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_facturas_fecha_emision_id');
        DB::statement('DROP INDEX IF EXISTS idx_socios_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_medidores_socio_estado');
        DB::statement('DROP INDEX IF EXISTS idx_periodos_fecha_inicio');
    }
};
