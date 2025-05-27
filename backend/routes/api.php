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

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toIso8601String()
    ]);
});

// Helper functions for demo user management
function ensureDemoStudent() {
    return User::firstOrCreate(
        ['email' => 'student@example.com'],
        [
            'name' => 'Demo Student',
            'role' => 'student',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]
    );
}

function ensureDemoInstructor() {
    return User::firstOrCreate(
        ['email' => 'instructor@example.com'],
        [
            'name' => 'Demo Instructor',
            'role' => 'instructor',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]
    );
}

// Authentication Routes
Route::post('/login', function (Request $request) {
    $data = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $users = [
        'admin@example.com' => ['id' => 1, 'name' => 'Admin User', 'role' => 'admin'],
        'instructor@example.com' => ['id' => 2, 'name' => 'Instructor User', 'role' => 'instructor'],
        'student@example.com' => ['id' => 3, 'name' => 'Student User', 'role' => 'student'],
    ];

    if (!isset($users[$data['email']]) || $data['password'] !== 'password') {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    $user = $users[$data['email']];
    $token = 'demo_token_'.md5($data['email'].'|'.microtime(true));
    
    Cache::put('auth:token:'.$token, $user, now()->addDays(7));

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user,
        'token' => $token
    ]);
});

Route::post('/register', function (Request $request) {
    $data = $request->validate([
        'name' => 'required|string',
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    $user = [
        'id' => random_int(100, 999),
        'name' => $data['name'],
        'email' => $data['email'],
        'role' => 'student',
    ];
    $token = 'demo_token_'.md5($data['email'].'|'.microtime(true));

    Cache::put('auth:token:'.$token, $user, now()->addDays(7));

    return response()->json([
        'message' => 'User registered successfully',
        'user' => $user,
        'token' => $token,
    ], 201);
});

Route::get('/me', function (Request $request) {
    $token = str_replace('Bearer ', '', $request->header('Authorization', ''));
    $user = Cache::get('auth:token:'.$token);
    
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    return response()->json(['user' => $user]);
});

Route::post('/refresh', function () {
    return response()->json(['message' => 'Token refresh not implemented in demo']);
});

// User Management Routes (Admin)
Route::get('/admin/users', function (Request $request) {
    $users = User::orderBy('created_at', 'desc')->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);
    return response()->json($users);
});

Route::post('/admin/users', function (Request $request) {
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:6',
        'role' => 'required|in:admin,instructor,student',
    ]);

    $data['password'] = Hash::make($data['password']);
    $data['is_active'] = true;
    
    $user = User::create($data);
    return response()->json($user, 201);
});

Route::put('/admin/users/{id}', function (Request $request, $id) {
    $user = User::findOrFail($id);
    
    $data = $request->validate([
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,'.$id,
        'role' => 'sometimes|in:admin,instructor,student',
        'is_active' => 'sometimes|boolean',
    ]);

    if ($request->has('password')) {
        $data['password'] = Hash::make($request->password);
    }

    $user->update($data);
    return response()->json($user);
});

Route::delete('/admin/users/{id}', function ($id) {
    User::findOrFail($id)->delete();
    return response()->json(['message' => 'User deleted successfully']);
});

// Course Management Routes
Route::get('/admin/courses', function () {
    $courses = Course::with('instructor:id,name')->orderBy('created_at', 'desc')->get();
    return response()->json($courses->map(function ($course) {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'instructor' => ['name' => optional($course->instructor)->name],
            'category' => $course->category,
            'level' => $course->level,
            'duration_hours' => $course->duration_hours,
            'price' => (float) $course->price,
            'status' => $course->status,
            'created_at' => $course->created_at->toIso8601String(),
        ];
    }));
});

Route::post('/admin/courses', function (Request $request) {
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'short_description' => 'nullable|string|max:500',
        'instructor_id' => 'required|exists:users,id',
        'category' => 'required|string|max:100',
        'level' => 'required|in:beginner,intermediate,advanced',
        'duration_hours' => 'nullable|integer|min:1',
        'price' => 'required|numeric|min:0',
        'status' => 'required|in:draft,published,archived',
    ]);

    $course = Course::create($data);
    return response()->json($course, 201);
});

Route::get('/admin/courses/{id}', function ($id) {
    $course = Course::with('instructor:id,name')->findOrFail($id);
    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'description' => $course->description,
        'short_description' => $course->short_description,
        'instructor' => ['id' => $course->instructor->id, 'name' => $course->instructor->name],
        'category' => $course->category,
        'level' => $course->level,
        'duration_hours' => $course->duration_hours,
        'price' => (float) $course->price,
        'status' => $course->status,
        'created_at' => $course->created_at->toIso8601String(),
        'enrollments_count' => $course->enrollments()->count(),
    ]);
});

Route::put('/admin/courses/{id}', function (Request $request, $id) {
    $course = Course::findOrFail($id);
    
    $data = $request->validate([
        'title' => 'sometimes|string|max:255',
        'description' => 'sometimes|string',
        'short_description' => 'sometimes|string|max:500',
        'instructor_id' => 'sometimes|exists:users,id',
        'category' => 'sometimes|string|max:100',
        'level' => 'sometimes|in:beginner,intermediate,advanced',
        'duration_hours' => 'sometimes|integer|min:1',
        'price' => 'sometimes|numeric|min:0',
        'status' => 'sometimes|in:draft,published,archived',
    ]);

    $course->update($data);
    return response()->json($course);
});

Route::delete('/admin/courses/{id}', function ($id) {
    Course::findOrFail($id)->delete();
    return response()->json(['message' => 'Course deleted successfully']);
});

// Instructor Routes
Route::get('/instructor/courses', function () {
    $instructor = ensureDemoInstructor();
    $courses = Course::where('instructor_id', $instructor->id)->orderBy('created_at', 'desc')->get();
    return response()->json($courses);
});

Route::post('/instructor/courses', function (Request $request) {
    $instructor = ensureDemoInstructor();
    
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'short_description' => 'nullable|string|max:500',
        'category' => 'required|string|max:100',
        'level' => 'required|in:beginner,intermediate,advanced',
        'duration_hours' => 'nullable|integer|min:1',
        'price' => 'required|numeric|min:0',
        'status' => 'required|in:draft,published',
    ]);

    $data['instructor_id'] = $instructor->id;
    $course = Course::create($data);
    return response()->json($course, 201);
});

Route::get('/instructor/courses/{id}', function ($id) {
    $instructor = ensureDemoInstructor();
    $course = Course::where('instructor_id', $instructor->id)->findOrFail($id);
    return response()->json($course);
});

Route::put('/instructor/courses/{id}', function (Request $request, $id) {
    $instructor = ensureDemoInstructor();
    $course = Course::where('instructor_id', $instructor->id)->findOrFail($id);
    
    $data = $request->validate([
        'title' => 'sometimes|string|max:255',
        'description' => 'sometimes|string',
        'short_description' => 'sometimes|string|max:500',
        'category' => 'sometimes|string|max:100',
        'level' => 'sometimes|in:beginner,intermediate,advanced',
        'duration_hours' => 'sometimes|integer|min:1',
        'price' => 'sometimes|numeric|min:0',
        'status' => 'sometimes|in:draft,published',
    ]);

    $course->update($data);
    return response()->json($course);
});

Route::get('/instructor/students', function () {
    $instructor = ensureDemoInstructor();
    $enrollments = Enrollment::with(['user:id,name,email', 'course:id,title'])
        ->whereHas('course', function($q) use ($instructor) {
            $q->where('instructor_id', $instructor->id);
        })
        ->orderBy('created_at', 'desc')
        ->get();
        
    return response()->json($enrollments->map(function ($enrollment) {
        return [
            'id' => $enrollment->id,
            'student' => $enrollment->user,
            'course' => $enrollment->course,
            'progress' => (int) ($enrollment->progress ?? 0),
            'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
        ];
    }));
});

// Student Routes
Route::get('/student/courses', function () {
    $student = ensureDemoStudent();
    $enrollments = Enrollment::with('course:id,title,category,level,price')
        ->where('user_id', $student->id)
        ->orderByDesc('updated_at')
        ->get();
        
    return response()->json($enrollments->map(function ($enrollment) {
        return [
            'id' => $enrollment->id,
            'course' => [
                'id' => $enrollment->course->id,
                'title' => $enrollment->course->title,
                'category' => $enrollment->course->category,
                'level' => $enrollment->course->level,
                'price' => (float) $enrollment->course->price,
            ],
            'progress' => (int) ($enrollment->progress ?? 0),
            'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
        ];
    }));
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

// Public Course Routes
Route::get('/courses/{id}', function ($id) {
    $course = Course::with('instructor:id,name')->findOrFail($id);
    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'description' => $course->description,
        'short_description' => $course->short_description,
        'instructor' => ['name' => optional($course->instructor)->name],
        'category' => $course->category,
        'level' => $course->level,
        'duration_hours' => $course->duration_hours,
        'price' => (float) $course->price,
        'enrollments_count' => $course->enrollments()->count(),
        'created_at' => $course->created_at->toIso8601String(),
    ]);
});

Route::get('/courses/{courseId}/quiz', function ($courseId) {
    $course = Course::findOrFail($courseId);
    $quiz = Quiz::where('course_id', $course->id)->where('is_active', true)->first();
    
    if (!$quiz) {
        return response()->json(['message' => 'No active quiz found for this course'], 404);
    }
    
    $questions = QuizQuestion::where('quiz_id', $quiz->id)->get(['id', 'question', 'options']);
    
    return response()->json([
        'id' => $quiz->id,
        'title' => $quiz->title,
        'duration_minutes' => $quiz->duration_minutes,
        'questions' => $questions,
    ]);
});

// Admin Settings
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

Route::put('/admin/settings', function (Request $request) {
    $data = $request->validate([
        'platform_name' => 'required|string|max:255',
        'support_email' => 'required|email|max:255',
    ]);

    $settings = Setting::first();
    if (!$settings) {
        $settings = Setting::create($data);
    } else {
        $settings->update($data);
    }

    return response()->json([
        'platform_name' => $settings->platform_name,
        'support_email' => $settings->support_email,
        'updatedAt' => $settings->updated_at->toIso8601String(),
    ]);
});