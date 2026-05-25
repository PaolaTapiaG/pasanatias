<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ordenes_pago', 'entidad_financiera')) {
            Schema::table('ordenes_pago', function (Blueprint $table) {
                $table->string('entidad_financiera', 120)->nullable();
            });
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_ordenes_pago_entidad_financiera ON ordenes_pago (entidad_financiera)');
            DB::statement("CREATE INDEX IF NOT EXISTS idx_ordenes_pago_ref_lower ON ordenes_pago (LOWER(comprobante_referencia)) WHERE comprobante_referencia IS NOT NULL");
            DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_socio_cobro_asc ON facturas (id_socio, fecha_fin_cobro ASC, id_factura ASC)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_estado_fecha_id ON facturas (estado, fecha_emision DESC, id_factura DESC)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_cobros_factura_estado_monto ON cobros (id_factura, estado, monto_pagado)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_medidores_estado_socio ON medidores (estado, id_socio)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_medidores_estado_socio');
            DB::statement('DROP INDEX IF EXISTS idx_cobros_factura_estado_monto');
            DB::statement('DROP INDEX IF EXISTS idx_facturas_estado_fecha_id');
            DB::statement('DROP INDEX IF EXISTS idx_facturas_socio_cobro_asc');
            DB::statement('DROP INDEX IF EXISTS idx_ordenes_pago_ref_lower');
            DB::statement('DROP INDEX IF EXISTS idx_ordenes_pago_entidad_financiera');
        }
        
        if (Schema::hasColumn('ordenes_pago', 'entidad_financiera')) {
            Schema::table('ordenes_pago', function (Blueprint $table) {
                $table->dropColumn('entidad_financiera');
            });
        }
    }
};
