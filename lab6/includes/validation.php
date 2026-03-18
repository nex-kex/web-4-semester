<?php
// Функции валидации (DRY - вынесено из index.php)
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
?>