<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperacionSistema extends Model
{
    protected $table = 'operaciones_sistema';
    protected $primaryKey = 'id_operacion';

    protected $fillable = [
        'fecha_operacion',
        'tipo_operacion',
        'zona',
        'estado',
        'horario',
        'descripcion',
        'id_empleado',
    ];

    protected $casts = [
        'fecha_operacion' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
