<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    public function home(): RedirectResponse
    {
        return Auth::check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    }

    public function usuarios(): View
    {
        return view('placeholders.simple-message', [
            'title' => 'Usuarios',
            'message' => 'Modulo de Usuarios (En desarrollo)',
        ]);
    }

    public function permisos(): View
    {
        return view('placeholders.simple-message', [
            'title' => 'Permisos',
            'message' => 'Modulo de Permisos (En desarrollo)',
        ]);
    }

    public function auditoria(): View
    {
        return view('placeholders.simple-message', [
            'title' => 'Auditoria',
            'message' => 'Modulo de Auditoria (En desarrollo)',
        ]);
    }
}
