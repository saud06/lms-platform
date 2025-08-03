<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructors = User::where('role', User::ROLE_INSTRUCTOR)->get();

        $courses = [
            [
                'title' => 'Full Stack Web Development with Laravel & React',
                'description' => 'Master modern web development by building real-world applications with Laravel backend and React frontend. This comprehensive course covers everything from basic concepts to advanced deployment strategies.',
                'short_description' => 'Learn to build modern web applications with Laravel and React from scratch.',
                'instructor_id' => $instructors->first()->id,
                'category' => 'Web Development',
                'level' => Course::LEVEL_INTERMEDIATE,
                'duration_hours' => 40,
                'price' => 199.99,
                'status' => Course::STATUS_PUBLISHED,
                'max_students' => 100,
                'requirements' => ['Basic HTML/CSS knowledge', 'JavaScript fundamentals', 'Basic PHP understanding'],
                'learning_outcomes' => [
                    'Build full-stack web applications',
                    'Master Laravel framework',
                    'Create dynamic React interfaces',
                    'Implement authentication systems',
                    'Deploy applications to production'
                ],
                'is_featured' => true,
            ],
            [
                'title' => 'Data Science with Python & Machine Learning',
                'description' => 'Dive deep into data science and machine learning using Python. Learn to analyze data, build predictive models, and create data visualizations that drive business decisions.',
                'short_description' => 'Complete data science course covering Python, ML algorithms, and real-world projects.',
                'instructor_id' => $instructors->skip(1)->first()->id,
                'category' => 'Data Science',
                'level' => Course::LEVEL_INTERMEDIATE,
                'duration_hours' => 35,
                'price' => 249.99,
                'status' => Course::STATUS_PUBLISHED,
                'max_students' => 80,
                'requirements' => ['Basic Python knowledge', 'Statistics fundamentals', 'Mathematics background'],
                'learning_outcomes' => [
                    'Perform data analysis with pandas',
                    'Build machine learning models',
                    'Create data visualizations',
                    'Work with real datasets',
                    'Deploy ML models'
                ],
                'is_featured' => true,
            ],
            [
                'title' => 'JavaScript Fundamentals for Beginners',
                'description' => 'Start your programming journey with JavaScript. This beginner-friendly course covers all the essential concepts you need to become proficient in JavaScript programming.',
                'short_description' => 'Learn JavaScript from scratch with hands-on projects and exercises.',
                'instructor_id' => $instructors->first()->id,
                'category' => 'Programming',
                'level' => Course::LEVEL_BEGINNER,
                'duration_hours' => 25,
                'price' => 99.99,
                'status' => Course::STATUS_PUBLISHED,
                'max_students' => 150,
                'requirements' => ['Basic computer skills', 'No programming experience required'],
                'learning_outcomes' => [
                    'Understand JavaScript syntax',
                    'Work with DOM manipulation',
                    'Handle events and user interactions',
                    'Build interactive web pages',
                    'Debug JavaScript code'
                ],
                'is_featured' => false,
            ],
            [
                'title' => 'Advanced React Development',
                'description' => 'Take your React skills to the next level with advanced patterns, performance optimization, and modern React features including hooks, context, and concurrent features.',
                'short_description' => 'Master advanced React concepts and build scalable applications.',
                'instructor_id' => $instructors->first()->id,
                'category' => 'Frontend Development',
                'level' => Course::LEVEL_ADVANCED,
                'duration_hours' => 30,
                'price' => 179.99,
                'status' => Course::STATUS_PUBLISHED,
                'max_students' => 60,
                'requirements' => ['Solid React fundamentals', 'JavaScript ES6+', 'Component lifecycle knowledge'],
                'learning_outcomes' => [
                    'Master React hooks and context',
                    'Implement performance optimizations',
                    'Build complex state management',
                    'Create reusable component libraries',
                    'Test React applications'
                ],
                'is_featured' => false,
            ],
            [
                'title' => 'Database Design & SQL Mastery',
                'description' => 'Learn to design efficient databases and write complex SQL queries. Cover normalization, indexing, stored procedures, and database optimization techniques.',
                'short_description' => 'Master database design principles and advanced SQL techniques.',
                'instructor_id' => $instructors->skip(2)->first()->id,
                'category' => 'Database',
                'level' => Course::LEVEL_INTERMEDIATE,
                'duration_hours' => 28,
                'price' => 149.99,
                'status' => Course::STATUS_PUBLISHED,
                'max_students' => 90,
                'requirements' => ['Basic SQL knowledge', 'Understanding of relational databases'],
                'learning_outcomes' => [
                    'Design normalized database schemas',
                    'Write complex SQL queries',
                    'Optimize database performance',
                    'Implement stored procedures',
                    'Handle database security'
                ],
                'is_featured' => false,
            ],
            [
                'title' => 'DevOps with Docker & Kubernetes',
                'description' => 'Learn modern DevOps practices using containerization with Docker and orchestration with Kubernetes. Deploy and manage scalable applications in production.',
                'short_description' => 'Master containerization and orchestration for modern application deployment.',
                'instructor_id' => $instructors->skip(1)->first()->id,
                'category' => 'DevOps',
                'level' => Course::LEVEL_ADVANCED,
                'duration_hours' => 32,
                'price' => 229.99,
                'status' => Course::STATUS_DRAFT,
                'max_students' => 50,
                'requirements' => ['Linux command line', 'Basic networking', 'Application deployment experience'],
                'learning_outcomes' => [
                    'Containerize applications with Docker',
                    'Orchestrate with Kubernetes',
                    'Implement CI/CD pipelines',
                    'Monitor application performance',
                    'Manage production deployments'
                ],
                'is_featured' => false,
            ],
        ];

        foreach ($courses as $courseData) {
            Course::create($courseData);
        }

        // Create additional courses using factory
        Course::factory(10)->published()->create();
    }
}
