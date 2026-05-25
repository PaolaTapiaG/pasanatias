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
        // Add columns compatible with both SQLite and PostgreSQL
        if (!Schema::hasColumn('facturas', 'fecha_inicio_cobro')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->date('fecha_inicio_cobro')->nullable();
            });
        }
        
        if (!Schema::hasColumn('facturas', 'fecha_fin_cobro')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->date('fecha_fin_cobro')->nullable();
            });
        }

        // Update dates - compatible approach for both databases
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
UPDATE facturas f
SET
    fecha_inicio_cobro = COALESCE(f.fecha_inicio_cobro, pf.fecha_inicio),
    fecha_fin_cobro = COALESCE(f.fecha_fin_cobro, pf.fecha_fin)
FROM periodos_facturacion pf
WHERE pf.id_periodo = f.id_periodo
  AND (f.fecha_inicio_cobro IS NULL OR f.fecha_fin_cobro IS NULL)
SQL);
        } else {
            // SQLite uses different UPDATE syntax
            DB::update(<<<'SQL'
UPDATE facturas SET
    fecha_inicio_cobro = COALESCE(fecha_inicio_cobro, (SELECT fecha_inicio FROM periodos_facturacion WHERE id_periodo = facturas.id_periodo)),
    fecha_fin_cobro = COALESCE(fecha_fin_cobro, (SELECT fecha_fin FROM periodos_facturacion WHERE id_periodo = facturas.id_periodo))
WHERE fecha_inicio_cobro IS NULL OR fecha_fin_cobro IS NULL
SQL);
        }

        // Create indexes only on PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_socio_fecha_fin_cobro ON facturas (id_socio, fecha_fin_cobro DESC)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_socio_periodo_estado ON facturas (id_socio, id_periodo, estado)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_lecturas_medidor_fecha_facturacion ON lecturas (id_medidor, fecha_lectura, id_lectura)');
        }


        if (!Schema::hasTable('intentos_pago')) {
            Schema::create('intentos_pago', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('numero_socio', 30)->index();
                $table->decimal('monto', 12, 2);
                $table->string('referencia', 100)->unique();
                $table->string('estado', 30)->default('pendiente')->index();
                $table->timestampsTz();
            });
        }

        $this->safeStatement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach ($this->trigramIndexes() as $statement) {
            $this->safeStatement($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'idx_roles_nombre_trgm',
            'idx_tarifas_nombre_trgm',
            'idx_sectores_nombre_trgm',
            'idx_facturas_numero_trgm',
            'idx_medidores_modelo_trgm',
            'idx_medidores_marca_trgm',
            'idx_medidores_serie_trgm',
            'idx_socios_direccion_trgm',
            'idx_socios_numero_trgm',
            'idx_personas_email_trgm',
            'idx_personas_ci_trgm',
            'idx_personas_apellidos_trgm',
            'idx_personas_nombres_trgm',
            'idx_lecturas_medidor_fecha_facturacion',
            'idx_facturas_socio_periodo_estado',
            'idx_facturas_socio_fecha_fin_cobro',
        ] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }

        Schema::dropIfExists('intentos_pago');
        DB::statement('ALTER TABLE facturas DROP COLUMN IF EXISTS fecha_inicio_cobro');
        DB::statement('ALTER TABLE facturas DROP COLUMN IF EXISTS fecha_fin_cobro');
    }

    private function trigramIndexes(): array
    {
        return [
            'CREATE INDEX IF NOT EXISTS idx_personas_nombres_trgm ON personas USING gin (nombres gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_personas_apellidos_trgm ON personas USING gin (apellidos gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_personas_ci_trgm ON personas USING gin (cedula_identidad gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_personas_email_trgm ON personas USING gin (email gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_socios_numero_trgm ON socios USING gin (numero_socio gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_socios_direccion_trgm ON socios USING gin (direccion gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_medidores_serie_trgm ON medidores USING gin (numero_serie gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_medidores_marca_trgm ON medidores USING gin (marca gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_medidores_modelo_trgm ON medidores USING gin (modelo gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_facturas_numero_trgm ON facturas USING gin (numero_factura gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_sectores_nombre_trgm ON sectores USING gin (nombre gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_tarifas_nombre_trgm ON tarifas USING gin (nombre gin_trgm_ops)',
            'CREATE INDEX IF NOT EXISTS idx_roles_nombre_trgm ON roles USING gin (nombre gin_trgm_ops)',
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
