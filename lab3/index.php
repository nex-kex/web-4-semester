<?php
$db_host = 'localhost';
$db_name = 'u82269';
$db_user = 'u82269';
$db_pass = '8571433';

function validateForm($data) {
    $errors = [];

    // ФИО
    $full_name = trim($data['full_name'] ?? '');
    if (empty($full_name)) {
        $errors[] = 'Укажите ФИО';
    } elseif (strlen($full_name) > 150) {
        $errors[] = 'ФИО не может быть длиннее 150 символов';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $full_name)) {
        $errors[] = 'ФИО может содержать только буквы, пробелы и дефис';
    }

    // Телефон
    $phone = trim($data['phone'] ?? '');
    if (empty($phone)) {
        $errors[] = 'Укажите телефон';
    } elseif (strlen($phone) > 20) {
        $errors[] = 'Телефон не может быть длиннее 20 символов';
    }

    // Email
    $email = trim($data['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Укажите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email не может быть длиннее 100 символов';
    }

    // Дата рождения
    $birth_date = $data['birth_date'] ?? '';
    if (empty($birth_date)) {
        $errors[] = 'Укажите дату рождения';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        $errors[] = 'Некорректный формат даты';
    }

    // Пол
    $gender = $data['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'])) {
        $errors[] = 'Укажите пол';
    }

    // Языки
    $languages = $data['languages'] ?? [];
    if (empty($languages)) {
        $errors[] = 'Выберите хотя бы один язык программирования';
    } else {
        $validIds = range(1, 12);
        foreach ($languages as $lang) {
            if (!in_array((int)$lang, $validIds)) {
                $errors[] = 'Выбран недопустимый язык';
                break;
            }
        }
    }

    // Биография
    $biography = trim($data['biography'] ?? '');
    if (empty($biography)) {
        $errors[] = 'Заполните биографию';
    }

    // Контракт
    if (!isset($data['contract'])) {
        $errors[] = 'Необходимо согласие с контрактом';
    }

    return $errors;
}

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Подключение к БД
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Валидация
        $errors = validateForm($_POST);

        if (!empty($errors)) {
            $errorMsg = urlencode(implode(', ', $errors));
            header("Location: form.php?error=$errorMsg");
            exit;
        }

        // Начинаем транзакцию
        $pdo->beginTransaction();

        // Вставляем основную анкету
        $stmt = $pdo->prepare("
            INSERT INTO application (full_name, phone, email, birth_date, gender, biography, contract_accepted)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            trim($_POST['full_name']),
            trim($_POST['phone']),
            trim($_POST['email']),
            $_POST['birth_date'],
            $_POST['gender'],
            trim($_POST['biography']),
            1
        ]);

        $application_id = $pdo->lastInsertId();

        // Вставляем языки
        $langStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($_POST['languages'] as $lang_id) {
            $langStmt->execute([$application_id, $lang_id]);
        }

        // Подтверждаем транзакцию
        $pdo->commit();

        // Успех
        header("Location: form.php?success=1&id=$application_id");
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMsg = urlencode('Ошибка БД: ' . $e->getMessage());
        header("Location: form.php?error=$errorMsg");
        exit;
    }
} else {
    header('Location: form.php');
    exit;
}