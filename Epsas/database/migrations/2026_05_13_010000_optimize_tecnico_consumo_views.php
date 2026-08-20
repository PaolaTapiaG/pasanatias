<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') return;

        DB::statement('CREATE INDEX IF NOT EXISTS idx_lecturas_fecha_id_desc ON lecturas (fecha_lectura DESC, id_lectura DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medidores_estado_serie ON medidores (estado, numero_serie)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medidores_socio_estado ON medidores (id_socio, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_socios_persona_sector ON socios (id_persona, id_sector)');

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_tecnico_medidores_consumo WITH (security_invoker = true) AS
SELECT
    m.id_medidor,
    m.numero_serie,
    m.id_socio,
    COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) AS codigo_usuario,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS socio_nombre,
    COALESCE(sec.nombre, 'Sin zona') AS zona,
    COALESCE(s.direccion, 'Sin direccion') AS direccion,
    COALESCE(ul.lectura_actual, 0) AS lectura_sugerida,
    ul.fecha_lectura AS ultima_fecha
FROM medidores m
LEFT JOIN socios s ON s.id_socio = m.id_socio
LEFT JOIN personas p ON p.id_persona = s.id_persona
LEFT JOIN sectores sec ON sec.id_sector = s.id_sector
LEFT JOIN LATERAL (
    SELECT l.lectura_actual, l.fecha_lectura
    FROM lecturas l
    WHERE l.id_medidor = m.id_medidor
    ORDER BY l.fecha_lectura DESC, l.id_lectura DESC
    LIMIT 1
) ul ON TRUE
WHERE m.estado = 'activo'
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_tecnico_lecturas_recientes WITH (security_invoker = true) AS
SELECT
    l.id_lectura,
    l.fecha_lectura,
    l.lectura_actual,
    l.consumo_m3,
    l.id_medidor,
    m.numero_serie,
    COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) AS codigo_usuario,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS socio_nombre
FROM lecturas l
LEFT JOIN medidores m ON m.id_medidor = l.id_medidor
LEFT JOIN socios s ON s.id_socio = m.id_socio
LEFT JOIN personas p ON p.id_persona = s.id_persona
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_tecnico_lecturas_recientes');
        DB::statement('DROP VIEW IF EXISTS v_tecnico_medidores_consumo');
        DB::statement('DROP INDEX IF EXISTS idx_socios_persona_sector');
        DB::statement('DROP INDEX IF EXISTS idx_medidores_socio_estado');
        DB::statement('DROP INDEX IF EXISTS idx_medidores_estado_serie');
        DB::statement('DROP INDEX IF EXISTS idx_lecturas_fecha_id_desc');
    }
};
