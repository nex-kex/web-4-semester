<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

$errors = validateRegistrationForm($data);

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation failed', 'errors' => $errors]);
    exit;
}

try {
    $pdo = getGymDBConnection();

    $login = generateGymLogin($data['name']);

    $stmt = $pdo->prepare("SELECT id FROM gym_applications WHERE login = ?");
    while (true) {
        $stmt->execute([$login]);
        if (!$stmt->fetch()) break;
        $login = generateGymLogin($data['name']) . rand(10, 99);
    }

    $password = bin2hex(random_bytes(4));
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO gym_applications (login, password_hash, name, phone, email, status)
        VALUES (?, ?, ?, ?, ?, 'new')
    ");

    $stmt->execute([
        $login,
        $passwordHash,
        trim($data['name']),
        trim($data['phone']),
        trim($data['email'])
    ]);

    $id = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $id,
            'login' => $login,
            'password' => $password
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>