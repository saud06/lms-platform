<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@lms.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'bio' => 'System Administrator with full access to manage the LMS platform.',
            'phone' => '+1-555-0101',
            'is_active' => true,
        ]);

        // Create Instructor Users
        User::create([
            'name' => 'Dr. Sarah Johnson',
            'email' => 'instructor@lms.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_INSTRUCTOR,
            'bio' => 'Senior Software Engineer with 10+ years of experience in web development and teaching.',
            'phone' => '+1-555-0102',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Prof. Michael Chen',
            'email' => 'michael.chen@lms.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_INSTRUCTOR,
            'bio' => 'Data Science expert and machine learning researcher.',
            'phone' => '+1-555-0103',
            'is_active' => true,
        ]);

        // Create Student Users
        User::create([
            'name' => 'John Smith',
            'email' => 'student@lms.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'bio' => 'Aspiring web developer looking to enhance my skills.',
            'phone' => '+1-555-0201',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Emma Wilson',
            'email' => 'emma.wilson@lms.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'bio' => 'Computer Science student interested in full-stack development.',
            'phone' => '+1-555-0202',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'David Rodriguez',
            'email' => 'david.rodriguez@lms.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'bio' => 'Career changer transitioning into tech from marketing.',
            'phone' => '+1-555-0203',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Lisa Thompson',
            'email' => 'lisa.thompson@lms.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'bio' => 'Data analyst looking to expand into machine learning.',
            'phone' => '+1-555-0204',
            'is_active' => true,
        ]);

        // Create additional students using factory
        User::factory(15)->create([
            'role' => User::ROLE_STUDENT,
        ]);

        // Create additional instructors using factory
        User::factory(3)->create([
            'role' => User::ROLE_INSTRUCTOR,
        ]);
    }
}
