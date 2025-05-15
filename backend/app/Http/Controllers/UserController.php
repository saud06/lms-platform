<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users (Admin only)
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Apply filters
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * Get user by ID
     */
    public function show($id)
    {
        $user = User::with(['enrollments.course', 'taughtCourses', 'certificates'])->findOrFail($id);
        
        // Only allow users to see their own profile or admin to see any
        if (!auth()->user()->isAdmin() && auth()->id() !== (int)$id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['user' => $user]);
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Only allow users to update their own profile or admin to update any
        if (!auth()->user()->isAdmin() && auth()->id() !== (int)$id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'bio' => 'sometimes|string|max:1000',
            'phone' => 'sometimes|string|max:20',
            'date_of_birth' => 'sometimes|date',
            'password' => 'sometimes|string|min:6|confirmed',
        ];

        // Only admin can update role and is_active
        if (auth()->user()->isAdmin()) {
            $rules['role'] = 'sometimes|in:admin,instructor,student';
            $rules['is_active'] = 'sometimes|boolean';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only([
            'name', 'email', 'bio', 'phone', 'date_of_birth'
        ]);

        // Only admin can update these fields
        if (auth()->user()->isAdmin()) {
            $updateData = array_merge($updateData, $request->only(['role', 'is_active']));
        }

        // Handle password update
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete user (Admin only)
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if (auth()->id() === (int)$id) {
            return response()->json(['error' => 'Cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Get user statistics
     */
    public function stats($id)
    {
        $user = User::with(['enrollments', 'taughtCourses', 'certificates'])->findOrFail($id);
        
        // Only allow users to see their own stats or admin to see any
        if (!auth()->user()->isAdmin() && auth()->id() !== (int)$id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [];

        if ($user->isStudent()) {
            $stats = [
                'enrolled_courses' => $user->enrollments->count(),
                'completed_courses' => $user->enrollments->whereNotNull('completed_at')->count(),
                'certificates_earned' => $user->certificates->count(),
                'average_rating' => $user->enrollments->whereNotNull('rating')->avg('rating') ?? 0,
            ];
        } elseif ($user->isInstructor()) {
            $stats = [
                'courses_created' => $user->taughtCourses->count(),
                'total_students' => $user->taughtCourses->sum(function($course) {
                    return $course->enrollments->count();
                }),
                'average_course_rating' => $user->taughtCourses->avg(function($course) {
                    return $course->enrollments->whereNotNull('rating')->avg('rating') ?? 0;
                }),
            ];
        }

        return response()->json([
            'user' => $user,
            'stats' => $stats
        ]);
    }
}
