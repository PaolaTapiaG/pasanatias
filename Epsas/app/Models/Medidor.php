<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medidor extends Model
{
    protected $table = 'medidores';
    protected $primaryKey = 'id_medidor';
    public $timestamps = false;

    protected $fillable = [
        'numero_medidor',
        'marca',
        'modelo',
        'fecha_instalacion',
        'estado',
        'id_socio',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class, 'id_medidor', 'id_medidor');
    }
}
