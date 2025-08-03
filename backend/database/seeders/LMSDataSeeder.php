<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\Setting;
use Carbon\Carbon;

class LMSDataSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        QuizAnswer::truncate();
        QuizAttempt::truncate();
        QuizQuestion::truncate();
        Quiz::truncate();
        Enrollment::truncate();
        Course::truncate();
        User::truncate();
        Setting::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create Settings
        Setting::create([
            'platform_name' => 'LMS Platform',
            'support_email' => 'support@lmsplatform.com',
        ]);

        // Create Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $instructors = [];
        $instructorData = [
            ['name' => 'Dr. Sarah Johnson', 'email' => 'instructor@example.com'],
            ['name' => 'Prof. Michael Chen', 'email' => 'michael.chen@example.com'],
            ['name' => 'Dr. Emily Rodriguez', 'email' => 'emily.rodriguez@example.com'],
            ['name' => 'Prof. David Wilson', 'email' => 'david.wilson@example.com'],
            ['name' => 'Dr. Lisa Thompson', 'email' => 'lisa.thompson@example.com'],
        ];

        foreach ($instructorData as $instructor) {
            $instructors[] = User::create([
                'name' => $instructor['name'],
                'email' => $instructor['email'],
                'password' => Hash::make('password'),
                'role' => 'instructor',
                'is_active' => true,
            ]);
        }

        $students = [];
        $studentData = [
            ['name' => 'Student User', 'email' => 'student@example.com'],
            ['name' => 'John Smith', 'email' => 'john.smith@example.com'],
            ['name' => 'Emma Davis', 'email' => 'emma.davis@example.com'],
            ['name' => 'Alex Johnson', 'email' => 'alex.johnson@example.com'],
            ['name' => 'Maria Garcia', 'email' => 'maria.garcia@example.com'],
            ['name' => 'James Brown', 'email' => 'james.brown@example.com'],
            ['name' => 'Sophie Wilson', 'email' => 'sophie.wilson@example.com'],
            ['name' => 'Ryan Miller', 'email' => 'ryan.miller@example.com'],
            ['name' => 'Olivia Taylor', 'email' => 'olivia.taylor@example.com'],
            ['name' => 'Daniel Anderson', 'email' => 'daniel.anderson@example.com'],
            ['name' => 'Isabella Martinez', 'email' => 'isabella.martinez@example.com'],
            ['name' => 'Matthew Thomas', 'email' => 'matthew.thomas@example.com'],
            ['name' => 'Ava Jackson', 'email' => 'ava.jackson@example.com'],
            ['name' => 'Christopher White', 'email' => 'christopher.white@example.com'],
            ['name' => 'Mia Harris', 'email' => 'mia.harris@example.com'],
        ];

        foreach ($studentData as $student) {
            $students[] = User::create([
                'name' => $student['name'],
                'email' => $student['email'],
                'password' => Hash::make('password'),
                'role' => 'student',
                'is_active' => true,
            ]);
        }

        // Create Courses
        $courses = [];
        $courseData = [
            [
                'title' => 'Full Stack Web Development with Laravel & React',
                'description' => 'Learn to build modern web applications using Laravel backend and React frontend. This comprehensive course covers everything from basic concepts to advanced deployment strategies.',
                'short_description' => 'Master full-stack development with Laravel and React',
                'category' => 'Web Development',
                'level' => 'intermediate',
                'duration_hours' => 40,
                'price' => 299.99,
                'status' => 'published',
                'instructor_id' => $instructors[0]->id,
            ],
            [
                'title' => 'Python for Data Science and Machine Learning',
                'description' => 'Comprehensive course covering Python programming, data analysis with pandas, visualization with matplotlib, and machine learning with scikit-learn.',
                'short_description' => 'Learn Python for data science and ML applications',
                'category' => 'Data Science',
                'level' => 'beginner',
                'duration_hours' => 35,
                'price' => 249.99,
                'status' => 'published',
                'instructor_id' => $instructors[1]->id,
            ],
            [
                'title' => 'Advanced JavaScript and Node.js Development',
                'description' => 'Deep dive into modern JavaScript ES6+, asynchronous programming, Node.js backend development, and building RESTful APIs.',
                'short_description' => 'Master advanced JavaScript and Node.js',
                'category' => 'Programming',
                'level' => 'advanced',
                'duration_hours' => 30,
                'price' => 199.99,
                'status' => 'published',
                'instructor_id' => $instructors[2]->id,
            ],
            [
                'title' => 'Mobile App Development with React Native',
                'description' => 'Build cross-platform mobile applications using React Native. Learn navigation, state management, API integration, and app deployment.',
                'short_description' => 'Create mobile apps with React Native',
                'category' => 'Mobile Development',
                'level' => 'intermediate',
                'duration_hours' => 45,
                'price' => 349.99,
                'status' => 'published',
                'instructor_id' => $instructors[3]->id,
            ],
            [
                'title' => 'Cloud Computing with AWS',
                'description' => 'Learn Amazon Web Services from basics to advanced. Cover EC2, S3, RDS, Lambda, and deployment strategies for scalable applications.',
                'short_description' => 'Master AWS cloud computing services',
                'category' => 'Cloud Computing',
                'level' => 'intermediate',
                'duration_hours' => 25,
                'price' => 279.99,
                'status' => 'published',
                'instructor_id' => $instructors[4]->id,
            ],
            [
                'title' => 'Digital Marketing Fundamentals',
                'description' => 'Complete guide to digital marketing including SEO, social media marketing, email campaigns, and analytics.',
                'short_description' => 'Learn essential digital marketing skills',
                'category' => 'Marketing',
                'level' => 'beginner',
                'duration_hours' => 20,
                'price' => 149.99,
                'status' => 'published',
                'instructor_id' => $instructors[0]->id,
            ],
            [
                'title' => 'UI/UX Design Principles',
                'description' => 'Learn user interface and user experience design principles, prototyping with Figma, and creating user-centered designs.',
                'short_description' => 'Master UI/UX design fundamentals',
                'category' => 'Design',
                'level' => 'beginner',
                'duration_hours' => 28,
                'price' => 189.99,
                'status' => 'published',
                'instructor_id' => $instructors[1]->id,
            ],
            [
                'title' => 'Cybersecurity Essentials',
                'description' => 'Introduction to cybersecurity concepts, threat analysis, network security, and best practices for protecting digital assets.',
                'short_description' => 'Learn cybersecurity fundamentals',
                'category' => 'Security',
                'level' => 'beginner',
                'duration_hours' => 22,
                'price' => 229.99,
                'status' => 'published',
                'instructor_id' => $instructors[2]->id,
            ],
        ];

        foreach ($courseData as $courseInfo) {
            $courses[] = Course::create($courseInfo);
        }

        // Create Enrollments (spread over the last 6 months)
        $enrollments = [];
        $enrollmentDates = [];
        
        // Generate enrollment dates over the last 6 months
        for ($i = 0; $i < 150; $i++) {
            $enrollmentDates[] = Carbon::now()->subDays(rand(1, 180));
        }

        // Create enrollments
        $enrollmentCount = 0;
        foreach ($students as $student) {
            // Each student enrolls in 2-5 random courses
            $coursesToEnroll = collect($courses)->random(rand(2, 5));
            
            foreach ($coursesToEnroll as $course) {
                if ($enrollmentCount < count($enrollmentDates)) {
                    $enrollmentDate = $enrollmentDates[$enrollmentCount];
                    $progress = rand(0, 100);
                    $completedAt = $progress >= 100 ? $enrollmentDate->copy()->addDays(rand(7, 30)) : null;
                    
                    $enrollments[] = Enrollment::create([
                        'user_id' => $student->id,
                        'course_id' => $course->id,
                        'enrolled_at' => $enrollmentDate,
                        'progress' => $progress,
                        'completed_at' => $completedAt,
                    ]);
                    
                    $enrollmentCount++;
                }
            }
        }

        // Create Quizzes for each course
        foreach ($courses as $course) {
            $quiz = Quiz::create([
                'course_id' => $course->id,
                'title' => $course->title . ' Quiz',
                'description' => 'Test your knowledge of ' . $course->title,
                'time_limit' => rand(15, 45),
                'max_attempts' => rand(2, 5),
                'passing_score' => rand(60, 80),
                'is_active' => true,
                'order' => 1,
            ]);

            // Create 5-10 questions per quiz
            $questionCount = rand(5, 10);
            for ($i = 1; $i <= $questionCount; $i++) {
                $questions = [
                    'web_dev' => [
                        'What does MVC stand for in Laravel?',
                        'Which React hook is used for state management?',
                        'What is the purpose of middleware in Laravel?',
                        'How do you handle forms in React?',
                        'What is JSX in React?',
                    ],
                    'data_science' => [
                        'What is pandas used for in Python?',
                        'Which library is commonly used for machine learning?',
                        'What does NumPy provide?',
                        'What is the purpose of matplotlib?',
                        'What is a DataFrame?',
                    ],
                    'javascript' => [
                        'What is a Promise in JavaScript?',
                        'What does async/await do?',
                        'What is Node.js?',
                        'What is the event loop?',
                        'What are arrow functions?',
                    ],
                    'mobile' => [
                        'What is React Native?',
                        'How do you navigate between screens?',
                        'What is Expo?',
                        'How do you handle state in React Native?',
                        'What are native modules?',
                    ],
                    'cloud' => [
                        'What is AWS EC2?',
                        'What is the purpose of S3?',
                        'What is AWS Lambda?',
                        'What is RDS?',
                        'What is CloudFormation?',
                    ],
                ];

                $categoryQuestions = $questions['web_dev']; // Default
                if (str_contains(strtolower($course->category), 'data')) {
                    $categoryQuestions = $questions['data_science'];
                } elseif (str_contains(strtolower($course->category), 'programming')) {
                    $categoryQuestions = $questions['javascript'];
                } elseif (str_contains(strtolower($course->category), 'mobile')) {
                    $categoryQuestions = $questions['mobile'];
                } elseif (str_contains(strtolower($course->category), 'cloud')) {
                    $categoryQuestions = $questions['cloud'];
                }

                $questionText = $categoryQuestions[($i - 1) % count($categoryQuestions)];
                $options = [
                    'Option A - Correct Answer',
                    'Option B - Incorrect',
                    'Option C - Incorrect',
                    'Option D - Incorrect'
                ];
                
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => $questionText,
                    'type' => 'multiple_choice',
                    'options' => $options,
                    'correct_answer' => 0, // First option is always correct
                    'points' => 10,
                    'order' => $i,
                ]);
            }
        }

        // Create Quiz Attempts
        foreach ($enrollments as $enrollment) {
            // 60% of enrolled students attempt the quiz
            if (rand(1, 100) <= 60) {
                $quiz = Quiz::where('course_id', $enrollment->course_id)->first();
                if ($quiz) {
                    $questions = $quiz->questions;
                    $totalQuestions = $questions->count();
                    
                    // Random score between 40-95%
                    $scorePercentage = rand(40, 95);
                    $correctAnswers = round(($scorePercentage / 100) * $totalQuestions);
                    $passed = $scorePercentage >= $quiz->passing_score;
                    
                    $attemptDate = $enrollment->enrolled_at->copy()->addDays(rand(1, 14));
                    
                    $attempt = QuizAttempt::create([
                        'quiz_id' => $quiz->id,
                        'user_id' => $enrollment->user_id,
                        'started_at' => $attemptDate,
                        'completed_at' => $attemptDate->copy()->addMinutes(rand(5, $quiz->time_limit)),
                        'score' => $scorePercentage,
                        'total_questions' => $totalQuestions,
                        'correct_answers' => $correctAnswers,
                        'time_taken' => rand(300, $quiz->time_limit * 60), // in seconds
                        'passed' => $passed,
                    ]);

                    // Create answers for each question
                    $correctCount = 0;
                    foreach ($questions as $question) {
                        // Ensure we get the right number of correct answers
                        $shouldBeCorrect = $correctCount < $correctAnswers;
                        if ($shouldBeCorrect) {
                            $selectedAnswer = $question->correct_answer;
                            $isCorrect = true;
                            $correctCount++;
                        } else {
                            // Select a wrong answer
                            do {
                                $selectedAnswer = rand(0, 3);
                            } while ($selectedAnswer == $question->correct_answer);
                            $isCorrect = false;
                        }
                        
                        QuizAnswer::create([
                            'quiz_attempt_id' => $attempt->id,
                            'quiz_question_id' => $question->id,
                            'answer' => json_encode($selectedAnswer),
                            'is_correct' => $isCorrect,
                        ]);
                    }
                }
            }
        }

        $this->command->info('LMS Platform seeded successfully!');
        $this->command->info('Users created: ' . User::count());
        $this->command->info('Courses created: ' . Course::count());
        $this->command->info('Enrollments created: ' . Enrollment::count());
        $this->command->info('Quizzes created: ' . Quiz::count());
        $this->command->info('Quiz Questions created: ' . QuizQuestion::count());
        $this->command->info('Quiz Attempts created: ' . QuizAttempt::count());
    }
}
