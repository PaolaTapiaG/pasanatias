<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // PostgreSQL: Load advanced schema
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(file_get_contents(database_path('sql/schema.sql')));
            DB::unprepared(file_get_contents(database_path('sql/triggers.sql')));
            DB::unprepared(file_get_contents(database_path('sql/rls.sql')));
        }
        // SQLite: Tables are already created by 2026_04_24_190000 migration
    }

    public function down(): void
    {
        // PostgreSQL cleanup
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
DROP VIEW IF EXISTS v_cobros_periodo_actual;
DROP VIEW IF EXISTS v_saldo_socios;
DROP VIEW IF EXISTS v_empleados;
DROP VIEW IF EXISTS v_socios;

DROP TRIGGER IF EXISTS trg_tarifa_factura ON facturas;
DROP TRIGGER IF EXISTS trg_validar_pago ON cobros;
DROP TRIGGER IF EXISTS trg_actualizar_estado_factura ON cobros;
DROP TRIGGER IF EXISTS trg_historial_cobro ON cobros;
DROP TRIGGER IF EXISTS trg_auditoria_facturas ON facturas;
DROP TRIGGER IF EXISTS trg_auditoria_cobros ON cobros;
DROP TRIGGER IF EXISTS trg_auditoria_socios ON socios;
DROP TRIGGER IF EXISTS trg_auditoria_tarifas ON tarifas;

DROP FUNCTION IF EXISTS get_mi_rol();
DROP FUNCTION IF EXISTS tiene_rol(VARIADIC TEXT[]);
DROP FUNCTION IF EXISTS get_mi_empleado_id();
DROP FUNCTION IF EXISTS guardar_tarifa_factura();
DROP FUNCTION IF EXISTS validar_pago_factura();
DROP FUNCTION IF EXISTS actualizar_estado_factura();
DROP FUNCTION IF EXISTS registrar_historial_cobro();
DROP FUNCTION IF EXISTS marcar_facturas_vencidas();
DROP FUNCTION IF EXISTS auditoria_trigger();

DROP TABLE IF EXISTS auditoria CASCADE;
DROP TABLE IF EXISTS notificaciones CASCADE;
DROP TABLE IF EXISTS historial_pagos CASCADE;
DROP TABLE IF EXISTS cobros CASCADE;
DROP TABLE IF EXISTS facturas CASCADE;
DROP TABLE IF EXISTS lecturas CASCADE;
DROP TABLE IF EXISTS periodos_facturacion CASCADE;
DROP TABLE IF EXISTS medidores CASCADE;
DROP TABLE IF EXISTS empleados CASCADE;
DROP TABLE IF EXISTS socios CASCADE;
DROP TABLE IF EXISTS metodos_pago CASCADE;
DROP TABLE IF EXISTS tarifas CASCADE;
DROP TABLE IF EXISTS sectores CASCADE;
DROP TABLE IF EXISTS roles CASCADE;
DROP TABLE IF EXISTS personas CASCADE;
SQL);
        } else {
            // SQLite cleanup
            Schema::dropIfExists('auditoria');
            Schema::dropIfExists('notificaciones');
            Schema::dropIfExists('historial_pagos');
            Schema::dropIfExists('cobros');
            Schema::dropIfExists('facturas');
            Schema::dropIfExists('lecturas');
            Schema::dropIfExists('periodos_facturacion');
            Schema::dropIfExists('medidores');
            Schema::dropIfExists('metodos_pago');
        }
    }
};
