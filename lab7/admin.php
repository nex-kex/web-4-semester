<?php
// SECURITY AUDIT FIX: скрываем PHP-ошибки (Information Disclosure)
error_reporting(0);
ini_set('display_errors','0');
header('Content-Type: text/html; charset=UTF-8');

// ─── Настройки БД (те же что в index.php) ─────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'uXXXXX');
define('DB_USER', 'uXXXXX');
define('DB_PASS', 'your_pass');

// ─── Допустимые значения (DRY — те же что в index.php) ────────
$validLanguageIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
$validGenders     = ['male', 'female'];

// ══════════════════════════════════════════════════════════════
//  Хелперы
// ══════════════════════════════════════════════════════════════
function get_db() {
    static $db = null;
    if ($db === null) {
        $db = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $db;
}

function h($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function str_char_len($s) {
    if (function_exists('iconv_strlen')) return iconv_strlen($s, 'UTF-8');
    if (function_exists('mb_strlen'))   return mb_strlen($s, 'UTF-8');
    return strlen($s);
}

// Валидирует поля заявки, возвращает массив ошибок (пустой = ОК)
function validate_application($post, $validLanguageIds, $validGenders) {
    $errors = [];

    $fio = trim($post['fio'] ?? '');
    if ($fio === '')
        $errors['fio'] = 'Укажите ФИО.';
    elseif (!preg_match('/^[\p{L} \-]+$/u', $fio))
        $errors['fio'] = 'ФИО: только буквы, пробелы и дефисы.';
    elseif (str_char_len($fio) > 150)
        $errors['fio'] = 'ФИО не длиннее 150 символов.';

    $phone = trim($post['phone'] ?? '');
    if ($phone === '')
        $errors['phone'] = 'Укажите телефон.';
    elseif (!preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone))
        $errors['phone'] = 'Телефон: цифры, +, (, ), пробел, дефис.';

    $email = trim($post['email'] ?? '');
    if ($email === '')
        $errors['email'] = 'Укажите e-mail.';
    elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email))
        $errors['email'] = 'E-mail: введите адрес вида name@domain.ru.';

    $birthdate = trim($post['birthdate'] ?? '');
    if ($birthdate === '') {
        $errors['birthdate'] = 'Укажите дату рождения.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        $errors['birthdate'] = 'Дата: формат ГГГГ-ММ-ДД.';
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$d || $d->format('Y-m-d') !== $birthdate)
            $errors['birthdate'] = 'Введите существующую дату.';
        elseif ($d > new DateTime())
            $errors['birthdate'] = 'Дата не может быть в будущем.';
    }

    $gender = trim($post['gender'] ?? '');
    if (!in_array($gender, $validGenders, true))
        $errors['gender'] = 'Выберите пол.';

    $rawLangs = $post['languages'] ?? [];
    if (!is_array($rawLangs) || count($rawLangs) === 0) {
        $errors['languages'] = 'Выберите хотя бы один язык.';
    } else {
        foreach ($rawLangs as $lid) {
            if (!in_array((int)$lid, $validLanguageIds, true)) {
                $errors['languages'] = 'Недопустимое значение языка.';
                break;
            }
        }
    }

    $biography = trim($post['biography'] ?? '');
    if ($biography === '')
        $errors['biography'] = 'Заполните биографию.';
    elseif (str_char_len($biography) > 10000)
        $errors['biography'] = 'Биография не длиннее 10 000 символов.';

    return $errors;
}

// --- CSRF (admin) ---
function admin_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION["admin_csrf"])) {
        $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["admin_csrf"];
}
function admin_csrf_verify() {
    $sub = $_POST["csrf_token"] ?? "";
    if (session_status() === PHP_SESSION_NONE) session_start();
    $exp = $_SESSION["admin_csrf"] ?? "";
    if (!$exp || !hash_equals($exp, $sub)) { http_response_code(403); exit("403 Forbidden"); }
    $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
}

// ══════════════════════════════════════════════════════════════
//  HTTP Basic Auth — проверяем логин/пароль по таблице admin
// ══════════════════════════════════════════════════════════════
$authOk = false;

if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
    try {
        $stmt = get_db()->prepare(
            "SELECT id FROM admin WHERE login = :l AND password_hash = MD5(:p) LIMIT 1"
        );
        $stmt->execute([':l' => $_SERVER['PHP_AUTH_USER'], ':p' => $_SERVER['PHP_AUTH_PW']]);
        $authOk = (bool)$stmt->fetch();
    } catch (PDOException $e) {
        // [FIX: Info Disclosure] детали скрыты
        error_log('[admin] DB auth: ' . $e->getMessage());
    }
}

if (!$authOk) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin panel"');
    echo '<h1>401 Требуется авторизация</h1>';
    exit();
}

// ══════════════════════════════════════════════════════════════
//  Обработка POST-действий: delete, update
// ══════════════════════════════════════════════════════════════
$actionMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_verify(); // [FIX: CSRF]
    $action = $_POST['action'] ?? '';

    // ── Удаление ─────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // application_language удалится каскадно (FK ON DELETE CASCADE)
            $db = get_db();
            $db->prepare("DELETE FROM application WHERE id = :id")->execute([':id' => $id]);
            $actionMsg = "Запись #$id удалена.";
        }

    // ── Обновление ───────────────────────────────────────────
    } elseif ($action === 'update') {
        $id     = (int)($_POST['id'] ?? 0);
        $errors = validate_application($_POST, $validLanguageIds, $validGenders);

        if ($id > 0 && empty($errors)) {
            $db   = get_db();
            $stmt = $db->prepare(
                "UPDATE application
                 SET name=:name, phone=:phone, email=:email,
                     birthdate=:birthdate, gender=:gender, biography=:biography
                 WHERE id=:id"
            );
            $stmt->execute([
                ':name'      => trim($_POST['fio']),
                ':phone'     => trim($_POST['phone']),
                ':email'     => trim($_POST['email']),
                ':birthdate' => trim($_POST['birthdate']),
                ':gender'    => trim($_POST['gender']),
                ':biography' => trim($_POST['biography']),
                ':id'        => $id,
            ]);

            $db->prepare("DELETE FROM application_language WHERE application_id = :id")
               ->execute([':id' => $id]);

            $stmtL = $db->prepare(
                "INSERT INTO application_language (application_id, language_id) VALUES (:a, :l)"
            );
            foreach (array_unique(array_map('intval', $_POST['languages'])) as $lid) {
                $stmtL->execute([':a' => $id, ':l' => $lid]);
            }
            $actionMsg = "Запись #$id обновлена.";
        } elseif (!empty($errors)) {
            // Показываем форму редактирования с ошибками — передаём через GET
            $editId     = $id;
            $editErrors = $errors;
            $editValues = $_POST; // восстанавливаем введённые значения
            $editValues['languages'] = array_map('intval', $_POST['languages'] ?? []);
        }
    }
}

// ─── CSRF: сессия для хранения токена ─────────────────────────
// CSRF Protection: уникальный токен на сессию администратора
session_start();
if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}
$csrfAdmin = $_SESSION['csrf_admin'];

// ── Загрузка записи для редактирования (GET ?edit=N) ─────────
$editRow = null;
if (!isset($editId) && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
}

if (!empty($editId) && empty($editErrors)) {
    $stmt = get_db()->prepare(
        "SELECT a.*, GROUP_CONCAT(al.language_id) AS lang_ids
         FROM application a
         LEFT JOIN application_language al ON al.application_id = a.id
         WHERE a.id = :id GROUP BY a.id"
    );
    $stmt->execute([':id' => $editId]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editRow) {
        $editValues = [
            'fio'       => $editRow['name'],
            'phone'     => $editRow['phone'],
            'email'     => $editRow['email'],
            'birthdate' => $editRow['birthdate'],
            'gender'    => $editRow['gender'],
            'biography' => $editRow['biography'],
            'languages' => $editRow['lang_ids']
                ? array_map('intval', explode(',', $editRow['lang_ids'])) : [],
        ];
        $editErrors = $editErrors ?? [];
    }
}

// ── Загрузка всех заявок ─────────────────────────────────────
$applications = get_db()->query(
    "SELECT a.id, a.name, a.phone, a.email, a.birthdate, a.gender,
            a.biography, a.login, a.created_at,
            GROUP_CONCAT(l.name ORDER BY l.name SEPARATOR ', ') AS languages
     FROM application a
     LEFT JOIN application_language al ON al.application_id = a.id
     LEFT JOIN language l              ON l.id = al.language_id
     GROUP BY a.id
     ORDER BY a.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Статистика по языкам ─────────────────────────────────────
$stats = get_db()->query(
    "SELECT l.name, COUNT(al.application_id) AS cnt
     FROM language l
     LEFT JOIN application_language al ON al.language_id = l.id
     GROUP BY l.id, l.name
     ORDER BY cnt DESC, l.name"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Список языков для формы редактирования ───────────────────
$allLanguages = get_db()->query("SELECT id, name FROM language ORDER BY id")
                        ->fetchAll(PDO::FETCH_ASSOC);

$genderLabel = ['male' => 'Мужской', 'female' => 'Женский'];
$adminCsrf = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Панель администратора</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --sage: #7D9B76; --sage-light: #B2C9AD; --sage-pale: #EEF3ED;
      --sage-dark: #4F6B4A; --text: #2E3A2C; --muted: #6B7F69;
      --err-text: #C0392B; --err-bg: #fdf0ef; --err-border: #e8b4b0;
      --del: #c0392b; --del-hover: #96281b;
      --radius: 8px;
    }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: var(--sage-pale); color: var(--text);
      padding: 2rem 1.5rem;
    }
    h1 { font-size: 1.5rem; color: var(--sage-dark); margin-bottom: .3rem; }
    h2 { font-size: 1.1rem; color: var(--sage-dark); margin: 2rem 0 .8rem; }
    .subtitle { font-size: .88rem; color: var(--muted); margin-bottom: 1.6rem; }

    /* ── Флеш-сообщение ── */
    .flash {
      background: #f0f6ef; border: 1.5px solid var(--sage-light);
      border-radius: var(--radius); padding: .7rem 1rem;
      margin-bottom: 1.2rem; color: var(--sage-dark); font-size: .92rem;
    }

    /* ── Таблица ── */
    .table-wrap { overflow-x: auto; margin-bottom: 2rem; }
    table { width: 100%; border-collapse: collapse; font-size: .88rem; background: #fff;
            border-radius: var(--radius); overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.07); }
    thead { background: var(--sage); color: #fff; }
    th, td { padding: .6rem .85rem; text-align: left; vertical-align: top; }
    tbody tr:nth-child(even) { background: var(--sage-pale); }
    tbody tr:hover { background: #ddebd9; }
    td.nowrap { white-space: nowrap; }
    td.langs { max-width: 200px; color: var(--muted); font-size: .82rem; }
    td.bio { max-width: 180px; overflow: hidden; text-overflow: ellipsis;
             white-space: nowrap; color: var(--muted); }

    /* ── Кнопки в таблице ── */
    .btn-edit, .btn-del {
      display: inline-block; padding: .3rem .65rem; border-radius: 6px;
      font-size: .8rem; font-weight: 600; cursor: pointer; border: none;
      text-decoration: none; white-space: nowrap; transition: background .15s, transform .1s;
    }
    .btn-edit {
      background: var(--sage); color: #fff;
    }
    .btn-edit:hover { background: var(--sage-dark); }
    .btn-del {
      background: var(--del); color: #fff; margin-left: .35rem;
    }
    .btn-del:hover { background: var(--del-hover); }

    /* ── Форма редактирования ── */
    .edit-card {
      background: #fff; border-radius: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
      padding: 2rem 2.4rem; max-width: 680px; margin-bottom: 2rem;
    }
    .edit-card h2 { margin-top: 0; }
    .field { margin-bottom: 1rem; display: flex; flex-direction: column; gap: .25rem; }
    .field label { font-size: .85rem; font-weight: 500; color: var(--muted); }
    .field input[type="text"], .field input[type="tel"],
    .field input[type="email"], .field input[type="date"],
    .field textarea, .field select {
      padding: .5rem .75rem; border: 1.5px solid var(--sage-light);
      border-radius: var(--radius); font-size: .93rem; font-family: inherit;
      outline: none; transition: border-color .18s, box-shadow .18s;
    }
    .field input:focus, .field textarea:focus, .field select:focus {
      border-color: var(--sage); box-shadow: 0 0 0 3px rgba(125,155,118,.18);
    }
    .field-error input, .field-error textarea, .field-error select {
      border-color: var(--err-text);
    }
    .err-msg { font-size: .8rem; color: var(--err-text); }
    .field textarea { resize: vertical; min-height: 80px; }
    .field select[multiple] { min-height: 140px; }
    .radio-group { display: flex; gap: 1.2rem; }
    .radio-group label {
      display: flex; align-items: center; gap: .35rem;
      font-weight: 400; color: var(--text); cursor: pointer;
    }
    input[type="radio"], input[type="checkbox"] { accent-color: var(--sage); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .btn-save {
      margin-top: .8rem; padding: .6rem 1.6rem;
      background: var(--sage); color: #fff; border: none;
      border-radius: var(--radius); font-size: .95rem; font-weight: 600;
      cursor: pointer; transition: background .15s, transform .12s;
    }
    .btn-save:hover { background: var(--sage-dark); transform: translateY(-1px); }
    .btn-cancel {
      margin-top: .8rem; margin-left: .6rem; padding: .6rem 1.2rem;
      background: transparent; border: 1.5px solid var(--sage-light);
      border-radius: var(--radius); font-size: .95rem; color: var(--muted);
      cursor: pointer; text-decoration: none;
      transition: border-color .15s, color .15s;
    }
    .btn-cancel:hover { border-color: var(--sage); color: var(--sage-dark); }

    /* ── Статистика ── */
    .stats-table { max-width: 400px; }
    .stats-table td:last-child { text-align: right; font-weight: 600; color: var(--sage-dark); }
    .bar { display: inline-block; height: 10px; background: var(--sage);
           border-radius: 3px; margin-left: .5rem; vertical-align: middle; }
  </style>
</head>
<body>

<h1>Панель администратора</h1>
<p class="subtitle">Добро пожаловать, <strong><?= h($_SERVER['PHP_AUTH_USER']) ?></strong>.
  Записей в базе: <strong><?= count($applications) ?></strong>.</p>

<?php if ($actionMsg): ?>
  <div class="flash"><?= h($actionMsg) ?></div>
<?php endif; ?>

<?php
// ══════════════════════════════════════════════════════════════
//  Форма редактирования
// ══════════════════════════════════════════════════════════════
if (!empty($editId) && !empty($editValues)):
    $ev = $editValues;
    $ee = $editErrors ?? [];
    function ev($k) { global $ev; return h($ev[$k] ?? ''); }
    function ee($k) { global $ee; return h($ee[$k] ?? ''); }
?>
<div class="edit-card">
  <h2>Редактирование записи #<?= (int)$editId ?></h2>

  <?php if (!empty($ee)): ?>
    <div style="background:var(--err-bg);border:1.5px solid var(--err-border);
                border-radius:var(--radius);padding:.65rem .9rem;margin-bottom:1rem;
                color:var(--err-text);font-size:.88rem;">
      Исправьте ошибки в форме.
    </div>
  <?php endif; ?>

  <form action="admin.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= h($adminCsrf) ?>">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id"     value="<?= (int)$editId ?>">

    <div class="form-row">
      <div class="field <?= $ee['fio'] ? 'field-error' : '' ?>">
        <label>ФИО</label>
        <input type="text" name="fio" value="<?= ev('fio') ?>" maxlength="150">
        <?php if ($ee['fio']): ?><span class="err-msg"><?= ee('fio') ?></span><?php endif; ?>
      </div>
      <div class="field <?= $ee['phone'] ? 'field-error' : '' ?>">
        <label>Телефон</label>
        <input type="tel" name="phone" value="<?= ev('phone') ?>">
        <?php if ($ee['phone']): ?><span class="err-msg"><?= ee('phone') ?></span><?php endif; ?>
      </div>
    </div>

    <div class="form-row">
      <div class="field <?= $ee['email'] ? 'field-error' : '' ?>">
        <label>E-mail</label>
        <input type="email" name="email" value="<?= ev('email') ?>">
        <?php if ($ee['email']): ?><span class="err-msg"><?= ee('email') ?></span><?php endif; ?>
      </div>
      <div class="field <?= $ee['birthdate'] ? 'field-error' : '' ?>">
        <label>Дата рождения</label>
        <input type="date" name="birthdate" value="<?= ev('birthdate') ?>">
        <?php if ($ee['birthdate']): ?><span class="err-msg"><?= ee('birthdate') ?></span><?php endif; ?>
      </div>
    </div>

    <div class="field <?= $ee['gender'] ? 'field-error' : '' ?>">
      <label>Пол</label>
      <div class="radio-group">
        <label><input type="radio" name="gender" value="male"
          <?= ($ev['gender'] ?? '') === 'male'   ? 'checked' : '' ?>> Мужской</label>
        <label><input type="radio" name="gender" value="female"
          <?= ($ev['gender'] ?? '') === 'female' ? 'checked' : '' ?>> Женский</label>
      </div>
      <?php if ($ee['gender']): ?><span class="err-msg"><?= ee('gender') ?></span><?php endif; ?>
    </div>

    <div class="field <?= $ee['languages'] ? 'field-error' : '' ?>">
      <label>Языки программирования</label>
      <select name="languages[]" multiple>
        <?php foreach ($allLanguages as $lang): ?>
          <option value="<?= $lang['id'] ?>"
            <?= in_array((int)$lang['id'], (array)($ev['languages'] ?? []), true) ? 'selected' : '' ?>>
            <?= h($lang['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($ee['languages']): ?><span class="err-msg"><?= ee('languages') ?></span><?php endif; ?>
    </div>

    <div class="field <?= $ee['biography'] ? 'field-error' : '' ?>">
      <label>Биография</label>
      <textarea name="biography"><?= ev('biography') ?></textarea>
      <?php if ($ee['biography']): ?><span class="err-msg"><?= ee('biography') ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn-save">Сохранить</button>
    <a href="admin.php" class="btn-cancel">Отмена</a>
  </form>
</div>
<?php endif; ?>

<?php
// ══════════════════════════════════════════════════════════════
//  Таблица заявок
// ══════════════════════════════════════════════════════════════
?>
<h2>Все заявки</h2>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>ФИО</th>
        <th>Телефон</th>
        <th>E-mail</th>
        <th>Дата рождения</th>
        <th>Пол</th>
        <th>Языки</th>
        <th>Биография</th>
        <th>Логин</th>
        <th>Дата отправки</th>
        <th>Действия</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($applications)): ?>
        <tr><td colspan="11" style="text-align:center;color:var(--muted);padding:1.5rem">
          Заявок пока нет.
        </td></tr>
      <?php else: ?>
        <?php foreach ($applications as $row): ?>
          <tr>
            <td class="nowrap"><?= (int)$row['id'] ?></td>
            <td><?= h($row['name']) ?></td>
            <td class="nowrap"><?= h($row['phone']) ?></td>
            <td><?= h($row['email']) ?></td>
            <td class="nowrap"><?= h($row['birthdate']) ?></td>
            <td class="nowrap"><?= h($genderLabel[$row['gender']] ?? $row['gender']) ?></td>
            <td class="langs"><?= h($row['languages'] ?? '—') ?></td>
            <td class="bio" title="<?= h($row['biography']) ?>"><?= h($row['biography']) ?></td>
            <td class="nowrap"><?= h($row['login'] ?? '—') ?></td>
            <td class="nowrap"><?= h($row['created_at']) ?></td>
            <td class="nowrap">
              <a href="admin.php?edit=<?= (int)$row['id'] ?>" class="btn-edit">✎ Изменить</a>
              <form action="admin.php" method="POST" style="display:inline"
                    onsubmit="return confirm('Удалить запись #<?= (int)$row['id'] ?>?')">
                <input type="hidden" name="csrf_token" value="<?= h($adminCsrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= (int)$row['id'] ?>">
                <button type="submit" class="btn-del">✕ Удалить</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
// ══════════════════════════════════════════════════════════════
//  Статистика по языкам
// ══════════════════════════════════════════════════════════════
$maxCnt = !empty($stats) ? max(array_column($stats, 'cnt')) : 1;
?>
<h2>Статистика: популярность языков</h2>
<div class="table-wrap">
  <table class="stats-table">
    <thead>
      <tr><th>Язык</th><th>Пользователей</th></tr>
    </thead>
    <tbody>
      <?php foreach ($stats as $s): ?>
        <tr>
          <td><?= h($s['name']) ?></td>
          <td>
            <?= (int)$s['cnt'] ?>
            <?php if ($s['cnt'] > 0): ?>
              <span class="bar" style="width:<?= round($s['cnt'] / $maxCnt * 120) ?>px"></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
