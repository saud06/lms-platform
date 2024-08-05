<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection with optimization
$host = $_ENV['DB_HOST'] ?? 'nozomi.proxy.rlwy.net';
$port = $_ENV['DB_PORT'] ?? '55229';
$database = $_ENV['DB_DATABASE'] ?? 'railway';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? 'yqZGCjlCsuPmeEzlaDxWmIEmHllcujWJ';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => true, // Use persistent connections
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_TIMEOUT => 30, // Increase timeout
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    // Test connection and warm up
    $pdo->query("SELECT 1")->fetchColumn();
    
} catch (Exception $e) {
    // Retry connection once
    try {
        sleep(2);
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_TIMEOUT => 30,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
        $pdo->query("SELECT 1")->fetchColumn();
    } catch (Exception $e2) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e2->getMessage()]);
        exit();
    }
}

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$path = '';

// Extract path from REQUEST_URI
if (strpos($requestUri, '/api/') !== false) {
    $path = substr($requestUri, strpos($requestUri, '/api/') + 5);
    // Remove query parameters
    if (strpos($path, '?') !== false) {
        $path = substr($path, 0, strpos($path, '?'));
    }
}

$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

// Simple authentication check
function checkAuth() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        // For demo, just check if token exists and starts with demo_token_
        if (strpos($token, 'demo_token_') === 0) {
            return true;
        }
    }
    return false;
}

// Route handler
try {
    // Handle login endpoint
    if ($path === 'login' && $method === 'POST') {
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and password required']);
            exit();
        }
        
        $email = $data['email'];
        $inputPassword = $data['password'];
        
        // Get user from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
        
        // Verify password
        if (!password_verify($inputPassword, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit();
        }
        
        // Success
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => 'demo_token_' . md5($email . time())
        ]);
        exit();
    }
    
    // Handle admin/settings
    if ($path === 'admin/settings') {
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settingsObj = [];
            foreach ($settings as $setting) {
                $settingsObj[$setting['key']] = $setting['value'];
            }
            
            echo json_encode([
                'success' => true,
                'settings' => $settingsObj
            ]);
            
        } elseif ($method === 'PUT') {
            if (!checkAuth()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            foreach ($data as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        }
        exit();
    }
    
    // Handle dashboard/admin (admin dashboard stats)
    if ($path === 'dashboard/admin') {
        if (!checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        if ($method === 'GET') {
            // Check if we have minimal data, if not, auto-seed
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $courseCount = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
            $enrollmentCount = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
            
            // Auto-seed if data is missing (less than 5 users or 3 courses)
            if ($userCount < 5 || $courseCount < 3) {
                // Create minimal seed data
                $pdo->exec("INSERT IGNORE INTO users (name, email, password, role, created_at) VALUES 
                    ('Admin User', 'admin@lms.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'admin', NOW()),
                    ('Dr. John Smith', 'john.smith@lms.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'instructor', NOW()),
                    ('Student One', 'student1@lms.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'student', NOW()),
                    ('Student Two', 'student2@lms.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'student', NOW()),
                    ('Student Three', 'student3@lms.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'student', NOW())");
                
                $pdo->exec("INSERT IGNORE INTO courses (title, description, instructor_id, category, level, price, status, created_at) VALUES 
                    ('Web Development Basics', 'Learn HTML, CSS, and JavaScript fundamentals', 2, 'Web Development', 'beginner', 99.99, 'published', NOW()),
                    ('Advanced Programming', 'Master advanced programming concepts', 2, 'Programming', 'advanced', 199.99, 'published', NOW()),
                    ('Data Science Intro', 'Introduction to data science and analytics', 2, 'Data Science', 'intermediate', 149.99, 'published', NOW())");
                
                $pdo->exec("INSERT IGNORE INTO enrollments (user_id, course_id, enrolled_at) VALUES 
                    (3, 1, NOW()), (3, 2, NOW()),
                    (4, 1, NOW()), (4, 3, NOW()),
                    (5, 2, NOW()), (5, 3, NOW())");
                
                $pdo->exec("INSERT IGNORE INTO quizzes (course_id, title, description, created_at) VALUES 
                    (1, 'Web Dev Quiz', 'Basic web development assessment', NOW()),
                    (2, 'Programming Quiz', 'Advanced programming assessment', NOW())");
                
                // Refresh counts
                $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $courseCount = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
                $enrollmentCount = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
            }
            
            // Calculate total revenue from enrollments
            $revenueQuery = $pdo->query("
                SELECT COALESCE(SUM(c.price), 0) as total_revenue 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id
            ");
            $totalRevenue = $revenueQuery->fetchColumn();
            
            // Get quiz count
            $quizCount = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
            
            // Get courses with quizzes
            $coursesWithQuizzes = $pdo->query("
                SELECT COUNT(DISTINCT course_id) 
                FROM quizzes
            ")->fetchColumn();
            
            // Get enrollment trends (last 6 months)
            $enrollmentTrends = $pdo->query("
                SELECT 
                    DATE_FORMAT(enrolled_at, '%Y-%m') as month,
                    COUNT(*) as enrollments
                FROM enrollments 
                WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(enrolled_at, '%Y-%m')
                ORDER BY month
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Get top courses by enrollment
            $topCourses = $pdo->query("
                SELECT 
                    c.title,
                    c.price,
                    COUNT(e.id) as enrollment_count
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                GROUP BY c.id, c.title, c.price
                ORDER BY enrollment_count DESC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_users' => (int)$userCount,
                    'total_courses' => (int)$courseCount,
                    'total_enrollments' => (int)$enrollmentCount,
                    'total_revenue' => (float)$totalRevenue,
                    'quiz_courses' => (int)$coursesWithQuizzes,
                    'total_quizzes' => (int)$quizCount,
                    'active_users' => (int)$userCount // For demo
                ],
                'enrollment_trends' => $enrollmentTrends,
                'top_courses' => $topCourses
            ]);
        }
        exit();
    }
    
    // Handle admin/courses (list all courses for admin)
    if ($path === 'admin/courses') {
        if (!checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        if ($method === 'GET') {
            try {
                $stmt = $pdo->prepare("SELECT c.*, COALESCE(u.name, 'Unknown Instructor') as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.id ORDER BY c.created_at DESC");
                $stmt->execute();
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Ensure we always return an array
                if (empty($courses)) {
                    $courses = [];
                }
                
                // Return courses array directly (frontend expects array, not object)
                echo json_encode($courses);
                
            } catch (Exception $e) {
                // Fallback: return empty array instead of error
                echo json_encode([]);
            }
            
        } elseif ($method === 'POST') {
            // Create new course
            $stmt = $pdo->prepare("INSERT INTO courses (title, description, instructor_id, category, level, price, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['title'] ?? 'New Course',
                $data['description'] ?? '',
                $data['instructor_id'] ?? 1,
                $data['category'] ?? 'general',
                $data['level'] ?? 'beginner',
                $data['price'] ?? 0,
                $data['status'] ?? 'draft'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Course created successfully',
                'course_id' => $pdo->lastInsertId()
            ]);
        }
        exit();
    }
    
    // Handle admin/users (list all users for admin)
    if ($path === 'admin/users') {
        if (!checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        if ($method === 'GET') {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Ensure we always return an array
                if (empty($users)) {
                    $users = [];
                }
                
                // Return users array directly (frontend expects array, not object)
                echo json_encode($users);
                
            } catch (Exception $e) {
                // Fallback: return empty array instead of error
                echo json_encode([]);
            }
        }
        exit();
    }
    
    // Handle courses/{id}/quiz
    if (preg_match('/^courses\/(\d+)\/quiz$/', $path, $matches)) {
        $courseId = $matches[1];
        
        if ($method === 'GET') {
            $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id = ?");
            $stmt->execute([$courseId]);
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($quiz) {
                // Return quiz directly (frontend expects object, not wrapped)
                echo json_encode($quiz);
            } else {
                // Return empty quiz structure if none exists
                echo json_encode([
                    'id' => null,
                    'course_id' => $courseId,
                    'title' => '',
                    'description' => '',
                    'questions' => []
                ]);
            }
        }
        exit();
    }
    
    // Handle admin/users/{id}
    if (preg_match('/^admin\/users\/(\d+)$/', $path, $matches)) {
        $userId = $matches[1];
        
        if (!checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        if ($method === 'GET') {
            $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            
        } elseif ($method === 'PUT') {
            // Update user
            $updateFields = [];
            $updateValues = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'email', 'role'])) {
                    $updateFields[] = "`$key` = ?";
                    $updateValues[] = $value;
                }
            }
            
            if (!empty($updateFields)) {
                $updateValues[] = $userId;
                $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateValues);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No valid fields to update'
                ]);
            }
            
        } elseif ($method === 'DELETE') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        }
        exit();
    }
    
    // Handle admin/courses/{id}
    if (preg_match('/^admin\/courses\/(\d+)$/', $path, $matches)) {
        $courseId = $matches[1];
        
        if (!checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        if ($method === 'GET') {
            $stmt = $pdo->prepare("SELECT c.*, u.name as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.id WHERE c.id = ?");
            $stmt->execute([$courseId]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($course) {
                // Return course directly (frontend expects object, not wrapped)
                echo json_encode($course);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Course not found']);
            }
            
        } elseif ($method === 'PUT') {
            // Update course
            $updateFields = [];
            $updateValues = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['title', 'description', 'category', 'level', 'price', 'status'])) {
                    $updateFields[] = "`$key` = ?";
                    $updateValues[] = $value;
                }
            }
            
            if (!empty($updateFields)) {
                $updateValues[] = $courseId;
                $sql = "UPDATE courses SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateValues);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Course updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No valid fields to update'
                ]);
            }
            
        } elseif ($method === 'DELETE') {
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Course deleted successfully'
            ]);
        }
        exit();
    }
    
    // Handle courses (general)
    if ($path === 'courses') {
        if ($method === 'GET') {
            // Ensure we have data, auto-seed if needed
            $courseCount = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'published'")->fetchColumn();
            if ($courseCount < 2) {
                // Auto-seed some courses if missing
                $pdo->exec("INSERT IGNORE INTO courses (title, description, instructor_id, category, level, price, status, created_at) VALUES 
                    ('Web Development Basics', 'Learn HTML, CSS, and JavaScript fundamentals', 1, 'Web Development', 'beginner', 99.99, 'published', NOW()),
                    ('Advanced Programming', 'Master advanced programming concepts', 1, 'Programming', 'advanced', 199.99, 'published', NOW()),
                    ('Data Science Intro', 'Introduction to data science and analytics', 1, 'Data Science', 'intermediate', 149.99, 'published', NOW())");
            }
            
            // Use optimized query with proper timeout handling
            try {
                $stmt = $pdo->prepare("SELECT c.*, COALESCE(u.name, 'Unknown Instructor') as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.id WHERE c.status = 'published' ORDER BY c.created_at DESC");
                $stmt->execute();
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Ensure we always return an array
                if (empty($courses)) {
                    $courses = [];
                }
                
                // Return courses array directly (frontend expects array, not object)
                echo json_encode($courses);
                
            } catch (Exception $e) {
                // Fallback: return empty array instead of error
                echo json_encode([]);
            }
            
        } elseif ($method === 'POST') {
            if (!checkAuth()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            $stmt = $pdo->prepare("INSERT INTO courses (title, description, instructor_id, category, level, price, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['title'] ?? '',
                $data['description'] ?? '',
                $data['instructor_id'] ?? 1,
                $data['category'] ?? '',
                $data['level'] ?? 'beginner',
                $data['price'] ?? 0,
                $data['status'] ?? 'draft'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Course created successfully',
                'course_id' => $pdo->lastInsertId()
            ]);
        }
        exit();
    }
    
    // Handle users
    if ($path === 'users') {
        if (!checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        if ($method === 'GET') {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Ensure we always return an array
                if (empty($users)) {
                    $users = [];
                }
                
                // Return users array directly (frontend expects array, not object)
                echo json_encode($users);
                
            } catch (Exception $e) {
                // Fallback: return empty array instead of error
                echo json_encode([]);
            }
        }
        exit();
    }
    
    // Handle quizzes
    if ($path === 'quizzes') {
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT q.*, c.title as course_title FROM quizzes q LEFT JOIN courses c ON q.course_id = c.id ORDER BY q.created_at DESC");
            $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return quizzes array directly (frontend expects array, not object)
            echo json_encode($quizzes);
        }
        exit();
    }
    
    // Handle me
    if ($path === 'me') {
        if (!checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        // For demo, return admin user
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
        $stmt->execute(['admin@lms.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        exit();
    }
    
    // Handle health check (required by Render)
    if ($path === 'health') {
        try {
            // Check database connection
            $stmt = $pdo->query("SELECT 1");
            echo json_encode([
                'status' => 'healthy',
                'database' => 'connected',
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            http_response_code(503);
            echo json_encode([
                'status' => 'unhealthy',
                'database' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ]);
        }
        exit();
    }
    
    // Handle warmup - pre-loads database connections and caches
    if ($path === 'warmup') {
        try {
            // Warm up database with common queries
            $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
            $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
            
            echo json_encode([
                'status' => 'warmed_up',
                'database' => 'ready',
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'warmup_failed',
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ]);
        }
        exit();
    }
    
    // Handle seed-data
    if ($path === 'seed-data') {
        try {
            // Just get existing users and courses, then create more enrollments
            $existingUsersStmt = $pdo->query("SELECT id, role FROM users WHERE role IN ('instructor', 'student')");
            $insertedUsers = $existingUsersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add a few more users with unique emails
            $newUsers = [
                ['name' => 'Alex Thompson', 'email' => 'alex.thompson' . time() . '@student.com', 'role' => 'student'],
                ['name' => 'Sarah Wilson', 'email' => 'sarah.wilson' . time() . '@student.com', 'role' => 'student'],
                ['name' => 'Mike Johnson', 'email' => 'mike.johnson' . time() . '@student.com', 'role' => 'student'],
                ['name' => 'Emma Davis', 'email' => 'emma.davis' . time() . '@student.com', 'role' => 'student'],
                ['name' => 'John Smith', 'email' => 'john.smith' . time() . '@student.com', 'role' => 'student'],
                ['name' => 'Lisa Brown', 'email' => 'lisa.brown' . time() . '@student.com', 'role' => 'student'],
                ['name' => 'Dr. Robert Chen', 'email' => 'robert.chen' . time() . '@lms.com', 'role' => 'instructor']
            ];

            $userStmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($newUsers as $user) {
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

            // Get existing courses and add a few more
            $existingCoursesStmt = $pdo->query("SELECT id FROM courses");
            $insertedCourses = $existingCoursesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get instructor IDs
            $instructors = array_filter($insertedUsers, function($user) {
                return $user['role'] === 'instructor';
            });
            $instructorIds = array_column($instructors, 'id');

            // Add a few more courses with unique titles
            $newCourses = [
                [
                    'title' => 'Advanced JavaScript ' . time(),
                    'description' => 'Master modern JavaScript ES6+ features and async programming.',
                    'category' => 'Web Development',
                    'level' => 'advanced',
                    'price' => 159.99,
                    'status' => 'published'
                ],
                [
                    'title' => 'Docker & DevOps ' . time(),
                    'description' => 'Learn containerization and modern deployment practices.',
                    'category' => 'DevOps',
                    'level' => 'intermediate',
                    'price' => 189.99,
                    'status' => 'published'
                ],
                [
                    'title' => 'Cybersecurity Basics ' . time(),
                    'description' => 'Essential cybersecurity concepts for developers.',
                    'category' => 'Security',
                    'level' => 'beginner',
                    'price' => 129.99,
                    'status' => 'published'
                ]
            ];

            if (!empty($instructorIds)) {
                $courseStmt = $pdo->prepare("INSERT INTO courses (title, description, instructor_id, category, level, price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                foreach ($newCourses as $course) {
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
            }

            // Create enrollments (only for new students to avoid duplicates)
            $students = array_filter($insertedUsers, function($user) {
                return $user['role'] === 'student';
            });
            $studentIds = array_column($students, 'id');

            // Only create enrollments for students added in this session (to avoid duplicates)
            $newStudentIds = [];
            foreach ($newUsers as $index => $user) {
                if ($user['role'] === 'student') {
                    $newStudentIds[] = $insertedUsers[count($insertedUsers) - count($newUsers) + $index]['id'];
                }
            }

            if (!empty($newStudentIds) && !empty($insertedCourses)) {
                $enrollmentStmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, ?)");
                
                // Create random enrollments for new students only
                foreach ($newStudentIds as $studentId) {
                    $numEnrollments = rand(2, min(4, count($insertedCourses)));
                    $selectedCourses = array_rand(array_flip($insertedCourses), $numEnrollments);
                    
                    if (!is_array($selectedCourses)) {
                        $selectedCourses = [$selectedCourses];
                    }
                    
                    foreach ($selectedCourses as $courseId) {
                        $enrolledAt = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
                        
                        $enrollmentStmt->execute([
                            $studentId,
                            $courseId,
                            $enrolledAt
                        ]);
                    }
                }
            }

            // Add some sample quizzes to courses
            if (!empty($insertedCourses)) {
                $quizStmt = $pdo->prepare("INSERT IGNORE INTO quizzes (course_id, title, description, created_at) VALUES (?, ?, ?, ?)");
                
                // Add quizzes to first 3 courses
                for ($i = 0; $i < min(3, count($insertedCourses)); $i++) {
                    $courseId = $insertedCourses[$i];
                    $quizTitle = "Assessment Quiz " . ($i + 1);
                    $questions = json_encode([
                        [
                            'question' => 'What is the main learning objective of this course?',
                            'options' => ['Understanding basics', 'Advanced concepts', 'Practical application', 'All of the above'],
                            'correct' => 3
                        ],
                        [
                            'question' => 'Which skill level is most appropriate for this course?',
                            'options' => ['Beginner', 'Intermediate', 'Advanced', 'Expert'],
                            'correct' => 1
                        ]
                    ]);
                    
                    $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(1, 20) . ' days'));
                    
                    $quizStmt->execute([
                        $courseId,
                        $quizTitle,
                        'Assessment quiz for course completion',
                        $createdAt
                    ]);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Sample data seeded successfully!',
                'stats' => [
                    'users_added' => count($newUsers),
                    'courses_added' => count($newCourses),
                    'total_users' => count($insertedUsers),
                    'total_courses' => count($insertedCourses),
                    'enrollments_created' => count($newStudentIds) * 2,
                    'quizzes_added' => min(3, count($insertedCourses)),
                    'instructors' => count($instructorIds),
                    'students' => count($studentIds)
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
        exit();
    }
    
    // Default - endpoint not found
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Endpoint not found',
        'path' => $path,
        'method' => $method,
        'request_uri' => $requestUri,
        'available_endpoints' => [
            'GET /api/health',
            'GET /api/warmup',
            'POST /api/login',
            'GET /api/me',
            'GET /api/courses',
            'POST /api/courses',
            'GET /api/courses/{id}/quiz',
            'GET /api/users',
            'GET /api/quizzes',
            'GET /api/dashboard/admin',
            'GET /api/admin/courses',
            'POST /api/admin/courses',
            'GET /api/admin/courses/{id}',
            'PUT /api/admin/courses/{id}',
            'DELETE /api/admin/courses/{id}',
            'GET /api/admin/users',
            'GET /api/admin/users/{id}',
            'PUT /api/admin/users/{id}',
            'DELETE /api/admin/users/{id}',
            'GET /api/admin/settings',
            'PUT /api/admin/settings'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}
?>
