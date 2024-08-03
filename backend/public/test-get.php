<?php
header('Content-Type: application/json');

$response = [
    'success' => true,
    'message' => 'Direct PHP GET test successful',
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
