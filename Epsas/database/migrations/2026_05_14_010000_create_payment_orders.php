<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_pago', function (Blueprint $table) {
            $table->id('id_orden_pago');
            $table->string('codigo', 30)->unique();
            $table->foreignId('id_socio')->constrained('socios', 'id_socio')->cascadeOnDelete();
            $table->decimal('total', 12, 2);
            $table->string('estado', 30)->default('pendiente');
            $table->string('metodo', 40)->default('qr_estatico');
            $table->string('access_token', 80);
            $table->timestampTz('fecha_vencimiento')->nullable();
            $table->string('comprobante_path')->nullable();
            $table->string('comprobante_referencia', 120)->nullable();
            $table->decimal('comprobante_monto', 12, 2)->nullable();
            $table->date('comprobante_fecha')->nullable();
            $table->text('observaciones_cliente')->nullable();
            $table->text('notas_revision')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('empleados', 'id_empleado')->nullOnDelete();
            $table->timestampTz('revisado_en')->nullable();
            $table->timestampsTz();

            $table->index(['estado', 'created_at']);
            $table->index(['id_socio', 'estado']);
            $table->index('access_token');
        });

        Schema::create('orden_pago_detalles', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->foreignId('id_orden_pago')->constrained('ordenes_pago', 'id_orden_pago')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->unsignedBigInteger('referencia_id');
            $table->string('descripcion', 180);
            $table->decimal('monto', 12, 2);
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->unique(['id_orden_pago', 'tipo', 'referencia_id'], 'orden_detalle_ref_unique');
            $table->index(['tipo', 'referencia_id']);
        });

        Schema::table('cobros', function (Blueprint $table) {
            $table->foreignId('id_orden_pago')->nullable()->after('id_empleado')->constrained('ordenes_pago', 'id_orden_pago')->nullOnDelete();
        });

        // Add CHECK constraint only on PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE ordenes_pago ADD CONSTRAINT chk_ordenes_pago_estado CHECK (estado IN ('pendiente', 'en_revision', 'aprobada', 'rechazada', 'cancelada', 'vencida'))");
        }
    }

    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_orden_pago');
        });

        Schema::dropIfExists('orden_pago_detalles');
        Schema::dropIfExists('ordenes_pago');
    }
};
