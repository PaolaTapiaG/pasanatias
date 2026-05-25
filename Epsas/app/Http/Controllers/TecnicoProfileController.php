<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TecnicoProfileController extends Controller
{
    public function edit(): View
    {
        return view('tecnico.configuracion', [
            'user' => Auth::user()->loadMissing('persona'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user()->loadMissing('persona');
        $email = Str::lower(trim((string) $request->input('email')));
        $emailRules = ['required', 'email', 'max:150'];

        if ($email !== Str::lower((string) $user->email)) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($user->id);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $userUpdates = [
            'name' => $data['name'],
            'email' => $email,
        ];

        if (!empty($data['new_password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contrasena actual no es valida.']);
            }

            $userUpdates['password'] = $data['new_password'];
            $userUpdates['must_change_password'] = false;
        }

        $persona = $user->persona ?? Persona::query()
            ->where('email', $email)
            ->orderBy('id_persona')
            ->first();

        if ($persona) {
            [$nombres, $apellidos] = $this->splitName($data['name']);
            $photoPath = $persona->foto_path;

            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                if ($photoPath && Str::startsWith($photoPath, 'storage/')) {
                    Storage::disk('public')->delete(Str::after($photoPath, 'storage/'));
                }

                $stored = $request->file('photo')->storeAs(
                    'perfiles',
                    'perfil_tecnico_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . strtolower($request->file('photo')->extension() ?: 'jpg'),
                    'public'
                );

                $photoPath = 'storage/' . $stored;
            }

            $persona->update([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['phone'] ?? $persona->telefono,
                'email' => $email,
                'foto_path' => $photoPath,
            ]);

            $persona->forceFill([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['phone'] ?? $persona->telefono,
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

        return redirect()->route('tecnico.configuracion.index')->with('success', 'Perfil tecnico actualizado correctamente.');
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) <= 1) {
            return [$fullName, ''];
        }

        $half = (int) ceil(count($parts) / 2);

        return [
            implode(' ', array_slice($parts, 0, $half)),
            implode(' ', array_slice($parts, $half)),
        ];
    }

    private function authUserCacheKey($user): string
    {
        return 'auth:user:' . str_replace('\\', '.', $user::class) . ':' . $user->getAuthIdentifier();
    }
}
