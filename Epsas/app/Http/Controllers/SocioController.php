<?php

namespace App\Controllers;

use App\Models\Socio;
use App\Models\Persona;
use App\Models\Medidor;
use App\Models\Factura;

class SocioController
{
    // ─────────────────────────────────────────
    //  CRUD Principal
    // ─────────────────────────────────────────

    /**
     * Listar todos los socios con su persona, sector y tarifa.
     * GET /socios
     */
    public function index(): array
    {
        return Socio::with(['persona', 'sector', 'tarifa'])
            ->orderBy('numero_socio')
            ->get()
            ->toArray();
    }

    /**
     * Crear un nuevo socio junto con su persona.
     * POST /socios
     *
     * Body esperado:
     * {
     *   "nombres": "Juan",
     *   "apellidos": "Pérez",
     *   "cedula_identidad": "1234567",
     *   "telefono": "70000000",
     *   "email": "juan@mail.com",
     *   "direccion": "Calle Falsa 123",
     *   "id_sector": 1,
     *   "id_tarifa": 1
     * }
     */
    public function store(array $data): array
    {
        // 1. Crear o recuperar la persona
        $persona = Persona::firstOrCreate(
            ['cedula_identidad' => $data['cedula_identidad']],
            [
                'nombres'  => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'] ?? null,
                'email'    => $data['email']    ?? null,
            ]
        );

        // 2. Generar número de socio correlativo
        $ultimoNumero = Socio::max('numero_socio') ?? 'SOC-0000';
        $secuencia    = (int) substr($ultimoNumero, 4) + 1;
        $numeroSocio  = 'SOC-' . str_pad($secuencia, 4, '0', STR_PAD_LEFT);

        // 3. Crear el socio
        $socio = Socio::create([
            'numero_socio' => $numeroSocio,
            'direccion'    => $data['direccion'] ?? null,
            'id_persona'   => $persona->id_persona,
            'id_sector'    => $data['id_sector'],
            'id_tarifa'    => $data['id_tarifa'],
            'estado'       => 'activo',
        ]);

        return $socio->load(['persona', 'sector', 'tarifa'])->toArray();
    }

    /**
     * Ver detalle de un socio.
     * GET /socios/{id}
     */
    public function show(int $id): array
    {
        $socio = Socio::with(['persona', 'sector', 'tarifa', 'medidores', 'facturas'])
            ->findOrFail($id);

        return $socio->toArray();
    }

    /**
     * Actualizar datos del socio y/o su persona.
     * PUT /socios/{id}
     */
    public function update(int $id, array $data): array
    {
        $socio = Socio::with('persona')->findOrFail($id);

        // Actualizar persona si vienen datos personales
        $camposPersona = ['nombres', 'apellidos', 'telefono', 'email'];
        $datosPersona  = array_intersect_key($data, array_flip($camposPersona));
        if (!empty($datosPersona)) {
            $socio->persona->update($datosPersona);
        }

        // Actualizar campos del socio
        $camposSocio = ['direccion', 'id_sector', 'id_tarifa', 'estado'];
        $datosSocio  = array_intersect_key($data, array_flip($camposSocio));
        if (!empty($datosSocio)) {
            $socio->update($datosSocio);
        }

        return $socio->fresh(['persona', 'sector', 'tarifa'])->toArray();
    }

    /**
     * Eliminar (o desactivar) un socio.
     * DELETE /socios/{id}
     */
    public function destroy(int $id): array
    {
        $socio = Socio::findOrFail($id);

        // Soft-delete lógico: pasar a inactivo si tiene facturas
        if ($socio->facturas()->exists()) {
            $socio->update(['estado' => 'inactivo']);
            return ['mensaje' => 'Socio desactivado (tiene facturas asociadas).'];
        }

        $socio->delete();
        return ['mensaje' => 'Socio eliminado correctamente.'];
    }

    // ─────────────────────────────────────────
    //  Acciones de negocio
    // ─────────────────────────────────────────

    /**
     * Cambiar el estado del socio (activo, inactivo, suspendido, cortado).
     * PATCH /socios/{id}/estado
     */
    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        $estadosValidos = ['activo', 'inactivo', 'suspendido', 'cortado'];

        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new \InvalidArgumentException("Estado '$nuevoEstado' no válido.");
        }

        $socio = Socio::findOrFail($id);
        $socio->update(['estado' => $nuevoEstado]);

        return [
            'mensaje' => "Estado actualizado a '$nuevoEstado'.",
            'socio'   => $socio->toArray(),
        ];
    }

    /**
     * Obtener el historial de facturas de un socio.
     * GET /socios/{id}/facturas
     */
    public function facturas(int $id): array
    {
        $socio = Socio::findOrFail($id);

        return $socio->facturas()
            ->with(['periodo', 'lectura'])
            ->orderByDesc('fecha_emision')
            ->get()
            ->toArray();
    }

    /**
     * Obtener el historial de pagos de un socio.
     * GET /socios/{id}/historial-pagos
     */
    public function historialPagos(int $id): array
    {
        $socio = Socio::findOrFail($id);

        return $socio->historialPagos()
            ->with(['factura', 'cobro', 'empleado.persona'])
            ->orderByDesc('fecha_evento')
            ->get()
            ->toArray();
    }

    /**
     * Buscar socios por nombre, apellido, cédula o número de socio.
     * GET /socios/buscar?q=...
     */
    public function buscar(string $termino): array
    {
        return Socio::with(['persona', 'sector'])
            ->whereHas('persona', function ($q) use ($termino) {
                $q->where('nombres', 'ilike', "%$termino%")
                  ->orWhere('apellidos', 'ilike', "%$termino%")
                  ->orWhere('cedula_identidad', 'ilike', "%$termino%");
            })
            ->orWhere('numero_socio', 'ilike', "%$termino%")
            ->get()
            ->toArray();
    }
}