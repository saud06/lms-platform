<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::where('status', Course::STATUS_PUBLISHED)->get();

        foreach ($courses as $course) {
            $this->createQuizzesForCourse($course);
        }
    }

    private function createQuizzesForCourse(Course $course)
    {
        $quizTemplates = $this->getQuizTemplates($course->category);
        
        foreach ($quizTemplates as $index => $quizData) {
            $quiz = Quiz::create([
                'course_id' => $course->id,
                'title' => $quizData['title'],
                'description' => $quizData['description'],
                'time_limit' => $quizData['time_limit'],
                'max_attempts' => $quizData['max_attempts'],
                'passing_score' => $quizData['passing_score'],
                'is_active' => true,
                'order' => $index + 1,
            ]);

            $this->createQuestionsForQuiz($quiz, $quizData['questions']);
        }
    }

    private function createQuestionsForQuiz(Quiz $quiz, array $questions)
    {
        foreach ($questions as $index => $questionData) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'options' => $questionData['options'] ?? null,
                'correct_answer' => $questionData['correct_answer'],
                'explanation' => $questionData['explanation'] ?? null,
                'points' => $questionData['points'] ?? 1,
                'order' => $index + 1,
            ]);
        }
    }

    private function getQuizTemplates($category)
    {
        $templates = [
            'Web Development' => [
                [
                    'title' => 'Laravel Fundamentals Quiz',
                    'description' => 'Test your understanding of Laravel basics',
                    'time_limit' => 30,
                    'max_attempts' => 3,
                    'passing_score' => 70,
                    'questions' => [
                        [
                            'question' => 'What does MVC stand for in Laravel?',
                            'type' => 'multiple_choice',
                            'options' => ['Model-View-Controller', 'Model-View-Component', 'Module-View-Controller', 'Model-Virtual-Controller'],
                            'correct_answer' => ['Model-View-Controller'],
                            'explanation' => 'MVC stands for Model-View-Controller, which is the architectural pattern used by Laravel.',
                            'points' => 2
                        ],
                        [
                            'question' => 'Laravel uses Composer for dependency management.',
                            'type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => ['True'],
                            'explanation' => 'Laravel uses Composer, which is a dependency manager for PHP.',
                            'points' => 1
                        ],
                        [
                            'question' => 'Which Artisan command creates a new controller?',
                            'type' => 'short_answer',
                            'correct_answer' => ['php artisan make:controller'],
                            'explanation' => 'The make:controller command creates a new controller class.',
                            'points' => 2
                        ]
                    ]
                ],
                [
                    'title' => 'React Components Quiz',
                    'description' => 'Test your knowledge of React components',
                    'time_limit' => 25,
                    'max_attempts' => 3,
                    'passing_score' => 75,
                    'questions' => [
                        [
                            'question' => 'What is JSX?',
                            'type' => 'multiple_choice',
                            'options' => ['JavaScript XML', 'Java Syntax Extension', 'JSON XML', 'JavaScript Extension'],
                            'correct_answer' => ['JavaScript XML'],
                            'explanation' => 'JSX stands for JavaScript XML and allows you to write HTML-like syntax in JavaScript.',
                            'points' => 2
                        ],
                        [
                            'question' => 'Props in React are mutable.',
                            'type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => ['False'],
                            'explanation' => 'Props are immutable in React. They cannot be changed by the component that receives them.',
                            'points' => 2
                        ]
                    ]
                ]
            ],
            'Data Science' => [
                [
                    'title' => 'Python Data Science Quiz',
                    'description' => 'Test your Python data science knowledge',
                    'time_limit' => 35,
                    'max_attempts' => 3,
                    'passing_score' => 70,
                    'questions' => [
                        [
                            'question' => 'Which library is primarily used for data manipulation in Python?',
                            'type' => 'multiple_choice',
                            'options' => ['NumPy', 'Pandas', 'Matplotlib', 'Scikit-learn'],
                            'correct_answer' => ['Pandas'],
                            'explanation' => 'Pandas is the primary library for data manipulation and analysis in Python.',
                            'points' => 2
                        ],
                        [
                            'question' => 'What does NaN stand for?',
                            'type' => 'short_answer',
                            'correct_answer' => ['Not a Number'],
                            'explanation' => 'NaN stands for "Not a Number" and represents missing or undefined numerical data.',
                            'points' => 1
                        ]
                    ]
                ]
            ],
            'Programming' => [
                [
                    'title' => 'JavaScript Basics Quiz',
                    'description' => 'Test your JavaScript fundamentals',
                    'time_limit' => 20,
                    'max_attempts' => 5,
                    'passing_score' => 60,
                    'questions' => [
                        [
                            'question' => 'Which of the following is a JavaScript data type?',
                            'type' => 'multiple_choice',
                            'options' => ['String', 'Boolean', 'Number', 'All of the above'],
                            'correct_answer' => ['All of the above'],
                            'explanation' => 'JavaScript has several primitive data types including String, Boolean, and Number.',
                            'points' => 1
                        ],
                        [
                            'question' => 'JavaScript is case-sensitive.',
                            'type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => ['True'],
                            'explanation' => 'JavaScript is case-sensitive, meaning "Variable" and "variable" are different identifiers.',
                            'points' => 1
                        ]
                    ]
                ]
            ]
        ];

        return $templates[$category] ?? $templates['Programming'];
    }
}
