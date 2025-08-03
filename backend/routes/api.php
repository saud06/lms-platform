<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

// Helper functions are defined in app/helpers.php

// Authentication Routes
Route::post('/login', function (Request $request) {
    $data = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    // Check if user exists in database
    $dbUser = User::where('email', $data['email'])->first();
    
    if (!$dbUser || $data['password'] !== 'password') {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    $user = [
        'id' => $dbUser->id,
        'name' => $dbUser->name,
        'role' => $dbUser->role,
        'email' => $dbUser->email,
    ];
    
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
            'instructor' => optional($course->instructor)->name ?? 'No Instructor',
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
        'instructor' => optional($course->instructor)->name ?? 'No Instructor',
        'instructor_id' => optional($course->instructor)->id,
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

// Quiz Management Routes (Admin)
Route::get('/admin/quizzes', function () {
    $quizzes = Quiz::with(['course:id,title'])
                   ->orderBy('created_at', 'desc')
                   ->get();
    
    return response()->json($quizzes->map(function ($quiz) {
        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'course' => optional($quiz->course)->title ?? 'No Course',
            'course_id' => $quiz->course_id,
            'time_limit' => $quiz->time_limit,
            'max_attempts' => $quiz->max_attempts,
            'passing_score' => $quiz->passing_score,
            'is_active' => $quiz->is_active,
            'questions_count' => $quiz->questions()->count(),
            'created_at' => $quiz->created_at->toIso8601String(),
        ];
    }));
});

Route::get('/admin/quizzes/{id}', function ($id) {
    $quiz = Quiz::with(['course:id,title', 'questions'])->findOrFail($id);
    
    return response()->json([
        'id' => $quiz->id,
        'title' => $quiz->title,
        'description' => $quiz->description,
        'course' => optional($quiz->course)->title ?? 'No Course',
        'course_id' => $quiz->course_id,
        'time_limit' => $quiz->time_limit,
        'max_attempts' => $quiz->max_attempts,
        'passing_score' => $quiz->passing_score,
        'is_active' => $quiz->is_active,
        'questions' => $quiz->questions->map(function ($question) {
            return [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'options' => $question->options ?? [], // Ensure options is always an array
                'correct_answer' => $question->correct_answer,
                'points' => $question->points,
                'order' => $question->order,
            ];
        }),
        'created_at' => $quiz->created_at->toIso8601String(),
    ]);
});

Route::post('/admin/quizzes', function (Request $request) {
    $data = $request->validate([
        'course_id' => 'required|exists:courses,id',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'time_limit' => 'required|integer|min:1',
        'max_attempts' => 'required|integer|min:1',
        'passing_score' => 'required|integer|min:0|max:100',
        'is_active' => 'boolean',
        'questions' => 'array',
        'questions.*.question' => 'required|string',
        'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
        'questions.*.options' => 'array',
        'questions.*.correct_answer' => 'required',
        'questions.*.points' => 'integer|min:1',
    ]);

    $quiz = Quiz::create([
        'course_id' => $data['course_id'],
        'title' => $data['title'],
        'description' => $data['description'] ?? '',
        'time_limit' => $data['time_limit'],
        'max_attempts' => $data['max_attempts'],
        'passing_score' => $data['passing_score'],
        'is_active' => $data['is_active'] ?? true,
        'order' => Quiz::where('course_id', $data['course_id'])->count() + 1,
    ]);

    // Create questions
    if (isset($data['questions'])) {
        foreach ($data['questions'] as $index => $questionData) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'options' => $questionData['options'] ?? [],
                'correct_answer' => $questionData['correct_answer'],
                'points' => $questionData['points'] ?? 10,
                'order' => $index + 1,
            ]);
        }
    }

    return response()->json($quiz->load('questions'), 201);
});

Route::put('/admin/quizzes/{id}', function (Request $request, $id) {
    $quiz = Quiz::findOrFail($id);
    
    $data = $request->validate([
        'title' => 'sometimes|string|max:255',
        'description' => 'sometimes|string',
        'time_limit' => 'sometimes|integer|min:1',
        'max_attempts' => 'sometimes|integer|min:1',
        'passing_score' => 'sometimes|integer|min:0|max:100',
        'is_active' => 'sometimes|boolean',
        'questions' => 'sometimes|array',
        'questions.*.id' => 'sometimes|integer',
        'questions.*.question' => 'required|string',
        'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
        'questions.*.options' => 'array',
        'questions.*.correct_answer' => 'required',
        'questions.*.points' => 'integer|min:1',
    ]);

    // Update quiz basic info
    $quiz->update(array_filter([
        'title' => $data['title'] ?? null,
        'description' => $data['description'] ?? null,
        'time_limit' => $data['time_limit'] ?? null,
        'max_attempts' => $data['max_attempts'] ?? null,
        'passing_score' => $data['passing_score'] ?? null,
        'is_active' => $data['is_active'] ?? null,
    ]));

    // Update questions if provided
    if (isset($data['questions'])) {
        // Delete existing questions
        $quiz->questions()->delete();
        
        // Create new questions
        foreach ($data['questions'] as $index => $questionData) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'options' => $questionData['options'] ?? [],
                'correct_answer' => $questionData['correct_answer'],
                'points' => $questionData['points'] ?? 10,
                'order' => $index + 1,
            ]);
        }
    }

    return response()->json($quiz->load('questions'));
});

Route::delete('/admin/quizzes/{id}', function ($id) {
    $quiz = Quiz::findOrFail($id);
    $quiz->questions()->delete(); // Delete questions first
    $quiz->delete();
    return response()->json(['message' => 'Quiz deleted successfully']);
});

// Quiz Question Management
Route::post('/admin/quizzes/{quizId}/questions', function (Request $request, $quizId) {
    $quiz = Quiz::findOrFail($quizId);
    
    $data = $request->validate([
        'question' => 'required|string',
        'type' => 'required|in:multiple_choice,true_false,short_answer',
        'options' => 'array',
        'correct_answer' => 'required',
        'points' => 'integer|min:1',
        'explanation' => 'nullable|string',
    ]);

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question' => $data['question'],
        'type' => $data['type'],
        'options' => $data['options'] ?? [],
        'correct_answer' => $data['correct_answer'],
        'points' => $data['points'] ?? 10,
        'explanation' => $data['explanation'] ?? null,
        'order' => $quiz->questions()->count() + 1,
    ]);

    return response()->json($question, 201);
});

Route::put('/admin/quiz-questions/{id}', function (Request $request, $id) {
    $question = QuizQuestion::findOrFail($id);
    
    $data = $request->validate([
        'question' => 'sometimes|string',
        'type' => 'sometimes|in:multiple_choice,true_false,short_answer',
        'options' => 'sometimes|array',
        'correct_answer' => 'sometimes',
        'points' => 'sometimes|integer|min:1',
        'explanation' => 'sometimes|nullable|string',
    ]);

    $question->update(array_filter([
        'question' => $data['question'] ?? null,
        'type' => $data['type'] ?? null,
        'options' => isset($data['options']) ? $data['options'] : null,
        'correct_answer' => $data['correct_answer'] ?? null,
        'points' => $data['points'] ?? null,
        'explanation' => $data['explanation'] ?? null,
    ]));

    return response()->json($question);
});

Route::delete('/admin/quiz-questions/{id}', function ($id) {
    $question = QuizQuestion::findOrFail($id);
    $question->delete();
    return response()->json(['message' => 'Question deleted successfully']);
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
Route::get('/courses', function () {
    $courses = Course::with('instructor:id,name')
                    ->where('status', Course::STATUS_PUBLISHED)
                    ->orderBy('created_at', 'desc')
                    ->get();
    
    return response()->json($courses->map(function ($course) {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'short_description' => $course->short_description,
            'instructor' => optional($course->instructor)->name ?? 'No Instructor',
            'category' => $course->category,
            'level' => $course->level,
            'duration_hours' => $course->duration_hours,
            'price' => (float) $course->price,
            'status' => $course->status,
            'enrollments_count' => $course->enrollments()->count(),
            'created_at' => $course->created_at->toIso8601String(),
        ];
    }));
});

Route::get('/courses/{id}', function ($id) {
    $course = Course::with('instructor:id,name')->findOrFail($id);
    return response()->json([
        'id' => $course->id,
        'title' => $course->title,
        'description' => $course->description,
        'short_description' => $course->short_description,
        'instructor' => optional($course->instructor)->name ?? 'No Instructor',
        'category' => $course->category,
        'level' => $course->level,
        'duration_hours' => $course->duration_hours,
        'price' => (float) $course->price,
        'enrollments_count' => $course->enrollments()->count(),
        'created_at' => $course->created_at->toIso8601String(),
    ]);
});

// Public Quiz Routes
Route::get('/quizzes', function () {
    $quizzes = Quiz::with(['course:id,title'])
                   ->where('is_active', true)
                   ->whereHas('course', function($q) {
                       $q->where('status', Course::STATUS_PUBLISHED);
                   })
                   ->orderBy('created_at', 'desc')
                   ->get();
    
    return response()->json($quizzes->map(function ($quiz) {
        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'course' => optional($quiz->course)->title ?? 'No Course',
            'course_id' => $quiz->course_id,
            'time_limit' => $quiz->time_limit,
            'max_attempts' => $quiz->max_attempts,
            'passing_score' => $quiz->passing_score,
            'questions_count' => $quiz->questions()->count(),
        ];
    }));
});

Route::get('/courses/{courseId}/quiz', function ($courseId) {
    $course = Course::findOrFail($courseId);
    $quiz = Quiz::where('course_id', $course->id)->first();
    
    if (!$quiz) {
        // Create a default quiz for the course if it doesn't exist
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => $course->title . ' Quiz',
            'description' => 'Quiz for ' . $course->title,
            'time_limit' => 30, // 30 minutes default
            'max_attempts' => 3,
            'passing_score' => 70,
            'is_active' => true,
            'order' => 1,
        ]);
    }
    
    $questions = QuizQuestion::where('quiz_id', $quiz->id)->orderBy('order')->get();
    
    return response()->json([
        'id' => $quiz->id,
        'title' => $quiz->title,
        'description' => $quiz->description,
        'time_limit' => $quiz->time_limit,
        'max_attempts' => $quiz->max_attempts,
        'passing_score' => $quiz->passing_score,
        'questions' => $questions->map(function ($question) {
            return [
                'id' => $question->id,
                'text' => $question->question, // Map 'question' field to 'text' for frontend compatibility
                'options' => $question->options ?? [],
                'answerIndex' => is_numeric($question->correct_answer) ? (int)$question->correct_answer : 0,
            ];
        }),
    ]);
});

// Quiz Questions Management for Courses
Route::post('/courses/{courseId}/quiz/questions', function (Request $request, $courseId) {
    $course = Course::findOrFail($courseId);
    
    // Get or create quiz for this course
    $quiz = Quiz::where('course_id', $course->id)->first();
    if (!$quiz) {
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => $course->title . ' Quiz',
            'description' => 'Quiz for ' . $course->title,
            'time_limit' => 30,
            'max_attempts' => 3,
            'passing_score' => 70,
            'is_active' => true,
            'order' => 1,
        ]);
    }
    
    $data = $request->validate([
        'id' => 'nullable|string',
        'text' => 'required|string',
        'options' => 'required|array|size:4',
        'options.*' => 'required|string',
        'answerIndex' => 'required|integer|min:0|max:3',
    ]);
    
    // Check if this is an update (has existing ID) or create new
    if (!empty($data['id'])) {
        // Try to find existing question by the frontend ID
        $question = QuizQuestion::where('quiz_id', $quiz->id)
            ->where('id', $data['id'])
            ->first();
            
        if ($question) {
            // Update existing question
            $question->update([
                'question' => $data['text'],
                'options' => $data['options'],
                'correct_answer' => $data['answerIndex'],
                'type' => 'multiple_choice',
                'points' => 10,
            ]);
        } else {
            // Create new question with specific ID if it doesn't exist
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $data['text'],
                'type' => 'multiple_choice',
                'options' => $data['options'],
                'correct_answer' => $data['answerIndex'],
                'points' => 10,
                'order' => $quiz->questions()->count() + 1,
            ]);
        }
    } else {
        // Create new question
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => $data['text'],
            'type' => 'multiple_choice',
            'options' => $data['options'],
            'correct_answer' => $data['answerIndex'],
            'points' => 10,
            'order' => $quiz->questions()->count() + 1,
        ]);
    }
    
    return response()->json([
        'id' => $question->id,
        'text' => $question->question,
        'options' => $question->options,
        'answerIndex' => (int)$question->correct_answer,
    ], 201);
});

Route::delete('/courses/{courseId}/quiz/questions/{questionId}', function ($courseId, $questionId) {
    $course = Course::findOrFail($courseId);
    $quiz = Quiz::where('course_id', $course->id)->first();
    
    if (!$quiz) {
        return response()->json(['message' => 'Quiz not found'], 404);
    }
    
    $question = QuizQuestion::where('quiz_id', $quiz->id)->where('id', $questionId)->first();
    
    if (!$question) {
        return response()->json(['message' => 'Question not found'], 404);
    }
    
    $question->delete();
    
    return response()->json(['message' => 'Question deleted successfully']);
});

// Quiz Attempts for Courses
Route::get('/courses/{courseId}/quiz/attempt', function ($courseId) {
    $course = Course::findOrFail($courseId);
    $quiz = Quiz::where('course_id', $course->id)->first();
    
    if (!$quiz) {
        return response()->json(['message' => 'No quiz found for this course'], 404);
    }
    
    // For demo purposes, we'll check if there's a user context
    // In a real app, you'd get the user from authentication
    $token = request()->header('Authorization');
    if ($token) {
        $token = str_replace('Bearer ', '', $token);
        $user = \Illuminate\Support\Facades\Cache::get('auth:token:' . $token);
        
        if ($user) {
            // Find the user's latest attempt for this quiz
            $attempt = QuizAttempt::where('quiz_id', $quiz->id)
                                 ->where('user_id', $user['id'])
                                 ->orderBy('created_at', 'desc')
                                 ->first();
            
            if ($attempt) {
                return response()->json([
                    'id' => $attempt->id,
                    'quiz_id' => $attempt->quiz_id,
                    'started_at' => $attempt->started_at,
                    'completed_at' => $attempt->completed_at,
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'passed' => $attempt->passed,
                    'time_taken' => $attempt->time_taken,
                ]);
            }
        }
    }
    
    // No attempt found
    return response()->json(['message' => 'No attempt found'], 404);
});

Route::post('/courses/{courseId}/quiz/attempts', function (Request $request, $courseId) {
    $course = Course::findOrFail($courseId);
    $quiz = Quiz::where('course_id', $course->id)->first();
    
    if (!$quiz) {
        return response()->json(['message' => 'No quiz found for this course'], 404);
    }
    
    $data = $request->validate([
        'answers' => 'required|array',
    ]);
    
    // Get user from token
    $token = str_replace('Bearer ', '', $request->header('Authorization', ''));
    $user = \Illuminate\Support\Facades\Cache::get('auth:token:' . $token);
    
    if (!$user) {
        return response()->json(['message' => 'Authentication required'], 401);
    }
    
    $questions = $quiz->questions;
    $totalQuestions = $questions->count();
    $correctAnswers = 0;
    
    // Calculate score
    foreach ($questions as $question) {
        $userAnswer = $data['answers'][$question->id] ?? null;
        if ($userAnswer !== null && $userAnswer == $question->correct_answer) {
            $correctAnswers++;
        }
    }
    
    $scorePercentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
    $passed = $scorePercentage >= $quiz->passing_score;
    
    // Create attempt
    $attempt = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'user_id' => $user['id'],
        'started_at' => now(),
        'completed_at' => now(),
        'score' => $scorePercentage,
        'total_questions' => $totalQuestions,
        'correct_answers' => $correctAnswers,
        'time_taken' => 0, // Could be calculated from frontend
        'passed' => $passed,
    ]);
    
    // Create individual answers
    foreach ($questions as $question) {
        $userAnswer = $data['answers'][$question->id] ?? null;
        $isCorrect = $userAnswer !== null && $userAnswer == $question->correct_answer;
        
        QuizAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'quiz_question_id' => $question->id,
            'answer' => json_encode($userAnswer),
            'is_correct' => $isCorrect,
        ]);
    }
    
    return response()->json([
        'id' => $attempt->id,
        'score' => $scorePercentage,
        'total_questions' => $totalQuestions,
        'correct_answers' => $correctAnswers,
        'passed' => $passed,
        'submittedAt' => $attempt->completed_at->toIso8601String(),
    ]);
});

// Database Management Routes
Route::get('/admin/database/tables', function () {
    $tables = DB::select('SHOW TABLES');
    $tableList = [];
    foreach ($tables as $table) {
        $tableName = array_values((array) $table)[0];
        $count = DB::table($tableName)->count();
        $tableList[] = [
            'name' => $tableName,
            'count' => $count
        ];
    }
    return response()->json($tableList);
});

Route::get('/admin/database/table/{tableName}', function ($tableName) {
    try {
        $data = DB::table($tableName)->limit(100)->get();
        $columns = [];
        if ($data->count() > 0) {
            $columns = array_keys((array) $data->first());
        }
        return response()->json([
            'table' => $tableName,
            'columns' => $columns,
            'data' => $data,
            'total_count' => DB::table($tableName)->count()
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
});

Route::get('/admin/database/structure/{tableName}', function ($tableName) {
    try {
        $structure = DB::select("DESCRIBE {$tableName}");
        return response()->json([
            'table' => $tableName,
            'structure' => $structure
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
});

Route::post('/admin/database/query', function (Request $request) {
    $query = $request->input('query');
    if (empty($query)) {
        return response()->json(['error' => 'Query is required'], 400);
    }
    
    try {
        // Only allow SELECT queries for safety
        if (!preg_match('/^\s*SELECT\s+/i', trim($query))) {
            return response()->json(['error' => 'Only SELECT queries are allowed'], 400);
        }
        
        $results = DB::select($query);
        return response()->json([
            'query' => $query,
            'results' => $results,
            'count' => count($results)
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
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

// Admin Dashboard
Route::get('/dashboard/admin', function () {
    $totalUsers = User::count();
    $totalCourses = Course::count();
    $totalEnrollments = Enrollment::count();
    $totalRevenue = Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
                              ->sum('courses.price');
    
    // Recent enrollments (last 7 days)
    $recentEnrollments = Enrollment::with(['user:id,name,email', 'course:id,title'])
                                  ->where('enrolled_at', '>=', now()->subDays(7))
                                  ->orderBy('enrolled_at', 'desc')
                                  ->limit(10)
                                  ->get();
    
    // Popular courses (by enrollment count)
    $popularCourses = Course::withCount('enrollments')
                           ->orderBy('enrollments_count', 'desc')
                           ->limit(5)
                           ->get(['id', 'title', 'price']);
    
    // Monthly enrollment stats (last 6 months)
    $monthlyStats = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = now()->subMonths($i);
        $enrollmentCount = Enrollment::whereYear('enrolled_at', $month->year)
                                   ->whereMonth('enrolled_at', $month->month)
                                   ->count();
        $monthlyStats[] = [
            'month' => $month->format('M Y'),
            'enrollments' => $enrollmentCount
        ];
    }
    
    // User role distribution
    $userStats = [
        'students' => User::where('role', User::ROLE_STUDENT)->count(),
        'instructors' => User::where('role', User::ROLE_INSTRUCTOR)->count(),
        'admins' => User::where('role', User::ROLE_ADMIN)->count(),
    ];
    
    // Quiz statistics (server-side aggregation to avoid 404s)
    $quizStats = [
        'coursesWithQuiz' => Quiz::distinct('course_id')->count(),
        'totalQuestions' => QuizQuestion::count(),
        'totalAttempts' => QuizAttempt::count(),
    ];
    
    return response()->json([
        'stats' => [
            'total_users' => $totalUsers,
            'total_courses' => $totalCourses,
            'total_enrollments' => $totalEnrollments,
            'total_revenue' => round($totalRevenue, 2),
            'total_students' => $userStats['students'],
            'total_instructors' => $userStats['instructors'],
            'published_courses' => Course::where('status', 'published')->count(),
            'completed_enrollments' => Enrollment::whereNotNull('completed_at')->count(),
        ],
        'quiz_stats' => $quizStats,
        'recent_enrollments' => $recentEnrollments->map(function ($enrollment) {
            return [
                'id' => $enrollment->id,
                'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                'student' => [
                    'name' => $enrollment->user->name,
                    'email' => $enrollment->user->email,
                ],
                'course' => [
                    'title' => $enrollment->course->title,
                ],
            ];
        }),
        'top_courses' => $popularCourses,
        'enrollment_trends' => $monthlyStats,
        'userStats' => $userStats,
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