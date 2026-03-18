<?php
$db_host = 'localhost';
$db_name = 'u82269';
$db_user = 'u82269';
$db_pass = '8571433';

// Функция генерации уникального логина
function generateLogin($full_name) {
    // Берем первую букву имени и фамилию транслитом
    $nameParts = explode(' ', trim($full_name));
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    // Простая транслитерация
    $translit = [
        'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 'д'=>'d', 'е'=>'e', 'ё'=>'e',
        'ж'=>'zh', 'з'=>'z', 'и'=>'i', 'й'=>'y', 'к'=>'k', 'л'=>'l', 'м'=>'m',
        'н'=>'n', 'о'=>'o', 'п'=>'p', 'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u',
        'ф'=>'f', 'х'=>'h', 'ц'=>'ts', 'ч'=>'ch', 'ш'=>'sh', 'щ'=>'sch', 'ъ'=>'',
        'ы'=>'y', 'ь'=>'', 'э'=>'e', 'ю'=>'yu', 'я'=>'ya'
    ];

    $lastNameTrans = strtr(mb_strtolower($lastName), $translit);
    $firstInitial = mb_substr($firstName, 0, 1);

    $baseLogin = $lastNameTrans . $firstInitial;
    $baseLogin = preg_replace('/[^a-z0-9]/', '', $baseLogin);

    // Добавляем случайное число для уникальности
    return $baseLogin . rand(100, 999);
}

// Функция валидации (та же)
function validateForm($data) {
    $errors = [];
    $fieldErrors = [];

    // ФИО
    $full_name = trim($data['full_name'] ?? '');
    if (empty($full_name)) {
        $errors[] = 'Укажите ФИО';
        $fieldErrors['full_name'] = 'Поле обязательно для заполнения';
    } elseif (strlen($full_name) > 150) {
        $errors[] = 'ФИО не может быть длиннее 150 символов';
        $fieldErrors['full_name'] = 'Максимальная длина 150 символов';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $full_name)) {
        $errors[] = 'ФИО может содержать только буквы, пробелы и дефис';
        $fieldErrors['full_name'] = 'Используйте только буквы, пробелы и дефис';
    }

    // Телефон
    $phone = trim($data['phone'] ?? '');
    if (empty($phone)) {
        $errors[] = 'Укажите телефон';
        $fieldErrors['phone'] = 'Поле обязательно для заполнения';
    } elseif (strlen($phone) > 20) {
        $errors[] = 'Телефон не может быть длиннее 20 символов';
        $fieldErrors['phone'] = 'Максимальная длина 20 символов';
    } elseif (!preg_match('/^[\d\s\-\(\)\+]+$/', $phone)) {
        $errors[] = 'Телефон содержит недопустимые символы';
        $fieldErrors['phone'] = 'Используйте только цифры, пробелы, дефисы, скобки и знак +';
    }

    // Email
    $email = trim($data['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Укажите email';
        $fieldErrors['email'] = 'Поле обязательно для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
        $fieldErrors['email'] = 'Введите корректный email (пример: name@domain.com)';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email не может быть длиннее 100 символов';
        $fieldErrors['email'] = 'Максимальная длина 100 символов';
    }

    // Дата рождения
    $birth_date = $data['birth_date'] ?? '';
    if (empty($birth_date)) {
        $errors[] = 'Укажите дату рождения';
        $fieldErrors['birth_date'] = 'Поле обязательно для заполнения';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        $errors[] = 'Некорректный формат даты';
        $fieldErrors['birth_date'] = 'Используйте формат ГГГГ-ММ-ДД';
    } else {
        $dateParts = explode('-', $birth_date);
        if (!checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
            $errors[] = 'Некорректная дата';
            $fieldErrors['birth_date'] = 'Укажите существующую дату';
        }
    }

    // Пол
    $gender = $data['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'])) {
        $errors[] = 'Укажите пол';
        $fieldErrors['gender'] = 'Выберите один из вариантов';
    }

    // Языки
    $languages = $data['languages'] ?? [];
    if (empty($languages)) {
        $errors[] = 'Выберите хотя бы один язык программирования';
        $fieldErrors['languages'] = 'Выберите 1 или более языков';
    } else {
        $validIds = range(1, 12);
        foreach ($languages as $lang) {
            if (!in_array((int)$lang, $validIds)) {
                $errors[] = 'Выбран недопустимый язык';
                $fieldErrors['languages'] = 'Выберите языки из списка';
                break;
            }
        }
    }

    // Биография
    $biography = trim($data['biography'] ?? '');
    if (empty($biography)) {
        $errors[] = 'Заполните биографию';
        $fieldErrors['biography'] = 'Поле обязательно для заполнения';
    }

    // Контракт
    if (!isset($data['contract'])) {
        $errors[] = 'Необходимо согласие с контрактом';
        $fieldErrors['contract'] = 'Необходимо отметить согласие';
    }

    return ['errors' => $errors, 'fieldErrors' => $fieldErrors];
}

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Валидация
        $validation = validateForm($_POST);
        $errors = $validation['errors'];
        $fieldErrors = $validation['fieldErrors'];

        if (!empty($errors)) {
            setcookie('form_errors', json_encode($fieldErrors), 0, '/');
            setcookie('form_data', json_encode($_POST), time() + 365*24*60*60, '/');
            $errorMsg = urlencode('Пожалуйста, исправьте ошибки в форме');
            header("Location: form.php?error=$errorMsg");
            exit;
        }

        // Подключение к БД
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERR

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

        // При успехе сохраняем данные в cookies на год
        setcookie('form_data', json_encode($_POST), time() + 365*24*60*60, '/');

        // Удаляем ошибки если были
        setcookie('form_errors', '', time() - 3600, '/');

        // Успех
        header("Location: form.php?success=1&id=$application_id");
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Сохраняем данные при ошибке БД
        setcookie('form_data', json_encode($_POST), time() + 365*24*60*60, '/');

        $errorMsg = urlencode('Ошибка базы данных: ' . $e->getMessage());
        header("Location: form.php?error=$errorMsg");
        exit;
    }
} else {
    header('Location: form.php');
    exit;
}