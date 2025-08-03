<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * Get quizzes for a course
     */
    public function index($courseId)
    {
        $course = Course::findOrFail($courseId);
        
        // Check if user has access to this course
        if (!$this->hasAccessToCourse($course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $quizzes = Quiz::where('course_id', $courseId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        // Add attempt information for students
        if (auth()->user()->isStudent()) {
            $quizzes->each(function ($quiz) {
                $attempts = QuizAttempt::where('user_id', auth()->id())
                    ->where('quiz_id', $quiz->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                $quiz->attempts_count = $attempts->count();
                $quiz->best_score = $attempts->max('score') ?? 0;
                $quiz->can_take = $quiz->canUserTake(auth()->id());
                $quiz->last_attempt = $attempts->first();
            });
        }

        return response()->json(['quizzes' => $quizzes]);
    }

    /**
     * Get specific quiz
     */
    public function show($id)
    {
        $quiz = Quiz::with(['course', 'questions' => function($query) {
            $query->orderBy('order');
        }])->findOrFail($id);
        
        // Check if user has access to this quiz
        if (!$this->hasAccessToCourse($quiz->course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // For students, don't show correct answers
        if (auth()->user()->isStudent()) {
            $quiz->questions->each(function ($question) {
                unset($question->correct_answer);
                unset($question->explanation);
            });

            // Add attempt information
            $attempts = QuizAttempt::where('user_id', auth()->id())
                ->where('quiz_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $quiz->attempts_count = $attempts->count();
            $quiz->best_score = $attempts->max('score') ?? 0;
            $quiz->can_take = $quiz->canUserTake(auth()->id());
        }

        return response()->json(['quiz' => $quiz]);
    }

    /**
     * Create new quiz
     */
    public function store(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        
        // Check if user can create quizzes for this course
        if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'integer|min:0',
            'passing_score' => 'integer|min:0|max:100',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.options' => 'required_if:questions.*.type,multiple_choice|array',
            'questions.*.correct_answer' => 'required|array',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.points' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Get next order number
            $maxOrder = Quiz::where('course_id', $courseId)->max('order') ?? 0;

            $quiz = Quiz::create([
                'course_id' => $courseId,
                'title' => $request->title,
                'description' => $request->description,
                'time_limit' => $request->time_limit,
                'max_attempts' => $request->max_attempts ?? 0,
                'passing_score' => $request->passing_score ?? 70,
                'order' => $maxOrder + 1,
                'is_active' => true,
            ]);

            // Create questions
            foreach ($request->questions as $index => $questionData) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'options' => $questionData['options'] ?? null,
                    'correct_answer' => $questionData['correct_answer'],
                    'explanation' => $questionData['explanation'] ?? null,
                    'points' => $questionData['points'] ?? 1,
                    'order' => $index + 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Quiz created successfully',
                'quiz' => $quiz->load('questions')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create quiz'], 500);
        }
    }

    /**
     * Start quiz attempt
     */
    public function startAttempt($id)
    {
        $quiz = Quiz::with('questions')->findOrFail($id);
        
        // Check if user can take this quiz
        if (!$quiz->canUserTake(auth()->id())) {
            return response()->json(['error' => 'Cannot take this quiz'], 400);
        }

        // Check if user is enrolled in the course
        $enrollment = Enrollment::where('user_id', auth()->id())
            ->where('course_id', $quiz->course_id)
            ->first();

        if (!$enrollment) {
            return response()->json(['error' => 'Not enrolled in this course'], 403);
        }

        $attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'quiz_id' => $id,
            'started_at' => now(),
            'total_questions' => $quiz->questions->count(),
        ]);

        return response()->json([
            'message' => 'Quiz attempt started',
            'attempt' => $attempt,
            'quiz' => $quiz
        ], 201);
    }

    /**
     * Submit quiz attempt
     */
    public function submitAttempt(Request $request, $attemptId)
    {
        $attempt = QuizAttempt::with(['quiz.questions'])->findOrFail($attemptId);
        
        // Check if this is the user's attempt
        if ($attempt->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if already completed
        if ($attempt->completed_at) {
            return response()->json(['error' => 'Attempt already submitted'], 400);
        }

        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.answer' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Save answers
            foreach ($request->answers as $answerData) {
                $question = $attempt->quiz->questions->find($answerData['question_id']);
                $isCorrect = $question->isCorrectAnswer($answerData['answer']);

                QuizAnswer::create([
                    'quiz_attempt_id' => $attemptId,
                    'quiz_question_id' => $answerData['question_id'],
                    'answer' => is_array($answerData['answer']) ? $answerData['answer'] : [$answerData['answer']],
                    'is_correct' => $isCorrect,
                ]);
            }

            // Calculate time taken
            $timeTaken = now()->diffInSeconds($attempt->started_at);

            // Complete the attempt
            $attempt->update([
                'completed_at' => now(),
                'time_taken' => $timeTaken,
            ]);

            // Calculate score
            $score = $attempt->calculateScore();

            DB::commit();

            return response()->json([
                'message' => 'Quiz submitted successfully',
                'attempt' => $attempt->fresh(['answers.question']),
                'score' => $score,
                'passed' => $attempt->passed,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to submit quiz'], 500);
        }
    }

    /**
     * Get quiz attempt details
     */
    public function getAttempt($attemptId)
    {
        $attempt = QuizAttempt::with([
            'quiz',
            'answers.question'
        ])->findOrFail($attemptId);
        
        // Check if this is the user's attempt or user is instructor/admin
        if ($attempt->user_id !== auth()->id() && 
            !auth()->user()->isAdmin() && 
            !auth()->user()->isInstructor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['attempt' => $attempt]);
    }

    /**
     * Update quiz
     */
    public function update(Request $request, $id)
    {
        $quiz = Quiz::with('course')->findOrFail($id);
        
        // Check if user can update this quiz
        if (!auth()->user()->isAdmin() && $quiz->course->instructor_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'time_limit' => 'sometimes|integer|min:1',
            'max_attempts' => 'sometimes|integer|min:0',
            'passing_score' => 'sometimes|integer|min:0|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $quiz->update($request->all());

        return response()->json([
            'message' => 'Quiz updated successfully',
            'quiz' => $quiz
        ]);
    }

    /**
     * Delete quiz
     */
    public function destroy($id)
    {
        $quiz = Quiz::with('course')->findOrFail($id);
        
        // Check if user can delete this quiz
        if (!auth()->user()->isAdmin() && $quiz->course->instructor_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted successfully']);
    }

    /**
     * Check if user has access to course
     */
    private function hasAccessToCourse(Course $course)
    {
        $user = auth()->user();
        
        // Admin has access to all courses
        if ($user->isAdmin()) {
            return true;
        }
        
        // Instructor has access to their own courses
        if ($user->isInstructor() && $course->instructor_id === $user->id) {
            return true;
        }
        
        // Students need to be enrolled
        if ($user->isStudent()) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            
            return $enrollment !== null;
        }
        
        return false;
    }
}
