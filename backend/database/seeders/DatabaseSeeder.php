<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CourseSeeder::class,
            LessonSeeder::class,
            QuizSeeder::class,
            EnrollmentSeeder::class,
        ]);
    }
}
