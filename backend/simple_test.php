<?php
// Simple PHP server test
$host = '127.0.0.1';
$port = 8001;

echo "Starting simple PHP server on {$host}:{$port}\n";

// Create a simple HTTP server
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, $host, $port);
socket_listen($socket);

echo "Server listening...\n";

while (true) {
    $client = socket_accept($socket);
    
    $request = socket_read($client, 1024);
    
    $response = "HTTP/1.1 200 OK\r\n";
    $response .= "Content-Type: application/json\r\n";
    $response .= "Access-Control-Allow-Origin: *\r\n";
    $response .= "\r\n";
    $response .= json_encode(['message' => 'Simple PHP server working!', 'timestamp' => date('Y-m-d H:i:s')]);
    
    socket_write($client, $response);
    socket_close($client);
}

socket_close($socket);
?>
