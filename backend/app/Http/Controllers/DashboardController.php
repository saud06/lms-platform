<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Admin Dashboard Statistics
     */
    public function adminStats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_students' => User::where('role', User::ROLE_STUDENT)->count(),
            'total_instructors' => User::where('role', User::ROLE_INSTRUCTOR)->count(),
            'total_courses' => Course::count(),
            'published_courses' => Course::where('status', Course::STATUS_PUBLISHED)->count(),
            'total_enrollments' => Enrollment::count(),
            'completed_enrollments' => Enrollment::whereNotNull('completed_at')->count(),
            'total_revenue' => Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
                ->sum('courses.price'),
        ];

        // Monthly enrollment trends
        $enrollmentTrends = Enrollment::select(
            DB::raw('DATE_FORMAT(enrolled_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as enrollments')
        )
        ->where('enrolled_at', '>=', now()->subMonths(12))
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Top courses by enrollment
        $topCourses = Course::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->limit(5)
            ->get();

        // Recent enrollments
        $recentEnrollments = Enrollment::with(['student', 'course'])
            ->orderBy('enrolled_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'enrollment_trends' => $enrollmentTrends,
            'top_courses' => $topCourses,
            'recent_enrollments' => $recentEnrollments,
        ]);
    }

    /**
     * Instructor Dashboard Statistics
     */
    public function instructorStats()
    {
        $instructorId = auth()->id();

        $stats = [
            'total_courses' => Course::where('instructor_id', $instructorId)->count(),
            'published_courses' => Course::where('instructor_id', $instructorId)
                ->where('status', Course::STATUS_PUBLISHED)->count(),
            'total_students' => Enrollment::whereHas('course', function($query) use ($instructorId) {
                $query->where('instructor_id', $instructorId);
            })->distinct('user_id')->count(),
            'total_revenue' => Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
                ->where('courses.instructor_id', $instructorId)
                ->sum('courses.price'),
        ];

        // Course performance
        $coursePerformance = Course::where('instructor_id', $instructorId)
            ->withCount(['enrollments', 'enrollments as completed_enrollments_count' => function($query) {
                $query->whereNotNull('completed_at');
            }])
            ->with(['enrollments' => function($query) {
                $query->selectRaw('course_id, AVG(rating) as avg_rating')
                    ->whereNotNull('rating')
                    ->groupBy('course_id');
            }])
            ->get();

        // Recent student activity
        $recentActivity = Enrollment::with(['student', 'course'])
            ->whereHas('course', function($query) use ($instructorId) {
                $query->where('instructor_id', $instructorId);
            })
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'course_performance' => $coursePerformance,
            'recent_activity' => $recentActivity,
        ]);
    }

    /**
     * Student Dashboard Statistics
     */
    public function studentStats()
    {
        $studentId = auth()->id();

        $stats = [
            'enrolled_courses' => Enrollment::where('user_id', $studentId)->count(),
            'completed_courses' => Enrollment::where('user_id', $studentId)
                ->whereNotNull('completed_at')->count(),
            'certificates_earned' => Enrollment::where('user_id', $studentId)
                ->where('certificate_issued', true)->count(),
            'total_study_hours' => Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
                ->where('enrollments.user_id', $studentId)
                ->whereNotNull('enrollments.completed_at')
                ->sum('courses.duration_hours'),
        ];

        // Learning progress
        $learningProgress = Enrollment::with(['course'])
            ->where('user_id', $studentId)
            ->orderBy('enrolled_at', 'desc')
            ->get();

        // Quiz performance
        $quizPerformance = QuizAttempt::with(['quiz.course'])
            ->where('user_id', $studentId)
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();

        // Recommended courses (based on enrolled course categories)
        $enrolledCategories = Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->where('enrollments.user_id', $studentId)
            ->pluck('courses.category')
            ->unique();

        $recommendedCourses = Course::whereIn('category', $enrolledCategories)
            ->whereNotIn('id', function($query) use ($studentId) {
                $query->select('course_id')
                    ->from('enrollments')
                    ->where('user_id', $studentId);
            })
            ->where('status', Course::STATUS_PUBLISHED)
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'learning_progress' => $learningProgress,
            'quiz_performance' => $quizPerformance,
            'recommended_courses' => $recommendedCourses,
        ]);
    }
}
