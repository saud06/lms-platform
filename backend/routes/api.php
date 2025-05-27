<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;

// Minimal API: health/test endpoint only (portfolio-friendly)
Route::get('/test', function () {
    try {
        return response()->json([
            'message' => 'API is working!',
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'database_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Health check failed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable debug for trace'
        ], 500);
    }
});

// Simple JSON response test
Route::get('/test/json', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'JSON response test successful',
        'timestamp' => now()->toIso8601String(),
        'data' => [
            'test_string' => 'Hello World',
            'test_number' => 12345,
            'test_boolean' => true,
            'test_array' => [1, 2, 3],
            'test_object' => ['key' => 'value']
        ]
    ]);
});

// Simple login test without cache or complex logic
Route::post('/test/login', function (Request $request) {
    \Log::info('Simple login test started');
    
    try {
        return response()->json([
            'success' => true,
            'message' => 'Simple login test successful',
            'user' => ['id' => 1, 'name' => 'Test User', 'role' => 'admin'],
            'token' => 'test_token_123',
            'timestamp' => now()->toIso8601String(),
            'request_data' => $request->all()
        ]);
    } catch (\Exception $e) {
        \Log::error('Simple login test failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()->toIso8601String()
        ], 500);
    }
});

// Debug endpoint for checking users in database
Route::get('/debug/users', function () {
    try {
        // Check if database connection works
        \DB::connection()->getPdo();
        
        // Get user count
        $userCount = User::count();
        
        // Get users with limited info (no passwords)
        $users = User::select('id', 'name', 'email', 'role', 'is_active', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->take(20) // Limit to 20 users for safety
                    ->get();
        
        return response()->json([
            'status' => 'success',
            'total_users' => $userCount,
            'sample_users' => $users,
            'timestamp' => now()->toIso8601String(),
            'database_info' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.pgsql.host'),
                'database' => config('database.connections.pgsql.database')
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'error_type' => get_class($e),
            'timestamp' => now()->toIso8601String()
        ], 500);
    }
});

// Debug endpoint for troubleshooting
Route::get('/debug', function () {
    try {
        $dbTest = null;
        try {
            \DB::connection()->getPdo();
            $dbTest = 'connected';
        } catch (\Exception $e) {
            $dbTest = 'failed: ' . $e->getMessage();
        }
        
        return response()->json([
            'status' => 'debug_info',
            'timestamp' => now()->toIso8601String(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'app_key' => config('app.key') ? 'set' : 'missing',
            'database' => [
                'default' => config('database.default'),
                'host' => config('database.connections.pgsql.host'),
                'port' => config('database.connections.pgsql.port'),
                'database' => config('database.connections.pgsql.database'),
                'username' => config('database.connections.pgsql.username') ? 'set' : 'missing',
                'password' => config('database.connections.pgsql.password') ? 'set' : 'missing',
                'connection_test' => $dbTest
            ],
            'extensions' => [
                'pdo_pgsql' => extension_loaded('pdo_pgsql'),
                'mbstring' => extension_loaded('mbstring'),
                'bcmath' => extension_loaded('bcmath')
            ],
            'directories' => [
                'storage_writable' => is_writable(storage_path()),
                'cache_writable' => is_writable(storage_path('framework/cache'))
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Debug endpoint failed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// CORS debugging endpoint
Route::get('/debug/cors', function (Request $request) {
    $origin = $request->header('Origin');
    $corsConfig = config('cors');
    
    return response()->json([
        'request_info' => [
            'origin' => $origin,
            'host' => $request->header('Host'),
            'user_agent' => $request->header('User-Agent'),
            'referer' => $request->header('Referer'),
            'all_headers' => $request->headers->all()
        ],
        'cors_config' => [
            'allowed_origins' => $corsConfig['allowed_origins'],
            'allowed_origins_patterns' => $corsConfig['allowed_origins_patterns'],
            'allowed_methods' => $corsConfig['allowed_methods'],
            'allowed_headers' => $corsConfig['allowed_headers']
        ],
        'cors_check' => [
            'origin_in_allowed' => in_array($origin, $corsConfig['allowed_origins']),
            'origin_matches_pattern' => $origin ? collect($corsConfig['allowed_origins_patterns'])->contains(function($pattern) use ($origin) {
                return fnmatch($pattern, $origin);
            }) : false
        ]
    ]);
});

// Enhanced login test endpoint for debugging
Route::post('/debug/login', function (Request $request) {
    try {
        $requestData = $request->all();
        $headers = $request->headers->all();
        
        \Log::info('Debug login attempt', [
            'data' => $requestData,
            'headers' => $headers,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Validate request
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        // Demo users
        $users = [
            'admin@example.com' => ['id' => 1, 'name' => 'Admin User', 'role' => 'admin'],
            'instructor@example.com' => ['id' => 2, 'name' => 'Instructor User', 'role' => 'instructor'],
            'student@example.com' => ['id' => 3, 'name' => 'Student User', 'role' => 'student'],
        ];
        
        if (!isset($users[$data['email']]) || $data['password'] !== 'password') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'debug_info' => [
                    'email_exists' => isset($users[$data['email']]),
                    'password_correct' => $data['password'] === 'password',
                    'available_emails' => array_keys($users)
                ]
            ], 401);
        }
        
        $user = $users[$data['email']];
        $token = 'debug_token_'.md5($data['email'].'|'.microtime(true));
        
        // Store in cache
        Cache::put('auth:token:'.$token, $user, now()->addDays(7));
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'debug_info' => [
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'accept_header' => $request->header('Accept'),
                'origin' => $request->header('Origin'),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String()
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Debug login error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Debug login failed',
            'message' => $e->getMessage(),
            'debug_info' => [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
});
Route::get('/student/courses/{courseId}/progress', function ($courseId) {
    $student = ensureDemoStudent();
    $course = Course::findOrFail($courseId);
    $enrollment = Enrollment::firstOrCreate(
        ['user_id' => $student->id, 'course_id' => $course->id],
        ['enrolled_at' => now(), 'progress' => 0]
    );
    return response()->json([
        'course_id' => $course->id,
        'progress' => (int) ($enrollment->progress ?? 0),
        'completed_at' => optional($enrollment->completed_at)->toIso8601String(),
    ]);
});

// PUT /student/courses/{courseId}/progress - update progress percentage 0..100 for demo student
Route::put('/student/courses/{courseId}/progress', function (Request $request, $courseId) {
    $student = ensureDemoStudent();
    $course = Course::findOrFail($courseId);
    $data = $request->validate([
        'progress' => 'required|integer|min:0|max:100',
    ]);
    $enrollment = Enrollment::firstOrCreate(
        ['user_id' => $student->id, 'course_id' => $course->id],
        ['enrolled_at' => now(), 'progress' => 0]
    );
    $enrollment->progress = $data['progress'];
    if ($enrollment->progress >= 100 && !$enrollment->completed_at) {
        $enrollment->completed_at = now();
    }
    if ($enrollment->progress < 100) {
        $enrollment->completed_at = null;
    }
    $enrollment->save();
    return response()->json([
        'course_id' => $course->id,
        'progress' => (int) $enrollment->progress,
        'completed_at' => optional($enrollment->completed_at)->toIso8601String(),
    ]);
});

// ---------------------------------------------------------------------------
// Admin Settings
// ---------------------------------------------------------------------------
Route::get('/admin/settings', function () {
    $settings = Setting::first();
    if (!$settings) {
        $settings = Setting::create([
            'platform_name' => 'LMS Platform',
            'support_email' => 'support@example.com',
        ]);
    }
    return response()->json([
        'platform_name' => $settings->platform_name,
        'support_email' => $settings->support_email,
        'updatedAt' => optional($settings->updated_at)->toIso8601String(),
    ]);
});

 

// ---------------------------------------------------------------------------
// Student endpoints (demo-friendly)
// ---------------------------------------------------------------------------

// GET /dashboard/student - aggregated stats and data for demo student
Route::get('/dashboard/student', function () {
    $student = ensureDemoStudent();

    // Ensure there are some instructor courses to enroll in
    $instructor = ensureDemoInstructor();
    if (Course::count() === 0) {
        Course::create([
            'title' => 'JavaScript Essentials',
            'description' => 'Learn the fundamentals of JavaScript',
            'short_description' => 'JS basics',
            'instructor_id' => $instructor->id,
            'category' => 'Web Development',
            'level' => 'beginner',
            'duration_hours' => 8,
            'price' => 39.99,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        Course::create([
            'title' => 'Python for Data Science',
            'description' => 'Intro to Python for data analysis',
            'short_description' => 'Python DS',
            'instructor_id' => $instructor->id,
            'category' => 'Data Science',
            'level' => 'beginner',
            'duration_hours' => 14,
            'price' => 69.00,
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    // Seed a couple of enrollments for the demo student if none
    if (Enrollment::where('user_id', $student->id)->count() === 0) {
        $courses = Course::inRandomOrder()->limit(2)->get();
        foreach ($courses as $i => $course) {
            Enrollment::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'enrolled_at' => now()->subDays(5 - $i),
                'progress' => $i === 0 ? 45 : 20,
                'completed_at' => null,
                'rating' => null,
            ]);
        }
    }

    // Compute stats and data
    $enrollments = Enrollment::with(['course' => function ($q) {
            $q->select('id','title','instructor_id','category','level','duration_hours','price');
        }, 'course.instructor:id,name'])
        ->where('user_id', $student->id)
        ->orderByDesc('updated_at')
        ->get();

    $stats = [
        'enrolled_courses' => $enrollments->count(),
        'completed_courses' => $enrollments->whereNotNull('completed_at')->count(),
        'total_learning_hours' => (int) $enrollments->sum(fn($e) => (int)($e->course->duration_hours ?? 0) * ($e->progress ?? 0) / 100),
        'average_rating' => (float) $enrollments->whereNotNull('rating')->avg('rating'),
    ];

    $recommended = Course::with(['instructor:id,name'])
        ->whereNotIn('id', $enrollments->pluck('course_id'))
        ->orderByDesc('created_at')
        ->limit(5)
        ->get(['id','title','short_description','level','price','instructor_id','duration_hours'])
        ->map(fn($c) => [
            'id' => $c->id,
            'title' => $c->title,
            'short_description' => $c->short_description,
            'level' => $c->level,
            'price' => (float)$c->price,
            'duration' => (int)($c->duration_hours ?? 0),
            'instructor' => ['name' => optional($c->instructor)->name],
            'enrollments_count' => $c->enrollments()->count(),
            'rating' => null,
        ]);

    $recentProgress = $enrollments->take(5)->map(fn($e) => [
        'id' => $e->id,
        'course' => ['id' => $e->course->id, 'title' => $e->course->title],
        'lesson' => ['title' => 'Lesson placeholder'],
        'progress' => (int)($e->progress ?? 0),
        'completed_at' => optional($e->completed_at)->toIso8601String(),
        'updated_at' => optional($e->updated_at)->toIso8601String(),
    ]);

    return response()->json([
        'stats' => $stats,
        'enrolled_courses' => $enrollments->map(fn($e) => [
            'id' => $e->id,
            'course' => [
                'id' => $e->course->id,
                'title' => $e->course->title,
                'instructor' => ['name' => optional($e->course->instructor)->name],
            ],
            'progress' => (int)($e->progress ?? 0),
        ]),
        'recommended_courses' => $recommended,
        'recent_progress' => $recentProgress,
    ]);
});

// GET /student/courses - list the student's enrollments
Route::get('/student/courses', function () {
    $student = ensureDemoStudent();
    $enrollments = Enrollment::with('course:id,title,category,level,price')
        ->where('user_id', $student->id)
        ->orderByDesc('updated_at')
        ->get();
    return response()->json($enrollments->map(fn($e) => [
        'id' => $e->id,
        'course' => [
            'id' => $e->course->id,
            'title' => $e->course->title,
            'category' => $e->course->category,
            'level' => $e->course->level,
            'price' => (float)$e->course->price,
        ],
        'progress' => (int)($e->progress ?? 0),
        'enrolled_at' => optional($e->enrolled_at)->toIso8601String(),
    ]));
});

Route::put('/admin/settings', function (Request $request) {
    $data = $request->validate([
        'platform_name' => 'required|string|max:255',
        'support_email' => 'required|email|max:255',
    ]);

    $settings = Setting::first();
    if (!$settings) {
        $settings = new Setting();
    }
    $settings->platform_name = $data['platform_name'];
    $settings->support_email = $data['support_email'];
    $settings->save();

    return response()->json([
        'platform_name' => $settings->platform_name,
        'support_email' => $settings->support_email,
        'updatedAt' => optional($settings->updated_at)->toIso8601String(),
    ]);
});

// Minimal auth endpoints for frontend integration (no DB/JWT)
// NOTE: For demo purposes only. Replace with real auth later.

Route::post('/login', function (Request $request) {
    // Force all errors to be logged and prevent silent failures
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    try {
        // Log the request details for debugging
        \Log::info('=== Login Request Started ===', [
            'origin' => $request->header('Origin'),
            'host' => $request->header('Host'),
            'user_agent' => $request->header('User-Agent'),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'raw_input' => $request->getContent(),
            'timestamp' => now()->toIso8601String()
        ]);
        
        // Test basic response first
        if ($request->has('test')) {
            return response()->json([
                'test' => 'basic response works',
                'timestamp' => now()->toIso8601String()
            ]);
        }
        
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        \Log::info('Validation passed', ['email' => $data['email']]);

        $users = [
            'admin@example.com' => ['id' => 1, 'name' => 'Admin User', 'role' => 'admin'],
            'instructor@example.com' => ['id' => 2, 'name' => 'Instructor User', 'role' => 'instructor'],
            'student@example.com' => ['id' => 3, 'name' => 'Student User', 'role' => 'student'],
        ];

        // Debug logging
        \Log::info('Login attempt', [
            'email' => $data['email'],
            'user_exists' => isset($users[$data['email']]),
            'password_correct' => $data['password'] === 'password'
        ]);

        if (!isset($users[$data['email']]) || $data['password'] !== 'password') {
            \Log::warning('Invalid login attempt', ['email' => $data['email']]);
            
            $errorResponse = response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'timestamp' => now()->toIso8601String()
            ], 401);
            
            if ($origin = $request->header('Origin')) {
                $errorResponse->header('Access-Control-Allow-Origin', $origin);
            }
            
            \Log::info('Sending error response', ['status' => 401]);
            return $errorResponse;
        }

        $user = $users[$data['email']];
        $token = 'demo_token_'.md5($data['email'].'|'.microtime(true));
        
        \Log::info('User authenticated, generating response', [
            'user' => $user,
            'token_length' => strlen($token)
        ]);

        // Try to use cache, but don't fail if it doesn't work
        try {
            Cache::put('auth:token:'.$token, $user, now()->addDays(7));
            \Log::info('Token cached successfully');
        } catch (\Exception $cacheError) {
            \Log::warning('Cache failed but continuing', [
                'error' => $cacheError->getMessage()
            ]);
        }

        \Log::info('Preparing JSON response');
        
        $responseData = [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'timestamp' => now()->toIso8601String(),
            'debug_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'cache_driver' => config('cache.default'),
                'response_generated_at' => now()->toIso8601String()
            ]
        ];
        
        \Log::info('Response data prepared', [
            'data_keys' => array_keys($responseData),
            'user_id' => $responseData['user']['id'],
            'token_prefix' => substr($token, 0, 10) . '...',
        ]);
        
        $response = response()->json($responseData);
        
        // Add CORS headers explicitly
        $origin = $request->header('Origin');
        if ($origin) {
            $response->header('Access-Control-Allow-Origin', $origin);
            \Log::info('Added CORS origin header', ['origin' => $origin]);
        }
        $response->header('Access-Control-Allow-Credentials', 'true');
        
        \Log::info('=== Login Response Ready ===', [
            'status' => 200,
            'has_content' => true,
            'content_type' => 'application/json',
            'timestamp' => now()->toIso8601String()
        ]);
        
        return $response;
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('=== Login Validation Error ===', [
            'errors' => $e->errors(),
            'input' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $validationResponse = response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors(),
            'timestamp' => now()->toIso8601String()
        ], 422);
        
        if ($origin = $request->header('Origin')) {
            $validationResponse->header('Access-Control-Allow-Origin', $origin);
        }
        
        return $validationResponse;
        
    } catch (\Exception $e) {
        \Log::error('=== Login Critical Error ===', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'class' => get_class($e)
        ]);
        
        $errorResponse = response()->json([
            'success' => false,
            'message' => 'Authentication system error',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal error',
            'debug_info' => config('app.debug') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e)
            ] : null,
            'timestamp' => now()->toIso8601String()
        ], 500);
        
        if ($origin = $request->header('Origin')) {
            $errorResponse->header('Access-Control-Allow-Origin', $origin);
        }
        
        return $errorResponse;
    }
});

Route::post('/register', function (Request $request) {
    $data = $request->validate([
        'name' => 'required|string',
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    // Demo: fake user creation, always student role
    $user = [
        'id' => random_int(100, 999),
        'name' => $data['name'],
        'email' => $data['email'],
        'role' => 'student',
    ];
    $token = 'demo_token_'.md5($data['email'].'|'.microtime(true));

    // Persist mapping token -> user for demo auth
    Cache::put('auth:token:'.$token, $user, now()->addDays(7));

    return response()->json([
        'message' => 'User registered successfully',
        'user' => $user,
        'token' => $token,
    ], 201);
});

Route::get('/me', function (Request $request) {
    // Demo auth: look up user by token stored at login/register
    $auth = $request->header('Authorization');
    if (!$auth || !str_starts_with($auth, 'Bearer ')) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }
    $token = trim(substr($auth, 7));
    $user = Cache::get('auth:token:'.$token);
    if (!$user) {
        return response()->json(['message' => 'Session expired'], 401);
    }
    return response()->json(['user' => $user]);
});

Route::post('/refresh', function () {
    $token = 'demo_token_'.md5('refresh|'.microtime(true));
    return response()->json(['token' => $token]);
});

// ---------------------------------------------------------------------------
// Admin endpoints (MySQL-backed) for dashboard and Users/Courses CRUD
// ---------------------------------------------------------------------------

Route::get('/dashboard/admin', function () {
    // Aggregate stats
    $totalUsers = User::count();
    $totalStudents = User::where('role', User::ROLE_STUDENT)->count();
    $totalInstructors = User::where('role', User::ROLE_INSTRUCTOR)->count();
    $totalCourses = Course::count();
    $publishedCourses = Course::where('status', Course::STATUS_PUBLISHED)->count();
    $totalEnrollments = Enrollment::count();
    $completedEnrollments = Enrollment::whereNotNull('completed_at')->count();
    // Approximate revenue: sum of course price per enrollment
    $totalRevenue = (float) Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
        ->sum('courses.price');

    // Simple monthly enrollment trend for last 6 months
    $enrollmentTrends = collect(range(0,5))->map(function ($i) {
        $monthStart = now()->startOfMonth()->subMonths($i);
        $monthEnd = $monthStart->copy()->endOfMonth();
        return [
            'month' => $monthStart->format('M'),
            'enrollments' => Enrollment::whereBetween('enrolled_at', [$monthStart, $monthEnd])->count(),
        ];
    })->reverse()->values();

    // Top courses by enrollments
    $topCourses = Course::withCount('enrollments')
        ->orderByDesc('enrollments_count')
        ->limit(3)
        ->get(['id','title'])
        ->map(fn($c) => [
            'id' => $c->id,
            'title' => $c->title,
            'enrollments_count' => $c->enrollments_count,
        ]);

    // Recent enrollments
    $recentEnrollments = Enrollment::with(['course:id,title', 'student:id,name,email'])
        ->orderByDesc('enrolled_at')
        ->limit(5)
        ->get()
        ->map(fn($e) => [
            'id' => $e->id,
            'student' => ['name' => optional($e->student)->name, 'email' => optional($e->student)->email],
            'course' => ['title' => optional($e->course)->title],
            // Use a widely supported Carbon method
            'enrolled_at' => optional($e->enrolled_at)->toIso8601String(),
        ]);

    return response()->json([
        'stats' => [
            'total_users' => $totalUsers,
            'total_students' => $totalStudents,
            'total_instructors' => $totalInstructors,
            'total_courses' => $totalCourses,
            'published_courses' => $publishedCourses,
            'total_enrollments' => $totalEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'total_revenue' => $totalRevenue,
        ],
        'enrollment_trends' => $enrollmentTrends,
        'top_courses' => $topCourses,
        'recent_enrollments' => $recentEnrollments,
    ]);
});

// Users CRUD (DB)
Route::get('/admin/users', function (Request $request) {
    $users = User::orderByDesc('created_at')->get();
    return response()->json($users->map(function ($u) {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'isActive' => (bool)$u->is_active,
            'createdAt' => optional($u->created_at)->toDateString(),
        ];
    }));
});
Route::post('/admin/users', function (Request $request) {
    $data = $request->validate([
        'name' => 'required|string|min:2|max:255',
        'email' => 'required|email|unique:users,email',
        'role' => 'required|in:admin,instructor,student',
    ]);

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'role' => $data['role'],
        'password' => Hash::make('password'), // default demo password
        'is_active' => true,
    ]);

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'isActive' => (bool)$user->is_active,
        'createdAt' => optional($user->created_at)->toDateString(),
    ], 201);
});
Route::put('/admin/users/{id}', function (Request $request, $id) {
    $data = $request->validate([
        'name' => 'sometimes|string|min:2|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $id,
        'role' => 'sometimes|in:admin,instructor,student',
        'isActive' => 'sometimes|boolean',
    ]);
    $user = User::findOrFail($id);
    $user->update([
        'name' => $data['name'] ?? $user->name,
        'email' => $data['email'] ?? $user->email,
        'role' => $data['role'] ?? $user->role,
        'is_active' => array_key_exists('isActive', $data) ? (bool)$data['isActive'] : $user->is_active,
    ]);
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'isActive' => (bool)$user->is_active,
        'createdAt' => optional($user->created_at)->toDateString(),
    ]);
});
Route::delete('/admin/users/{id}', function ($id) {
    $user = User::findOrFail($id);
    $user->delete();
    return response()->json(['deleted' => (int)$id]);
});

// Courses CRUD (DB)
Route::get('/admin/courses', function () {
    $courses = Course::with(['instructor', 'enrollments'])->orderByDesc('created_at')->get();
    return response()->json($courses->map(function ($c) {
        return [
            'id' => $c->id,
            'title' => $c->title,
            'instructor' => optional($c->instructor)->name ?? 'Unknown',
            'status' => $c->status,
            'enrollments' => $c->enrollments()->count(),
            'price' => (float)$c->price,
            'createdAt' => optional($c->created_at)->toDateString(),
            'category' => $c->category,
        ];
    }));
});
Route::post('/admin/courses', function (Request $request) {
    $data = $request->validate([
        'title' => 'required|string|min:3|max:255',
        'instructor' => 'required|string|min:2|max:255',
        'status' => 'required|in:published,draft,archived',
        'price' => 'required|numeric|min:0',
        'category' => 'required|string|min:2|max:100',
        'learning_material' => 'nullable|string',
        'youtube_url' => 'nullable|url',
    ]);

    // Find or create instructor by name (demo convenience)
    $instructor = User::firstOrCreate(
        ['name' => $data['instructor'], 'role' => User::ROLE_INSTRUCTOR],
        [
            'email' => strtolower(str_replace(' ', '.', $data['instructor'])) . '+' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]
    );

    $course = Course::create([
        'title' => $data['title'],
        'description' => $request->input('description', $data['title']), // minimal required
        'short_description' => $request->input('short_description'),
        'instructor_id' => $instructor->id,
        'category' => $data['category'],
        'level' => $request->input('level', 'beginner'),
        'duration_hours' => $request->input('duration_hours', 0),
        'price' => $data['price'],
        'status' => $data['status'],
        'max_students' => $request->input('max_students'),
        'requirements' => $request->input('requirements'),
        'learning_outcomes' => $request->input('learning_outcomes'),
        'learning_material' => $data['learning_material'] ?? null,
        'youtube_url' => $data['youtube_url'] ?? null,
    ]);

    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'instructor' => $instructor->name,
        'status' => $course->status,
        'enrollments' => 0,
        'price' => (float)$course->price,
        'createdAt' => optional($course->created_at)->toDateString(),
        'category' => $course->category,
    ], 201);
});
Route::put('/admin/courses/{id}', function (Request $request, $id) {
    $data = $request->validate([
        'title' => 'sometimes|string|min:3|max:255',
        'instructor' => 'sometimes|string|min:2|max:255',
        'status' => 'sometimes|in:published,draft,archived',
        'price' => 'sometimes|numeric|min:0',
        'category' => 'sometimes|string|min:2|max:100',
        'learning_material' => 'nullable|string',
        'youtube_url' => 'nullable|url',
    ]);
    $course = Course::findOrFail($id);

    if (isset($data['instructor'])) {
        $instructor = User::firstOrCreate(
            ['name' => $data['instructor'], 'role' => User::ROLE_INSTRUCTOR],
            [
                'email' => strtolower(str_replace(' ', '.', $data['instructor'])) . '+' . uniqid() . '@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $course->instructor_id = $instructor->id;
    }

    $course->title = $data['title'] ?? $course->title;
    $course->status = $data['status'] ?? $course->status;
    $course->price = $data['price'] ?? $course->price;
    $course->category = $data['category'] ?? $course->category;
    if (array_key_exists('learning_material', $data)) { $course->learning_material = $data['learning_material']; }
    if (array_key_exists('youtube_url', $data)) { $course->youtube_url = $data['youtube_url']; }
    $course->save();

    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'instructor' => optional($course->instructor)->name ?? 'Unknown',
        'status' => $course->status,
        'enrollments' => $course->enrollments()->count(),
        'price' => (float)$course->price,
        'createdAt' => optional($course->created_at)->toDateString(),
        'category' => $course->category,
    ]);
});
Route::delete('/admin/courses/{id}', function ($id) {
    $course = Course::findOrFail($id);
    $course->delete();
    return response()->json(['deleted' => (int)$id]);
});

// Admin: Show a single course (details for preview)
Route::get('/admin/courses/{id}', function ($id) {
    $course = Course::with(['instructor', 'enrollments'])->findOrFail($id);
    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'description' => $course->description,
        'short_description' => $course->short_description,
        'instructor' => optional($course->instructor)->name ?? 'Unknown',
        'status' => $course->status,
        'price' => (float)$course->price,
        'category' => $course->category,
        'level' => $course->level,
        'duration_hours' => (int)($course->duration_hours ?? 0),
        'createdAt' => optional($course->created_at)->toIso8601String(),
        'updatedAt' => optional($course->updated_at)->toIso8601String(),
        'enrollments' => $course->enrollments()->count(),
        'learning_material' => $course->learning_material,
        'youtube_url' => $course->youtube_url,
    ]);
});

// ---------------------------------------------------------------------------
// Instructor endpoints (demo-friendly)
// ---------------------------------------------------------------------------

// GET /dashboard/instructor - aggregated stats and activity for demo instructor
Route::get('/dashboard/instructor', function () {
    $instructor = ensureDemoInstructor();

    // Seed a couple of demo courses if none exist
    if (Course::where('instructor_id', $instructor->id)->count() === 0) {
        $c1 = Course::create([
            'title' => 'React for Beginners',
            'description' => 'Learn React fundamentals',
            'short_description' => 'React basics',
            'instructor_id' => $instructor->id,
            'category' => 'Web Development',
            'level' => 'beginner',
            'duration_hours' => 10,
            'price' => 49.99,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $c2 = Course::create([
            'title' => 'Advanced Laravel',
            'description' => 'Deep dive into Laravel',
            'short_description' => 'Laravel advanced',
            'instructor_id' => $instructor->id,
            'category' => 'Backend',
            'level' => 'advanced',
            'duration_hours' => 12,
            'price' => 59.99,
            'status' => Course::STATUS_DRAFT,
        ]);

        $student = ensureDemoStudent();
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $c1->id,
            'enrolled_at' => now()->subDays(3),
            'progress' => 35,
        ]);
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $c2->id,
            'enrolled_at' => now()->subDays(1),
            'progress' => 10,
        ]);
    }

    // Stats for this instructor
    $courses = Course::where('instructor_id', $instructor->id)->get(['id','title','status']);
    $courseIds = $courses->pluck('id');
    $publishedCount = Course::where('instructor_id', $instructor->id)
        ->where('status', Course::STATUS_PUBLISHED)->count();

    $totalStudents = Enrollment::whereIn('course_id', $courseIds)->count();
    $totalRevenue = (float) Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
        ->whereIn('course_id', $courseIds)
        ->sum('courses.price');
    $avgRating = (float) Enrollment::whereIn('course_id', $courseIds)->whereNotNull('rating')->avg('rating');

    $coursePerformance = Course::withCount([
            'enrollments',
            'enrollments as completed_enrollments_count' => function ($q) { $q->whereNotNull('completed_at'); }
        ])
        ->where('instructor_id', $instructor->id)
        ->orderByDesc('enrollments_count')
        ->limit(10)
        ->get(['id','title','status']);

    $recentActivity = Enrollment::with(['course:id,title', 'student:id,name,email'])
        ->whereIn('course_id', $courseIds)
        ->orderByDesc('updated_at')
        ->limit(10)
        ->get()
        ->map(fn($e) => [
            'id' => $e->id,
            'student' => ['name' => optional($e->student)->name, 'email' => optional($e->student)->email],
            'course' => ['id' => $e->course_id, 'title' => optional($e->course)->title],
            'progress' => (int)($e->progress ?? 0),
            'completed_at' => optional($e->completed_at)->toIso8601String(),
            'updated_at' => optional($e->updated_at)->toIso8601String(),
        ]);

    return response()->json([
        'stats' => [
            'total_courses' => $courses->count(),
            'published_courses' => $publishedCount,
            'total_students' => $totalStudents,
            'average_course_rating' => $avgRating,
            'total_revenue' => $totalRevenue,
        ],
        'course_performance' => $coursePerformance,
        'recent_activity' => $recentActivity,
    ]);
});

// GET /instructor/courses - list instructor's own courses
Route::get('/instructor/courses', function () {
    $instructor = ensureDemoInstructor();
    $courses = Course::where('instructor_id', $instructor->id)
        ->orderByDesc('created_at')
        ->get(['id','title','status','category','price']);
    return response()->json($courses->map(fn($c) => [
        'id' => $c->id,
        'title' => $c->title,
        'status' => $c->status,
        'category' => $c->category,
        'price' => (float)$c->price,
    ]));
});

// GET /instructor/students - list students across instructor's courses
Route::get('/instructor/students', function () {
    $instructor = ensureDemoInstructor();
    $courseIds = Course::where('instructor_id', $instructor->id)->pluck('id');
    $students = Enrollment::with('student:id,name,email')
        ->whereIn('course_id', $courseIds)
        ->get()
        ->groupBy('user_id')
        ->map(function ($group) {
            $e = $group->first();
            return [
                'id' => $e->user_id,
                'name' => optional($e->student)->name,
                'email' => optional($e->student)->email,
                'courses' => $group->pluck('course_id')->unique()->values(),
                'enrollments' => $group->count(),
            ];
        })
        ->values();
    return response()->json($students);
});

// POST /instructor/courses - create a course for the demo instructor
Route::post('/instructor/courses', function (Request $request) {
    $instructor = ensureDemoInstructor();
    $data = $request->validate([
        'title' => 'required|string|min:3|max:255',
        'status' => 'required|in:published,draft,archived',
        'price' => 'required|numeric|min:0',
        'category' => 'required|string|min:2|max:100',
        'learning_material' => 'nullable|string',
        'youtube_url' => 'nullable|url',
    ]);
    $course = Course::create([
        'title' => $data['title'],
        'description' => $request->input('description', $data['title']),
        'short_description' => $request->input('short_description'),
        'instructor_id' => $instructor->id,
        'category' => $data['category'],
        'level' => $request->input('level', 'beginner'),
        'duration_hours' => $request->input('duration_hours', 0),
        'price' => $data['price'],
        'status' => $data['status'],
        'max_students' => $request->input('max_students'),
        'requirements' => $request->input('requirements'),
        'learning_outcomes' => $request->input('learning_outcomes'),
        'learning_material' => $data['learning_material'] ?? null,
        'youtube_url' => $data['youtube_url'] ?? null,
    ]);
    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'status' => $course->status,
        'category' => $course->category,
        'price' => (float)$course->price,
    ], 201);
});

// GET /instructor/courses/{id} - view a single course owned by instructor
Route::get('/instructor/courses/{id}', function ($id) {
    $instructor = ensureDemoInstructor();
    $course = Course::where('instructor_id', $instructor->id)->findOrFail($id);
    $course->loadCount('enrollments');
    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'description' => $course->description,
        'short_description' => $course->short_description,
        'status' => $course->status,
        'category' => $course->category,
        'level' => $course->level,
        'duration_hours' => (int)($course->duration_hours ?? 0),
        'price' => (float)$course->price,
        'createdAt' => optional($course->created_at)->toIso8601String(),
        'updatedAt' => optional($course->updated_at)->toIso8601String(),
        'enrollments' => $course->enrollments_count,
        'learning_material' => $course->learning_material,
        'youtube_url' => $course->youtube_url,
    ]);
});

// PUT /instructor/courses/{id} - update a course owned by instructor
Route::put('/instructor/courses/{id}', function (Request $request, $id) {
    $instructor = ensureDemoInstructor();
    $course = Course::where('instructor_id', $instructor->id)->findOrFail($id);
    $data = $request->validate([
        'title' => 'sometimes|string|min:3|max:255',
        'status' => 'sometimes|in:published,draft,archived',
        'price' => 'sometimes|numeric|min:0',
        'category' => 'sometimes|string|min:2|max:100',
        'learning_material' => 'nullable|string',
        'youtube_url' => 'nullable|url',
    ]);
    $course->title = $data['title'] ?? $course->title;
    $course->status = $data['status'] ?? $course->status;
    $course->price = $data['price'] ?? $course->price;
    $course->category = $data['category'] ?? $course->category;
    if (array_key_exists('learning_material', $data)) { $course->learning_material = $data['learning_material']; }
    if (array_key_exists('youtube_url', $data)) { $course->youtube_url = $data['youtube_url']; }
    $course->save();
    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'status' => $course->status,
        'category' => $course->category,
        'price' => (float)$course->price,
    ]);
});

// Public course view for students
Route::get('/courses/{id}', function ($id) {
    $course = Course::with('instructor')->findOrFail($id);
    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'description' => $course->description,
        'short_description' => $course->short_description,
        'instructor' => optional($course->instructor)->name,
        'category' => $course->category,
        'level' => $course->level,
        'duration_hours' => (int)($course->duration_hours ?? 0),
        'price' => (float)$course->price,
        'learning_material' => $course->learning_material,
        'youtube_url' => $course->youtube_url,
    ]);
});

// ---------------------------------------------------------------------------
// Quizzes (DB-backed, minimal demo endpoints)
// ---------------------------------------------------------------------------

// Helper: ensure a quiz exists for a course
if (!function_exists('ensureCourseQuiz')) {
    function ensureCourseQuiz($courseId): Quiz {
        $quiz = Quiz::where('course_id', $courseId)->first();
        if (!$quiz) {
            $quiz = Quiz::create([
                'course_id' => $courseId,
                'title' => 'Course Quiz',
                'description' => null,
                'time_limit' => 0,
                'max_attempts' => 0,
                'passing_score' => 60,
                'is_active' => true,
                'order' => 1,
            ]);
        }
        return $quiz;
    }
}

// GET /courses/{courseId}/quiz - returns quiz questions in simplified shape
Route::get('/courses/{courseId}/quiz', function ($courseId) {
    $quiz = ensureCourseQuiz($courseId);
    $questions = $quiz->questions()->get()->map(function ($q) {
        // Map DB fields to frontend shape
        $answerIndex = null;
        $correct = $q->correct_answer[0] ?? null;
        if (is_numeric($correct)) {
            $answerIndex = (int)$correct;
        } elseif (is_string($correct)) {
            $idx = array_search($correct, $q->options ?? [], true);
            $answerIndex = $idx === false ? null : $idx;
        }
        return [
            'id' => (string)$q->id,
            'text' => $q->question,
            'options' => array_values($q->options ?? []),
            'answerIndex' => $answerIndex,
        ];
    });
    return response()->json(['questions' => $questions]);
});

// POST /courses/{courseId}/quiz/questions - upsert a question by id
Route::post('/courses/{courseId}/quiz/questions', function (Request $request, $courseId) {
    $quiz = ensureCourseQuiz($courseId);
    $data = $request->validate([
        'id' => 'nullable|string',
        'text' => 'required|string|min:3',
        'options' => 'required|array|size:4',
        'options.*' => 'required|string',
        'answerIndex' => 'required|integer|min:0|max:3',
    ]);

    $question = null;
    if (!empty($data['id'])) {
        $question = QuizQuestion::where('quiz_id', $quiz->id)->where('id', $data['id'])->first();
    }
    if (!$question) {
        $question = new QuizQuestion();
        $question->quiz_id = $quiz->id;
        $question->order = ($quiz->questions()->max('order') ?? 0) + 1;
    }
    $question->question = $data['text'];
    $question->options = array_values($data['options']);
    $question->correct_answer = [ (int)$data['answerIndex'] ];
    $question->type = QuizQuestion::TYPE_MULTIPLE_CHOICE;
    $question->points = 1;
    $question->save();

    return response()->json([
        'id' => (string)$question->id,
        'text' => $question->question,
        'options' => $question->options,
        'answerIndex' => (int)($question->correct_answer[0] ?? 0),
    ], 201);
});

// DELETE /courses/{courseId}/quiz/questions/{id}
Route::delete('/courses/{courseId}/quiz/questions/{id}', function ($courseId, $id) {
    $quiz = ensureCourseQuiz($courseId);
    $question = QuizQuestion::where('quiz_id', $quiz->id)->where('id', $id)->firstOrFail();
    $question->delete();
    return response()->json(['deleted' => (string)$id]);
});

// POST /courses/{courseId}/quiz/attempts - submit answers and compute score
Route::post('/courses/{courseId}/quiz/attempts', function (Request $request, $courseId) {
    $student = ensureDemoStudent();
    $quiz = ensureCourseQuiz($courseId);
    $data = $request->validate([
        'answers' => 'required|array', // { [questionId]: index }
    ]);

    // Check attempts policy
    if (!$quiz->canUserTake($student->id)) {
        return response()->json(['message' => 'Max attempts reached'], 422);
    }

    $attempt = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'started_at' => now(),
    ]);

    $totalQuestions = $quiz->questions()->count();
    foreach ($quiz->questions as $q) {
        $providedIndex = $data['answers'][$q->id] ?? null;
        $isCorrect = $q->isCorrectAnswer(is_null($providedIndex) ? null : (int)$providedIndex);
        QuizAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'quiz_question_id' => $q->id,
            'answer' => [ is_null($providedIndex) ? null : (int)$providedIndex ],
            'is_correct' => (bool)$isCorrect,
        ]);
    }

    $attempt->total_questions = $totalQuestions;
    $attempt->completed_at = now();
    $attempt->time_taken = $attempt->completed_at->diffInSeconds($attempt->started_at);
    $attempt->save();
    $score = $attempt->calculateScore();

    return response()->json([
        'answers' => $data['answers'],
        'score' => $score,
        'total' => $totalQuestions,
        'submittedAt' => optional($attempt->completed_at)->toIso8601String(),
        'passed' => (bool)$attempt->passed,
    ], 201);
});

// GET /courses/{courseId}/quiz/attempt - fetch latest attempt for demo student
Route::get('/courses/{courseId}/quiz/attempt', function ($courseId) {
    $student = ensureDemoStudent();
    $quiz = ensureCourseQuiz($courseId);
    $attempt = QuizAttempt::where('user_id', $student->id)
        ->where('quiz_id', $quiz->id)
        ->orderByDesc('completed_at')
        ->first();
    if (!$attempt) return response()->json(null);

    $answers = [];
    foreach ($attempt->answers as $ans) {
        $answers[$ans->quiz_question_id] = $ans->answer[0] ?? null;
    }
    return response()->json([
        'answers' => $answers,
        'score' => (int)($attempt->score ?? 0),
        'total' => (int)($attempt->total_questions ?? 0),
        'submittedAt' => optional($attempt->completed_at)->toIso8601String(),
        'passed' => (bool)$attempt->passed,
    ]);
});
