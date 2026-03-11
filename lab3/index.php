<?php
$db_host = 'localhost';
$db_name = 'u82269';
$db_user = 'u82269';
$db_pass = '8571433';

// Функция валидации данных
function validateForm($data) {
    $errors = [];

    // Валидация ФИО
    $full_name = trim($data['full_name'] ?? '');
    if (empty($full_name)) {
        $errors[] = 'Поле ФИО обязательно';
    } elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s-]+$/u', $full_name)) {
        $errors[] = 'ФИО должно содержать только буквы, пробелы и дефисы';
    } elseif (mb_strlen($full_name) > 150) {
        $errors[] = 'ФИО не должно превышать 150 символов';
    }

    // Валидация телефона
    $phone = trim($data['phone'] ?? '');
    if (empty($phone)) {
        $errors[] = 'Поле телефон обязательно';
    } elseif (!preg_match('/^[\+\-\s\(\)0-9]{10,20}$/', $phone)) {
        $errors[] = 'Телефон должен содержать только цифры, пробелы, дефисы, скобки и знак +';
    }

    // Валидация email
    $email = trim($data['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Поле email обязательно';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат email';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email не должен превышать 100 символов';
    }

    // Валидация даты рождения
    $birth_date = $data['birth_date'] ?? '';
    if (empty($birth_date)) {
        $errors[] = 'Поле дата рождения обязательно';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date) {
            $errors[] = 'Некорректный формат даты';
        } elseif ($date > new DateTime()) {
            $errors[] = 'Дата рождения не может быть в будущем';
        }
    }

    // Валидация пола
    $gender = $data['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'])) {
        $errors[] = 'Выберите корректное значение пола';
    }

    // Валидация языков программирования
    $languages = $data['languages'] ?? [];
    if (empty($languages)) {
        $errors[] = 'Выберите хотя бы один язык программирования';
    } else {
        // Проверка, что все выбранные языки существуют в БД
        $validLanguages = [1,2,3,4,5,6,7,8,9,10,11,12]; // ID из таблицы programming_languages
        foreach ($languages as $lang) {
            if (!in_array((int)$lang, $validLanguages)) {
                $errors[] = 'Выбран недопустимый язык программирования';
                break;
            }
        }
    }

    // Валидация биографии
    $biography = trim($data['biography'] ?? '');
    if (empty($biography)) {
        $errors[] = 'Поле биография обязательно';
    }

    // Валидация чекбокса
    if (!isset($data['contract'])) {
        $errors[] = 'Необходимо подтвердить ознакомление с контрактом';
    }

    return $errors;
}

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Подключение к БД
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Валидация данных
        $errors = validateForm($_POST);

        if (!empty($errors)) {
            $errorMessage = urlencode(implode('; ', $errors));
            header("Location: form.php?error=$errorMessage");
            exit;
        }

        // Начинаем транзакцию
        $pdo->beginTransaction();

        // Подготовка данных
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $birth_date = $_POST['birth_date'];
        $gender = $_POST['gender'];
        $biography = trim($_POST['biography']);
        $contract = 1; // Чекбокс отмечен

        // Вставка в таблицу application
        $stmt = $pdo->prepare("
            INSERT INTO application (full_name, phone, email, birth_date, gender, biography, contract_accepted)
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract)
        ");

        $stmt->execute([
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':email' => $email,
            ':birth_date' => $birth_date,
            ':gender' => $gender,
            ':biography' => $biography,
            ':contract' => $contract
        ]);

        $application_id = $pdo->lastInsertId();

        // Вставка выбранных языков в таблицу связи
        $langStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");

        foreach ($_POST['languages'] as $language_id) {
            $langStmt->execute([$application_id, $language_id]);
        }

        // Подтверждаем транзакцию
        $pdo->commit();

        // Перенаправление с сообщением об успехе
        header("Location: form.php?success=1&id=$application_id");
        exit;

    } catch (PDOException $e) {
        // Откат транзакции в случае ошибки
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $errorMessage = urlencode('Ошибка базы данных: ' . $e->getMessage());
        header("Location: form.php?error=$errorMessage");
        exit;
    }
} else {
    // Если не POST-запрос, перенаправляем на форму
    header('Location: form.php');
    exit;
}