<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$host = $_ENV['DB_HOST'] ?? 'nozomi.proxy.rlwy.net';
$port = $_ENV['DB_PORT'] ?? '55229';
$database = $_ENV['DB_DATABASE'] ?? 'railway';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? 'yqZGCjlCsuPmeEzlaDxWmIEmHllcujWJ';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

try {
    // Sample users data
    $users = [
        ['name' => 'Dr. Sarah Johnson', 'email' => 'sarah.johnson@lms.com', 'role' => 'instructor'],
        ['name' => 'Prof. Michael Chen', 'email' => 'michael.chen@lms.com', 'role' => 'instructor'],
        ['name' => 'Dr. Emily Rodriguez', 'email' => 'emily.rodriguez@lms.com', 'role' => 'instructor'],
        ['name' => 'James Wilson', 'email' => 'james.wilson@student.com', 'role' => 'student'],
        ['name' => 'Maria Garcia', 'email' => 'maria.garcia@student.com', 'role' => 'student'],
        ['name' => 'David Brown', 'email' => 'david.brown@student.com', 'role' => 'student'],
        ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@student.com', 'role' => 'student'],
        ['name' => 'Robert Taylor', 'email' => 'robert.taylor@student.com', 'role' => 'student'],
        ['name' => 'Jennifer Lee', 'email' => 'jennifer.lee@student.com', 'role' => 'student'],
        ['name' => 'Christopher Davis', 'email' => 'chris.davis@student.com', 'role' => 'student'],
        ['name' => 'Amanda Martinez', 'email' => 'amanda.martinez@student.com', 'role' => 'student'],
        ['name' => 'Daniel Thompson', 'email' => 'daniel.thompson@student.com', 'role' => 'student'],
        ['name' => 'Michelle White', 'email' => 'michelle.white@student.com', 'role' => 'student'],
        ['name' => 'Kevin Harris', 'email' => 'kevin.harris@student.com', 'role' => 'student'],
        ['name' => 'Rachel Clark', 'email' => 'rachel.clark@student.com', 'role' => 'student'],
        ['name' => 'Steven Lewis', 'email' => 'steven.lewis@student.com', 'role' => 'student'],
        ['name' => 'Nicole Walker', 'email' => 'nicole.walker@student.com', 'role' => 'student'],
        ['name' => 'Brian Hall', 'email' => 'brian.hall@student.com', 'role' => 'student'],
        ['name' => 'Laura Allen', 'email' => 'laura.allen@student.com', 'role' => 'student'],
        ['name' => 'Mark Young', 'email' => 'mark.young@student.com', 'role' => 'student']
    ];

    // Insert users
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
    $insertedUsers = [];
    
    foreach ($users as $user) {
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
        
        $userStmt->execute([
            $user['name'],
            $user['email'],
            $hashedPassword,
            $user['role'],
            $createdAt
        ]);
        
        $insertedUsers[] = [
            'id' => $pdo->lastInsertId(),
            'role' => $user['role']
        ];
    }

    // Sample courses data
    $courses = [
        [
            'title' => 'Introduction to Web Development',
            'description' => 'Learn the fundamentals of HTML, CSS, and JavaScript to build modern websites.',
            'category' => 'Web Development',
            'level' => 'beginner',
            'price' => 99.99,
            'status' => 'published'
        ],
        [
            'title' => 'Advanced React Development',
            'description' => 'Master React hooks, context, and advanced patterns for building scalable applications.',
            'category' => 'Web Development',
            'level' => 'advanced',
            'price' => 199.99,
            'status' => 'published'
        ],
        [
            'title' => 'Python for Data Science',
            'description' => 'Learn Python programming with focus on data analysis, pandas, and machine learning.',
            'category' => 'Data Science',
            'level' => 'intermediate',
            'price' => 149.99,
            'status' => 'published'
        ],
        [
            'title' => 'Digital Marketing Fundamentals',
            'description' => 'Complete guide to digital marketing including SEO, social media, and analytics.',
            'category' => 'Marketing',
            'level' => 'beginner',
            'price' => 79.99,
            'status' => 'published'
        ],
        [
            'title' => 'Mobile App Development with Flutter',
            'description' => 'Build cross-platform mobile applications using Flutter and Dart.',
            'category' => 'Mobile Development',
            'level' => 'intermediate',
            'price' => 179.99,
            'status' => 'published'
        ],
        [
            'title' => 'Machine Learning Basics',
            'description' => 'Introduction to machine learning algorithms and practical implementation.',
            'category' => 'Data Science',
            'level' => 'intermediate',
            'price' => 229.99,
            'status' => 'published'
        ],
        [
            'title' => 'UI/UX Design Principles',
            'description' => 'Learn design thinking, user research, and creating intuitive user interfaces.',
            'category' => 'Design',
            'level' => 'beginner',
            'price' => 119.99,
            'status' => 'published'
        ],
        [
            'title' => 'Database Design and SQL',
            'description' => 'Master database design principles and advanced SQL queries.',
            'category' => 'Database',
            'level' => 'intermediate',
            'price' => 139.99,
            'status' => 'published'
        ],
        [
            'title' => 'Cloud Computing with AWS',
            'description' => 'Learn Amazon Web Services and cloud architecture fundamentals.',
            'category' => 'Cloud Computing',
            'level' => 'advanced',
            'price' => 299.99,
            'status' => 'published'
        ],
        [
            'title' => 'Cybersecurity Fundamentals',
            'description' => 'Essential cybersecurity concepts and practical security measures.',
            'category' => 'Security',
            'level' => 'beginner',
            'price' => 159.99,
            'status' => 'published'
        ]
    ];

    // Get instructor IDs
    $instructors = array_filter($insertedUsers, function($user) {
        return $user['role'] === 'instructor';
    });
    $instructorIds = array_column($instructors, 'id');

    // Insert courses
    $courseStmt = $pdo->prepare("INSERT INTO courses (title, description, instructor_id, category, level, price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertedCourses = [];
    
    foreach ($courses as $course) {
        $instructorId = $instructorIds[array_rand($instructorIds)];
        $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'));
        
        $courseStmt->execute([
            $course['title'],
            $course['description'],
            $instructorId,
            $course['category'],
            $course['level'],
            $course['price'],
            $course['status'],
            $createdAt
        ]);
        
        $insertedCourses[] = $pdo->lastInsertId();
    }

    // Create enrollments
    $students = array_filter($insertedUsers, function($user) {
        return $user['role'] === 'student';
    });
    $studentIds = array_column($students, 'id');

    $enrollmentStmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, enrolled_at, status) VALUES (?, ?, ?, ?)");
    
    // Create random enrollments (each student enrolls in 2-5 courses)
    foreach ($studentIds as $studentId) {
        $numEnrollments = rand(2, 5);
        $selectedCourses = array_rand(array_flip($insertedCourses), $numEnrollments);
        
        if (!is_array($selectedCourses)) {
            $selectedCourses = [$selectedCourses];
        }
        
        foreach ($selectedCourses as $courseId) {
            $enrolledAt = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
            $status = rand(1, 10) > 2 ? 'active' : 'completed'; // 80% active, 20% completed
            
            $enrollmentStmt->execute([
                $studentId,
                $courseId,
                $enrolledAt,
                $status
            ]);
        }
    }

    // Create sample quizzes
    $quizStmt = $pdo->prepare("INSERT INTO quizzes (course_id, title, description, questions, created_at) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($insertedCourses as $index => $courseId) {
        if ($index < 5) { // Add quizzes to first 5 courses
            $quizTitle = "Quiz: " . $courses[$index]['title'];
            $questions = json_encode([
                [
                    'question' => 'What is the main topic of this course?',
                    'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                    'correct' => 0
                ],
                [
                    'question' => 'Which skill level is required?',
                    'options' => ['Beginner', 'Intermediate', 'Advanced', 'Expert'],
                    'correct' => 1
                ]
            ]);
            
            $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
            
            $quizStmt->execute([
                $courseId,
                $quizTitle,
                'Assessment quiz for ' . $courses[$index]['title'],
                $questions,
                $createdAt
            ]);
        }
    }

    // Update settings with more realistic values
    $settingsData = [
        'site_name' => 'LMS Platform',
        'site_description' => 'Professional Learning Management System',
        'max_students_per_course' => '50',
        'default_course_price' => '99.99',
        'currency' => 'EUR',
        'timezone' => 'Europe/Berlin',
        'email_notifications' => 'true',
        'auto_enrollment' => 'false'
    ];

    $settingStmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    foreach ($settingsData as $key => $value) {
        $settingStmt->execute([$key, $value, $value]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sample data seeded successfully!',
        'stats' => [
            'users_added' => count($users),
            'courses_added' => count($courses),
            'enrollments_created' => count($studentIds) * 3, // approximate
            'quizzes_created' => 5
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Seeding failed: ' . $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>
