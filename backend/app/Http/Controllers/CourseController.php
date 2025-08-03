<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Get all courses with filters
     */
    public function index(Request $request)
    {
        $query = Course::with(['instructor', 'enrollments'])
            ->where('status', Course::STATUS_PUBLISHED);

        // Apply filters
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $courses = $query->paginate($request->get('per_page', 12));

        return response()->json($courses);
    }

    /**
     * Get course by ID
     */
    public function show($id)
    {
        $course = Course::with([
            'instructor',
            'lessons' => function($query) {
                $query->orderBy('order');
            },
            'quizzes' => function($query) {
                $query->where('is_active', true)->orderBy('order');
            },
            'enrollments'
        ])->findOrFail($id);

        // Check if user is enrolled
        $isEnrolled = false;
        $userProgress = null;
        
        if (auth()->check()) {
            $enrollment = Enrollment::where('user_id', auth()->id())
                ->where('course_id', $id)
                ->first();
            
            if ($enrollment) {
                $isEnrolled = true;
                $userProgress = $enrollment->progress;
            }
        }

        return response()->json([
            'course' => $course,
            'is_enrolled' => $isEnrolled,
            'user_progress' => $userProgress,
        ]);
    }

    /**
     * Create a new course (Instructor/Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
            'level' => 'required|in:beginner,intermediate,advanced',
            'duration_hours' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'max_students' => 'nullable|integer|min:1',
            'requirements' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $course = Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'instructor_id' => auth()->id(),
            'category' => $request->category,
            'level' => $request->level,
            'duration_hours' => $request->duration_hours,
            'price' => $request->price,
            'max_students' => $request->max_students,
            'requirements' => $request->requirements,
            'learning_outcomes' => $request->learning_outcomes,
            'status' => Course::STATUS_DRAFT,
        ]);

        return response()->json([
            'message' => 'Course created successfully',
            'course' => $course->load('instructor')
        ], 201);
    }

    /**
     * Update course (Instructor/Admin only)
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        // Check if user can update this course
        if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'short_description' => 'sometimes|string|max:500',
            'category' => 'sometimes|string|max:100',
            'level' => 'sometimes|in:beginner,intermediate,advanced',
            'duration_hours' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'max_students' => 'sometimes|integer|min:1',
            'requirements' => 'sometimes|array',
            'learning_outcomes' => 'sometimes|array',
            'status' => 'sometimes|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $course->update($request->all());

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course->load('instructor')
        ]);
    }

    /**
     * Delete course (Admin only)
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }

    /**
     * Enroll in course
     */
    public function enroll($id)
    {
        $course = Course::findOrFail($id);

        // Check if course is published
        if ($course->status !== Course::STATUS_PUBLISHED) {
            return response()->json(['error' => 'Course is not available for enrollment'], 400);
        }

        // Check if course has available spots
        if (!$course->hasAvailableSpots()) {
            return response()->json(['error' => 'Course is full'], 400);
        }

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('user_id', auth()->id())
            ->where('course_id', $id)
            ->first();

        if ($existingEnrollment) {
            return response()->json(['error' => 'Already enrolled in this course'], 400);
        }

        $enrollment = Enrollment::create([
            'user_id' => auth()->id(),
            'course_id' => $id,
            'enrolled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully enrolled in course',
            'enrollment' => $enrollment
        ], 201);
    }

    /**
     * Get instructor's courses
     */
    public function instructorCourses()
    {
        $courses = Course::with(['enrollments'])
            ->where('instructor_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($courses);
    }

    /**
     * Get enrolled courses for student
     */
    public function enrolledCourses()
    {
        $enrollments = Enrollment::with(['course.instructor'])
            ->where('user_id', auth()->id())
            ->orderBy('enrolled_at', 'desc')
            ->paginate(10);

        return response()->json($enrollments);
    }
}
