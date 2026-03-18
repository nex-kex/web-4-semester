<?php
session_start();

// Если уже авторизован, перенаправляем на редактирование
if (isset($_SESSION['user_id'])) {
    header('Location: edit.php');
    exit;
}

// Загрузка сохраненных данных из cookies для неавторизованных
$savedData = [];
if (isset($_COOKIE['form_data'])) {
    $savedData = json_decode($_COOKIE['form_data'], true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 450px;
            width: 100%;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #e1e1e1;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            color: #666;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            margin-bottom: -2px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #5a67d8;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #155724;
        }

        .info {
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 14px;
        }

        .info a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .info a:hover {
            text-decoration: underline;
        }

        .hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>🔐 Авторизация</h1>

            <?php if (isset($_GET['registered'])): ?>
                <div class="success">
                    ✅ Регистрация успешна! Ваши данные для входа отправлены на email.<br>
                    <strong>Логин:</strong> <?= htmlspecialchars($_GET['login']) ?><br>
                    <strong>Пароль:</strong> <?= htmlspecialchars($_GET['password']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="error">❌ <?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="success">✅ <?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <div class="tabs">
                <div class="tab active" onclick="showTab('login')">Вход</div>
                <div class="tab" onclick="showTab('register')">Регистрация</div>
            </div>

            <!-- Форма входа -->
            <form id="login-form" action="auth.php" method="POST">
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="login" required
                           placeholder="Введите логин"
                           value="<?= htmlspecialchars($_GET['login'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required
                           placeholder="Введите пароль">
                </div>

                <button type="submit">Войти</button>
            </form>

            <!-- Форма регистрации (новая анкета) -->
            <form id="register-form" action="form.php" method="GET" style="display: none;">
                <p style="text-align: center; margin: 20px 0; color: #666;">
                    Для регистрации заполните анкету<br>
                    Логин и пароль будут сгенерированы автоматически
                </p>
                <button type="submit">Заполнить анкету</button>
            </form>

            <div class="info">
                <a href="form.php">← Вернуться к форме без авторизации</a>
            </div>
        </div>
    </div>

    <script>
    function showTab(tab) {
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const tabs = document.querySelectorAll('.tab');

        if (tab === 'login') {
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
            tabs[0].classList.add('active');
            tabs[1].classList.remove('active');
        } else {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            tabs[0].classList.remove('active');
            tabs[1].classList.add('active');
        }
    }
    </script>
</body>
</html>