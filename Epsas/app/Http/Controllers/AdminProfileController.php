<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user()->loadMissing('persona');
        $email = Str::lower(trim((string) $request->input('admin_email')));
        $emailRules = ['required', 'email', 'max:150'];

        if ($email !== Str::lower((string) $user->email)) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($user->id);
        }

        $data = $request->validate([
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => $emailRules,
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'admin_description' => ['nullable', 'string', 'max:500'],
            'admin_photo' => ['nullable', 'image', 'max:2048'],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);
        $data['admin_email'] = $email;

        $userUpdates = [
            'name' => $data['admin_name'],
            'email' => $email,
        ];
        if (!empty($data['new_password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contrasena actual no es valida.']);
            }

            $userUpdates['password'] = $data['new_password'];
        }

        $persona = $this->resolvePersonaForUser($user, $data);

        if ($persona) {
            $photoPath = $persona->foto_path;
            [$nombres, $apellidos] = $this->splitFullName($data['admin_name']);

            if ($request->hasFile('admin_photo') && $request->file('admin_photo')->isValid()) {
                if ($photoPath && Str::startsWith($photoPath, 'storage/')) {
                    Storage::disk('public')->delete(Str::after($photoPath, 'storage/'));
                }

                $filename = 'perfil_admin_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . strtolower($request->file('admin_photo')->extension() ?: 'jpg');
                $stored = $request->file('admin_photo')->storeAs('perfiles', $filename, 'public');
                $photoPath = 'storage/' . $stored;
            }

            $persona->update([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['admin_phone'] ?? $persona->telefono,
                'email' => $email,
                'foto_path' => $photoPath,
            ]);

            $persona->forceFill([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['admin_phone'] ?? $persona->telefono,
                'email' => $email,
                'foto_path' => $photoPath,
            ]);
            $userUpdates['name'] = trim($nombres . ' ' . $apellidos);
            $userUpdates['email'] = $email;
            $userUpdates['id_persona'] = $persona->id_persona;
            $user->setRelation('persona', $persona);
        }

        $user->forceFill($userUpdates)->save();
        $user->flushAuthCache();
        Cache::put($this->authUserCacheKey($user), $user, now()->addMinutes((int) config('auth.user_cache_minutes', 1440)));

        return redirect()
            ->route('admin.configuracion.index')
            ->with('success', 'Perfil de administrador actualizado correctamente.');
    }

    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) <= 1) {
            return [$fullName, ''];
        }

        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }

        $half = (int) ceil(count($parts) / 2);
        $nombres = implode(' ', array_slice($parts, 0, $half));
        $apellidos = implode(' ', array_slice($parts, $half));

        return [$nombres, $apellidos];
    }

    private function resolvePersonaForUser($user, array $data): ?Persona
    {
        if ($user->persona) {
            return $user->persona;
        }

        $emails = array_filter([
            Str::lower((string) $user->email),
            Str::lower((string) ($data['admin_email'] ?? '')),
        ]);

        if (empty($emails)) {
            return null;
        }

        $persona = Persona::query()
            ->whereIn('email', array_values(array_unique($emails)))
            ->orderBy('id_persona')
            ->first();

        if ($persona) {
            Log::info('[ADMIN PROFILE UPDATE] Persona linked automatically', [
                'user_id' => $user->id,
                'persona_id' => $persona->id_persona,
                'persona_email' => $persona->email,
            ]);
        }

        return $persona;
    }

    private function authUserCacheKey($user): string
    {
        return 'auth:user:' . str_replace('\\', '.', $user::class) . ':' . $user->getAuthIdentifier();
    }
}
