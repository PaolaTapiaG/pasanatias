<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    protected $table = 'facturas';
    protected $primaryKey = 'id_factura';
    public $timestamps = false;

    protected $fillable = [
        'numero_factura',
        'fecha_emision',
        'fecha_vencimiento',
        'total',
        'estado',
        'fecha_pago',
        'id_socio',
        'id_periodo',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class, 'id_factura', 'id_factura');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class, 'id_factura', 'id_factura');
    }
}
