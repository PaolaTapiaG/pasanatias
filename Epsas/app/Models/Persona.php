<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $table      = 'personas';
    protected $primaryKey = 'id_persona';

    protected $fillable = [
        'nombres',
        'apellidos',
        'cedula_identidad',
        'telefono',
        'email',
    ];

    // ── Accessors ──────────────────────────────
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    // ── Relaciones ─────────────────────────────
    public function socio(): HasOne
    {
        return $this->hasOne(Socio::class, 'id_persona', 'id_persona');
    }

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class, 'id_persona', 'id_persona');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_persona', 'id_persona');
    }
}