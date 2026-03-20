<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/auth.php';

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'data' => $user]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
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
            $user['id']
        ]);

        $_SESSION['gym_user_name'] = trim($data['name']);

        $stmt = $pdo->prepare("SELECT id, login, name, phone, email, comment, status FROM gym_applications WHERE id = ?");
        $stmt->execute([$user['id']]);
        $updatedUser = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $updatedUser]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>