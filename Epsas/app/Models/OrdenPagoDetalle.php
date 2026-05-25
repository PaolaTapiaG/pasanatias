<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenPagoDetalle extends Model
{
    protected $table = 'orden_pago_detalles';
    protected $primaryKey = 'id_detalle';

    protected $fillable = [
        'id_orden_pago',
        'tipo',
        'referencia_id',
        'descripcion',
        'monto',
        'metadata',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ordenPago(): BelongsTo
    {
        return $this->belongsTo(OrdenPago::class, 'id_orden_pago', 'id_orden_pago');
    }
}
