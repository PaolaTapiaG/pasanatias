<?php

namespace App\Http\Controllers;

use App\Mail\InvoicePdfMail;
use App\Models\Factura;
use App\Models\Lectura;
use App\Models\PeriodoFacturacion;
use App\Models\Socio;
use App\Models\SystemSetting;
use App\Http\Services\RuntimeMailService;
use App\Support\OperationalCache;
use App\Services\BillingAutomationService;
use App\Services\WaterBillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FacturaController extends Controller
{
    public function __construct(
        private WaterBillingService $waterBilling,
        private BillingAutomationService $billingAutomation,
        private RuntimeMailService $runtimeMailService
    )
    {
    }

    public function index(Request $request)
    {
        $syncResult = $this->billingAutomation->ensureCurrentInvoices();

        return view('facturas.index', [
            'facturas' => $this->invoicePaginator($request),
            'periodos' => $this->periodOptions(),
            'totales' => $this->invoiceTotals(),
            'candidatos' => $this->billingCandidates(),
            'syncResult' => $syncResult,
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/facturas', 'GET');

        $this->invoicePaginator($request, url('/admin/facturas'));
        $this->periodOptions();
        $this->invoiceTotals();
        $this->billingCandidates();
    }

    private function invoicePaginator(Request $request, ?string $path = null)
    {
        $query = DB::table('facturas as f')
            ->leftJoin('socios as s', 's.id_socio', '=', 'f.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->select([
                'f.id_factura',
                'f.numero_factura',
                'f.fecha_emision',
                'f.consumo_m3',
                'f.total',
                'f.estado',
                's.id_socio',
                's.numero_socio',
                'pf.nombre as periodo_nombre',
            ])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo")
            ->selectRaw("COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo_display")
            ->orderByDesc('f.fecha_emision')
            ->orderByDesc('f.id_factura');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('f.numero_factura', 'ilike', "%{$term}%")
                    ->orWhere('f.estado', 'ilike', "%{$term}%")
                    ->orWhere('s.numero_socio', 'ilike', "%{$term}%")
                    ->orWhere('p.nombres', 'ilike', "%{$term}%")
                    ->orWhere('p.apellidos', 'ilike', "%{$term}%")
                    ->orWhere('p.cedula_identidad', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('f.estado', $request->estado);
        }

        if ($request->filled('periodo')) {
            $query->where('f.id_periodo', $request->periodo);
        }

        Cache::add('facturas:index:version', 1, now()->addYears(2));
        $cacheKey = 'facturas:index:v' . Cache::get('facturas:index:version', 1) . ':' . md5(json_encode($request->query()));

        return Cache::remember($cacheKey, now()->addDays(7), fn () => $query
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/facturas'))
            ->appends($request->query())
            ->through(function ($factura) {
                $factura->fecha_emision = $factura->fecha_emision ? Carbon::parse($factura->fecha_emision) : null;

                return $factura;
            }));
    }

    private function periodOptions()
    {
        return Cache::remember('facturas.periodos', now()->addDays(7), function () {
            return PeriodoFacturacion::select('id_periodo', 'nombre', 'fecha_inicio')
                ->orderByDesc('fecha_inicio')
                ->get();
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_socio' => ['required', 'exists:socios,id_socio'],
        ]);

        $socio = Socio::with(['persona', 'tarifa', 'medidorActivo'])->findOrFail($data['id_socio']);
        $medidor = $socio->medidorActivo;

        if (!$medidor) {
            return back()->with('error', 'El socio no tiene un medidor activo para facturar.');
        }

        $lectura = Lectura::where('id_medidor', $medidor->id_medidor)
            ->whereDoesntHave('facturas')
            ->orderByDesc('fecha_lectura')
            ->first();

        if (!$lectura) {
            return back()->with('error', 'No existe una lectura pendiente de facturacion para este socio.');
        }

        $tarifa = $socio->tarifa;

        if (!$tarifa) {
            return back()->with('error', 'El socio no tiene una tarifa asignada.');
        }

        $fechaLectura = Carbon::parse($lectura->fecha_lectura);
        $periodo = $this->resolvePeriodo($fechaLectura);
        $inicioCobro = $this->resolveInicioCobro($socio, $medidor->fecha_instalacion, $fechaLectura);
        $saldoPendiente = $this->pendingBalance($socio->id_socio);
        $desgloseTarifa = $tarifa->calcularDesglose((float) $lectura->consumo_m3);
        $montoConsumo = $desgloseTarifa['water_charge'];
        $recargoMora = ($saldoPendiente > 0 ? round($saldoPendiente * 0.02, 2) : 0) + $desgloseTarifa['cutoff_penalty'];

        $factura = Factura::create([
            'numero_factura' => $this->nextNumeroFactura(),
            'fecha_emision' => now()->toDateString(),
            'fecha_inicio_cobro' => $inicioCobro->toDateString(),
            'fecha_fin_cobro' => $fechaLectura->toDateString(),
            'consumo_m3' => $lectura->consumo_m3,
            'monto_consumo' => $montoConsumo,
            'cargo_fijo' => $desgloseTarifa['sewer_fixed_charge'],
            'recargo_mora' => $recargoMora,
            'descuentos' => 0,
            'precio_m3_aplicado' => $desgloseTarifa['excess_rate'],
            'cargo_fijo_aplicado' => $desgloseTarifa['fixed_charge'],
            'estado' => $saldoPendiente > 0 ? 'vencida' : 'pendiente',
            'id_socio' => $socio->id_socio,
            'id_lectura' => $lectura->id_lectura,
            'id_periodo' => $periodo->id_periodo,
        ]);

        Cache::forget('facturas.totales');
        Cache::forget('facturas.billing_candidates');
        Cache::add('facturas:index:version', 1, now()->addYears(2));
        Cache::increment('facturas:index:version');
        Cache::add('reportes:index:version', 1, now()->addYears(2));
        Cache::increment('reportes:index:version');
        Cache::forget('tecnico:billing-signals');
        Cache::forget('tecnico:corte:open-socios');
        Cache::forget('tecnico:reconexion:open-socios');
        Cache::forget('tecnico:reconexion:latest-cuts');
        Cache::forget('api.dashboard.tecnico');
        OperationalCache::bump();

        return redirect()
            ->route('secretaria.facturas.show', $factura)
            ->with('success', 'Factura generada correctamente para ' . $socio->persona?->nombre_completo . '.');
    }

    public function show(Factura $factura)
    {
        $factura->load([
            'socio.persona',
            'socio.sector',
            'socio.tarifa',
            'periodo',
            'lectura.medidor',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ]);

        $pagado = (float) $factura->cobros
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
        $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
        $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);
        $pdfUrl = route('secretaria.facturas.pdf', $factura);
        $printUrl = route('secretaria.facturas.print', $factura);
        $shareMessage = "Hola {$factura->socio?->persona?->nombre_completo}, tu factura {$factura->numero_factura} ya esta lista. Te la enviaremos como PDF adjunto por correo o puedes solicitarla en administracion.";
        $whatsappUrl = $this->whatsappUrl($factura->socio?->persona?->telefono, $shareMessage);

        return view('facturas.show', [
            'factura' => $factura,
            'company' => SystemSetting::getValue('general', []),
            'billingBreakdown' => $this->buildBillingBreakdown($factura),
            'resumenCobro' => [
                'subtotal' => $subtotal,
                'pagado' => $pagado,
                'pendiente' => $pendiente,
            ],
            'pdfUrl' => $pdfUrl,
            'printUrl' => $printUrl,
            'whatsappUrl' => $whatsappUrl,
        ]);
    }

    public function sendEmail(Factura $factura)
    {
        $factura->load([
            'socio.persona',
            'socio.sector',
            'socio.tarifa',
            'periodo',
            'lectura.medidor',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ]);

        $email = $factura->socio?->persona?->email;

        if (!$email) {
            return back()->with('error', 'El socio no tiene correo registrado para enviar la factura.');
        }

        try {
            $this->runtimeMailService->send($email, new InvoicePdfMail(
                $factura,
                Pdf::loadView('facturas.pdf', $this->invoicePdfViewData($factura))->output()
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo enviar la factura por email: ' . $exception->getMessage());
        }

        return back()->with('success', 'Factura enviada por email a ' . $email . '.');
    }

    public function pdf(Factura $factura)
    {
        $factura->load([
            'socio.persona',
            'socio.sector',
            'socio.tarifa',
            'periodo',
            'lectura.medidor',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ]);

        $pagado = (float) $factura->cobros
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
        $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
        $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);
        $company = SystemSetting::getValue('general', []);
        $viewData = [
            'factura' => $factura,
            'company' => $company,
            'billingBreakdown' => $this->buildBillingBreakdown($factura),
            'companyLogoDataUri' => $this->buildPdfLogoDataUri($company),
            'resumenCobro' => [
                'subtotal' => $subtotal,
                'pagado' => $pagado,
                'pendiente' => $pendiente,
            ],
        ];

        try {
            return Pdf::loadView('facturas.pdf', $viewData)->download("{$factura->numero_factura}.pdf");
        } catch (\Throwable $exception) {
            report($exception);

            $viewData['companyLogoDataUri'] = null;

            return Pdf::loadView('facturas.pdf', $viewData)->download("{$factura->numero_factura}.pdf");
        }
    }

    public function print(Factura $factura)
    {
        $factura->load([
            'socio.persona',
            'socio.sector',
            'socio.tarifa',
            'periodo',
            'lectura.medidor',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ]);

        $pagado = (float) $factura->cobros
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
        $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
        $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);

        return view('facturas.print', [
            'factura' => $factura,
            'company' => SystemSetting::getValue('general', []),
            'billingBreakdown' => $this->buildBillingBreakdown($factura),
            'resumenCobro' => [
                'subtotal' => $subtotal,
                'pagado' => $pagado,
                'pendiente' => $pendiente,
            ],
        ]);
    }

    private function billingCandidates()
    {
        return Cache::remember('facturas.billing_candidates', now()->addDays(7), function () {
            $socios = Socio::query()
                ->select(['id_socio', 'numero_socio', 'fecha_registro', 'id_persona', 'id_tarifa'])
                ->with([
                    'persona:id_persona,nombres,apellidos',
                    'tarifa:id_tarifa,nombre,tipo_uso',
                    'medidorActivo:id_medidor,id_socio,fecha_instalacion',
                ])
                ->where('estado', '!=', 'inactivo')
                ->get()
                ->filter(fn ($socio) => $socio->medidorActivo && $socio->tarifa);

            if ($socios->isEmpty()) {
                return collect();
            }

            $medidorIds = $socios->pluck('medidorActivo.id_medidor')->filter()->values();
            $socioIds = $socios->pluck('id_socio')->values();

            $lecturas = Lectura::query()
                ->select(['id_lectura', 'id_medidor', 'fecha_lectura', 'consumo_m3'])
                ->whereIn('id_medidor', $medidorIds)
                ->whereDoesntHave('facturas')
                ->orderBy('id_medidor')
                ->orderByDesc('fecha_lectura')
                ->get()
                ->unique('id_medidor')
                ->keyBy('id_medidor');

            $ultimasFacturas = Factura::query()
                ->select([
                    'facturas.id_socio',
                    'facturas.fecha_fin_cobro',
                    'periodos_facturacion.fecha_fin',
                ])
                ->join('periodos_facturacion', 'periodos_facturacion.id_periodo', '=', 'facturas.id_periodo')
                ->whereIn('facturas.id_socio', $socioIds)
                ->orderBy('facturas.id_socio')
                ->orderByRaw('COALESCE(facturas.fecha_fin_cobro, periodos_facturacion.fecha_fin) desc')
                ->get()
                ->unique('id_socio')
                ->keyBy('id_socio');

            $pagosPorFactura = DB::table('cobros')
                ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
                ->groupBy('id_factura');

            $saldosPendientes = DB::table('facturas as f')
                ->leftJoinSub($pagosPorFactura, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
                ->whereIn('f.id_socio', $socioIds)
                ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
                ->groupBy('f.id_socio')
                ->selectRaw('f.id_socio, ROUND(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 2) as saldo_pendiente')
                ->pluck('saldo_pendiente', 'id_socio');

            return $socios->map(function ($socio) use ($lecturas, $ultimasFacturas, $saldosPendientes) {
                    $lectura = $lecturas->get($socio->medidorActivo?->id_medidor);

                    if (!$lectura) {
                        return null;
                    }

                    $ultimaFactura = $ultimasFacturas->get($socio->id_socio);
                    $ultimoFin = $ultimaFactura?->fecha_fin_cobro ?: $ultimaFactura?->fecha_fin;
                    $inicio = $ultimoFin
                        ? Carbon::parse($ultimoFin)->addDay()
                        : ($socio->medidorActivo->fecha_instalacion ?? $socio->fecha_registro);

                    return (object) [
                        'id_socio' => $socio->id_socio,
                        'nombre_completo' => $socio->persona?->nombre_completo ?? 'Sin socio',
                        'codigo_display' => $socio->codigo_display,
                        'tarifa_nombre' => $socio->tarifa?->nombre,
                        'tipo_uso' => $socio->tarifa?->tipo_uso ?? 'domestico',
                        'fecha_inicio' => optional($inicio)?->format('d/m/Y'),
                        'fecha_instalacion' => optional($socio->medidorActivo->fecha_instalacion)?->format('d/m/Y'),
                        'fecha_lectura' => optional($lectura->fecha_lectura)?->format('d/m/Y'),
                        'consumo_m3' => (float) $lectura->consumo_m3,
                        'saldo_pendiente' => (float) ($saldosPendientes[$socio->id_socio] ?? 0),
                    ];
                })
                ->filter()
                ->sortByDesc('fecha_lectura')
                ->values();
        });
    }

    private function invoiceTotals(): array
    {
        return Cache::remember('facturas.totales', now()->addDays(7), function () {
            $summary = Factura::query()
                ->selectRaw("
                    COUNT(*) FILTER (WHERE estado IN ('pendiente', 'parcial', 'vencida')) as pendientes,
                    COUNT(*) FILTER (WHERE estado = 'pagada') as pagadas,
                    COALESCE(SUM(CASE WHEN estado <> 'anulada' THEN total ELSE 0 END), 0) as monto_total
                ")
                ->first();

            return [
                'pendientes' => (int) ($summary?->pendientes ?? 0),
                'pagadas' => (int) ($summary?->pagadas ?? 0),
                'monto_total' => (float) ($summary?->monto_total ?? 0),
            ];
        });
    }

    private function pendingBalance(int $idSocio): float
    {
        $payments = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        return (float) DB::table('facturas as f')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_socio', $idSocio)
            ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
            ->selectRaw('ROUND(COALESCE(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 0), 2) as saldo')
            ->value('saldo');
    }

    private function resolvePeriodo(Carbon $fechaLectura): PeriodoFacturacion
    {
        return $this->billingAutomation->monthlyPeriodFor($fechaLectura);
    }

    private function resolveInicioCobro(Socio $socio, $fechaInstalacion, Carbon $fechaLectura): Carbon
    {
        $fallback = $fechaInstalacion ?: $socio->fecha_registro ?: now()->toDateString();
        $inicio = $this->billingAutomation->nextChargeStartForSocio($socio->id_socio, (string) $fallback);

        return $inicio->gt($fechaLectura) ? $fechaLectura->copy() : $inicio;
    }

    private function nextNumeroFactura(): string
    {
        $next = ((int) Factura::max('id_factura')) + 1;

        return 'FAC-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function buildBillingBreakdown(Factura $factura): array
    {
        $base = $this->waterBilling->breakdown((float) $factura->consumo_m3);
        $cutoffPenalty = $base['cutoff_penalty'];
        $moraSaldoAnterior = max(0, round((float) $factura->recargo_mora - $cutoffPenalty, 2));

        return $base + [
            'previous_reading' => (float) ($factura->lectura?->lectura_anterior ?? 0),
            'current_reading' => (float) ($factura->lectura?->lectura_actual ?? 0),
            'consumed_m3' => (float) $factura->consumo_m3,
            'mora_saldo_anterior' => $moraSaldoAnterior,
            'codigo_usuario' => $factura->socio?->codigo_display ?? ('SOC-' . str_pad((string) $factura->id_socio, 4, '0', STR_PAD_LEFT)),
        ];
    }

    private function buildPdfLogoDataUri(array $company): ?string
    {
        if (empty($company['company_logo'])) {
            return null;
        }

        $relative = str_replace('storage/', '', $company['company_logo']);
        $resolved = storage_path('app/public/' . $relative);

        if (!file_exists($resolved) || !is_readable($resolved)) {
            return null;
        }

        $mimeType = mime_content_type($resolved) ?: 'image/png';
        $contents = @file_get_contents($resolved);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    private function invoicePdfViewData(Factura $factura): array
    {
        $pagado = (float) $factura->cobros
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
        $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
        $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);
        $company = SystemSetting::getValue('general', []);

        return [
            'factura' => $factura,
            'company' => $company,
            'billingBreakdown' => $this->buildBillingBreakdown($factura),
            'companyLogoDataUri' => $this->buildPdfLogoDataUri($company),
            'resumenCobro' => [
                'subtotal' => $subtotal,
                'pagado' => $pagado,
                'pendiente' => $pendiente,
            ],
        ];
    }

    private function whatsappUrl(?string $phone, string $message): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return 'https://wa.me/?text=' . urlencode($message);
        }

        if (strlen($digits) === 8) {
            $digits = '591' . $digits;
        }

        return 'https://wa.me/' . $digits . '?text=' . urlencode($message);
    }
}
