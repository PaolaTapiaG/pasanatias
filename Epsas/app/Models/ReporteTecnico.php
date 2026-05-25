<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteTecnico extends Model
{
    protected $table = 'reportes_tecnicos';
    protected $primaryKey = 'id_reporte';

    protected $fillable = [
        'titulo',
        'categoria',
        'fecha_reporte',
        'estado',
        'resumen',
        'recomendaciones',
        'id_empleado',
    ];

    protected $casts = [
        'fecha_reporte' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
