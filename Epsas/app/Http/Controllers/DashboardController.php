<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the dashboard based on user role
     */
    public function index()
    {
        $user = Auth::user();

        // Check user's primary role and redirect to appropriate dashboard
        if ($user->hasRole('administrador')) {
            return view('dashboard.admin');
        } elseif ($user->hasRole('secretaria')) {
            return view('dashboard.secretaria');
        } elseif ($user->hasRole('tecnico')) {
            return view('dashboard.tecnico');
        }

        // Fallback to a generic dashboard if user has no recognized role
        return view('dashboard.index');
    }
}
