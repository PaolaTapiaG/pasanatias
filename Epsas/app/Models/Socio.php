<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Socio extends Model
{
    protected $table      = 'socios';
    protected $primaryKey = 'id_socio';

    protected $fillable = [
        'numero_socio',
        'direccion',
        'fecha_registro',
        'estado',
        'id_persona',
        'id_sector',
        'id_tarifa',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
    ];

    // ── Scopes ─────────────────────────────────
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeCortados($query)
    {
        return $query->where('estado', 'cortado');
    }

    public function scopePorSector($query, int $idSector)
    {
        return $query->where('id_sector', $idSector);
    }

    // ── Accessors ──────────────────────────────
    public function getNombreCompletoAttribute(): string
    {
        return $this->persona?->nombre_completo ?? '—';
    }

    // ── Relaciones ─────────────────────────────
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'id_sector', 'id_sector');
    }

    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(Tarifa::class, 'id_tarifa', 'id_tarifa');
    }

    /** Medidor actualmente activo. */
    public function medidorActivo(): HasOne
    {
        return $this->hasOne(Medidor::class, 'id_socio', 'id_socio')
                    ->where('estado', 'activo');
    }

    /** Todos los medidores (historial). */
    public function medidores(): HasMany
    {
        return $this->hasMany(Medidor::class, 'id_socio', 'id_socio');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_socio', 'id_socio');
    }

    public function facturasPendientes(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_socio', 'id_socio')
                    ->whereIn('estado', ['pendiente', 'vencida', 'parcial']);
    }

    public function historialPagos(): HasMany
    {
        return $this->hasMany(HistorialPago::class, 'id_socio', 'id_socio');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_socio', 'id_socio');
    }
}