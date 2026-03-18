<?php
require_once __DIR__ . '/../config/database.php';

// Получение списка всех пользователей
function getAllUsers() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*,
               GROUP_CONCAT(pl.name SEPARATOR ', ') as languages_list
        FROM application a
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN programming_languages pl ON al.language_id = pl.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ");
    return $stmt->fetchAll();
}

// Получение данных пользователя по ID
function getUserById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT a.*,
               GROUP_CONCAT(al.language_id) as language_ids
        FROM application a
        LEFT JOIN application_languages al ON a.id = al.application_id
        WHERE a.id = ?
        GROUP BY a.id
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Получение статистики по языкам
function getLanguageStats() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT pl.id, pl.name, COUNT(al.application_id) as user_count
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.id, pl.name
        ORDER BY user_count DESC, pl.name
    ");
    return $stmt->fetchAll();
}

// Получение списка языков для формы
function getLanguages() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, name FROM programming_languages ORDER BY name");
    return $stmt->fetchAll();
}

// Генерация логина
function generateLogin($full_name) {
    $nameParts = explode(' ', trim($full_name));
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    $translit = [
        'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 'д'=>'d', 'е'=>'e', 'ё'=>'e',
        'ж'=>'zh', 'з'=>'z', 'и'=>'i', 'й'=>'y', 'к'=>'k', 'л'=>'l', 'м'=>'m',
        'н'=>'n', 'о'=>'o', 'п'=>'p', 'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u',
        'ф'=>'f', 'х'=>'h', 'ц'=>'ts', 'ч'=>'ch', 'ш'=>'sh', 'щ'=>'sch', 'ъ'=>'',
        'ы'=>'y', 'ь'=>'', 'э'=>'e', 'ю'=>'yu', 'я'=>'ya',
        'А'=>'A', 'Б'=>'B', 'В'=>'V', 'Г'=>'G', 'Д'=>'D', 'Е'=>'E', 'Ё'=>'E',
        'Ж'=>'Zh', 'З'=>'Z', 'И'=>'I', 'Й'=>'Y', 'К'=>'K', 'Л'=>'L', 'М'=>'M',
        'Н'=>'N', 'О'=>'O', 'П'=>'P', 'Р'=>'R', 'С'=>'S', 'Т'=>'T', 'У'=>'U',
        'Ф'=>'F', 'Х'=>'H', 'Ц'=>'Ts', 'Ч'=>'Ch', 'Ш'=>'Sh', 'Щ'=>'Sch', 'Ъ'=>'',
        'Ы'=>'Y', 'Ь'=>'', 'Э'=>'E', 'Ю'=>'Yu', 'Я'=>'Ya'
    ];

    $lastNameTrans = strtr($lastName, $translit);

    if (function_exists('mb_substr')) {
        $firstInitial = mb_substr($firstName, 0, 1, 'UTF-8');
    } else {
        $firstInitial = substr($firstName, 0, 1);
    }

    if (!$firstInitial && strlen($firstName) > 0) {
        $firstInitial = $firstName[0];
    }

    $baseLogin = strtolower($lastNameTrans . $firstInitial);
    $baseLogin = preg_replace('/[^a-z0-9]/', '', $baseLogin);

    if (empty($baseLogin)) {
        $baseLogin = 'user' . rand(1000, 9999);
    }

    return $baseLogin . rand(100, 999);
}
?>