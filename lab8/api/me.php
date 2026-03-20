<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

session_start();

if (!isset($_SESSION['gym_user_id']) || empty($_SESSION['gym_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'redirect' => '/web4/lab8/public/register.html']);
    exit;
}

function getCurrentUser() {
    $pdo = getGymDBConnection();
    $stmt = $pdo->prepare("SELECT id, login, name, phone, email, status FROM gym_applications WHERE id = ?");
    $stmt->execute([$_SESSION['gym_user_id']]);
    return $stmt->fetch();
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

    // Валидация
    $errors = [];
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');

    if (empty($name)) {
        $errors['name'] = 'Укажите имя';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Имя не может быть длиннее 100 символов';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $name)) {
        $errors['name'] = 'Имя может содержать только буквы, пробелы и дефис';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Укажите телефон';
    } elseif (strlen($phone) > 20) {
        $errors['phone'] = 'Телефон не может быть длиннее 20 символов';
    } elseif (!preg_match('/^[\d\s\-\(\)\+]+$/', $phone)) {
        $errors['phone'] = 'Используйте только цифры, пробелы, дефисы, скобки и знак +';
    }

    if (empty($email)) {
        $errors['email'] = 'Укажите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email не может быть длиннее 100 символов';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    try {
        $pdo = getGymDBConnection();

        // Обновляем данные и меняем статус на 'edited'
        $stmt = $pdo->prepare("
            UPDATE gym_applications
            SET name = ?, phone = ?, email = ?, status = 'edited'
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $phone,
            $email,
            $user['id']
        ]);

        // Обновляем имя в сессии
        $_SESSION['gym_user_name'] = $name;

        // Получаем обновленные данные
        $stmt = $pdo->prepare("SELECT id, login, name, phone, email, status FROM gym_applications WHERE id = ?");
        $stmt->execute([$user['id']]);
        $updatedUser = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $updatedUser]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>