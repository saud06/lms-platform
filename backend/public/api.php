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
$path = $_GET['path'] ?? '';
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

// Simple authentication check
function checkAuth($pdo) {
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
    switch ($path) {
        case 'admin/settings':
            if ($method === 'GET') {
                // Get settings
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
                if (!checkAuth($pdo)) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit();
                }
                
                // Update settings
                foreach ($data as $key => $value) {
                    $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
                    $stmt->execute([$key, $value, $value]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Settings updated successfully'
                ]);
            }
            break;
            
        case 'courses':
            if ($method === 'GET') {
                $stmt = $pdo->query("SELECT c.*, u.name as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.id WHERE c.status = 'published'");
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'courses' => $courses
                ]);
            }
            break;
            
        case 'users':
            if (!checkAuth($pdo)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            if ($method === 'GET') {
                $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'users' => $users
                ]);
            }
            break;
            
        case 'me':
            if (!checkAuth($pdo)) {
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
            break;
            
        case 'test':
            echo json_encode([
                'success' => true,
                'message' => 'PHP API is working!',
                'method' => $method,
                'path' => $path,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint not found',
                'path' => $path,
                'method' => $method,
                'available_endpoints' => [
                    'GET /api.php?path=test',
                    'GET /api.php?path=courses',
                    'GET /api.php?path=users',
                    'GET /api.php?path=me',
                    'GET /api.php?path=admin/settings',
                    'PUT /api.php?path=admin/settings'
                ]
            ]);
    }
    
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
