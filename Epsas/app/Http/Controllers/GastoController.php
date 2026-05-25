<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GastoController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->expenseIndexData($request);

        return view('gastos.index', $data);
    }

    public function warmIndexCache(): void
    {
        $this->expenseIndexData(Request::create('/admin/gastos', 'GET'), url('/admin/gastos'));
    }

    private function expenseIndexData(Request $request, ?string $path = null): array
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());

        $query = DB::table('gastos as g')
            ->leftJoin('empleados as e', 'e.id_empleado', '=', 'g.id_empleado')
            ->leftJoin('personas as p', 'p.id_persona', '=', 'e.id_persona')
            ->select(['g.id_gasto', 'g.fecha_gasto', 'g.concepto', 'g.categoria', 'g.descripcion', 'g.monto', 'g.id_empleado'])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as empleado_nombre")
            ->whereBetween('g.fecha_gasto', [$desde, $hasta])
            ->orderByDesc('g.fecha_gasto')
            ->orderByDesc('g.id_gasto');

        Cache::add('gastos:index:version', 1, now()->addYears(2));
        $cacheKey = 'gastos:index:v' . Cache::get('gastos:index:version', 1) . ':' . md5(json_encode($request->query()));

        return [
            'gastos' => Cache::remember($cacheKey, now()->addDays(7), fn () => $query
                ->simplePaginate(12)
                ->withPath($path ?? url('/admin/gastos'))
                ->appends($request->query())
                ->through(fn ($row) => $this->expenseRowForView($row))),
            'desde' => $desde,
            'hasta' => $hasta,
            'totalGastos' => Cache::remember("gastos:total:{$desde}:{$hasta}", now()->addDays(7), fn () => (float) Gasto::whereBetween('fecha_gasto', [$desde, $hasta])->sum('monto')),
        ];
    }

    private function expenseRowForView(object $row): object
    {
        $row->fecha_gasto = $row->fecha_gasto ? Carbon::parse($row->fecha_gasto) : null;
        $row->empleado = $row->id_empleado ? (object) [
            'id_empleado' => $row->id_empleado,
            'persona' => (object) [
                'nombre_completo' => $row->empleado_nombre ?: 'Sin responsable',
            ],
        ] : null;

        return $row;
    }

    public function store(Request $request): RedirectResponse
    {
        $empleadoId = Auth::user()?->persona?->empleado?->id_empleado;

        $data = $request->validate([
            'fecha_gasto' => ['required', 'date'],
            'concepto' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ]);

        Gasto::create($data + ['id_empleado' => $empleadoId]);
        Cache::forget('gastos:total:' . $data['fecha_gasto'] . ':' . $data['fecha_gasto']);
        Cache::forget('dashboard:recent-operational-expenses');
        Cache::add('gastos:index:version', 1, now()->addYears(2));
        Cache::increment('gastos:index:version');
        Cache::add('reportes:index:version', 1, now()->addYears(2));
        Cache::increment('reportes:index:version');
        OperationalCache::bump();

        return redirect()
            ->route('admin.gastos.index')
            ->with('success', 'Gasto registrado correctamente.');
    }
}
