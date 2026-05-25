<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Lectura;
use App\Models\Medidor;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LecturaController extends Controller
{
    public function index(Request $request): View
    {
        return view('lecturas.index', [
            'lecturas' => $this->readingPaginator($request),
            'stats' => $this->readingStats(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/lecturas', 'GET');

        $this->readingPaginator($request, url('/admin/lecturas'));
        $this->readingStats();
    }

    private function readingPaginator(Request $request, ?string $path = null)
    {
        $query = DB::table('v_tecnico_lecturas_index')
            ->select([
                'id_lectura',
                'fecha_lectura',
                'lectura_anterior',
                'lectura_actual',
                'consumo_m3',
                'observaciones',
                'id_medidor',
                'id_empleado',
                'numero_serie',
                'id_socio',
                'codigo_display',
                'socio_nombre',
                'cedula_identidad',
                'lector_nombre',
            ])
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id_lectura');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('numero_serie', 'ilike', "%{$term}%")
                    ->orWhere('codigo_display', 'ilike', "%{$term}%")
                    ->orWhere('socio_nombre', 'ilike', "%{$term}%")
                    ->orWhere('cedula_identidad', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha_lectura', [$request->desde, $request->hasta]);
        }

        Cache::add('lecturas:index:version', 1, now()->addYears(2));
        $cacheKey = 'lecturas:index:v' . Cache::get('lecturas:index:version', 1) . ':' . md5(json_encode($request->query()));

        return Cache::remember($cacheKey, now()->addDays(7), fn () => $query
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/lecturas'))
            ->appends($request->query())
            ->through(fn ($row) => $this->readingRowForView($row)));
    }

    private function readingRowForView(object $row): object
    {
        $row->fecha_lectura = $row->fecha_lectura ? Carbon::parse($row->fecha_lectura) : null;
        $row->medidor = (object) [
            'id_medidor' => $row->id_medidor,
            'numero_serie' => $row->numero_serie,
            'socio' => (object) [
                'id_socio' => $row->id_socio,
                'codigo_display' => $row->codigo_display ?: 'Sin socio',
                'persona' => (object) [
                    'nombre_completo' => $row->socio_nombre ?: 'Sin socio',
                ],
            ],
        ];
        $row->empleado = $row->id_empleado ? (object) [
            'id_empleado' => $row->id_empleado,
            'persona' => (object) [
                'nombre_completo' => $row->lector_nombre ?: 'Sin lector',
            ],
        ] : null;

        return $row;
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('tecnico.consumo.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $empleadoId = Auth::user()?->persona?->empleado?->id_empleado ?? Empleado::query()
            ->where('estado', 'activo')
            ->orderBy('id_empleado')
            ->value('id_empleado');

        $data = $request->validate([
            'fecha_lectura' => ['required', 'date', 'before_or_equal:today'],
            'lectura_anterior' => ['nullable', 'numeric', 'min:0'],
            'lectura_actual' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'id_medidor' => ['required', 'exists:medidores,id_medidor'],
            'estado_lectura' => ['nullable', 'in:normal,observada,requiere_verificacion'],
            'redirect_to' => ['nullable', 'in:tecnico.lecturas.index,tecnico.consumo.index'],
        ]);

        $lecturaAnterior = (float) Lectura::query()
            ->where('id_medidor', $data['id_medidor'])
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id_lectura')
            ->value('lectura_actual');

        $data['lectura_anterior'] = $lecturaAnterior;

        if ((float) $data['lectura_actual'] < $lecturaAnterior) {
            return back()->withInput()->withErrors([
                'lectura_actual' => 'La lectura actual no puede ser menor a la registrada anteriormente para este medidor.',
            ])->with('reading_issue', [
                'current' => round((float) $data['lectura_actual'], 2),
                'previous' => round($lecturaAnterior, 2),
                'medidor_id' => (int) $data['id_medidor'],
            ]);
        }

        $duplicada = Lectura::query()
            ->where('id_medidor', $data['id_medidor'])
            ->whereDate('fecha_lectura', $data['fecha_lectura'])
            ->exists();

        if ($duplicada) {
            return back()->withInput()->withErrors([
                'fecha_lectura' => 'Ya existe una lectura registrada para este medidor en la fecha seleccionada.',
            ]);
        }

        $observaciones = trim((string) ($data['observaciones'] ?? ''));

        if (!empty($data['estado_lectura']) && $data['estado_lectura'] !== 'normal') {
            $estadoLabel = str_replace('_', ' ', $data['estado_lectura']);
            $observaciones = trim("Estado de lectura: {$estadoLabel}. {$observaciones}");
        }

        unset($data['estado_lectura'], $data['redirect_to']);

        Lectura::create($data + [
            'id_empleado' => $empleadoId,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ]);

        Cache::forget('facturas.billing_candidates');
        Cache::forget('lecturas:stats');
        Cache::forget('lecturas:medidores-disponibles');
        Cache::forget('tecnico:consumo:catalogo');
        Cache::forget('tecnico:consumo:catalogo:v2');
        Cache::forget('tecnico:consumo:catalogo:v3');
        Cache::forget('tecnico:consumo:stats');
        Cache::forget('tecnico:consumo:recent-readings');
        Cache::forget('dashboard:tecnico:upcoming-readings');
        Cache::forget('dashboard:tecnico:reading-calendar:' . now()->format('Y-m'));
        Cache::forget('api.dashboard.tecnico');
        Cache::add('lecturas:index:version', 1, now()->addYears(2));
        Cache::increment('lecturas:index:version');
        Cache::add('reportes:index:version', 1, now()->addYears(2));
        Cache::increment('reportes:index:version');
        OperationalCache::bump();

        $route = $request->input('redirect_to', 'tecnico.lecturas.index');
        $message = $route === 'tecnico.consumo.index'
            ? 'Consumo registrado correctamente.'
            : 'Lecturacion registrada correctamente.';

        return redirect()->route($route)->with('success', $message);
    }

    private function medidoresDisponibles()
    {
        return OperationalCache::remember('lecturas:medidores-disponibles', function () {
            return $this->meterCatalogQuery()
                ->get()
                ->map(function (Medidor $medidor) {
                    return (object) [
                        'id_medidor' => $medidor->id_medidor,
                        'numero_serie' => $medidor->numero_serie,
                        'socio_nombre' => $medidor->socio?->persona?->nombre_completo ?? 'Sin socio',
                        'codigo_usuario' => $medidor->socio?->codigo_display ?? '-',
                        'lectura_sugerida' => (float) ($medidor->ultima_lectura_actual ?? 0),
                        'ultima_fecha' => $medidor->ultima_fecha_lectura
                            ? \Illuminate\Support\Carbon::parse($medidor->ultima_fecha_lectura)->format('d/m/Y')
                            : null,
                    ];
                });
        });
    }

    private function meterCatalogQuery(): Builder
    {
        $ultimaLecturaActual = Lectura::query()
            ->select('lectura_actual')
            ->whereColumn('id_medidor', 'medidores.id_medidor')
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id_lectura')
            ->limit(1);

        $ultimaFechaLectura = Lectura::query()
            ->select('fecha_lectura')
            ->whereColumn('id_medidor', 'medidores.id_medidor')
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id_lectura')
            ->limit(1);

        return Medidor::query()
            ->select(['id_medidor', 'numero_serie', 'id_socio'])
            ->selectSub($ultimaLecturaActual, 'ultima_lectura_actual')
            ->selectSub($ultimaFechaLectura, 'ultima_fecha_lectura')
            ->with([
                'socio:id_socio,numero_socio,id_persona',
                'socio.persona:id_persona,nombres,apellidos',
            ])
            ->where('estado', 'activo')
            ->orderBy('numero_serie');
    }

    private function readingStats(): array
    {
        return OperationalCache::remember('lecturas:stats:' . now()->format('Y-m'), function () {
            $summary = Lectura::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE fecha_lectura BETWEEN ? AND ?) as mes,
                    AVG(consumo_m3) as promedio_consumo
                ", [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->first();

            return [
                'total' => (int) ($summary?->total ?? 0),
                'mes' => (int) ($summary?->mes ?? 0),
                'promedio_consumo' => round((float) ($summary?->promedio_consumo ?? 0), 2),
            ];
        });
    }
}
