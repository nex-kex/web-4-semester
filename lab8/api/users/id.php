<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/functions.php';

$path = $_SERVER['PATH_INFO'] ?? '';
$id = trim($path, '/');

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// GET - получение данных пользователя (публично)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = getGymDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, phone, email, comment, status, created_at FROM gym_applications WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $user]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// PUT - обновление данных (требует авторизации)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    session_start();
    if (!isset($_SESSION['gym_user_id']) || $_SESSION['gym_user_id'] != $id) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];

    $errors = validateGymForm($data);

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    try {
        $pdo = getGymDBConnection();

        $stmt = $pdo->prepare("
            UPDATE gym_applications
            SET name = ?, phone = ?, email = ?, comment = ?, status = 'processed'
            WHERE id = ?
        ");

        $stmt->execute([
            trim($data['name']),
            trim($data['phone']),
            trim($data['email']),
            trim($data['comment'] ?? ''),
            $id
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        // Обновляем имя в сессии
        $_SESSION['gym_user_name'] = trim($data['name']);

        $stmt = $pdo->prepare("SELECT id, login, name, phone, email, comment, status FROM gym_applications WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $user]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>