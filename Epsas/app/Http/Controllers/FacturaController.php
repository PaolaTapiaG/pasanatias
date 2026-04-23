<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Lectura;
use App\Models\Socio;
use App\Models\PeriodoFacturacion;

class FacturaController
{
    // ─────────────────────────────────────────
    //  CRUD Principal
    // ─────────────────────────────────────────

    /**
     * Listar facturas con filtros opcionales.
     * GET /facturas?estado=&id_socio=&id_periodo=
     */
    public function index(array $filtros = []): array
    {
        $query = Factura::with(['socio.persona', 'periodo', 'lectura'])
            ->orderByDesc('fecha_emision');

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (!empty($filtros['id_socio'])) {
            $query->where('id_socio', $filtros['id_socio']);
        }
        if (!empty($filtros['id_periodo'])) {
            $query->where('id_periodo', $filtros['id_periodo']);
        }

        return $query->get()->toArray();
    }

    /**
     * Generar una nueva factura a partir de una lectura.
     * POST /facturas
     *
     * Body esperado:
     * {
     *   "id_lectura": 10,
     *   "id_periodo": 2,
     *   "cargo_fijo": 5.00,       // opcional, se puede leer de la tarifa
     *   "recargo_mora": 0.00,     // opcional
     *   "descuentos": 0.00        // opcional
     * }
     */
    public function store(array $data): array
    {
        $lectura = Lectura::with(['medidor.socio.tarifa'])->findOrFail($data['id_lectura']);

        // Verificar que la lectura no tenga factura ya generada
        if ($lectura->facturas()->exists()) {
            throw new \RuntimeException(
                "La lectura #{$lectura->id_lectura} ya tiene una factura generada."
            );
        }

        $socio  = $lectura->medidor->socio;
        $tarifa = $socio->tarifa;

        // Calcular monto de consumo según tarifa
        $consumo      = $lectura->consumo_m3;
        $montoConsumo = $consumo > $tarifa->consumo_minimo_m3
            ? $consumo * $tarifa->precio_m3_base
            : $tarifa->consumo_minimo_m3 * $tarifa->precio_m3_base;

        $cargoFijo    = $data['cargo_fijo']    ?? $tarifa->cargo_fijo;
        $recagoMora   = $data['recargo_mora']  ?? 0;
        $descuentos   = $data['descuentos']    ?? 0;
        $total        = ($montoConsumo + $cargoFijo + $recagoMora) - $descuentos;

        // Generar número de factura
        $ultimoNum     = Factura::max('numero_factura') ?? 'FAC-000000';
        $secuencia     = (int) substr($ultimoNum, 4) + 1;
        $numeroFactura = 'FAC-' . str_pad($secuencia, 6, '0', STR_PAD_LEFT);

        $factura = Factura::create([
            'numero_factura' => $numeroFactura,
            'fecha_emision'  => now()->toDateString(),
            'consumo_m3'     => $consumo,
            'monto_consumo'  => $montoConsumo,
            'cargo_fijo'     => $cargoFijo,
            'recargo_mora'   => $recagoMora,
            'descuentos'     => $descuentos,
            'total'          => $total,
            'estado'         => 'pendiente',
            'id_socio'       => $socio->id_socio,
            'id_lectura'     => $lectura->id_lectura,
            'id_periodo'     => $data['id_periodo'],
        ]);

        return $factura->load(['socio.persona', 'periodo', 'lectura'])->toArray();
    }

    /**
     * Ver detalle de una factura con sus cobros.
     * GET /facturas/{id}
     */
    public function show(int $id): array
    {
        return Factura::with([
            'socio.persona',
            'socio.sector',
            'periodo',
            'lectura.medidor',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ])->findOrFail($id)->toArray();
    }

    /**
     * Actualizar recargo, descuento o estado de una factura NO pagada.
     * PUT /facturas/{id}
     */
    public function update(int $id, array $data): array
    {
        $factura = Factura::findOrFail($id);

        if (in_array($factura->estado, ['pagada', 'anulada'])) {
            throw new \RuntimeException(
                "No se puede modificar una factura en estado '{$factura->estado}'."
            );
        }

        $camposPermitidos = ['recargo_mora', 'descuentos', 'cargo_fijo'];
        $factura->update(array_intersect_key($data, array_flip($camposPermitidos)));

        // Recalcular total
        $factura->total = ($factura->monto_consumo + $factura->cargo_fijo + $factura->recargo_mora)
                          - $factura->descuentos;
        $factura->save();

        return $factura->fresh(['socio.persona', 'periodo'])->toArray();
    }

    /**
     * Anular una factura.
     * DELETE /facturas/{id}
     */
    public function destroy(int $id): array
    {
        $factura = Factura::findOrFail($id);

        if ($factura->estado === 'pagada') {
            throw new \RuntimeException(
                "No se puede anular una factura ya pagada. Use el proceso de devolución."
            );
        }

        $factura->update(['estado' => 'anulada']);
        return ['mensaje' => "Factura #{$factura->numero_factura} anulada correctamente."];
    }

    // ─────────────────────────────────────────
    //  Acciones de negocio
    // ─────────────────────────────────────────

    /**
     * Marcar facturas vencidas (fecha_pago < hoy y siguen 'pendiente').
     * POST /facturas/marcar-vencidas
     */
    public function marcarVencidas(): array
    {
        $cantidad = Factura::where('estado', 'pendiente')
            ->whereNotNull('fecha_pago')
            ->where('fecha_pago', '<', now()->toDateString())
            ->update(['estado' => 'vencida']);

        return ['mensaje' => "$cantidad factura(s) marcadas como vencidas."];
    }

    /**
     * Resumen de facturas por estado para un período.
     * GET /facturas/resumen?id_periodo=
     */
    public function resumenPorPeriodo(int $idPeriodo): array
    {
        $facturas = Factura::where('id_periodo', $idPeriodo)->get();

        return [
            'total_facturas' => $facturas->count(),
            'pendientes'     => $facturas->where('estado', 'pendiente')->count(),
            'pagadas'        => $facturas->where('estado', 'pagada')->count(),
            'vencidas'       => $facturas->where('estado', 'vencida')->count(),
            'parciales'      => $facturas->where('estado', 'parcial')->count(),
            'anuladas'       => $facturas->where('estado', 'anulada')->count(),
            'monto_total'    => $facturas->whereNotIn('estado', ['anulada'])->sum('total'),
            'monto_cobrado'  => $facturas->where('estado', 'pagada')->sum('total'),
            'monto_pendiente'=> $facturas->whereIn('estado', ['pendiente', 'vencida', 'parcial'])->sum('total'),
        ];
    }

    /**
     * Aplicar recargo de mora masivo a facturas vencidas de un período.
     * POST /facturas/aplicar-mora
     */
    public function aplicarMoraMasiva(int $idPeriodo, float $porcentajeMora): array
    {
        $facturas = Factura::where('id_periodo', $idPeriodo)
            ->where('estado', 'vencida')
            ->get();

        $cantidad = 0;
        foreach ($facturas as $factura) {
            $mora = round($factura->total * ($porcentajeMora / 100), 2);
            $factura->recargo_mora += $mora;
            $factura->total        += $mora;
            $factura->save();
            $cantidad++;
        }

        return ['mensaje' => "Mora del {$porcentajeMora}% aplicada a $cantidad factura(s) vencidas."];
    }
}