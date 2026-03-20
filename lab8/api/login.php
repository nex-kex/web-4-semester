<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../includes/auth.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

$login = trim($data['login'] ?? '');
$password = $data['password'] ?? '';

if (empty($login) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Логин и пароль обязательны']);
    exit;
}

if (authenticateUser($login, $password)) {
    echo json_encode(['success' => true, 'redirect' => '/lab7/public/profile.html']);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Неверный логин или пароль']);
}
?>