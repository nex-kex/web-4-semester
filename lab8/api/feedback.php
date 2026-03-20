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

if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

$errors = validateFeedbackForm($data);

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Validation failed', 'errors' => $errors]);
    exit;
}

try {
    $pdo = getGymDBConnection();

    $stmt = $pdo->prepare("
        INSERT INTO gym_feedback (name, phone, email, comment, status)
        VALUES (?, ?, ?, ?, 'new')
    ");

    $stmt->execute([
        trim($data['name']),
        trim($data['phone']),
        trim($data['email']),
        trim($data['comment'] ?? '')
    ]);

    $feedbackId = $pdo->lastInsertId();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.',
        'data' => ['id' => $feedbackId]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
}
?>