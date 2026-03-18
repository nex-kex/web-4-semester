<?php
header('Content-Type: text/html; charset=UTF-8');

define('DB_HOST', 'localhost');
define('DB_NAME', 'uXXXXX');
define('DB_USER', 'uXXXXX');
define('DB_PASS', 'your_pass');

$validLanguageIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
$validGenders     = ['male', 'female'];

function str_char_len($s) {
    if (function_exists('iconv_strlen')) return iconv_strlen($s, 'UTF-8');
    if (function_exists('mb_strlen'))   return mb_strlen($s, 'UTF-8');
    return strlen($s);
}

function set_error_cookie($name, $message) { setcookie($name, $message, 0, '/'); }
function set_temp_cookie($name, $value)    { setcookie($name, $value,   0, '/'); }
function set_perm_cookie($name, $value)    { setcookie($name, $value, time() + 365 * 24 * 3600, '/'); }
function del_cookie($name)                 { setcookie($name, '', 100000, '/'); }

function get_db() {
    static $db = null;
    if ($db === null) {
        $db = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_PERSISTENT       => true,
                PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $db;
}

function get_session_user() {
    if (empty($_COOKIE[session_name()])) return false;
    if (!session_start())               return false;
    if (empty($_SESSION['login']))      return false;
    return ['uid' => (int)$_SESSION['uid'], 'login' => $_SESSION['login']];
}

// ─── CSRF-защита ───────────────────────────────────────────────
// Генерирует/возвращает токен из сессии.
// Вызывается на GET — токен передаётся в form.php как $csrfToken.
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверяет токен из POST. При несовпадении — 403 и завершение.
function csrf_verify() {
    $submitted = $_POST['csrf_token'] ?? '';
    if (session_status() === PHP_SESSION_NONE) {
        if (empty($_COOKIE[session_name()])) {
            http_response_code(403); exit('403 Forbidden');
        }
        session_start();
    }
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$expected || !hash_equals($expected, $submitted)) {
        http_response_code(403); exit('403 Forbidden: invalid CSRF token');
    }
    // Ротация: каждый запрос получает новый токен
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ══════════════════════════════════════════════════════════════
//  GET — показываем форму
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $messages    = [];
    $errors      = [];
    $values      = [];
    $sessionUser = get_session_user();

    if ($sessionUser) {
        try {
            $db   = get_db();
            $stmt = $db->prepare(
                "SELECT a.*, GROUP_CONCAT(al.language_id) AS lang_ids
                 FROM application a
                 LEFT JOIN application_language al ON al.application_id = a.id
                 WHERE a.id = :id GROUP BY a.id"
            );
            $stmt->execute([':id' => $sessionUser['uid']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $values['fio']       = $row['name'];
                $values['phone']     = $row['phone'];
                $values['email']     = $row['email'];
                $values['birthdate'] = $row['birthdate'];
                $values['gender']    = $row['gender'];
                $values['biography'] = $row['biography'];
                $values['languages'] = $row['lang_ids']
                    ? array_map('intval', explode(',', $row['lang_ids'])) : [];
            }
        } catch (PDOException $e) {
            // [FIX: Information Disclosure] Детали ошибки БД скрыты от пользователя
            error_log('[index.php GET] DB error: ' . $e->getMessage());
            $messages['error_hint'] = 'Ошибка загрузки данных. Попробуйте позже.';
        }

        $fieldMap = [
            'fio' => 'err_fio', 'phone' => 'err_phone', 'email' => 'err_email',
            'birthdate' => 'err_birthdate', 'gender' => 'err_gender',
            'languages' => 'err_languages', 'biography' => 'err_biography',
            'agreed' => 'err_agreed',
        ];
        $anyErr = false;
        foreach ($fieldMap as $field => $cookieName) {
            if (!empty($_COOKIE[$cookieName])) {
                $errors[$field] = $_COOKIE[$cookieName];
                del_cookie($cookieName);
                $anyErr = true;
                $valCookie = 'val_' . $field;
                if (!empty($_COOKIE[$valCookie])) {
                    $values[$field] = ($field === 'languages')
                        ? (json_decode($_COOKIE[$valCookie], true) ?? [])
                        : $_COOKIE[$valCookie];
                    del_cookie($valCookie);
                }
            } else {
                $errors[$field] = '';
            }
        }
        if ($anyErr) $messages['error_hint'] = 'Исправьте ошибки в форме.';

    } else {

        if (!empty($_COOKIE['save'])) {
            del_cookie('save');
            $messages['success'] = 'Данные успешно сохранены!';
            if (!empty($_COOKIE['new_login']) && !empty($_COOKIE['new_pass'])) {
                $messages['credentials'] = sprintf(
                    'Запомните: логин <strong>%s</strong>, пароль <strong>%s</strong>. ' .
                    'Используйте их для <a href="login.php">входа</a> и изменения данных.',
                    htmlspecialchars($_COOKIE['new_login'], ENT_QUOTES),
                    htmlspecialchars($_COOKIE['new_pass'],  ENT_QUOTES)
                );
                del_cookie('new_login');
                del_cookie('new_pass');
            }
        }

        $fieldMap = [
            'fio'       => ['err' => 'err_fio',       'val' => 'val_fio'],
            'phone'     => ['err' => 'err_phone',     'val' => 'val_phone'],
            'email'     => ['err' => 'err_email',     'val' => 'val_email'],
            'birthdate' => ['err' => 'err_birthdate', 'val' => 'val_birthdate'],
            'gender'    => ['err' => 'err_gender',    'val' => 'val_gender'],
            'languages' => ['err' => 'err_languages', 'val' => 'val_languages'],
            'biography' => ['err' => 'err_biography', 'val' => 'val_biography'],
            'agreed'    => ['err' => 'err_agreed',    'val' => null],
        ];

        $anyErr = false;
        foreach ($fieldMap as $field => $keys) {
            if (!empty($_COOKIE[$keys['err']])) {
                $errors[$field] = $_COOKIE[$keys['err']];
                del_cookie($keys['err']);
                $anyErr = true;
                if ($keys['val']) {
                    $values[$field] = $_COOKIE[$keys['val']] ?? '';
                    del_cookie($keys['val']);
                }
            } else {
                $errors[$field] = '';
                if ($keys['val']) $values[$field] = $_COOKIE[$keys['val']] ?? '';
            }
        }

        $values['languages'] = !empty($values['languages'])
            ? (json_decode($values['languages'], true) ?? []) : [];

        if ($anyErr) $messages['error_hint'] = 'Исправьте ошибки в форме.';
    }

    // [FIX: CSRF] Генерируем токен и передаём в шаблон
    $csrfToken = csrf_token();

    include 'form.php';
    exit();
}

// ══════════════════════════════════════════════════════════════
//  POST — сначала CSRF-проверка, затем валидация
// ══════════════════════════════════════════════════════════════

// [FIX: CSRF] Проверяем токен до любой обработки данных
csrf_verify();

$post        = $_POST;
$hasErr      = false;
$sessionUser = get_session_user();

// ── 1. ФИО ────────────────────────────────────────────────────
$fio = trim($post['fio'] ?? '');
if ($fio === '') {
    set_error_cookie('err_fio', 'Укажите ФИО.');
    set_temp_cookie('val_fio', '');
    $hasErr = true;
} elseif (!preg_match('/^[\p{L} \-]+$/u', $fio)) {
    set_error_cookie('err_fio', 'ФИО может содержать только буквы, пробелы и дефисы.');
    set_temp_cookie('val_fio', $fio);
    $hasErr = true;
} elseif (str_char_len($fio) > 150) {
    set_error_cookie('err_fio', 'ФИО не должно превышать 150 символов.');
    set_temp_cookie('val_fio', $fio);
    $hasErr = true;
} else {
    if (!$sessionUser) set_perm_cookie('val_fio', $fio);
}

// ── 2. Телефон ────────────────────────────────────────────────
$phone = trim($post['phone'] ?? '');
if ($phone === '') {
    set_error_cookie('err_phone', 'Укажите номер телефона.');
    set_temp_cookie('val_phone', '');
    $hasErr = true;
} elseif (!preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone)) {
    set_error_cookie('err_phone', 'Телефон: допустимы цифры, +, (, ), пробел, дефис (7–20 знаков).');
    set_temp_cookie('val_phone', $phone);
    $hasErr = true;
} else {
    if (!$sessionUser) set_perm_cookie('val_phone', $phone);
}

// ── 3. E-mail ─────────────────────────────────────────────────
$email = trim($post['email'] ?? '');
if ($email === '') {
    set_error_cookie('err_email', 'Укажите e-mail.');
    set_temp_cookie('val_email', '');
    $hasErr = true;
} elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
    set_error_cookie('err_email', 'E-mail: введите адрес вида name@domain.ru.');
    set_temp_cookie('val_email', $email);
    $hasErr = true;
} elseif (str_char_len($email) > 255) {
    set_error_cookie('err_email', 'E-mail слишком длинный (максимум 255 символов).');
    set_temp_cookie('val_email', $email);
    $hasErr = true;
} else {
    if (!$sessionUser) set_perm_cookie('val_email', $email);
}

// ── 4. Дата рождения ─────────────────────────────────────────
$birthdate = trim($post['birthdate'] ?? '');
if ($birthdate === '') {
    set_error_cookie('err_birthdate', 'Укажите дату рождения.');
    set_temp_cookie('val_birthdate', '');
    $hasErr = true;
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    set_error_cookie('err_birthdate', 'Дата рождения: ожидается формат ГГГГ-ММ-ДД.');
    set_temp_cookie('val_birthdate', $birthdate);
    $hasErr = true;
} else {
    $d = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$d || $d->format('Y-m-d') !== $birthdate) {
        set_error_cookie('err_birthdate', 'Введите существующую дату рождения.');
        set_temp_cookie('val_birthdate', $birthdate);
        $hasErr = true;
    } elseif ($d > new DateTime()) {
        set_error_cookie('err_birthdate', 'Дата рождения не может быть в будущем.');
        set_temp_cookie('val_birthdate', $birthdate);
        $hasErr = true;
    } else {
        if (!$sessionUser) set_perm_cookie('val_birthdate', $birthdate);
    }
}

// ── 5. Пол ────────────────────────────────────────────────────
$gender = trim($post['gender'] ?? '');
if (!in_array($gender, $validGenders, true)) {
    set_error_cookie('err_gender', 'Выберите пол: «Мужской» или «Женский».');
    set_temp_cookie('val_gender', '');
    $hasErr = true;
} else {
    if (!$sessionUser) set_perm_cookie('val_gender', $gender);
}

// ── 6. Языки программирования ─────────────────────────────────
$rawLangs = $post['languages'] ?? [];
$langs    = [];
if (!is_array($rawLangs) || count($rawLangs) === 0) {
    set_error_cookie('err_languages', 'Выберите хотя бы один язык программирования.');
    set_temp_cookie('val_languages', '[]');
    $hasErr = true;
} else {
    $langsBad = false;
    foreach ($rawLangs as $lid) {
        $lid = (int)$lid;
        if (!in_array($lid, $validLanguageIds, true)) { $langsBad = true; break; }
        $langs[] = $lid;
    }
    if ($langsBad) {
        set_error_cookie('err_languages', 'Выбрано недопустимое значение языка.');
        set_temp_cookie('val_languages', json_encode($langs));
        $hasErr = true;
    } else {
        $langs = array_unique($langs);
        if (!$sessionUser) set_perm_cookie('val_languages', json_encode($langs));
    }
}

// ── 7. Биография ─────────────────────────────────────────────
$biography = trim($post['biography'] ?? '');
if ($biography === '') {
    set_error_cookie('err_biography', 'Заполните биографию.');
    set_temp_cookie('val_biography', '');
    $hasErr = true;
} elseif (str_char_len($biography) > 10000) {
    set_error_cookie('err_biography', 'Биография слишком длинная (максимум 10 000 символов).');
    set_temp_cookie('val_biography', $biography);
    $hasErr = true;
} else {
    if (!$sessionUser) set_perm_cookie('val_biography', $biography);
}

// ── 8. Согласие с контрактом ─────────────────────────────────
$agreed = !empty($post['agreed']) && $post['agreed'] === '1';
if (!$agreed) {
    set_error_cookie('err_agreed', 'Необходимо подтвердить ознакомление с контрактом.');
    $hasErr = true;
}

if ($hasErr) {
    header('Location: index.php');
    exit();
}

// ══════════════════════════════════════════════════════════════
//  Сохранение в БД
// ══════════════════════════════════════════════════════════════
try {
    $db = get_db();

    if ($sessionUser) {
        $uid = $sessionUser['uid'];
        $stmt = $db->prepare(
            "UPDATE application
             SET name=:name, phone=:phone, email=:email,
                 birthdate=:birthdate, gender=:gender, biography=:biography
             WHERE id=:id"
        );
        $stmt->execute([
            ':name' => $fio, ':phone' => $phone, ':email' => $email,
            ':birthdate' => $birthdate, ':gender' => $gender,
            ':biography' => $biography, ':id' => $uid,
        ]);
        $db->prepare("DELETE FROM application_language WHERE application_id = :id")
           ->execute([':id' => $uid]);
        $stmtLang = $db->prepare(
            "INSERT INTO application_language (application_id, language_id) VALUES (:app, :lang)"
        );
        foreach ($langs as $langId) {
            $stmtLang->execute([':app' => $uid, ':lang' => $langId]);
        }
        setcookie('save', '1', 0, '/');
        header('Location: index.php');
        exit();
    }

    do {
        $login = 'user_' . substr(uniqid('', true), -7);
        $check = $db->prepare("SELECT id FROM application WHERE login = :l");
        $check->execute([':l' => $login]);
    } while ($check->fetch());

    $plainPass = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 4)
               . substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'),        0, 2)
               . substr(str_shuffle('23456789'),                         0, 2);
    $plainPass = str_shuffle($plainPass);
    $passHash  = md5($plainPass);

    $stmt = $db->prepare(
        "INSERT INTO application (name, phone, email, birthdate, gender, biography, agreed, login, password_hash)
         VALUES (:name, :phone, :email, :birthdate, :gender, :biography, :agreed, :login, :hash)"
    );
    $stmt->execute([
        ':name' => $fio, ':phone' => $phone, ':email' => $email,
        ':birthdate' => $birthdate, ':gender' => $gender,
        ':biography' => $biography, ':agreed' => 1,
        ':login' => $login, ':hash' => $passHash,
    ]);

    $applicationId = (int)$db->lastInsertId();
    $stmtLang = $db->prepare(
        "INSERT INTO application_language (application_id, language_id) VALUES (:app, :lang)"
    );
    foreach ($langs as $langId) {
        $stmtLang->execute([':app' => $applicationId, ':lang' => $langId]);
    }

} catch (PDOException $e) {
    // [FIX: Information Disclosure] Детали ошибки БД не раскрываются пользователю
    error_log('[index.php POST] DB error: ' . $e->getMessage());
    set_error_cookie('err_fio', 'Ошибка сервера. Попробуйте позже.');
    header('Location: index.php');
    exit();
}

setcookie('save',      '1',        0, '/');
setcookie('new_login', $login,     0, '/');
setcookie('new_pass',  $plainPass, 0, '/');
header('Location: index.php');
exit();
