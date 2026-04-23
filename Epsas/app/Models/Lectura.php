<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lectura extends Model
{
    protected $table = 'lecturas';
    protected $primaryKey = 'id_lectura';
    public $timestamps = false;

    protected $fillable = [
        'fecha_lectura',
        'valor_lectura',
        'consumo',
        'id_medidor',
        'id_factura',
    ];

    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidor::class, 'id_medidor', 'id_medidor');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura', 'id_factura');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
