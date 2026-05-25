<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\Lectura;
use App\Models\Medidor;
use App\Models\OrdenTecnica;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function warmForRoles($roles): void
    {
        $roles = collect($roles);

        if ($roles->contains('administrador')) {
            $this->adminStats();
            $this->warmAdminModuleIndexes();
            $this->pendingReconnectionApprovals();
            $this->completedInstallations();
            $this->recentOperationalExpenses();
        }

        if ($roles->contains('secretaria')) {
            $this->secretariaStats();
            $this->pendingPaymentOrders();
            $this->recentSecretaryPayments();
            $this->pendingReconnectionApprovals();
            $this->completedInstallations();
        }

        if ($roles->contains('tecnico')) {
            $this->upcomingReadings();
            $this->readingCalendar();
            OperationalCache::remember('dashboard:tecnico:medidores-total', fn () => Medidor::count());

            if (app()->runningInConsole()) {
                app(TecnicoPanelController::class)->warmIndexCache();
            } else {
                app()->terminating(function () {
                    try {
                        app(TecnicoPanelController::class)->warmIndexCache();
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                });
            }
        }
    }

    public function index()
    {
        $user = Auth::user();
        $roles = $user->cachedRoleNames();

        if ($roles->contains('administrador')) {
            return view('dashboard.admin', [
                'user' => $user,
                'dashboardStats' => $this->adminStats(),
                'pendingReconnectionApprovals' => $this->pendingReconnectionApprovals(),
                'completedInstallations' => $this->completedInstallations(),
                'recentOperationalExpenses' => $this->recentOperationalExpenses(),
            ]);
        }

        if ($roles->contains('secretaria')) {
            return view('dashboard.secretaria', [
                'user' => $user,
                'secretariaStats' => $this->secretariaStats(),
                'pendingPaymentOrders' => $this->pendingPaymentOrders(),
                'recentPayments' => $this->recentSecretaryPayments(),
                'pendingReconnectionApprovals' => $this->pendingReconnectionApprovals(),
                'completedInstallations' => $this->completedInstallations(),
            ]);
        }

        if ($roles->contains('tecnico')) {
            return view('dashboard.tecnico', [
                'readingCalendar' => $this->readingCalendar(),
                'upcomingReadings' => $this->upcomingReadings(),
            ]);
        }

        return view('dashboard.index', [
            'user' => $user,
            'roleLabel' => 'Usuario',
            'modules' => [
                'Panel principal',
                'Consulta de informacion',
                'Acceso a modulos asignados',
            ],
        ]);
    }

    private function adminStats(): array
    {
        return Cache::remember('dashboard.admin.stats', now()->addDays(7), fn () => [
            'users' => \App\Models\User::count(),
            'roles' => \App\Models\Role::count(),
            'permissions' => \App\Models\Permission::count(),
        ]);
    }

    private function warmAdminModuleIndexes(): void
    {
        foreach ([
            SocioController::class,
            EmpleadoController::class,
            TarifaController::class,
            MedidorController::class,
            LecturaController::class,
            FacturaController::class,
            CobroController::class,
            GastoController::class,
            ReporteController::class,
            SystemSettingController::class,
        ] as $controllerClass) {
            $controller = app($controllerClass);

            if (method_exists($controller, 'warmIndexCache')) {
                $controller->warmIndexCache();
            }
        }
    }

    private function pendingReconnectionApprovals()
    {
        return Cache::remember('dashboard:pending-reconnections', now()->addDays(7), fn () => DB::table('v_tecnico_ordenes_recientes')
            ->where('tipo', 'reconexion')
            ->where('estado', 'pendiente')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id_orden')
            ->limit(6)
            ->get()
            ->map(fn ($order) => $this->orderRowForView($order)));
    }

    private function completedInstallations()
    {
        return Cache::remember('dashboard:completed-installations', now()->addDays(7), fn () => DB::table('v_tecnico_ordenes_recientes')
            ->where('tipo', 'instalacion')
            ->where('estado', 'completada')
            ->orderByDesc('fecha_ejecucion')
            ->orderByDesc('id_orden')
            ->limit(6)
            ->get()
            ->map(fn ($order) => $this->orderRowForView($order)));
    }

    private function recentOperationalExpenses()
    {
        return Cache::remember('dashboard:recent-operational-expenses', now()->addDays(7), fn () => Gasto::query()
            ->select(['id_gasto', 'fecha_gasto', 'concepto', 'categoria', 'descripcion', 'monto'])
            ->latest('fecha_gasto')
            ->latest('id_gasto')
            ->limit(6)
            ->get());
    }

    private function secretariaStats(): array
    {
        return Cache::remember('dashboard.secretaria.stats', now()->addMinutes(3), function () {
            $inicioMes = now()->startOfMonth()->toDateString();
            $finMes = now()->endOfMonth()->toDateString();

            $row = DB::query()
                ->selectRaw("
                    (SELECT COUNT(*) FROM socios WHERE COALESCE(estado, 'activo') <> 'inactivo') as socios_activos,
                    (SELECT COUNT(*) FROM facturas WHERE estado IN ('pendiente', 'parcial', 'vencida')) as facturas_pendientes,
                    (SELECT COUNT(*) FROM ordenes_pago WHERE estado = 'en_revision') as qr_pendientes,
                    (SELECT COALESCE(SUM(monto_pagado), 0) FROM cobros WHERE estado <> 'anulado' AND fecha_cobro BETWEEN ? AND ?) as ingresos_mes
                ", [$inicioMes, $finMes])
                ->first();

            return [
                'socios_activos' => (int) ($row->socios_activos ?? 0),
                'facturas_pendientes' => (int) ($row->facturas_pendientes ?? 0),
                'qr_pendientes' => (int) ($row->qr_pendientes ?? 0),
                'ingresos_mes' => round((float) ($row->ingresos_mes ?? 0), 2),
            ];
        });
    }

    private function pendingPaymentOrders()
    {
        return Cache::remember('dashboard.secretaria.payment-orders.pending', now()->addMinutes(2), fn () => DB::table('ordenes_pago as op')
            ->leftJoin('socios as s', 's.id_socio', '=', 'op.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->select([
                'op.id_orden_pago',
                'op.codigo',
                'op.total',
                'op.comprobante_monto',
                'op.updated_at',
                's.numero_socio',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre"),
            ])
            ->where('op.estado', 'en_revision')
            ->orderByDesc('op.updated_at')
            ->orderByDesc('op.id_orden_pago')
            ->limit(6)
            ->get()
            ->map(function ($order) {
                $order->updated_at = $order->updated_at ? Carbon::parse($order->updated_at) : null;
                return $order;
            }));
    }

    private function recentSecretaryPayments()
    {
        return Cache::remember('dashboard.secretaria.payments.recent', now()->addMinutes(2), fn () => DB::table('cobros as c')
            ->leftJoin('facturas as f', 'f.id_factura', '=', 'c.id_factura')
            ->leftJoin('socios as s', 's.id_socio', '=', 'f.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('metodos_pago as mp', 'mp.id_metodo_pago', '=', 'c.id_metodo_pago')
            ->select([
                'c.id_cobro',
                'c.fecha_cobro',
                'c.monto_pagado',
                'f.numero_factura',
                's.numero_socio',
                'mp.nombre as metodo_pago',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre"),
            ])
            ->where('c.estado', '<>', 'anulado')
            ->orderByDesc('c.fecha_cobro')
            ->orderByDesc('c.id_cobro')
            ->limit(6)
            ->get()
            ->map(function ($payment) {
                $payment->fecha_cobro = $payment->fecha_cobro ? Carbon::parse($payment->fecha_cobro) : null;
                return $payment;
            }));
    }

    private function orderRowForView(object $order): object
    {
        $order->fecha_programada = $order->fecha_programada ? Carbon::parse($order->fecha_programada) : null;
        $order->fecha_ejecucion = $order->fecha_ejecucion ? Carbon::parse($order->fecha_ejecucion) : null;
        $order->socio = $order->id_socio ? (object) [
            'id_socio' => $order->id_socio,
            'codigo_display' => $order->codigo_display ?: 'Sin socio',
            'persona' => (object) [
                'nombre_completo' => $order->socio_nombre ?: 'Usuario',
            ],
        ] : null;

        return $order;
    }

    private function upcomingReadings()
    {
        return OperationalCache::remember('dashboard:tecnico:upcoming-readings', function () {
            return DB::table('v_tecnico_medidores_consumo as vm')
                ->leftJoin('medidores as m', 'm.id_medidor', '=', 'vm.id_medidor')
                ->select([
                    'vm.id_medidor',
                    'vm.numero_serie',
                    'vm.codigo_usuario',
                    'vm.socio_nombre',
                    'vm.ultima_fecha',
                    'vm.lectura_sugerida',
                    'm.fecha_instalacion',
                ])
                ->get()
                ->map(function ($medidor) {
                    $baseDate = $medidor->ultima_fecha
                        ?? $medidor->fecha_instalacion
                        ?? now();

                    $dueDate = Carbon::parse($baseDate)->addMonthNoOverflow()->startOfDay();

                    return (object) [
                        'id_medidor' => $medidor->id_medidor,
                        'numero_serie' => $medidor->numero_serie,
                        'codigo' => $medidor->codigo_usuario ?: ('MED-' . $medidor->id_medidor),
                        'socio' => $medidor->socio_nombre ?: 'Sin socio',
                        'due_date' => $dueDate,
                        'due_day' => $dueDate->format('d'),
                        'due_weekday' => ucfirst($dueDate->translatedFormat('D')),
                        'last_reading_date' => $medidor->ultima_fecha
                            ? Carbon::parse($medidor->ultima_fecha)->format('d/m/Y')
                            : null,
                        'last_reading_value' => $medidor->lectura_sugerida !== null
                            ? number_format((float) $medidor->lectura_sugerida, 2)
                            : null,
                        'is_overdue' => $dueDate->lt(now()->startOfDay()),
                        'days_left' => (int) now()->startOfDay()->diffInDays($dueDate, false),
                    ];
                })
                ->sortBy('due_date')
                ->values()
                ->take(6);
        });
    }

    private function readingCalendar(): array
    {
        return OperationalCache::remember('dashboard:tecnico:reading-calendar:' . now()->format('Y-m'), function () {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $schedule = $this->upcomingReadings();
        $scheduleByDate = $schedule->groupBy(fn ($item) => $item->due_date->toDateString());

        $cursor = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($day = 0; $day < 7; $day++) {
                $dateKey = $cursor->toDateString();
                $events = $scheduleByDate->get($dateKey, collect());

                $week[] = [
                    'date' => $cursor->copy(),
                    'day' => $cursor->format('j'),
                    'short_weekday' => mb_strtoupper($cursor->translatedFormat('D')),
                    'in_month' => $cursor->month === $monthStart->month,
                    'is_today' => $cursor->isToday(),
                    'is_busy' => $events->isNotEmpty(),
                    'count' => $events->count(),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return [
            'month_label' => ucfirst($monthStart->translatedFormat('F Y')),
            'weekdays' => ['L', 'M', 'M', 'J', 'V', 'S', 'D'],
            'weeks' => $weeks,
        ];
        });
    }
}
