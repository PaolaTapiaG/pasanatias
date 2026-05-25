<?php

namespace App\Http\Controllers;

use App\Mail\PaymentOrderInvoicesMail;
use App\Http\Services\RuntimeMailService;
use App\Models\Factura;
use App\Models\OrdenPago;
use App\Models\Socio;
use App\Models\SystemSetting;
use App\Services\BillingAutomationService;
use App\Services\PaymentOrderService;
use App\Services\WaterBillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentOrderController extends Controller
{
    public function __construct(
        private PaymentOrderService $paymentOrders,
        private BillingAutomationService $billingAutomation,
        private WaterBillingService $waterBilling,
        private RuntimeMailService $runtimeMailService
    ) {
    }

    public function storePortal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'numero_socio' => ['required', 'string', 'max:80'],
            'hasta_factura_id' => ['required', 'integer', 'exists:facturas,id_factura'],
        ], [
            'hasta_factura_id.required' => 'Selecciona hasta que factura deseas pagar.',
        ]);

        $socio = Socio::query()
            ->where('numero_socio', $data['numero_socio'])
            ->firstOrFail();

        $this->billingAutomation->ensureSocioInvoices($socio->id_socio);

        try {
            $orden = $this->paymentOrders->createSequentialUntilFactura($socio, (int) $data['hasta_factura_id']);
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('portal.ordenes.show', [$orden, $orden->access_token])
            ->with('success', 'Orden generada. Paga el monto exacto y sube tu comprobante.');
    }

    public function showPortal(OrdenPago $ordenPago, string $token): View
    {
        $this->authorizePublicAccess($ordenPago, $token);

        $ordenPago->load(['socio.persona', 'socio.medidorActivo', 'detalles']);

        return view('portal.cliente.orden-pago', [
            'company' => $this->companySettings(),
            'orden' => $ordenPago,
            'qrSvg' => $this->paymentOrders->staticQrSvg($ordenPago),
            'financialEntities' => $this->paymentOrders->financialEntities(),
        ]);
    }

    public function uploadProof(Request $request, OrdenPago $ordenPago, string $token): RedirectResponse
    {
        $this->authorizePublicAccess($ordenPago, $token);

        $data = $request->validate([
            'entidad_financiera' => ['required', 'string', Rule::in($this->paymentOrders->financialEntities())],
            'comprobante_referencia' => ['required', 'string', 'min:4', 'max:120'],
            'observaciones_cliente' => ['nullable', 'string', 'max:500'],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        try {
            $this->paymentOrders->uploadProof($ordenPago, $data, $request->file('comprobante'));
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('portal.ordenes.show', [$ordenPago, $token])
            ->with('success', 'Comprobante enviado. Un administrador verificara el pago antes de marcar tus facturas como pagadas.');
    }

    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'en_revision');
        $search = trim((string) $request->query('buscar', ''));

        $orders = OrdenPago::query()
            ->with(['socio.persona', 'detalles'])
            ->when($estado !== '', fn ($query) => $query->where('estado', $estado))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('codigo', 'ilike', "%{$search}%")
                        ->orWhereHas('socio', fn ($socio) => $socio->where('numero_socio', 'ilike', "%{$search}%"))
                        ->orWhereHas('socio.persona', function ($persona) use ($search) {
                            $persona->where('nombres', 'ilike', "%{$search}%")
                                ->orWhere('apellidos', 'ilike', "%{$search}%")
                                ->orWhere('cedula_identidad', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByRaw("CASE estado WHEN 'en_revision' THEN 0 WHEN 'pendiente' THEN 1 WHEN 'rechazada' THEN 2 WHEN 'aprobada' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->simplePaginate(12)
            ->withQueryString();

        $statsRow = OrdenPago::query()
            ->selectRaw("COUNT(*) FILTER (WHERE estado = 'en_revision') as en_revision")
            ->selectRaw("COUNT(*) FILTER (WHERE estado = 'pendiente') as pendientes")
            ->selectRaw("COUNT(*) FILTER (WHERE estado = 'aprobada') as aprobadas")
            ->selectRaw("COUNT(*) FILTER (WHERE estado = 'rechazada') as rechazadas")
            ->first();

        $stats = [
            'en_revision' => (int) ($statsRow->en_revision ?? 0),
            'pendientes' => (int) ($statsRow->pendientes ?? 0),
            'aprobadas' => (int) ($statsRow->aprobadas ?? 0),
            'rechazadas' => (int) ($statsRow->rechazadas ?? 0),
        ];

        return view('ordenes-pago.index', [
            'orders' => $orders,
            'stats' => $stats,
            'estado' => $estado,
            'search' => $search,
        ]);
    }

    public function show(OrdenPago $ordenPago): View
    {
        $ordenPago->load(['socio.persona', 'socio.sector', 'detalles', 'revisor.persona']);

        return view('ordenes-pago.show', [
            'orden' => $ordenPago,
            'qrSvg' => $this->paymentOrders->staticQrSvg($ordenPago),
            'invoiceActions' => $this->invoiceActionsForOrder($ordenPago),
        ]);
    }

    public function approve(Request $request, OrdenPago $ordenPago): RedirectResponse
    {
        $data = $request->validate([
            'notas_revision' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->paymentOrders->approve($ordenPago, $data['notas_revision'] ?? null);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = 'Orden aprobada y cobro(s) registrado(s).';
        if ($result['technical_order']) {
            $message .= ' Se genero solicitud de reconexion para el tecnico.';
        }

        return redirect()
            ->route('secretaria.ordenes-pago.show', $ordenPago)
            ->with('success', $message);
    }

    public function sendInvoicesEmail(OrdenPago $ordenPago): RedirectResponse
    {
        if ($ordenPago->estado !== 'aprobada') {
            return back()->with('error', 'Solo puedes enviar facturas cuando la orden ya fue aprobada.');
        }

        $ordenPago->loadMissing(['socio.persona', 'detalles']);
        $email = $ordenPago->socio?->persona?->email;

        if (!$email) {
            return back()->with('error', 'El socio no tiene correo registrado para enviar las facturas.');
        }

        $facturas = $this->paidFacturasForOrder($ordenPago);

        if ($facturas->isEmpty()) {
            return back()->with('error', 'No se encontraron facturas pagadas asociadas a esta orden.');
        }

        try {
            $this->runtimeMailService->send($email, new PaymentOrderInvoicesMail(
                $ordenPago,
                $facturas,
                $this->invoicePdfAttachments($facturas)
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo enviar el correo: ' . $exception->getMessage());
        }

        return back()->with('success', 'Facturas enviadas por correo a ' . $email . '.');
    }

    public function reject(Request $request, OrdenPago $ordenPago): RedirectResponse
    {
        $data = $request->validate([
            'notas_revision' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->paymentOrders->reject($ordenPago, $data['notas_revision']);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('secretaria.ordenes-pago.show', $ordenPago)
            ->with('success', 'Orden rechazada. El cliente podra generar o reenviar un comprobante correcto.');
    }

    private function authorizePublicAccess(OrdenPago $orden, string $token): void
    {
        abort_unless(hash_equals((string) $orden->access_token, $token), 404);
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

    private function invoiceActionsForOrder(OrdenPago $orden): Collection
    {
        return $orden->detalles
            ->where('tipo', 'factura')
            ->map(function ($detalle) use ($orden) {
                $invoiceNumber = data_get($detalle->metadata, 'numero_factura', $detalle->descripcion);
                $pdfUrl = route('secretaria.facturas.pdf', $detalle->referencia_id);
                $message = "Hola {$orden->socio?->persona?->nombre_completo}, tu pago {$orden->codigo} fue aprobado. Factura {$invoiceNumber}. Por favor revisa el PDF adjunto.";

                return [
                    'id_factura' => (int) $detalle->referencia_id,
                    'numero_factura' => $invoiceNumber,
                    'descripcion' => $detalle->descripcion,
                    'monto' => (float) $detalle->monto,
                    'pdf_url' => $pdfUrl,
                    'whatsapp_url' => $this->whatsappUrl($orden->socio?->persona?->telefono, $message),
                ];
            })
            ->values();
    }

    private function paidFacturasForOrder(OrdenPago $orden): Collection
    {
        $ids = $orden->detalles
            ->where('tipo', 'factura')
            ->pluck('referencia_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $order = array_flip($ids->all());

        return Factura::query()
            ->with([
                'socio.persona',
                'socio.sector',
                'socio.tarifa',
                'periodo',
                'lectura.medidor',
                'cobros.metodoPago',
                'cobros.empleado.persona',
            ])
            ->whereIn('id_factura', $ids)
            ->get()
            ->sortBy(fn (Factura $factura) => $order[$factura->id_factura] ?? 999999)
            ->values();
    }

    private function invoicePdfAttachments(Collection $facturas): array
    {
        return $facturas
            ->map(function (Factura $factura) {
                $pdf = Pdf::loadView('facturas.pdf', $this->invoiceViewData($factura))->output();

                return [
                    'filename' => Str::slug($factura->numero_factura ?: 'factura-' . $factura->id_factura) . '.pdf',
                    'content' => $pdf,
                ];
            })
            ->all();
    }

    private function invoiceViewData(Factura $factura): array
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

        return $contents === false ? null : 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    private function whatsappUrl(?string $phone, string $message): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 8) {
            $digits = '591' . $digits;
        }

        return 'https://wa.me/' . $digits . '?text=' . urlencode($message);
    }
}
