<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Serve React frontend for all non-API routes
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');

// API status endpoint
Route::get('/api/status', function () {
    try {
        return response()->json([
            'Laravel' => app()->version(),
            'message' => 'LMS Backend API is running',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'database' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'port' => config('database.connections.' . config('database.default') . '.port'),
                'database' => config('database.connections.' . config('database.default') . '.database')
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Application error',
            'message' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable debug mode for trace'
        ], 500);
    }
});

// Auth routes removed during cleanup
