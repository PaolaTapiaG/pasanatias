<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Factura;
use App\Models\HistorialPago;

class CobroController
{
    // ─────────────────────────────────────────
    //  CRUD Principal
    // ─────────────────────────────────────────

    /**
     * Listar cobros con filtros opcionales.
     * GET /cobros?estado=&id_empleado=&fecha_desde=&fecha_hasta=
     */
    public function index(array $filtros = []): array
    {
        $query = Cobro::with([
            'factura.socio.persona',
            'metodoPago',
            'empleado.persona',
        ])->orderByDesc('fecha_cobro');

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (!empty($filtros['id_empleado'])) {
            $query->where('id_empleado', $filtros['id_empleado']);
        }
        if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $query->whereBetween('fecha_cobro', [
                $filtros['fecha_desde'],
                $filtros['fecha_hasta'],
            ]);
        }

        return $query->get()->toArray();
    }

    /**
     * Registrar un cobro (pago total o parcial) sobre una factura.
     * POST /cobros
     *
     * Body esperado:
     * {
     *   "id_factura": 15,
     *   "id_metodo_pago": 1,
     *   "id_empleado": 2,
     *   "monto_pagado": 85.50,
     *   "comprobante": "REC-2024-001"   // opcional
     * }
     */
    public function store(array $data): array
    {
        $factura = Factura::findOrFail($data['id_factura']);

        // Validaciones previas
        if (in_array($factura->estado, ['pagada', 'anulada'])) {
            throw new \RuntimeException(
                "La factura #{$factura->numero_factura} ya está en estado '{$factura->estado}'."
            );
        }

        $montoPagado   = (float) $data['monto_pagado'];
        $montoPendiente = (float) $factura->total - $this->totalCobradoPrevio($factura);

        if ($montoPagado <= 0) {
            throw new \InvalidArgumentException("El monto pagado debe ser mayor a 0.");
        }

        if ($montoPagado > $montoPendiente) {
            throw new \InvalidArgumentException(
                "El monto pagado ($montoPagado) supera el monto pendiente ($montoPendiente)."
            );
        }

        $nuevoMontoPendiente = round($montoPendiente - $montoPagado, 2);
        $estadoCobro = $nuevoMontoPendiente == 0 ? 'completado' : 'parcial';

        // Crear el cobro
        $cobro = Cobro::create([
            'fecha_cobro'      => now()->toDateString(),
            'monto_pagado'     => $montoPagado,
            'monto_pendiente'  => $nuevoMontoPendiente,
            'estado'           => $estadoCobro,
            'comprobante'      => $data['comprobante'] ?? null,
            'id_factura'       => $factura->id_factura,
            'id_metodo_pago'   => $data['id_metodo_pago'],
            'id_empleado'      => $data['id_empleado'],
        ]);

        // Actualizar estado de la factura
        $nuevoEstadoFactura = $nuevoMontoPendiente == 0 ? 'pagada' : 'parcial';
        $factura->update([
            'estado'     => $nuevoEstadoFactura,
            'fecha_pago' => $nuevoMontoPendiente == 0 ? now()->toDateString() : null,
        ]);

        // Registrar en historial de pagos
        $this->registrarHistorial($cobro, $factura, $data['id_empleado']);

        return $cobro->load([
            'factura.socio.persona',
            'metodoPago',
            'empleado.persona',
        ])->toArray();
    }

    /**
     * Ver detalle de un cobro.
     * GET /cobros/{id}
     */
    public function show(int $id): array
    {
        return Cobro::with([
            'factura.socio.persona',
            'metodoPago',
            'empleado.persona',
        ])->findOrFail($id)->toArray();
    }

    /**
     * Anular un cobro (reversión de pago).
     * DELETE /cobros/{id}
     *
     * Body esperado:
     * { "id_empleado": 2, "motivo": "Error en el monto registrado" }
     */
    public function anular(int $id, array $data): array
    {
        $cobro   = Cobro::with('factura')->findOrFail($id);
        $factura = $cobro->factura;

        if ($cobro->estado === 'anulado') {
            throw new \RuntimeException("El cobro ya está anulado.");
        }

        // Anular el cobro
        $cobro->update(['estado' => 'anulado']);

        // Revertir el estado de la factura
        $totalCobradoRestante = $this->totalCobradoPrevio($factura);
        $nuevoEstado = match (true) {
            $totalCobradoRestante == 0  => 'pendiente',
            $totalCobradoRestante > 0   => 'parcial',
            default                     => 'pendiente',
        };
        $factura->update([
            'estado'     => $nuevoEstado,
            'fecha_pago' => null,
        ]);

        // Registrar en historial
        HistorialPago::create([
            'tipo_evento' => 'anulacion_cobro',
            'descripcion' => $data['motivo'] ?? 'Cobro anulado',
            'monto'       => $cobro->monto_pagado,
            'id_socio'    => $factura->id_socio,
            'id_factura'  => $factura->id_factura,
            'id_cobro'    => $cobro->id_cobro,
            'id_empleado' => $data['id_empleado'],
        ]);

        return [
            'mensaje'        => "Cobro #{$cobro->id_cobro} anulado. Factura revertida a '$nuevoEstado'.",
            'cobro'          => $cobro->toArray(),
            'factura_estado' => $nuevoEstado,
        ];
    }

    // ─────────────────────────────────────────
    //  Acciones de negocio
    // ─────────────────────────────────────────

    /**
     * Resumen de caja del día (cobros por empleado y método de pago).
     * GET /cobros/resumen-caja?fecha=&id_empleado=
     */
    public function resumenCaja(string $fecha, ?int $idEmpleado = null): array
    {
        $query = Cobro::where('fecha_cobro', $fecha)
            ->where('estado', '!=', 'anulado');

        if ($idEmpleado) {
            $query->where('id_empleado', $idEmpleado);
        }

        $cobros = $query->with(['metodoPago', 'empleado.persona'])->get();

        $porMetodo = $cobros->groupBy('id_metodo_pago')->map(fn($grupo) => [
            'metodo'    => $grupo->first()->metodoPago->nombre,
            'cantidad'  => $grupo->count(),
            'total'     => $grupo->sum('monto_pagado'),
        ])->values();

        return [
            'fecha'         => $fecha,
            'total_cobrado' => $cobros->sum('monto_pagado'),
            'cantidad_cobros' => $cobros->count(),
            'por_metodo_pago' => $porMetodo,
        ];
    }

    /**
     * Cobros de una factura específica.
     * GET /cobros/factura/{idFactura}
     */
    public function porFactura(int $idFactura): array
    {
        return Cobro::with(['metodoPago', 'empleado.persona'])
            ->where('id_factura', $idFactura)
            ->orderBy('fecha_cobro')
            ->get()
            ->toArray();
    }

    // ─────────────────────────────────────────
    //  Helpers privados
    // ─────────────────────────────────────────

    /**
     * Suma de montos cobrados válidos (no anulados) de una factura.
     */
    private function totalCobradoPrevio(Factura $factura): float
    {
        return (float) $factura->cobros()
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
    }

    /**
     * Registrar evento en historial de pagos.
     */
    private function registrarHistorial(Cobro $cobro, Factura $factura, int $idEmpleado): void
    {
        HistorialPago::create([
            'tipo_evento' => $cobro->estado === 'completado' ? 'pago_completo' : 'pago_parcial',
            'descripcion' => "Cobro registrado. Método: " . ($cobro->metodoPago?->nombre ?? '-') . ". "
                            . "Pendiente: {$cobro->monto_pendiente}",
            'monto'       => $cobro->monto_pagado,
            'id_socio'    => $factura->id_socio,
            'id_factura'  => $factura->id_factura,
            'id_cobro'    => $cobro->id_cobro,
            'id_empleado' => $idEmpleado,
        ]);
    }
}