<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? 0;

if (!$id) {
    header('Location: index.php?error=' . urlencode('Не указан ID'));
    exit;
}

$pdo = getGymDBConnection();
$stmt = $pdo->prepare("SELECT * FROM gym_applications WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?error=' . urlencode('Пользователь не найден'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $status = $_POST['status'] ?? 'new';

    $errors = validateGymForm(['name' => $name, 'phone' => $phone, 'email' => $email, 'comment' => $comment]);

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE gym_applications
                SET name = ?, phone = ?, email = ?, comment = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $email, $comment, $status, $id]);
            $success = 'Данные успешно обновлены';

            // Обновляем данные пользователя
            $stmt = $pdo->prepare("SELECT * FROM gym_applications WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных';
        }
    } else {
        $error = implode(', ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование заявки</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #0a0a0a;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 15px;
            border: 1px solid #b61815;
        }
        h1 {
            color: #b61815;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 5px;
            color: #fff;
        }
        button {
            background: #b61815;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .back-btn {
            background: #666;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            display: inline-block;
        }
        .success {
            background: #28a745;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #b61815;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .login-info {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .login-info p {
            color: #fff;
            margin: 5px 0;
        }
        .login-info strong {
            color: #b61815;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>✏️ Редактирование заявки #<?= $user['id'] ?></h1>

            <?php if ($success): ?>
                <div class="success">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error">❌ <?= $error ?></div>
            <?php endif; ?>

            <div class="login-info">
                <p><strong>Логин:</strong> <?= htmlspecialchars($user['login']) ?></p>
                <p><strong>Создана:</strong> <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></p>
                <p><strong>Обновлена:</strong> <?= date('d.m.Y H:i', strtotime($user['updated_at'])) ?></p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Комментарий</label>
                    <textarea name="comment" rows="5"><?= htmlspecialchars($user['comment']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status">
                        <option value="new" <?= $user['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                        <option value="processed" <?= $user['status'] == 'processed' ? 'selected' : '' ?>>В обработке</option>
                        <option value="completed" <?= $user['status'] == 'completed' ? 'selected' : '' ?>>Завершен</option>
                    </select>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit">💾 Сохранить</button>
                    <a href="index.php" class="back-btn">← Назад</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>