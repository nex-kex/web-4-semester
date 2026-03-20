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

// Получаем данные
$input = file_get_contents('php://input');
$data = [];

if (!empty($input) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $data = json_decode($input, true) ?? [];
} else {
    $data = $_POST;
}

// Валидация
$errors = [];
$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$email = trim($data['email'] ?? '');
$comment = trim($data['comment'] ?? '');

if (empty($name)) {
    $errors['name'] = 'Укажите имя';
} elseif (strlen($name) > 100) {
    $errors['name'] = 'Имя не может быть длиннее 100 символов';
}

if (empty($phone)) {
    $errors['phone'] = 'Укажите телефон';
} elseif (strlen($phone) > 20) {
    $errors['phone'] = 'Телефон не может быть длиннее 20 символов';
}

if (empty($email)) {
    $errors['email'] = 'Укажите email';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email';
} elseif (strlen($email) > 100) {
    $errors['email'] = 'Email не может быть длиннее 100 символов';
}

if (strlen($comment) > 5000) {
    $errors['comment'] = 'Комментарий слишком длинный';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Validation failed',
        'errors' => $errors
    ]);
    exit;
}

try {
    $pdo = getGymDBConnection();

    // Сохраняем в таблицу gym_feedback
    $stmt = $pdo->prepare("
        INSERT INTO gym_feedback (name, phone, email, comment, status)
        VALUES (?, ?, ?, ?, 'new')
    ");

    $result = $stmt->execute([$name, $phone, $email, $comment]);

    if (!$result) {
        throw new Exception('Ошибка при сохранении');
    }

    $feedbackId = $pdo->lastInsertId();

    // Отправляем успешный ответ
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.',
        'data' => ['id' => $feedbackId]
    ]);

} catch (PDOException $e) {
    error_log("Feedback DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка базы данных. Пожалуйста, попробуйте позже.'
    ]);
} catch (Exception $e) {
    error_log("Feedback error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Произошла ошибка. Пожалуйста, попробуйте позже.'
    ]);
}
?>