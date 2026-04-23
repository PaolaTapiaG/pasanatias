@extends('layouts.app')

@section('title', 'Panel Administrador - EPSAS')

@section('content')
<div class="flex h-screen bg-gray-50">
    <!-- Sidebar Admin -->
    @include('slideboard.slidebaradmin')

    <!-- Main Content -->
    <div class="flex-1 md:ml-64 flex flex-col min-h-screen">
        <!-- Top Navbar -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between h-16 px-6">
                <h1 class="text-2xl font-bold text-gray-900">Panel Administrador</h1>
                <div class="flex items-center space-x-4">
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1 right-1 block w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="w-1 h-8 bg-gray-200"></div>
                    <div class="flex items-center">
                        <img class="w-10 h-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=0D8ABC&color=fff" alt="Avatar">
                        <span class="ml-3 text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            @if (session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start">
                    <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="font-medium">¡Éxito!</p>
                        <p class="text-sm">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Welcome Section -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900">¡Bienvenido, {{ Auth::user()->name }}!</h2>
                <p class="text-gray-600 mt-2">Panel de control del administrador del sistema</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Usuarios -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Total de Usuarios</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ \App\Models\User::count() }}</p>
                            <p class="text-xs text-gray-500 mt-2">Usuarios activos en el sistema</p>
                        </div>
                        <div class="text-4xl text-blue-100">👥</div>
                    </div>
                </div>

                <!-- Roles Activos -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Roles Activos</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ \App\Models\Role::count() }}</p>
                            <p class="text-xs text-gray-500 mt-2">Roles definidos en el sistema</p>
                        </div>
                        <div class="text-4xl text-purple-100">🔐</div>
                    </div>
                </div>

                <!-- Permisos -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Permisos Configurados</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ \App\Models\Permission::count() }}</p>
                            <p class="text-xs text-gray-500 mt-2">Permisos en el sistema</p>
                        </div>
                        <div class="text-4xl text-green-100">✓</div>
                    </div>
                </div>

                <!-- Última Actividad -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Última Actividad</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2">Ahora</p>
                            <p class="text-xs text-gray-500 mt-2">Actualizado en tiempo real</p>
                        </div>
                        <div class="text-4xl text-yellow-100">📊</div>
                    </div>
                </div>
            </div>

            <!-- Management Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- User Management Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">👥 Gestión de Usuarios</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Crear y editar usuarios
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Asignar y revocar roles
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Resetear contraseñas
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Desactivar/reactivar cuentas
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Usuarios
                        </button>
                    </div>
                </div>

                <!-- System Configuration Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">⚙️ Configuración del Sistema</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-purple-500 mr-3">✓</span>
                                Parámetros generales del sistema
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-purple-500 mr-3">✓</span>
                                Tarifas y valores
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-purple-500 mr-3">✓</span>
                                Respaldos de base de datos
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-purple-500 mr-3">✓</span>
                                Logs y auditoría
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Configuración
                        </button>
                    </div>
                </div>

                <!-- Audit Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">📋 Auditoría del Sistema</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-red-500 mr-3">✓</span>
                                Registro de cambios
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-red-500 mr-3">✓</span>
                                Historial de accesos
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-red-500 mr-3">✓</span>
                                Errores del sistema
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-red-500 mr-3">✓</span>
                                Reportes de seguridad
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-red-600 hover:bg-red-700 text-white font-medium py-2 rounded-lg transition">
                            Ver Auditoría
                        </button>
                    </div>
                </div>

                <!-- Permissions Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">🔒 Roles y Permisos</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-green-500 mr-3">✓</span>
                                Crear y editar roles
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-green-500 mr-3">✓</span>
                                Asignar permisos a roles
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-green-500 mr-3">✓</span>
                                Permisos personalizados
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-green-500 mr-3">✓</span>
                                Exportar configuración
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Permisos
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-12 text-center text-gray-500 text-sm border-t border-gray-200 pt-6">
                <p>© 2026 EPSAS - Sistema de Gestión de Agua. Todos los derechos reservados.</p>
            </div>
        </main>
    </div>
</div>
@endsection
