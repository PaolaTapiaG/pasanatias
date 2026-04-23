<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoFacturacion extends Model
{
    use HasFactory;

    protected $table = 'periodos_fabricacion'; // nombre exacto del diagrama
    protected $primaryKey = 'id_periodo';

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'cerrado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'cerrado'      => 'boolean',
        'creado_en'    => 'datetime',
    ];

    // ─── Relaciones ───────────────────────────────────────────────

    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'id_periodo');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'id_periodo');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeAbiertos($query)
    {
        return $query->where('cerrado', false);
    }

    public function scopeCerrados($query)
    {
        return $query->where('cerrado', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function cerrar(): void
    {
        $this->update(['cerrado' => true]);
    }

    public function estaAbierto(): bool
    {
        return ! $this->cerrado;
    }
}