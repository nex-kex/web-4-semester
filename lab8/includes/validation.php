<?php
function validateGymForm($data) {
    $errors = [];

    // Имя
    $name = trim($data['name'] ?? '');
    if (empty($name)) {
        $errors['name'] = 'Укажите имя';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Имя не может быть длиннее 100 символов';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $name)) {
        $errors['name'] = 'Имя может содержать только буквы, пробелы и дефис';
    }

    // Телефон
    $phone = trim($data['phone'] ?? '');
    if (empty($phone)) {
        $errors['phone'] = 'Укажите телефон';
    } elseif (strlen($phone) > 20) {
        $errors['phone'] = 'Телефон не может быть длиннее 20 символов';
    } elseif (!preg_match('/^[\d\s\-\(\)\+]+$/', $phone)) {
        $errors['phone'] = 'Используйте только цифры, пробелы, дефисы, скобки и знак +';
    }

    // Email
    $email = trim($data['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'Укажите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email не может быть длиннее 100 символов';
    }

    // Комментарий (опционально)
    if (isset($data['comment']) && strlen($data['comment']) > 5000) {
        $errors['comment'] = 'Комментарий слишком длинный';
    }

    return $errors;
}

function generateGymLogin($name) {
    // Транслитерация имени
    $translit = [
        'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 'д'=>'d', 'е'=>'e', 'ё'=>'e',
        'ж'=>'zh', 'з'=>'z', 'и'=>'i', 'й'=>'y', 'к'=>'k', 'л'=>'l', 'м'=>'m',
        'н'=>'n', 'о'=>'o', 'п'=>'p', 'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u',
        'ф'=>'f', 'х'=>'h', 'ц'=>'ts', 'ч'=>'ch', 'ш'=>'sh', 'щ'=>'sch', 'ъ'=>'',
        'ы'=>'y', 'ь'=>'', 'э'=>'e', 'ю'=>'yu', 'я'=>'ya',
        'А'=>'a', 'Б'=>'b', 'В'=>'v', 'Г'=>'g', 'Д'=>'d', 'Е'=>'e', 'Ё'=>'e',
        'Ж'=>'zh', 'З'=>'z', 'И'=>'i', 'Й'=>'y', 'К'=>'k', 'Л'=>'l', 'М'=>'m',
        'Н'=>'n', 'О'=>'o', 'П'=>'p', 'Р'=>'r', 'С'=>'s', 'Т'=>'t', 'У'=>'u',
        'Ф'=>'f', 'Х'=>'h', 'Ц'=>'ts', 'Ч'=>'ch', 'Ш'=>'sh', 'Щ'=>'sch', 'Ъ'=>'',
        'Ы'=>'y', 'Ь'=>'', 'Э'=>'e', 'Ю'=>'yu', 'Я'=>'ya'
    ];

    $nameParts = explode(' ', trim($name));
    $firstName = $nameParts[0] ?? '';

    $transliterated = strtr($firstName, $translit);
    $transliterated = preg_replace('/[^a-z]/i', '', $transliterated);
    $transliterated = strtolower($transliterated);

    if (empty($transliterated)) {
        $transliterated = 'user';
    }

    return $transliterated . rand(100, 999);
}
?>