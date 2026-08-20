<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') return;

        DB::statement('CREATE INDEX IF NOT EXISTS idx_lecturas_medidor_fecha_id_desc ON lecturas (id_medidor, fecha_lectura DESC, id_lectura DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ordenes_tipo_estado_socio ON ordenes_tecnicas (tipo, estado, id_socio)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ordenes_tipo_fecha_id_desc ON ordenes_tecnicas (tipo, fecha_programada DESC, id_orden DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_socio_estado ON facturas (id_socio, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_cobros_factura_estado ON cobros (id_factura, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medidor_anomalias_medidor_estado_fecha ON medidor_anomalias (id_medidor, estado, fecha_reporte)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_empleados_persona ON empleados (id_persona)');

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_tecnico_socios_catalogo WITH (security_invoker = true) AS
SELECT
    s.id_socio,
    COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) AS codigo_display,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS socio_nombre,
    p.cedula_identidad,
    s.id_sector,
    COALESCE(sec.nombre, 'Sin zona') AS sector_nombre,
    COALESCE(sec.zona, sec.nombre, 'Sin zona') AS zona,
    COALESCE(s.direccion, 'Sin direccion') AS direccion,
    s.estado
FROM socios s
LEFT JOIN personas p ON p.id_persona = s.id_persona
LEFT JOIN sectores sec ON sec.id_sector = s.id_sector
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_tecnico_ordenes_recientes WITH (security_invoker = true) AS
SELECT
    ot.id_orden,
    ot.tipo,
    ot.estado,
    ot.prioridad,
    ot.fecha_programada,
    ot.fecha_ejecucion,
    ot.zona,
    ot.referencia,
    ot.descripcion,
    ot.id_socio,
    ot.id_medidor,
    COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) AS codigo_display,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS socio_nombre,
    COALESCE(sec.nombre, ot.zona, 'Sin zona') AS sector_nombre,
    m.numero_serie AS medidor_serie
FROM ordenes_tecnicas ot
LEFT JOIN socios s ON s.id_socio = ot.id_socio
LEFT JOIN personas p ON p.id_persona = s.id_persona
LEFT JOIN sectores sec ON sec.id_sector = s.id_sector
LEFT JOIN medidores m ON m.id_medidor = ot.id_medidor
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_tecnico_lecturas_index WITH (security_invoker = true) AS
SELECT
    l.id_lectura,
    l.fecha_lectura,
    l.lectura_anterior,
    l.lectura_actual,
    l.consumo_m3,
    l.observaciones,
    l.id_medidor,
    l.id_empleado,
    m.numero_serie,
    s.id_socio,
    COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) AS codigo_display,
    TRIM(COALESCE(ps.nombres, '') || ' ' || COALESCE(ps.apellidos, '')) AS socio_nombre,
    ps.cedula_identidad,
    TRIM(COALESCE(pe.nombres, '') || ' ' || COALESCE(pe.apellidos, '')) AS lector_nombre
FROM lecturas l
LEFT JOIN medidores m ON m.id_medidor = l.id_medidor
LEFT JOIN socios s ON s.id_socio = m.id_socio
LEFT JOIN personas ps ON ps.id_persona = s.id_persona
LEFT JOIN empleados e ON e.id_empleado = l.id_empleado
LEFT JOIN personas pe ON pe.id_persona = e.id_persona
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_tecnico_lecturas_index');
        DB::statement('DROP VIEW IF EXISTS v_tecnico_ordenes_recientes');
        DB::statement('DROP VIEW IF EXISTS v_tecnico_socios_catalogo');
        DB::statement('DROP INDEX IF EXISTS idx_empleados_persona');
        DB::statement('DROP INDEX IF EXISTS idx_medidor_anomalias_medidor_estado_fecha');
        DB::statement('DROP INDEX IF EXISTS idx_cobros_factura_estado');
        DB::statement('DROP INDEX IF EXISTS idx_facturas_socio_estado');
        DB::statement('DROP INDEX IF EXISTS idx_ordenes_tipo_fecha_id_desc');
        DB::statement('DROP INDEX IF EXISTS idx_ordenes_tipo_estado_socio');
        DB::statement('DROP INDEX IF EXISTS idx_lecturas_medidor_fecha_id_desc');
    }
};
