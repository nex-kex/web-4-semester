<?php
session_start();

if (isset($_SESSION['gym_admin_logged_in']) && $_SESSION['gym_admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Подключаем functions.php (там есть getGymDBConnection)
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        try {
            $pdo = getGymDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM gym_admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['gym_admin_logged_in'] = true;
                $_SESSION['gym_admin_username'] = $username;
                header('Location: index.php');
                exit;
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в админ-панель - Bull Gym</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
        }

        .login-box {
            background: #1a1a1a;
            padding: 40px;
            border-radius: 15px;
            border: 2px solid #b61815;
            box-shadow: 0 20px 60px rgba(182, 24, 21, 0.2);
        }

        h1 {
            font-family: 'Oswald', sans-serif;
            color: #b61815;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #ffffff;
            margin-bottom: 8px;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            color: #ffffff;
            font-size: 16px;
        }

        input:focus {
            outline: none;
            border-color: #b61815;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #b61815;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Oswald', sans-serif;
            margin-top: 10px;
        }

        button:hover {
            background: #8f1310;
            transform: translateY(-2px);
        }

        .error {
            background: rgba(182, 24, 21, 0.2);
            color: #b61815;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #b61815;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            color: #b61815;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>🔐 Вход в админ-панель</h1>

            <?php if ($error): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Войти</button>
            </form>

            <div class="back-link">
                <a href="/web4/lab8/public/index.html">← На главную</a>
            </div>
        </div>
    </div>
</body>
</html>