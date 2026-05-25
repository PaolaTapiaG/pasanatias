<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Medidor;
use App\Models\Socio;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedidorController extends Controller
{
    public function index(Request $request): View
    {
        return view('medidores.index', [
            'medidores' => $this->meterPaginator($request),
            'stats' => $this->meterStats(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/medidores', 'GET');

        $this->meterPaginator($request, url('/admin/medidores'));
        $this->meterStats();
    }

    private function meterPaginator(Request $request, ?string $path = null)
    {
        $query = DB::table('medidores as m')
            ->leftJoin('socios as s', 's.id_socio', '=', 'm.id_socio')
            ->leftJoin('personas as ps', 'ps.id_persona', '=', 's.id_persona')
            ->leftJoin('sectores as sec', 'sec.id_sector', '=', 's.id_sector')
            ->leftJoin('empleados as e', 'e.id_empleado', '=', 'm.id_empleado_instalador')
            ->leftJoin('personas as pe', 'pe.id_persona', '=', 'e.id_persona')
            ->select([
                'm.id_medidor',
                'm.numero_serie',
                'm.marca',
                'm.modelo',
                'm.fecha_instalacion',
                'm.estado',
                'm.id_socio',
                'm.id_empleado_instalador',
                'm.created_at',
                's.numero_socio',
                'sec.nombre as sector_nombre',
                'ps.cedula_identidad',
            ])
            ->selectRaw("TRIM(COALESCE(ps.nombres, '') || ' ' || COALESCE(ps.apellidos, '')) as socio_nombre")
            ->selectRaw("TRIM(COALESCE(pe.nombres, '') || ' ' || COALESCE(pe.apellidos, '')) as instalador_nombre")
            ->orderByDesc('m.created_at');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);

            $query->where(function ($builder) use ($term) {
                $builder->where('m.numero_serie', 'ilike', "%{$term}%")
                    ->orWhere('m.marca', 'ilike', "%{$term}%")
                    ->orWhere('m.modelo', 'ilike', "%{$term}%")
                    ->orWhere('ps.nombres', 'ilike', "%{$term}%")
                    ->orWhere('ps.apellidos', 'ilike', "%{$term}%")
                    ->orWhere('ps.cedula_identidad', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('m.estado', $request->estado);
        }

        Cache::add('medidores:index:version', 1, now()->addYears(2));
        $cacheKey = 'medidores:index:v' . Cache::get('medidores:index:version', 1) . ':' . md5(json_encode($request->query()));

        return Cache::remember($cacheKey, now()->addDays(7), fn () => $query
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/medidores'))
            ->appends($request->query())
            ->through(fn ($row) => $this->meterRowForView($row)));
    }

    private function meterRowForView(object $row): object
    {
        $row->fecha_instalacion = $row->fecha_instalacion ? Carbon::parse($row->fecha_instalacion) : null;
        $row->socio = $row->id_socio ? (object) [
            'id_socio' => $row->id_socio,
            'codigo_display' => $row->numero_socio ?: ('SOC-' . str_pad((string) $row->id_socio, 4, '0', STR_PAD_LEFT)),
            'persona' => (object) [
                'nombre_completo' => $row->socio_nombre ?: 'Sin socio',
            ],
            'sector' => (object) [
                'nombre' => $row->sector_nombre ?: 'Sin sector',
            ],
        ] : null;
        $row->empleadoInstalador = $row->id_empleado_instalador ? (object) [
            'id_empleado' => $row->id_empleado_instalador,
            'persona' => (object) [
                'nombre_completo' => $row->instalador_nombre ?: 'No asignado',
            ],
        ] : null;

        return $row;
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('medidores.create', [
            'sociosDisponibles' => $this->availableSocios(),
            'tecnicos' => $this->tecnicos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $this->validateMedidor($request);

        if ($data['estado'] === 'activo') {
            $medidorActivo = Medidor::query()
                ->where('id_socio', $data['id_socio'])
                ->where('estado', 'activo')
                ->first();

            if ($medidorActivo) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'id_socio' => "El socio ya tiene un medidor activo ({$medidorActivo->numero_serie}).",
                    ]);
            }
        }

        Medidor::create($data);
        $this->flushMeterCaches();

        return redirect()
            ->route('tecnico.medidores.index')
            ->with('success', 'Medidor registrado correctamente.');
    }

    public function edit(Medidor $medidor): View
    {
        $this->ensureAdmin();

        $medidor->load(['socio.persona', 'empleadoInstalador.persona']);

        return view('medidores.edit', [
            'medidor' => $medidor,
            'tecnicos' => $this->tecnicos(),
        ]);
    }

    public function update(Request $request, Medidor $medidor): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $this->validateMedidor($request, $medidor);

        if ($data['estado'] === 'activo') {
            $medidorActivo = Medidor::query()
                ->where('id_socio', $data['id_socio'])
                ->where('estado', 'activo')
                ->where('id_medidor', '!=', $medidor->id_medidor)
                ->first();

            if ($medidorActivo) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'id_socio' => "El socio ya tiene un medidor activo ({$medidorActivo->numero_serie}).",
                    ]);
            }
        }

        $medidor->update($data);
        $this->flushMeterCaches();

        return redirect()
            ->route('tecnico.medidores.index')
            ->with('success', 'Medidor actualizado correctamente.');
    }

    private function validateMedidor(Request $request, ?Medidor $medidor = null): array
    {
        return $request->validate([
            'numero_serie' => [
                'required',
                'string',
                'max:60',
                Rule::unique('medidores', 'numero_serie')->ignore($medidor?->id_medidor, 'id_medidor'),
            ],
            'marca' => ['required', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'fecha_instalacion' => ['required', 'date'],
            'estado' => ['required', Rule::in(['activo', 'inactivo', 'danado', 'reemplazado'])],
            'id_socio' => ['required', 'integer', 'exists:socios,id_socio'],
            'id_empleado_instalador' => ['nullable', 'integer', 'exists:empleados,id_empleado'],
        ]);
    }

    private function tecnicos()
    {
        return Cache::remember('medidores:tecnicos', now()->addMinutes(10), function () {
            return Empleado::query()
            ->select(['id_empleado', 'id_persona'])
            ->with('persona:id_persona,nombres,apellidos')
            ->where('estado', 'activo')
            ->whereHas('rol', fn ($rol) => $rol->where('nombre', 'tecnico'))
            ->orderBy('id_empleado')
            ->get();
        });
    }

    private function availableSocios()
    {
        return Cache::remember('medidores:socios-disponibles', now()->addMinutes(5), function () {
            return Socio::query()
            ->select(['id_socio', 'numero_socio', 'id_persona'])
            ->with('persona:id_persona,nombres,apellidos,cedula_identidad')
            ->whereDoesntHave('medidorActivo')
            ->orderBy('numero_socio')
            ->get();
        });
    }

    private function meterStats(): array
    {
        return Cache::remember('medidores:stats', now()->addDays(7), function () {
            $summary = Medidor::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE estado = 'activo') as activos,
                    COUNT(*) FILTER (WHERE estado = 'danado') as danados,
                    COUNT(*) FILTER (WHERE estado = 'reemplazado') as reemplazados
                ")
                ->first();

            return [
                'total' => (int) ($summary?->total ?? 0),
                'activos' => (int) ($summary?->activos ?? 0),
                'danados' => (int) ($summary?->danados ?? 0),
                'reemplazados' => (int) ($summary?->reemplazados ?? 0),
            ];
        });
    }

    private function flushMeterCaches(): void
    {
        Cache::forget('medidores:stats');
        Cache::forget('medidores:tecnicos');
        Cache::forget('medidores:socios-disponibles');
        Cache::forget('lecturas:medidores-disponibles');
        Cache::forget('tecnico:consumo:catalogo');
        Cache::forget('tecnico:consumo:catalogo:v2');
        Cache::forget('tecnico:consumo:catalogo:v3');
        Cache::forget('tecnico:consumo:stats');
        Cache::forget('tecnico:consumo:recent-readings');
        Cache::forget('dashboard:tecnico:upcoming-readings');
        Cache::forget('dashboard:tecnico:reading-calendar:' . now()->format('Y-m'));
        Cache::forget('dashboard:tecnico:medidores-total');
        Cache::forget('api.dashboard.tecnico');
        Cache::add('medidores:index:version', 1, now()->addYears(2));
        Cache::increment('medidores:index:version');
        OperationalCache::bump();
    }

    private function ensureAdmin(): void
    {
        if (!Auth::user()?->hasRole('administrador')) {
            throw new AuthorizationException('Solo el administrador puede modificar medidores.');
        }
    }
}
