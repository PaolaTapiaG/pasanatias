<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->indexes() as $statement) {
            $this->safeStatement($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'idx_ordenes_pago_revision_updated_partial',
            'idx_ordenes_pago_aprobada_revisado_partial',
            'idx_lecturas_created_at_id_desc',
            'idx_cobros_fecha_id_desc',
            'idx_tarifas_estado_created_at',
            'idx_medidores_created_at_id_desc',
            'idx_empleados_created_at_id_desc',
            'idx_socios_created_at_id_desc',
        ] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }

    private function indexes(): array
    {
        return [
            'CREATE INDEX IF NOT EXISTS idx_socios_created_at_id_desc ON socios (created_at DESC, id_socio DESC)',
            'CREATE INDEX IF NOT EXISTS idx_empleados_created_at_id_desc ON empleados (created_at DESC, id_empleado DESC)',
            'CREATE INDEX IF NOT EXISTS idx_medidores_created_at_id_desc ON medidores (created_at DESC, id_medidor DESC)',
            'CREATE INDEX IF NOT EXISTS idx_tarifas_estado_created_at ON tarifas (estado, created_at DESC, id_tarifa DESC)',
            'CREATE INDEX IF NOT EXISTS idx_cobros_fecha_id_desc ON cobros (fecha_cobro DESC, id_cobro DESC)',
            'CREATE INDEX IF NOT EXISTS idx_lecturas_created_at_id_desc ON lecturas (created_at DESC, id_lectura DESC)',
            "CREATE INDEX IF NOT EXISTS idx_ordenes_pago_aprobada_revisado_partial ON ordenes_pago (revisado_en DESC, id_orden_pago DESC) WHERE estado = 'aprobada'",
            "CREATE INDEX IF NOT EXISTS idx_ordenes_pago_revision_updated_partial ON ordenes_pago (updated_at DESC, id_orden_pago DESC) WHERE estado = 'en_revision'",
        ];
    }

    private function safeStatement(string $statement): void
    {
        try {
            DB::statement($statement);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
};
