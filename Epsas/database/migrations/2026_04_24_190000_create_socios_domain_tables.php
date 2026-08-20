<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personas')) {
            Schema::create('personas', function (Blueprint $table) {
                $table->id('id_persona');
                $table->string('nombres');
                $table->string('apellidos');
                $table->string('cedula_identidad')->unique();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->date('fecha_nacimiento')->nullable();
                $table->string('foto_path')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sectores')) {
            Schema::create('sectores', function (Blueprint $table) {
                $table->id('id_sector');
                $table->string('nombre');
                $table->string('descripcion')->nullable();
                $table->string('zona');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tarifas')) {
            Schema::create('tarifas', function (Blueprint $table) {
                $table->id('id_tarifa');
                $table->string('nombre');
                $table->decimal('precio_m3_base', 10, 2);
                $table->decimal('consumo_minimo_m3', 10, 2)->default(0);
                $table->decimal('cargo_fijo', 10, 2)->default(0);
                $table->date('fecha_vigencia')->nullable();
                $table->string('estado')->default('activa');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('socios')) {
            Schema::create('socios', function (Blueprint $table) {
                $table->id('id_socio');
                $table->string('numero_socio')->unique();
                $table->string('direccion')->nullable();
                $table->date('fecha_registro')->nullable();
                $table->string('estado')->default('activo');
                $table->boolean('oculto')->default(false);
                $table->text('motivo_ocultacion')->nullable();
                $table->timestamp('oculto_en')->nullable();
                $table->foreignId('oculto_por')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('id_persona')->constrained('personas', 'id_persona')->cascadeOnDelete();
                $table->foreignId('id_sector')->constrained('sectores', 'id_sector');
                $table->foreignId('id_tarifa')->constrained('tarifas', 'id_tarifa');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('medidores')) {
            Schema::create('medidores', function (Blueprint $table) {
                $table->id('id_medidor');
                $table->string('numero_serie')->unique();
                $table->string('marca')->nullable();
                $table->string('modelo')->nullable();
                $table->date('fecha_instalacion')->nullable();
                $table->string('estado')->default('activo');
                $table->foreignId('id_socio')->nullable()->constrained('socios', 'id_socio')->nullOnDelete();
                $table->foreignId('id_empleado_instalador')->nullable()->constrained('empleados', 'id_empleado')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('metodos_pago')) {
            Schema::create('metodos_pago', function (Blueprint $table) {
                $table->id('id_metodo_pago');
                $table->string('nombre')->unique();
                $table->text('descripcion')->nullable();
                $table->boolean('requiere_referencia')->default(false);
                $table->string('estado')->default('activo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('periodos_facturacion')) {
            Schema::create('periodos_facturacion', function (Blueprint $table) {
                $table->id('id_periodo');
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->string('descripcion')->nullable();
                $table->string('estado')->default('activo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lecturas')) {
            Schema::create('lecturas', function (Blueprint $table) {
                $table->id('id_lectura');
                $table->unsignedBigInteger('id_medidor');
                $table->decimal('lectura_actual', 10, 2);
                $table->dateTime('fecha_lectura');
                $table->text('observaciones')->nullable();
                $table->timestamps();
                $table->foreign('id_medidor')->references('id_medidor')->on('medidores')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('facturas')) {
            Schema::create('facturas', function (Blueprint $table) {
                $table->id('id_factura');
                $table->unsignedBigInteger('id_socio');
                $table->unsignedBigInteger('id_periodo');
                $table->unsignedBigInteger('id_tarifa');
                $table->string('numero_factura')->unique();
                $table->date('fecha_emision');
                $table->decimal('consumo_m3', 10, 2)->default(0);
                $table->decimal('monto_base', 12, 2)->default(0);
                $table->decimal('monto_total', 12, 2)->default(0);
                $table->string('estado')->default('pendiente');
                $table->dateTime('fecha_vencimiento')->nullable();
                $table->timestamps();
                $table->foreign('id_socio')->references('id_socio')->on('socios')->onDelete('cascade');
                $table->foreign('id_periodo')->references('id_periodo')->on('periodos_facturacion');
                $table->foreign('id_tarifa')->references('id_tarifa')->on('tarifas');
            });
        }

        if (!Schema::hasTable('cobros')) {
            Schema::create('cobros', function (Blueprint $table) {
                $table->id('id_cobro');
                $table->unsignedBigInteger('id_factura');
                $table->unsignedBigInteger('id_metodo_pago');
                $table->decimal('monto_pagado', 12, 2);
                $table->date('fecha_cobro');
                $table->string('comprobante_referencia')->nullable();
                $table->string('estado')->default('confirmado');
                $table->timestamps();
                $table->foreign('id_factura')->references('id_factura')->on('facturas')->onDelete('cascade');
                $table->foreign('id_metodo_pago')->references('id_metodo_pago')->on('metodos_pago');
            });
        }

        if (!Schema::hasTable('historial_pagos')) {
            Schema::create('historial_pagos', function (Blueprint $table) {
                $table->id('id_historial');
                $table->unsignedBigInteger('id_factura');
                $table->decimal('monto', 12, 2);
                $table->string('tipo');
                $table->text('descripcion')->nullable();
                $table->timestamps();
                $table->foreign('id_factura')->references('id_factura')->on('facturas')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('notificaciones')) {
            Schema::create('notificaciones', function (Blueprint $table) {
                $table->id('id_notificacion');
                $table->unsignedBigInteger('id_socio')->nullable();
                $table->string('tipo');
                $table->text('contenido');
                $table->boolean('leida')->default(false);
                $table->timestamps();
                $table->foreign('id_socio')->references('id_socio')->on('socios')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('auditoria')) {
            Schema::create('auditoria', function (Blueprint $table) {
                $table->id('id_auditoria');
                $table->string('tabla');
                $table->string('accion');
                $table->string('usuario')->nullable();
                $table->json('datos_antes')->nullable();
                $table->json('datos_despues')->nullable();
                $table->timestamp('fecha_cambio')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('historial_pagos');
        Schema::dropIfExists('cobros');
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('lecturas');
        Schema::dropIfExists('periodos_facturacion');
        Schema::dropIfExists('metodos_pago');
        Schema::dropIfExists('medidores');
        Schema::dropIfExists('socios');
        Schema::dropIfExists('tarifas');
        Schema::dropIfExists('sectores');
        Schema::dropIfExists('personas');
    }
};
