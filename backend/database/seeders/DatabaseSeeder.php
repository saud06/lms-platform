<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Course;
use App\Models\Setting;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@lms.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create default instructor
        User::firstOrCreate(
            ['email' => 'instructor@lms.com'],
            [
                'name' => 'Dr. Sarah Johnson',
                'password' => Hash::make('instructor123'),
                'role' => 'instructor',
                'email_verified_at' => now(),
            ]
        );

        // Create default student
        User::firstOrCreate(
            ['email' => 'student@lms.com'],
            [
                'name' => 'John Student',
                'password' => Hash::make('student123'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );

        // Create sample course
        $instructor = User::where('email', 'instructor@lms.com')->first();
        if ($instructor) {
            Course::firstOrCreate(
                ['title' => 'Full Stack Web Development with Laravel & React'],
                [
                    'description' => 'Learn to build modern web applications using Laravel backend and React frontend.',
                    'instructor_id' => $instructor->id,
                    'category' => 'Web Development',
                    'level' => 'intermediate',
                    'price' => 275.99,
                    'status' => 'published',
                ]
            );

            Course::firstOrCreate(
                ['title' => 'Python for Data Science and Machine Learning'],
                [
                    'description' => 'Master Python programming for data analysis and machine learning applications.',
                    'instructor_id' => $instructor->id,
                    'category' => 'Data Science',
                    'level' => 'beginner',
                    'price' => 229.99,
                    'status' => 'published',
                ]
            );
        }

        // Create system settings
        Setting::firstOrCreate(
            ['key' => 'site_name'],
            ['value' => 'LMS Platform']
        );

        Setting::firstOrCreate(
            ['key' => 'site_description'],
            ['value' => 'A modern learning management system built with Laravel and React']
        );

        Setting::firstOrCreate(
            ['key' => 'default_language'],
            ['value' => 'de']
        );

        Setting::firstOrCreate(
            ['key' => 'default_currency'],
            ['value' => 'EUR']
        );
    }
}
