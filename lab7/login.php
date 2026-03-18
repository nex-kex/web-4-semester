<?php
header('Content-Type: text/html; charset=UTF-8');

// ─── Настройки БД ──────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'uXXXXX');
define('DB_USER', 'uXXXXX');
define('DB_PASS', 'your_pass');

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

// ─── Выход (GET ?logout=1) — проверяем ДО редиректа авторизованных ──
if (isset($_GET['logout'])) {
    if (!empty($_COOKIE[session_name()]) && session_start()) {
        session_destroy();
        setcookie(session_name(), '', 100000, '/');
    }
    header('Location: index.php');
    exit();
}

// ─── Если уже авторизован — редирект на форму ─────────────────
if (!empty($_COOKIE[session_name()]) && session_start() && !empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// ══════════════════════════════════════════════════════════════
//  POST — проверяем логин/пароль
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login'] ?? '');
    $passInput  = trim($_POST['pass']  ?? '');

    if ($loginInput === '' || $passInput === '') {
        $error = 'Введите логин и пароль.';
    } else {
        try {
            $db   = get_db();
            $stmt = $db->prepare(
                "SELECT id, login FROM application
                 WHERE login = :login AND password_hash = :hash
                 LIMIT 1"
            );
            $stmt->execute([
                ':login' => $loginInput,
                ':hash'  => md5($passInput),
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Авторизация успешна — стартуем сессию и сохраняем данные
                if (empty($_COOKIE[session_name()])) session_start();
                session_regenerate_id(true); // защита от session fixation
                $_SESSION['login'] = $user['login'];
                $_SESSION['uid']   = (int)$user['id'];

                header('Location: index.php');
                exit();
            } else {
                $error = 'Неверный логин или пароль.';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// ── GET — показываем форму входа ────────────────────────────?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Вход</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --sage: #7D9B76; --sage-light: #B2C9AD; --sage-pale: #EEF3ED;
      --sage-dark: #4F6B4A; --text: #2E3A2C; --muted: #6B7F69;
      --err-text: #C0392B; --err-bg: #fdf0ef; --err-border: #e8b4b0;
      --radius: 8px;
    }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: var(--sage-pale); color: var(--text);
      min-height: 100vh; display: flex;
      justify-content: center; align-items: center; padding: 2rem 1rem;
    }
    .card {
      background: #fff; border-radius: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
      padding: 2.4rem 2.8rem; width: 100%; max-width: 400px;
    }
    h1 { font-size: 1.4rem; font-weight: 600; color: var(--sage-dark);
         margin-bottom: 1.6rem; text-align: center; }
    .field { margin-bottom: 1.1rem; display: flex; flex-direction: column; gap: .3rem; }
    label { font-size: .88rem; font-weight: 500; color: var(--muted); }
    input[type="text"], input[type="password"] {
      width: 100%; padding: .55rem .8rem;
      border: 1.5px solid var(--sage-light); border-radius: var(--radius);
      font-size: .95rem; font-family: inherit; outline: none;
      transition: border-color .18s, box-shadow .18s;
    }
    input:focus { border-color: var(--sage); box-shadow: 0 0 0 3px rgba(125,155,118,.18); }
    .msg-error {
      background: var(--err-bg); border: 1.5px solid var(--err-border);
      border-radius: var(--radius); padding: .75rem 1rem;
      margin-bottom: 1.2rem; color: var(--err-text); font-size: .9rem;
    }
    .btn {
      display: block; width: 100%; margin-top: 1.4rem; padding: .75rem;
      background: var(--sage); color: #fff; border: none;
      border-radius: var(--radius); font-size: 1rem; font-weight: 600;
      cursor: pointer; letter-spacing: .03em;
      box-shadow: 0 2px 6px rgba(79,107,74,.25);
      transition: background .18s, transform .14s, box-shadow .14s;
    }
    .btn:hover { background: var(--sage-dark); transform: translateY(-2px);
                 box-shadow: 0 6px 14px rgba(79,107,74,.30); }
    .btn:active { transform: translateY(0); }
    .back { display: block; text-align: center; margin-top: 1rem;
            font-size: .88rem; color: var(--muted); text-decoration: none; }
    .back:hover { color: var(--sage-dark); }
  </style>
</head>
<body>
<div class="card">
  <h1>Вход</h1>

  <?php if ($error): ?>
    <div class="msg-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <form action="login.php" method="POST">
    <div class="field">
      <label for="login">Логин</label>
      <input type="text" id="login" name="login"
             placeholder="user_xxxxxxx"
             value="<?= htmlspecialchars($_POST['login'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
             autocomplete="username">
    </div>
    <div class="field">
      <label for="pass">Пароль</label>
      <input type="password" id="pass" name="pass" autocomplete="current-password">
    </div>
    <button type="submit" class="btn">Войти</button>
  </form>

  <a href="index.php" class="back">← Вернуться к форме</a>
</div>
</body>
</html>
