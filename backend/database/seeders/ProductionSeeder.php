<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds for production environment.
     * Only creates essential users for system operation.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@lmsplatform.com',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'AdminPass123!')),
            'role' => User::ROLE_ADMIN,
            'bio' => 'System Administrator',
            'phone' => '+1-000-000-0000',
            'is_active' => true,
        ]);

        // Create Demo Instructor
        User::create([
            'name' => 'Demo Instructor',
            'email' => 'instructor@lmsplatform.com',
            'password' => Hash::make(env('INSTRUCTOR_PASSWORD', 'InstructorPass123!')),
            'role' => User::ROLE_INSTRUCTOR,
            'bio' => 'Demo instructor account for testing and demonstration purposes.',
            'phone' => '+1-000-000-0001',
            'is_active' => true,
        ]);

        // Create Demo Student
        User::create([
            'name' => 'Demo Student',
            'email' => 'student@lmsplatform.com',
            'password' => Hash::make(env('STUDENT_PASSWORD', 'StudentPass123!')),
            'role' => User::ROLE_STUDENT,
            'bio' => 'Demo student account for testing and demonstration purposes.',
            'phone' => '+1-000-000-0002',
            'is_active' => true,
        ]);
    }
}