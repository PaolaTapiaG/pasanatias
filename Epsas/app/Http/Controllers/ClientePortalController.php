<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\BillingAutomationService;
use App\Services\PaymentOrderService;
use App\Support\OperationalCache;
use App\Support\PortalContent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ClientePortalController extends Controller
{
    public function __construct(
        private BillingAutomationService $billingAutomation,
        private PaymentOrderService $paymentOrders
    )
    {
    }

    public function index(): View
    {
        return view('portal.cliente.index', [
            'company' => $this->companySettings(),
            'portal' => $this->portalSettings(),
        ]);
    }

    public function page(string $page): View
    {
        $pages = $this->portalPages();
        abort_unless(isset($pages[$page]), 404);

        return view('portal.cliente.page', [
            'company' => $this->companySettings(),
            'portal' => $this->portalSettings(),
            'pageKey' => $page,
            'pageData' => $pages[$page],
        ]);
    }

    public function buscarDeuda(Request $request)
    {
        $data = $request->validate([
            'numero_socio' => ['required', 'string', 'min:3', 'max:80'],
        ], [
            'numero_socio.required' => 'Ingresa tu numero de socio o el codigo del medidor.',
            'numero_socio.min' => 'Ingresa al menos 3 caracteres del codigo registrado.',
        ]);

        $term = trim($data['numero_socio']);
        $socio = $this->findSocio($term);

        if (!$socio) {
            return back()
                ->withInput()
                ->with('error', 'No encontramos un socio activo con ese dato. Revisa el codigo de tu factura o medidor.');
        }

        $this->billingAutomation->ensureSocioInvoices((int) $socio->id_socio);
        $account = $this->cachedAccountData((int) $socio->id_socio);

        return view('portal.cliente.resultado', [
            'company' => $this->companySettings(),
            'socio' => $socio,
            'deuda' => $account['deuda'],
            'facturas' => $account['facturas'],
            'paymentFacturas' => $account['paymentFacturas'] ?? $this->paymentFacturasSocio((int) $socio->id_socio),
            'pagos' => $account['pagos'],
            'numeroSocio' => $socio->numero_socio,
        ]);
    }

    public function verFactura(Request $request, int $id)
    {
        $numeroSocio = trim((string) $request->query('numero_socio', ''));
        $factura = $this->facturaDetalle($id);

        if (!$factura || $numeroSocio === '' || strcasecmp($numeroSocio, (string) $factura->numero_socio) !== 0) {
            abort(404);
        }

        return view('portal.cliente.factura-detalle', [
            'company' => $this->companySettings(),
            'factura' => $factura,
        ]);
    }

    public function iniciarPago(Request $request)
    {
        $validated = $request->validate([
            'numero_socio' => ['required', 'string', 'min:3', 'max:80'],
        ]);

        $socio = $this->findSocio($validated['numero_socio']);

        if (!$socio) {
            return back()->with('error', 'Socio no encontrado.');
        }

        $this->billingAutomation->ensureSocioInvoices((int) $socio->id_socio);
        $account = $this->cachedAccountData((int) $socio->id_socio);
        $paymentFacturas = $account['paymentFacturas'] ?? $this->paymentFacturasSocio((int) $socio->id_socio);
        $facturaIds = $paymentFacturas
            ->pluck('id_factura')
            ->all();

        if ($facturaIds === []) {
            return back()->with('success', 'Tu cuenta no tiene deuda pendiente.');
        }

        try {
            $orden = $this->paymentOrders->createFromFacturas($socio, $facturaIds);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('portal.ordenes.show', [$orden, $orden->access_token])
            ->with('success', 'Orden generada por el total pendiente. Sube tu comprobante para revision.');
    }

    public function pagoCancelado(Request $request)
    {
        $referencia = $request->input('reference');
        $estado = $request->input('status');
        $monto = $request->input('amount');

        if ($estado === 'success') {
            if (Schema::hasTable('intentos_pago')) {
                DB::table('intentos_pago')
                    ->where('referencia', $referencia)
                    ->update(['estado' => 'completado', 'updated_at' => now()]);
            }

            return view('portal.cliente.pago-exitoso', [
                'referencia' => $referencia,
                'monto' => $monto,
            ]);
        }

        return view('portal.cliente.pago-fallido', [
            'referencia' => $referencia,
        ]);
    }

    private function findSocio(string $term): ?object
    {
        $term = trim($term);
        $key = 'portal:cliente:lookup:v' . OperationalCache::version() . ':' . md5(strtolower($term));

        return Cache::remember($key, now()->addMinutes(30), fn () => $this->lookupSocio($term));
    }

    private function lookupSocio(string $term): ?object
    {
        $term = trim($term);

        $row = $this->socioLookupQuery()
            ->where('s.numero_socio', $term)
            ->first()
            ?: $this->socioLookupQuery()->where('p.cedula_identidad', $term)->first()
            ?: $this->socioLookupQuery()->where('m.numero_serie', $term)->first();

        if ($row) {
            return $this->mapSocioLookup($row);
        }

        return null;
    }

    private function socioLookupQuery()
    {
        return DB::table('socios as s')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('tarifas as t', 't.id_tarifa', '=', 's.id_tarifa')
            ->leftJoin('sectores as sec', 'sec.id_sector', '=', 's.id_sector')
            ->leftJoin('medidores as m', function ($join) {
                $join->on('m.id_socio', '=', 's.id_socio')
                    ->where('m.estado', '=', 'activo');
            })
            ->where('s.estado', '!=', 'inactivo')
            ->orderBy('s.id_socio')
            ->select([
                's.id_socio',
                's.numero_socio',
                's.direccion',
                's.estado',
                'p.nombres',
                'p.apellidos',
                'p.email',
                'p.telefono',
                'p.cedula_identidad',
                't.nombre as tarifa_nombre',
                't.tipo_uso',
                'sec.nombre as sector_nombre',
                'm.id_medidor',
                'm.numero_serie',
            ]);
    }

    private function mapSocioLookup(object $row): object
    {
        $row->persona = (object) [
            'nombre_completo' => trim(($row->nombres ?? '') . ' ' . ($row->apellidos ?? '')),
            'email' => $row->email,
            'telefono' => $row->telefono,
            'cedula_identidad' => $row->cedula_identidad,
        ];
        $row->tarifa = (object) [
            'nombre' => $row->tarifa_nombre,
            'tipo_uso' => $row->tipo_uso,
        ];
        $row->sector = (object) [
            'nombre' => $row->sector_nombre,
        ];
        $row->medidorActivo = $row->id_medidor ? (object) [
            'id_medidor' => $row->id_medidor,
            'numero_serie' => $row->numero_serie,
        ] : null;

        return $row;
    }

    private function cachedAccountData(int $idSocio): array
    {
        $key = 'portal:cliente:estado:v' . OperationalCache::version() . ':' . $idSocio;

        return Cache::remember($key, now()->addMinutes(10), fn () => [
            'deuda' => $this->deudaSocio($idSocio),
            'facturas' => $this->facturasSocio($idSocio),
            'paymentFacturas' => $this->paymentFacturasSocio($idSocio),
            'pagos' => $this->pagosSocio($idSocio),
        ]);
    }

    private function deudaSocio(int $idSocio): array
    {
        $payments = $this->paymentsSubquery();
        $saldo = "GREATEST(f.total - COALESCE(cp.pagado, 0), 0)";
        $vencida = "{$saldo} > 0 AND COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision) + INTERVAL '30 days' < CURRENT_DATE";

        $row = DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_socio', $idSocio)
            ->where('f.estado', '!=', 'anulada')
            ->selectRaw("ROUND(COALESCE(SUM({$saldo}), 0), 2) as total")
            ->selectRaw("COUNT(*) FILTER (WHERE {$saldo} > 0) as pendientes")
            ->selectRaw("COUNT(*) FILTER (WHERE {$vencida}) as vencidas")
            ->first();

        return [
            'total' => (float) ($row->total ?? 0),
            'pendientes' => (int) ($row->pendientes ?? 0),
            'vencidas' => (int) ($row->vencidas ?? 0),
        ];
    }

    private function facturasSocio(int $idSocio)
    {
        $payments = $this->paymentsSubquery();
        $saldo = "GREATEST(f.total - COALESCE(cp.pagado, 0), 0)";

        return DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->leftJoin('lecturas as l', 'l.id_lectura', '=', 'f.id_lectura')
            ->leftJoin('medidores as m', 'm.id_medidor', '=', 'l.id_medidor')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_socio', $idSocio)
            ->where('f.estado', '!=', 'anulada')
            ->orderByDesc(DB::raw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision)'))
            ->orderByDesc('f.id_factura')
            ->limit(18)
            ->get([
                'f.id_factura',
                'f.numero_factura',
                'f.fecha_emision',
                'f.fecha_inicio_cobro',
                'f.fecha_fin_cobro',
                'f.consumo_m3',
                'f.monto_consumo',
                'f.cargo_fijo',
                'f.recargo_mora',
                'f.descuentos',
                'f.total',
                'f.estado',
                'pf.nombre as periodo_nombre',
                'pf.fecha_inicio as periodo_inicio',
                'pf.fecha_fin as periodo_fin',
                'l.lectura_anterior',
                'l.lectura_actual',
                'm.numero_serie',
                DB::raw('COALESCE(cp.pagado, 0) as pagado'),
                DB::raw("{$saldo} as saldo"),
                DB::raw("CASE WHEN {$saldo} > 0 AND COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision) + INTERVAL '30 days' < CURRENT_DATE THEN 1 ELSE 0 END as esta_vencida"),
            ])
            ->map(fn ($factura) => $this->decorateFactura($factura));
    }

    private function paymentFacturasSocio(int $idSocio)
    {
        $payments = $this->paymentsSubquery();
        $saldo = "GREATEST(f.total - COALESCE(cp.pagado, 0), 0)";

        return DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->leftJoin('lecturas as l', 'l.id_lectura', '=', 'f.id_lectura')
            ->leftJoin('medidores as m', 'm.id_medidor', '=', 'l.id_medidor')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_socio', $idSocio)
            ->where('f.estado', '!=', 'anulada')
            ->whereRaw("{$saldo} > 0")
            ->orderByRaw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision) ASC')
            ->orderBy('f.id_factura')
            ->get([
                'f.id_factura',
                'f.numero_factura',
                'f.fecha_emision',
                'f.fecha_inicio_cobro',
                'f.fecha_fin_cobro',
                'f.consumo_m3',
                'f.monto_consumo',
                'f.cargo_fijo',
                'f.recargo_mora',
                'f.descuentos',
                'f.total',
                'f.estado',
                'pf.nombre as periodo_nombre',
                'pf.fecha_inicio as periodo_inicio',
                'pf.fecha_fin as periodo_fin',
                'l.lectura_anterior',
                'l.lectura_actual',
                'm.numero_serie',
                DB::raw('COALESCE(cp.pagado, 0) as pagado'),
                DB::raw("{$saldo} as saldo"),
                DB::raw("CASE WHEN {$saldo} > 0 AND COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision) + INTERVAL '30 days' < CURRENT_DATE THEN 1 ELSE 0 END as esta_vencida"),
            ])
            ->map(fn ($factura) => $this->decorateFactura($factura));
    }

    private function facturaDetalle(int $idFactura): ?object
    {
        $payments = $this->paymentsSubquery();
        $saldo = "GREATEST(f.total - COALESCE(cp.pagado, 0), 0)";

        $factura = DB::table('facturas as f')
            ->join('socios as s', 's.id_socio', '=', 'f.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->leftJoin('lecturas as l', 'l.id_lectura', '=', 'f.id_lectura')
            ->leftJoin('medidores as m', 'm.id_medidor', '=', 'l.id_medidor')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_factura', $idFactura)
            ->where('f.estado', '!=', 'anulada')
            ->select([
                'f.id_factura',
                'f.numero_factura',
                'f.fecha_emision',
                'f.fecha_inicio_cobro',
                'f.fecha_fin_cobro',
                'f.consumo_m3',
                'f.monto_consumo',
                'f.cargo_fijo',
                'f.recargo_mora',
                'f.descuentos',
                'f.total',
                'f.estado',
                's.numero_socio',
                's.direccion',
                'pf.nombre as periodo_nombre',
                'pf.fecha_inicio as periodo_inicio',
                'pf.fecha_fin as periodo_fin',
                'l.lectura_anterior',
                'l.lectura_actual',
                'm.numero_serie',
            ])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo")
            ->selectRaw('COALESCE(cp.pagado, 0) as pagado')
            ->selectRaw("{$saldo} as saldo")
            ->selectRaw("CASE WHEN {$saldo} > 0 AND COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision) + INTERVAL '30 days' < CURRENT_DATE THEN 1 ELSE 0 END as esta_vencida")
            ->first();

        return $factura ? $this->decorateFactura($factura) : null;
    }

    private function pagosSocio(int $idSocio)
    {
        return DB::table('cobros as c')
            ->join('facturas as f', 'f.id_factura', '=', 'c.id_factura')
            ->leftJoin('metodos_pago as mp', 'mp.id_metodo_pago', '=', 'c.id_metodo_pago')
            ->where('f.id_socio', $idSocio)
            ->where('c.estado', '<>', 'anulado')
            ->orderByDesc('c.fecha_cobro')
            ->orderByDesc('c.id_cobro')
            ->limit(5)
            ->get([
                'c.fecha_cobro',
                'c.monto_pagado',
                'c.comprobante',
                'f.numero_factura',
                'mp.nombre as metodo_pago',
            ]);
    }

    private function paymentsSubquery()
    {
        return DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');
    }

    private function decorateFactura(object $factura): object
    {
        $inicio = $factura->fecha_inicio_cobro ?: $factura->periodo_inicio;
        $fin = $factura->fecha_fin_cobro ?: $factura->periodo_fin;

        $factura->inicio_cobro = $inicio ? Carbon::parse($inicio) : null;
        $factura->fin_cobro = $fin ? Carbon::parse($fin) : null;
        $factura->fecha_emision = $factura->fecha_emision ? Carbon::parse($factura->fecha_emision) : null;
        $factura->estado_pago = (float) $factura->saldo <= 0
            ? 'Pagada'
            : ((int) $factura->esta_vencida === 1 ? 'Vencida' : 'Pendiente');

        return $factura;
    }

    private function crearUrlLukaBolivia(
        float $monto,
        string $referencia,
        string $concepto,
        string $correo,
        string $telefono,
        string $nombreCliente
    ): string {
        $baseUrl = config('services.luka.checkout_url') ?? env('LUKA_CHECKOUT_URL', 'https://checkout.lukabolivia.com');

        return $baseUrl . '?' . http_build_query([
            'merchant_id' => config('services.luka.merchant_id') ?? env('LUKA_MERCHANT_ID'),
            'reference' => $referencia,
            'amount' => number_format($monto, 2, '.', ''),
            'currency' => 'BOB',
            'description' => $concepto,
            'customer_name' => $nombreCliente,
            'customer_email' => $correo,
            'customer_phone' => $telefono,
            'return_url' => route('portal.pago-exitoso'),
            'cancel_url' => route('portal.pago-fallido'),
            'success_url' => route('portal.pago-exitoso'),
        ]);
    }

    private function companySettings(): array
    {
        return SystemSetting::getValue('general', [
            'company_name' => 'EPSAS',
            'company_alias' => 'Servicio de agua potable',
            'company_logo' => null,
            'company_phone' => '(591) 678-4664',
        ]);
    }

    private function portalSettings(): array
    {
        return PortalContent::merge(SystemSetting::getValue('portal', []));
    }

    private function portalPages(): array
    {
        return $this->portalSettings()['pages'];

        return [
            'sobre-nosotros' => [
                'kicker' => 'Sobre nosotros',
                'title' => 'Cuidamos el servicio de agua con informacion clara y trabajo de campo.',
                'intro' => 'EPSAS El Portillo centraliza lecturaciones, facturacion, pagos, cortes, reconexiones e incidencias para que cada socio tenga trazabilidad de su servicio.',
                'hero' => 'Gestion operativa',
                'cards' => [
                    ['title' => 'Mision', 'text' => 'Asegurar un servicio continuo, transparente y organizado para la comunidad.'],
                    ['title' => 'Vision', 'text' => 'Digitalizar la atencion ciudadana sin perder el trato cercano.'],
                    ['title' => 'Compromiso', 'text' => 'Registrar cada lectura, pago y solicitud con datos verificables.'],
                ],
                'bullets' => ['Lecturaciones mensuales', 'Facturacion por periodos reales', 'Seguimiento de cortes y reconexiones', 'Reportes administrativos'],
            ],
            'atencion-al-publico' => [
                'kicker' => 'Atencion al publico',
                'title' => 'Canales simples para resolver consultas, pagos y reclamos.',
                'intro' => 'La atencion al socio se organiza por prioridad: pagos y deuda, consultas de medidor, reclamos por consumo, solicitudes tecnicas y actualizacion de datos.',
                'hero' => 'Mesa de ayuda',
                'cards' => [
                    ['title' => 'Caja y pagos', 'text' => 'Revision de saldos, ordenes QR y comprobantes.'],
                    ['title' => 'Soporte tecnico', 'text' => 'Incidencias, fugas, cortes, reconexiones e instalaciones.'],
                    ['title' => 'Actualizacion', 'text' => 'Datos de contacto, direccion, correo y telefono.'],
                ],
                'bullets' => ['Trae tu numero de socio', 'Verifica tu medidor activo', 'Conserva el comprobante', 'Revisa comunicados antes de reclamar'],
            ],
            'comunicados' => [
                'kicker' => 'Comunicados',
                'title' => 'Avisos importantes para socios y vecinos.',
                'intro' => 'En esta seccion se publican cortes programados, mantenimientos, cambios de horario, campanas de regularizacion y novedades operativas.',
                'hero' => 'Avisos vigentes',
                'cards' => [
                    ['title' => 'Mantenimiento preventivo', 'text' => 'Revisa fechas de trabajo por zona antes de reportar baja presion.'],
                    ['title' => 'Regularizacion de deuda', 'text' => 'Los pagos se realizan desde la deuda mas antigua hacia adelante.'],
                    ['title' => 'Comprobantes QR', 'text' => 'Sube comprobantes legibles para acelerar la aprobacion administrativa.'],
                ],
                'bullets' => ['Cortes programados', 'Alertas por zona', 'Avisos de caja', 'Campanas comunitarias'],
            ],
            'horarios' => [
                'kicker' => 'Horarios',
                'title' => 'Horarios de atencion y ventanas operativas.',
                'intro' => 'Consulta los horarios sugeridos para caja, atencion al socio, soporte tecnico y emergencias reportadas por el portal.',
                'hero' => 'Agenda semanal',
                'cards' => [
                    ['title' => 'Atencion general', 'text' => 'Lunes a viernes de 08:00 a 16:00.'],
                    ['title' => 'Caja', 'text' => 'Pagos presenciales de 08:30 a 15:30.'],
                    ['title' => 'Emergencias', 'text' => 'Reportes prioritarios segun disponibilidad del equipo tecnico.'],
                ],
                'bullets' => ['Evita filas consultando en linea', 'Los domingos se consideran dias libres', 'Verifica comunicados por feriados', 'Guarda tu orden QR'],
            ],
            'proyectos' => [
                'kicker' => 'Proyectos',
                'title' => 'Mejoras de red, instalaciones y mantenimiento planificado.',
                'intro' => 'La empresa registra trabajos de campo, instalaciones nuevas, cambios de medidor, incidencias y necesidades de materiales para reportes administrativos.',
                'hero' => 'Obras y red',
                'cards' => [
                    ['title' => 'Instalaciones nuevas', 'text' => 'Coordenadas, zona, sector y fecha quedan registradas.'],
                    ['title' => 'Mantenimiento de red', 'text' => 'Materiales y gastos se reportan para revision administrativa.'],
                    ['title' => 'Control de medidores', 'text' => 'Seguimiento de reemplazos, danos y lecturas atipicas.'],
                ],
                'bullets' => ['Ampliacion de cobertura', 'Control de fugas', 'Planificacion por zona', 'Trazabilidad tecnica'],
            ],
            'pagos-online' => [
                'kicker' => 'Pagos online',
                'title' => 'Paga con una orden especifica y evita inconsistencias.',
                'intro' => 'El sistema no deja pagar meses recientes si existen meses antiguos pendientes. Selecciona hasta que factura pagar y el portal armara la orden completa.',
                'hero' => 'Orden QR segura',
                'cards' => [
                    ['title' => 'Seleccion secuencial', 'text' => 'Si eliges marzo, se incluyen enero y febrero si siguen abiertos.'],
                    ['title' => 'Monto protegido', 'text' => 'El total lo calcula el sistema y no puede editarse manualmente.'],
                    ['title' => 'Revision administrativa', 'text' => 'El comprobante se aprueba antes de marcar facturas pagadas.'],
                ],
                'bullets' => ['Consulta tu codigo', 'Elige hasta que mes pagar', 'Escanea el QR de prueba', 'Sube comprobante legible'],
            ],
            'puntos' => [
                'kicker' => 'Puntos de pago',
                'title' => 'Opciones disponibles para cancelar tu servicio.',
                'intro' => 'Puedes pagar mediante orden QR en el portal y registrar la entidad financiera usada. Para atencion presencial, lleva tu numero de socio.',
                'hero' => 'Puntos y canales',
                'cards' => [
                    ['title' => 'QR empresa', 'text' => 'Disponible para pruebas y validacion interna.'],
                    ['title' => 'Caja administrativa', 'text' => 'Pagos revisados por personal autorizado.'],
                    ['title' => 'Bancos y billeteras', 'text' => 'Selecciona la entidad al subir el comprobante.'],
                ],
                'bullets' => ['Banco Union', 'BNB', 'Mercantil Santa Cruz', 'BISA', 'Fassil / billeteras disponibles'],
            ],
            'epsas-informa' => [
                'kicker' => 'EPSAS informa',
                'title' => 'Informacion util para cuidar el agua y entender tu factura.',
                'intro' => 'Publicamos recomendaciones, explicaciones sobre cargos, consumo responsable, medidores y procesos de regularizacion.',
                'hero' => 'Guia ciudadana',
                'cards' => [
                    ['title' => 'Cargo fijo', 'text' => 'Aunque no exista consumo alto, el servicio mantiene un cargo base.'],
                    ['title' => 'Lectura mensual', 'text' => 'La lectura actual representa lo que marca el medidor en campo.'],
                    ['title' => 'Anomalias', 'text' => 'Medidores danados o manipulados deben reportarse para revision.'],
                ],
                'bullets' => ['Ahorro de agua', 'Lectura responsable', 'Reportes por fuga', 'Uso correcto del portal'],
            ],
            'contactanos' => [
                'kicker' => 'Contactanos',
                'title' => 'Estamos para ayudarte con consultas, reclamos y pagos.',
                'intro' => 'Usa los datos de contacto institucionales para consultar deuda, solicitar soporte o revisar un comprobante observado.',
                'hero' => 'Contacto directo',
                'cards' => [
                    ['title' => 'Telefono', 'text' => 'Comunicate con atencion al socio durante horario laboral.'],
                    ['title' => 'Correo', 'text' => 'Solicita informacion o actualizacion de datos.'],
                    ['title' => 'Oficina', 'text' => 'Lleva tu numero de socio para atencion rapida.'],
                ],
                'bullets' => ['Consultas de deuda', 'Soporte de comprobantes', 'Reclamos de lectura', 'Actualizacion de datos'],
            ],
        ];
    }
}
