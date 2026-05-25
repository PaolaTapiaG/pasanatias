<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medidor_anomalias', function (Blueprint $table) {
            $table->id('id_anomalia');
            $table->date('fecha_reporte')->default(DB::raw('CURRENT_DATE'));
            $table->string('tipo', 40);
            $table->string('prioridad', 20)->default('media');
            $table->string('estado', 20)->default('pendiente');
            $table->text('descripcion');
            $table->foreignId('id_medidor')->constrained('medidores', 'id_medidor');
            $table->foreignId('id_empleado')->constrained('empleados', 'id_empleado');
            $table->timestamps();

            $table->index(['estado', 'prioridad']);
            $table->index('fecha_reporte');
        });

        Schema::create('ordenes_tecnicas', function (Blueprint $table) {
            $table->id('id_orden');
            $table->string('tipo', 30);
            $table->string('estado', 20)->default('pendiente');
            $table->string('prioridad', 20)->default('media');
            $table->date('fecha_programada')->nullable();
            $table->date('fecha_ejecucion')->nullable();
            $table->string('zona', 120)->nullable();
            $table->string('referencia', 120)->nullable();
            $table->text('descripcion');
            $table->decimal('coord_x', 5, 2)->nullable();
            $table->decimal('coord_y', 5, 2)->nullable();
            $table->foreignId('id_socio')->nullable()->constrained('socios', 'id_socio')->nullOnDelete();
            $table->foreignId('id_medidor')->nullable()->constrained('medidores', 'id_medidor')->nullOnDelete();
            $table->foreignId('id_empleado')->constrained('empleados', 'id_empleado');
            $table->timestamps();

            $table->index(['tipo', 'estado']);
            $table->index(['fecha_programada', 'prioridad']);
        });

        Schema::create('operaciones_sistema', function (Blueprint $table) {
            $table->id('id_operacion');
            $table->date('fecha_operacion')->default(DB::raw('CURRENT_DATE'));
            $table->string('tipo_operacion', 40);
            $table->string('zona', 120);
            $table->string('estado', 20)->default('operativa');
            $table->string('horario', 120)->nullable();
            $table->text('descripcion');
            $table->foreignId('id_empleado')->constrained('empleados', 'id_empleado');
            $table->timestamps();

            $table->index(['estado', 'fecha_operacion']);
        });

        Schema::create('incidencias_tecnicas', function (Blueprint $table) {
            $table->id('id_incidencia');
            $table->string('tipo', 40);
            $table->string('prioridad', 20)->default('media');
            $table->string('estado', 20)->default('abierta');
            $table->string('zona', 120);
            $table->dateTime('fecha_reporte');
            $table->text('descripcion');
            $table->decimal('coord_x', 5, 2)->nullable();
            $table->decimal('coord_y', 5, 2)->nullable();
            $table->foreignId('id_socio')->nullable()->constrained('socios', 'id_socio')->nullOnDelete();
            $table->foreignId('id_empleado')->constrained('empleados', 'id_empleado');
            $table->timestamps();

            $table->index(['estado', 'prioridad']);
            $table->index('fecha_reporte');
        });

        Schema::create('reportes_tecnicos', function (Blueprint $table) {
            $table->id('id_reporte');
            $table->string('titulo', 160);
            $table->string('categoria', 40);
            $table->date('fecha_reporte')->default(DB::raw('CURRENT_DATE'));
            $table->string('estado', 20)->default('borrador');
            $table->text('resumen');
            $table->text('recomendaciones')->nullable();
            $table->foreignId('id_empleado')->constrained('empleados', 'id_empleado');
            $table->timestamps();

            $table->index(['categoria', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_tecnicos');
        Schema::dropIfExists('incidencias_tecnicas');
        Schema::dropIfExists('operaciones_sistema');
        Schema::dropIfExists('ordenes_tecnicas');
        Schema::dropIfExists('medidor_anomalias');
    }
};
