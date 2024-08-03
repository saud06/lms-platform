<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
            // Get dashboard statistics
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $courseCount = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
            $enrollmentCount = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_users' => (int)$userCount,
                    'total_courses' => (int)$courseCount,
                    'total_enrollments' => (int)$enrollmentCount,
                    'active_users' => (int)$userCount // For demo
                ]
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
            $stmt = $pdo->query("SELECT c.*, u.name as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.id ORDER BY c.created_at DESC");
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return courses array directly (frontend expects array, not object)
            echo json_encode($courses);
            
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
            $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return users array directly (frontend expects array, not object)
            echo json_encode($users);
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
            $stmt = $pdo->query("SELECT c.*, u.name as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.id WHERE c.status = 'published' ORDER BY c.created_at DESC");
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return courses array directly (frontend expects array, not object)
            echo json_encode($courses);
            
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
            $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return users array directly (frontend expects array, not object)
            echo json_encode($users);
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
    
    // Handle test
    if ($path === 'test') {
        echo json_encode([
            'success' => true,
            'message' => 'PHP API is working!',
            'method' => $method,
            'path' => $path,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
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
            'POST /api/login',
            'GET /api/test',
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
