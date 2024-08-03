<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['email']) || !isset($data['password'])) {
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
        
    } else {
        echo json_encode(['message' => 'Direct PHP login endpoint - use POST method']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
