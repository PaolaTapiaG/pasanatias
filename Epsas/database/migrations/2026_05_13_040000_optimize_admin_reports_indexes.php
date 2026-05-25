<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') return;

        DB::statement('CREATE INDEX IF NOT EXISTS idx_cobros_fecha_estado ON cobros (fecha_cobro, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gastos_fecha_id ON gastos (fecha_gasto DESC, id_gasto DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gastos_fecha_categoria ON gastos (fecha_gasto, categoria)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_periodo_estado_socio ON facturas (id_periodo, estado, id_socio)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_estado_fecha_socio ON facturas (estado, fecha_emision DESC, id_socio)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_lecturas_fecha ON lecturas (fecha_lectura)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') return;

        DB::statement('DROP INDEX IF EXISTS idx_lecturas_fecha');
        DB::statement('DROP INDEX IF EXISTS idx_facturas_estado_fecha_socio');
        DB::statement('DROP INDEX IF EXISTS idx_facturas_periodo_estado_socio');
        DB::statement('DROP INDEX IF EXISTS idx_gastos_fecha_categoria');
        DB::statement('DROP INDEX IF EXISTS idx_gastos_fecha_id');
        DB::statement('DROP INDEX IF EXISTS idx_cobros_fecha_estado');
    }
};
