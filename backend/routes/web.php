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

// Simple root route to serve React app
Route::get('/', function () {
    if (file_exists(public_path('index.html'))) {
        return file_get_contents(public_path('index.html'));
    }
    return response()->json([
        'message' => 'LMS Platform - React frontend not built yet',
        'api_status' => 'Available at /api/*'
    ]);
});

// Serve React app for all auth routes
Route::get('/auth/{any}', function () {
    if (file_exists(public_path('index.html'))) {
        return file_get_contents(public_path('index.html'));
    }
    return response()->json(['error' => 'Frontend not built']);
})->where('any', '.*');

// Serve React app for all dashboard routes  
Route::get('/dashboard/{any?}', function () {
    if (file_exists(public_path('index.html'))) {
        return file_get_contents(public_path('index.html'));
    }
    return response()->json(['error' => 'Frontend not built']);
})->where('any', '.*');

// Serve React app for all courses routes
Route::get('/courses/{any?}', function () {
    if (file_exists(public_path('index.html'))) {
        return file_get_contents(public_path('index.html'));
    }
    return response()->json(['error' => 'Frontend not built']);
})->where('any', '.*');

// Direct POST test in web routes to bypass API issues
Route::post('/test-post', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'POST works in web routes!',
        'method' => $request->method(),
        'data' => $request->all()
    ]);
});

// Direct login test in web routes
Route::post('/test-login', function (Request $request) {
    try {
        $email = $request->input('email', 'admin@lms.com');
        $password = $request->input('password', 'admin123');
        
        $user = DB::table('users')->where('email', $email)->first();
        
        if ($user && Hash::check($password, $user->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Login successful via web routes',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Auth routes removed during cleanup
