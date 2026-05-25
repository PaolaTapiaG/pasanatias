<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medidor_anomalias', function (Blueprint $table) {
            $table->string('evidencia_path')->nullable()->after('descripcion');
        });

        Schema::table('incidencias_tecnicas', function (Blueprint $table) {
            $table->string('evidencia_path')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('incidencias_tecnicas', function (Blueprint $table) {
            $table->dropColumn('evidencia_path');
        });

        Schema::table('medidor_anomalias', function (Blueprint $table) {
            $table->dropColumn('evidencia_path');
        });
    }
};
