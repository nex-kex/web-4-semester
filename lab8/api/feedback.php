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
$data = json_decode($input, true) ?? [];

// Если данные пришли как form-data (без JSON)
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// Валидация
$errors = [];
$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$email = trim($data['email'] ?? '');
$comment = trim($data['comment'] ?? '');

// Проверка имени
if (empty($name)) {
    $errors['name'] = 'Укажите имя';
} elseif (strlen($name) > 100) {
    $errors['name'] = 'Имя не может быть длиннее 100 символов';
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $name)) {
    $errors['name'] = 'Имя может содержать только буквы, пробелы и дефис';
}

// Проверка телефона
if (empty($phone)) {
    $errors['phone'] = 'Укажите телефон';
} elseif (strlen($phone) > 20) {
    $errors['phone'] = 'Телефон не может быть длиннее 20 символов';
} elseif (!preg_match('/^[\d\s\-\(\)\+]+$/', $phone)) {
    $errors['phone'] = 'Используйте только цифры, пробелы, дефисы, скобки и знак +';
}

// Проверка email
if (empty($email)) {
    $errors['email'] = 'Укажите email';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email';
} elseif (strlen($email) > 100) {
    $errors['email'] = 'Email не может быть длиннее 100 символов';
}

// Проверка комментария (необязательно, но есть ограничение на длину)
if (strlen($comment) > 5000) {
    $errors['comment'] = 'Комментарий слишком длинный (максимум 5000 символов)';
}

// Если есть ошибки - возвращаем
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

    // Создаем таблицу, если её нет
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS gym_feedback (
            id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) NOT NULL,
            comment TEXT,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Сохраняем заявку в БД
    $stmt = $pdo->prepare("
        INSERT INTO gym_feedback (name, phone, email, comment, status)
        VALUES (?, ?, ?, ?, 'new')
    ");

    $stmt->execute([
        $name,
        $phone,
        $email,
        $comment
    ]);

    $feedbackId = $pdo->lastInsertId();

    // Опционально: отправка email администратору
    $to = 'admin@bull-gym.ru'; // Замените на свой email
    $subject = 'Новая заявка с сайта Bull Gym #' . $feedbackId;
    $message = "Новая заявка с сайта:\n\n";
    $message .= "Имя: $name\n";
    $message .= "Телефон: $phone\n";
    $message .= "Email: $email\n";
    $message .= "Сообщение:\n$comment\n\n";
    $message .= "Дата: " . date('d.m.Y H:i:s') . "\n";
    $message .= "ID заявки: $feedbackId";

    $headers = "From: info@bull-gym.ru\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Отправляем email (может не работать на localhost)
    @mail($to, $subject, $message, $headers);

    // Успешный ответ
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.',
        'data' => [
            'id' => $feedbackId,
            'name' => $name,
            'email' => $email
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка базы данных. Пожалуйста, попробуйте позже.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Произошла ошибка. Пожалуйста, попробуйте позже.'
    ]);
}
?>