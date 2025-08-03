<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    /**
     * Get lessons for a course
     */
    public function index($courseId)
    {
        $course = Course::findOrFail($courseId);
        
        // Check if user has access to this course
        if (!$this->hasAccessToCourse($course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $lessons = Lesson::where('course_id', $courseId)
            ->orderBy('order')
            ->get();

        // Add progress information for enrolled students
        if (auth()->user()->isStudent()) {
            $lessons->each(function ($lesson) {
                $progress = LessonProgress::where('user_id', auth()->id())
                    ->where('lesson_id', $lesson->id)
                    ->first();
                
                $lesson->completed = $progress ? $progress->completed : false;
                $lesson->watch_time = $progress ? $progress->watch_time : 0;
            });
        }

        return response()->json(['lessons' => $lessons]);
    }

    /**
     * Get specific lesson
     */
    public function show($id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        
        // Check if user has access to this lesson
        if (!$this->hasAccessToCourse($lesson->course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Add progress information for students
        if (auth()->user()->isStudent()) {
            $progress = LessonProgress::where('user_id', auth()->id())
                ->where('lesson_id', $id)
                ->first();
            
            $lesson->completed = $progress ? $progress->completed : false;
            $lesson->watch_time = $progress ? $progress->watch_time : 0;
        }

        return response()->json(['lesson' => $lesson]);
    }

    /**
     * Create new lesson
     */
    public function store(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        
        // Check if user can create lessons for this course
        if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'duration_minutes' => 'required|integer|min:1',
            'is_free' => 'boolean',
            'resources' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get next order number
        $maxOrder = Lesson::where('course_id', $courseId)->max('order') ?? 0;

        $lesson = Lesson::create([
            'course_id' => $courseId,
            'title' => $request->title,
            'description' => $request->description,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'duration_minutes' => $request->duration_minutes,
            'order' => $maxOrder + 1,
            'is_free' => $request->boolean('is_free', false),
            'resources' => $request->resources ?? [],
        ]);

        return response()->json([
            'message' => 'Lesson created successfully',
            'lesson' => $lesson
        ], 201);
    }

    /**
     * Update lesson
     */
    public function update(Request $request, $id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        
        // Check if user can update this lesson
        if (!auth()->user()->isAdmin() && $lesson->course->instructor_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'content' => 'sometimes|string',
            'video_url' => 'sometimes|url',
            'duration_minutes' => 'sometimes|integer|min:1',
            'order' => 'sometimes|integer|min:1',
            'is_free' => 'sometimes|boolean',
            'resources' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lesson->update($request->all());

        return response()->json([
            'message' => 'Lesson updated successfully',
            'lesson' => $lesson
        ]);
    }

    /**
     * Delete lesson
     */
    public function destroy($id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        
        // Check if user can delete this lesson
        if (!auth()->user()->isAdmin() && $lesson->course->instructor_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted successfully']);
    }

    /**
     * Mark lesson as complete
     */
    public function markComplete($id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        
        // Check if user is enrolled in the course
        $enrollment = Enrollment::where('user_id', auth()->id())
            ->where('course_id', $lesson->course_id)
            ->first();

        if (!$enrollment) {
            return response()->json(['error' => 'Not enrolled in this course'], 403);
        }

        // Create or update lesson progress
        $progress = LessonProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'lesson_id' => $id,
            ],
            [
                'completed' => true,
                'completed_at' => now(),
            ]
        );

        // Update course progress
        $this->updateCourseProgress($lesson->course_id, auth()->id());

        return response()->json([
            'message' => 'Lesson marked as complete',
            'progress' => $progress
        ]);
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
        
        // Students need to be enrolled or lesson is free
        if ($user->isStudent()) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            
            return $enrollment !== null;
        }
        
        return false;
    }

    /**
     * Update course progress based on completed lessons
     */
    private function updateCourseProgress($courseId, $userId)
    {
        $totalLessons = Lesson::where('course_id', $courseId)->count();
        $completedLessons = LessonProgress::where('user_id', $userId)
            ->whereHas('lesson', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->where('completed', true)
            ->count();

        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment) {
            $enrollment->updateProgress($progress);
        }
    }
}
