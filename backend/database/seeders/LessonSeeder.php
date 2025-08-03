<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            $this->createLessonsForCourse($course);
        }
    }

    private function createLessonsForCourse(Course $course)
    {
        $lessonTemplates = $this->getLessonTemplates($course->category);
        
        foreach ($lessonTemplates as $index => $lessonData) {
            Lesson::create([
                'course_id' => $course->id,
                'title' => $lessonData['title'],
                'description' => $lessonData['description'],
                'content' => $lessonData['content'],
                'video_url' => $lessonData['video_url'] ?? null,
                'duration_minutes' => $lessonData['duration_minutes'],
                'order' => $index + 1,
                'is_free' => $index === 0, // First lesson is always free
                'resources' => $lessonData['resources'] ?? [],
            ]);
        }
    }

    private function getLessonTemplates($category)
    {
        $templates = [
            'Web Development' => [
                [
                    'title' => 'Introduction to Full Stack Development',
                    'description' => 'Overview of full stack development and course structure',
                    'content' => 'Welcome to the Full Stack Web Development course! In this lesson, we\'ll explore what it means to be a full stack developer and outline the technologies we\'ll be learning throughout this course.',
                    'duration_minutes' => 15,
                    'resources' => ['Course syllabus', 'Development environment setup guide']
                ],
                [
                    'title' => 'Setting Up Your Development Environment',
                    'description' => 'Install and configure all necessary tools',
                    'content' => 'Let\'s set up your development environment with all the tools you\'ll need: PHP, Composer, Node.js, npm, and your code editor.',
                    'duration_minutes' => 30,
                    'resources' => ['Installation guides', 'Configuration files']
                ],
                [
                    'title' => 'Laravel Fundamentals',
                    'description' => 'Learn the basics of Laravel framework',
                    'content' => 'Dive into Laravel fundamentals including routing, controllers, views, and the MVC pattern.',
                    'duration_minutes' => 45,
                    'resources' => ['Laravel documentation', 'Code examples']
                ],
                [
                    'title' => 'Building Your First API',
                    'description' => 'Create RESTful APIs with Laravel',
                    'content' => 'Learn to build RESTful APIs using Laravel, including proper HTTP methods, status codes, and response formatting.',
                    'duration_minutes' => 60,
                    'resources' => ['API documentation template', 'Postman collection']
                ],
                [
                    'title' => 'React Basics and Components',
                    'description' => 'Introduction to React and component-based architecture',
                    'content' => 'Understand React fundamentals, JSX, components, props, and state management.',
                    'duration_minutes' => 50,
                    'resources' => ['React documentation', 'Component examples']
                ]
            ],
            'Data Science' => [
                [
                    'title' => 'Introduction to Data Science',
                    'description' => 'Overview of data science field and applications',
                    'content' => 'Welcome to Data Science! Learn about the data science workflow, tools, and real-world applications.',
                    'duration_minutes' => 20,
                    'resources' => ['Data science roadmap', 'Industry case studies']
                ],
                [
                    'title' => 'Python for Data Science',
                    'description' => 'Essential Python libraries and tools',
                    'content' => 'Master Python libraries essential for data science: NumPy, Pandas, and Matplotlib.',
                    'duration_minutes' => 45,
                    'resources' => ['Python cheat sheet', 'Library documentation']
                ],
                [
                    'title' => 'Data Cleaning and Preprocessing',
                    'description' => 'Prepare data for analysis and modeling',
                    'content' => 'Learn techniques for cleaning messy data, handling missing values, and preparing datasets for analysis.',
                    'duration_minutes' => 55,
                    'resources' => ['Sample datasets', 'Cleaning scripts']
                ],
                [
                    'title' => 'Exploratory Data Analysis',
                    'description' => 'Discover patterns and insights in data',
                    'content' => 'Use statistical methods and visualizations to explore and understand your data.',
                    'duration_minutes' => 40,
                    'resources' => ['EDA templates', 'Visualization examples']
                ]
            ],
            'Programming' => [
                [
                    'title' => 'JavaScript Basics',
                    'description' => 'Variables, data types, and basic syntax',
                    'content' => 'Start your JavaScript journey by learning about variables, data types, operators, and basic syntax.',
                    'duration_minutes' => 25,
                    'resources' => ['JavaScript reference', 'Practice exercises']
                ],
                [
                    'title' => 'Functions and Scope',
                    'description' => 'Understanding functions and variable scope',
                    'content' => 'Learn how to write functions, understand scope, and work with parameters and return values.',
                    'duration_minutes' => 35,
                    'resources' => ['Function examples', 'Scope diagrams']
                ],
                [
                    'title' => 'DOM Manipulation',
                    'description' => 'Interact with HTML elements using JavaScript',
                    'content' => 'Learn to select, modify, and interact with HTML elements using the Document Object Model.',
                    'duration_minutes' => 40,
                    'resources' => ['DOM reference', 'Interactive examples']
                ]
            ]
        ];

        return $templates[$category] ?? $templates['Programming'];
    }
}
