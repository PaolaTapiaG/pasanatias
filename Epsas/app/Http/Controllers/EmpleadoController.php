<?php

namespace App\Http\Controllers;

use App\Http\Services\CredentialNotificationService;
use App\Models\Empleado;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    public function __construct(private CredentialNotificationService $credentialNotifications)
    {
    }

    public function index(Request $request)
    {
        return view('empleados.index', [
            'empleados' => $this->employeePaginator($request),
            'roles' => Rol::cachedOrderedList(),
            'totales' => $this->employeeTotals(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/empleados', 'GET');

        $this->employeePaginator($request, url('/admin/empleados'));
        Rol::cachedOrderedList();
        $this->employeeTotals();
    }

    private function employeePaginator(Request $request, ?string $path = null)
    {
        Cache::add('empleados:index:version', 1, now()->addYears(2));
        $cacheKey = 'empleados:index:v' . Cache::get('empleados:index:version', 1) . ':' . md5(json_encode($request->query()));

        return Cache::remember($cacheKey, now()->addDays(7), fn () => $this->employeeIndexQuery($request)
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/empleados'))
            ->appends($request->query())
            ->through(fn ($row) => $this->employeeRowForView($row)));
    }

    private function employeeIndexQuery(Request $request)
    {
        $query = DB::table('empleados as e')
            ->leftJoin('personas as p', 'p.id_persona', '=', 'e.id_persona')
            ->leftJoin('roles as r', 'r.id_rol', '=', 'e.id_rol')
            ->leftJoin('users as u', 'u.id_persona', '=', 'p.id_persona')
            ->select([
                'e.id_empleado',
                'e.fecha_ingreso',
                'e.estado',
                'e.id_persona',
                'e.id_rol',
                'p.nombres',
                'p.apellidos',
                'p.cedula_identidad',
                'p.telefono',
                'p.email as persona_email',
                'p.foto_path',
                'r.nombre as rol_nombre',
                'u.id as user_id',
                'u.username',
                'u.email as user_email',
                'u.name as user_name',
            ])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo")
            ->orderByDesc('e.fecha_ingreso')
            ->orderByDesc('e.id_empleado');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('p.nombres', 'ilike', "%{$term}%")
                    ->orWhere('p.apellidos', 'ilike', "%{$term}%")
                    ->orWhere('p.cedula_identidad', 'ilike', "%{$term}%")
                    ->orWhere('p.email', 'ilike', "%{$term}%")
                    ->orWhere('r.nombre', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('e.estado', $request->estado);
        }

        if ($request->filled('rol')) {
            $query->where('e.id_rol', $request->rol);
        }

        return $query;
    }

    private function employeeRowForView(object $row): object
    {
        $row->fecha_ingreso = $row->fecha_ingreso ? Carbon::parse($row->fecha_ingreso) : null;
        $row->persona = (object) [
            'id_persona' => $row->id_persona,
            'nombres' => $row->nombres,
            'apellidos' => $row->apellidos,
            'nombre_completo' => $row->nombre_completo ?: trim(($row->nombres ?? '') . ' ' . ($row->apellidos ?? '')),
            'cedula_identidad' => $row->cedula_identidad,
            'telefono' => $row->telefono,
            'email' => $row->persona_email,
            'foto_path' => $row->foto_path,
            'foto_url' => $this->photoUrl($row->foto_path),
        ];
        $row->rol = $row->id_rol ? (object) [
            'id_rol' => $row->id_rol,
            'nombre' => $row->rol_nombre,
        ] : null;
        $row->user = $row->user_id ? (object) [
            'id' => $row->user_id,
            'id_persona' => $row->id_persona,
            'username' => $row->username,
            'email' => $row->user_email,
            'name' => $row->user_name,
        ] : null;

        return $row;
    }

    private function photoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['storage/', 'uploads/'])) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public function create()
    {
        return view('empleados.create', [
            'roles' => Rol::cachedOrderedList(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $data = $this->validateEmpleado($request);
        $passwordTemporal = $this->generateTemporaryPassword();

        $resultado = DB::transaction(function () use ($data, $request, $passwordTemporal) {
            $email = Str::lower($data['email']);
            $username = Str::lower($data['username']);

            $persona = Persona::create([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cedula_identidad' => $data['cedula_identidad'],
                'telefono' => $data['telefono'],
                'email' => $email,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'foto_path' => $this->storeEmployeePhoto($request),
            ]);

            $empleado = Empleado::create([
                'fecha_ingreso' => $data['fecha_ingreso'],
                'estado' => $data['estado'],
                'id_persona' => $persona->id_persona,
                'id_rol' => $data['id_rol'],
            ]);

            $user = User::create([
                'name' => $persona->nombre_completo,
                'username' => $username,
                'email' => $email,
                'id_persona' => $persona->id_persona,
                'password' => $passwordTemporal,
                'must_change_password' => true,
            ]);

            $this->syncUserRole($user, $empleado->rol);

            return [
                'empleado' => $empleado,
                'user' => $user,
                'passwordTemporal' => $passwordTemporal,
            ];
        });

        $this->credentialNotifications->sendEmployeeWelcome(
            $resultado['user']->fresh(['persona']),
            $resultado['passwordTemporal']
        );

        $this->flushEmployeeCaches();

        return redirect()
            ->route('admin.empleados.show', $resultado['empleado'])
            ->with('success', 'Empleado registrado correctamente. Se creo su acceso al sistema y se notifico por SMS y correo cuando fue posible.')
            ->with('sms_preview', app()->isLocal() ? $resultado['passwordTemporal'] : null);
    }

    public function show(Empleado $empleado)
    {
        $empleado->load([
            'persona',
            'rol',
            'user',
            'cobros.factura',
            'lecturas.medidor',
            'medidoresInstalados.socio.persona',
        ]);

        return view('empleados.show', [
            'empleado' => $empleado,
        ]);
    }

    public function edit(Empleado $empleado)
    {
        $empleado->load(['persona', 'user']);

        return view('empleados.edit', [
            'empleado' => $empleado,
            'roles' => Rol::cachedOrderedList(),
        ]);
    }

    public function update(Request $request, Empleado $empleado)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $empleado->load(['persona', 'rol', 'user']);
        $data = $this->validateEmpleado($request, $empleado);

        DB::transaction(function () use ($data, $request, $empleado) {
            $email = Str::lower($data['email']);
            $username = Str::lower($data['username']);
            $fotoPath = $this->storeEmployeePhoto($request, $empleado->persona->foto_path);

            $empleado->persona->update([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cedula_identidad' => $data['cedula_identidad'],
                'telefono' => $data['telefono'],
                'email' => $email,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'foto_path' => $fotoPath,
            ]);

            $empleado->update([
                'fecha_ingreso' => $data['fecha_ingreso'],
                'estado' => $data['estado'],
                'id_rol' => $data['id_rol'],
            ]);

            $user = $empleado->user ?: User::create([
                'name' => $empleado->persona->nombre_completo,
                'username' => $username,
                'email' => $email,
                'id_persona' => $empleado->persona->id_persona,
                'password' => $this->generateTemporaryPassword(),
                'must_change_password' => true,
            ]);

            $user->update([
                'name' => $empleado->persona->fresh()->nombre_completo,
                'username' => $username,
                'email' => $email,
                'id_persona' => $empleado->persona->id_persona,
            ]);

            $this->syncUserRole($user->fresh(), $empleado->rol()->withDefault()->first());
        });

        $this->flushEmployeeCaches();

        return redirect()
            ->route('admin.empleados.show', $empleado)
            ->with('success', 'Empleado actualizado correctamente.');
    }

    private function validateEmpleado(Request $request, ?Empleado $empleado = null): array
    {
        $personaId = $empleado?->id_persona;
        $userId = $empleado?->user?->id;

        return $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'cedula_identidad' => [
                'required',
                'string',
                'max:30',
                Rule::unique('personas', 'cedula_identidad')->ignore($personaId, 'id_persona'),
            ],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('personas', 'email')->ignore($personaId, 'id_persona'),
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:50',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'fecha_ingreso' => ['required', 'date'],
            'estado' => ['required', Rule::in(['activo', 'inactivo', 'suspendido'])],
            'id_rol' => ['required', 'exists:roles,id_rol'],
        ], [
            'email.unique' => 'El correo ya esta registrado por otro usuario o persona.',
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        return Str::upper(Str::random(4)) . random_int(1000, 9999);
    }

    private function storeEmployeePhoto(Request $request, ?string $currentPath = null): ?string
    {
        if (!$request->hasFile('foto')) {
            return $currentPath;
        }

        $file = $request->file('foto');
        if (!$file->isValid()) {
            return $currentPath;
        }

        if ($currentPath && Str::startsWith($currentPath, 'storage/')) {
            Storage::disk('public')->delete(Str::after($currentPath, 'storage/'));
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = 'empleado_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . $extension;
        $path = $file->storeAs('empleados', $filename, 'public');

        return $path ? 'storage/' . $path : $currentPath;
    }

    private function syncUserRole(User $user, ?Rol $rolEmpleado): void
    {
        if (!$rolEmpleado) {
            return;
        }

        $aliases = [
            'admin' => 'administrador',
            'administrador' => 'administrador',
            'secretaria' => 'secretaria',
            'tecnico' => 'tecnico',
        ];

        $roleName = Str::lower(trim((string) $rolEmpleado->nombre));
        $lookup = $aliases[$roleName] ?? $roleName;

        $role = Role::whereRaw('lower(name) = ?', [$lookup])->first();
        if (!$role) {
            return;
        }

        $user->roles()->sync([$role->id]);
        Cache::forget("user:{$user->getKey()}:role-names");
    }

    private function employeeTotals(): array
    {
        return Cache::remember('empleados:index:totales', now()->addDays(7), function () {
            $summary = Empleado::query()
                ->leftJoin('roles', 'roles.id_rol', '=', 'empleados.id_rol')
                ->selectRaw("
                    COUNT(*) FILTER (WHERE empleados.estado = 'activo') as activos,
                    COUNT(*) FILTER (WHERE empleados.estado = 'inactivo') as inactivos,
                    COUNT(*) FILTER (WHERE LOWER(COALESCE(roles.nombre, '')) = 'tecnico') as tecnicos
                ")
                ->first();

            return [
                'activos' => (int) ($summary?->activos ?? 0),
                'inactivos' => (int) ($summary?->inactivos ?? 0),
                'tecnicos' => (int) ($summary?->tecnicos ?? 0),
            ];
        });
    }

    private function flushEmployeeCaches(): void
    {
        Cache::forget('empleados:index:totales');
        Cache::add('empleados:index:version', 1, now()->addYears(2));
        Cache::increment('empleados:index:version');
    }
}
