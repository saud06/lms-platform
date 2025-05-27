<?php

use Illuminate\Support\Facades\Hash;
use App\Models\User;

if (!function_exists('ensureDemoInstructor')) {
    /**
     * Helper: ensure a demo instructor exists
     */
    function ensureDemoInstructor(): User {
        $instructor = User::where('role', User::ROLE_INSTRUCTOR)->first();
        if (!$instructor) {
            $instructor = User::create([
                'name' => 'Instructor User',
                'email' => 'instructor@example.com',
                'role' => User::ROLE_INSTRUCTOR,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
        }
        return $instructor;
    }
}

if (!function_exists('ensureDemoStudent')) {
    /**
     * Helper: ensure at least one demo student exists
     */
    function ensureDemoStudent(): User {
        $student = User::where('role', User::ROLE_STUDENT)->first();
        if (!$student) {
            $student = User::create([
                'name' => 'Student User',
                'email' => 'student@example.com',
                'role' => User::ROLE_STUDENT,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
        }
        return $student;
    }
}