<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas de autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    // Dashboard - Accesible para todos los usuarios autenticados (redirecciona según rol)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ===============================
    // Rutas protegidas por rol ADMINISTRADOR
    // ===============================
    Route::middleware('role:administrador')->prefix('admin')->name('admin.')->group(function () {
        // Gestión de usuarios
        Route::get('/usuarios', function () {
            return 'Módulo de Usuarios (En desarrollo)';
        })->name('usuarios.index');
        
        // Gestión de permisos
        Route::get('/permisos', function () {
            return 'Módulo de Permisos (En desarrollo)';
        })->name('permisos.index');
        
        // Configuración
        Route::get('/configuracion', function () {
            return 'Módulo de Configuración (En desarrollo)';
        })->name('configuracion.index');
        
        // Auditoría
        Route::get('/auditoria', function () {
            return 'Módulo de Auditoría (En desarrollo)';
        })->name('auditoria.index');
    });

    // ===============================
    // Rutas protegidas por rol SECRETARIA
    // ===============================
    Route::middleware('role:secretaria')->prefix('admin')->name('secretaria.')->group(function () {
        // Gestión de socios
        Route::get('/socios', function () {
            return 'Módulo de Socios (En desarrollo)';
        })->name('socios.index');
        
        // Gestión de facturas
        Route::get('/facturas', function () {
            return 'Módulo de Facturas (En desarrollo)';
        })->name('facturas.index');
        
        Route::post('/facturas', function () {
            return 'Crear Factura (En desarrollo)';
        })->name('facturas.store');
        
        // Gestión de cobros
        Route::get('/cobros', function () {
            return 'Módulo de Cobros (En desarrollo)';
        })->name('cobros.index');
        
        Route::post('/cobros', function () {
            return 'Registrar Cobro (En desarrollo)';
        })->name('cobros.store');
        
        // Reportes
        Route::get('/reportes', function () {
            return 'Módulo de Reportes (En desarrollo)';
        })->name('reportes.index');
    });

    // ===============================
    // Rutas protegidas por rol TECNICO
    // ===============================
    Route::middleware('role:tecnico')->prefix('admin')->name('tecnico.')->group(function () {
        // Gestión de medidores
        Route::get('/medidores', function () {
            return 'Módulo de Medidores (En desarrollo)';
        })->name('medidores.index');
        
        Route::post('/medidores', function () {
            return 'Crear Medidor (En desarrollo)';
        })->name('medidores.store');
        
        // Gestión de lecturas
        Route::get('/lecturas', function () {
            return 'Módulo de Lecturas (En desarrollo)';
        })->name('lecturas.index');
        
        Route::post('/lecturas', function () {
            return 'Registrar Lectura (En desarrollo)';
        })->name('lecturas.store');
        
        // Mantenimiento
        Route::get('/mantenimiento', function () {
            return 'Módulo de Mantenimiento (En desarrollo)';
        })->name('mantenimiento.index');
        
        // Reportes técnicos
        Route::get('/reportes-tecnicos', function () {
            return 'Reportes Técnicos (En desarrollo)';
        })->name('reportes-tecnicos.index');
    });

    // ===============================
    // Rutas disponibles para ADMIN y SECRETARIA
    // ===============================
    Route::middleware('role:administrador,secretaria')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/socios', function () {
            return 'Módulo de Socios';
        })->name('socios.index');
    });

    // ===============================
    // Rutas disponibles para ADMIN y TECNICO
    // ===============================
    Route::middleware('role:administrador,tecnico')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/medidores', function () {
            return 'Módulo de Medidores';
        })->name('medidores.index');
    });
});

