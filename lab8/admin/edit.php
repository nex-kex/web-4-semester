<?php
session_start();

if (!isset($_SESSION['gym_admin_logged_in']) || $_SESSION['gym_admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'user';

if (!$id) {
    header('Location: index.php?error=' . urlencode('Не указан ID'));
    exit;
}

$pdo = getGymDBConnection();

if ($type === 'user') {
    $stmt = $pdo->prepare("SELECT * FROM gym_applications WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    $title = 'Редактирование пользователя';
    $backUrl = 'index.php';
} else {
    $stmt = $pdo->prepare("SELECT * FROM gym_feedback WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    $title = 'Редактирование заявки';
    $backUrl = 'index.php#feedback';
}

if (!$item) {
    header('Location: index.php?error=' . urlencode('Запись не найдена'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($type === 'user') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'new';

        $errors = validateRegistrationForm(['name' => $name, 'phone' => $phone, 'email' => $email]);

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE gym_applications
                    SET name = ?, phone = ?, email = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $phone, $email, $status, $id]);
                $success = 'Данные пользователя обновлены';

                $stmt = $pdo->prepare("SELECT * FROM gym_applications WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'Ошибка базы данных';
            }
        } else {
            $error = implode(', ', $errors);
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        $status = $_POST['status'] ?? 'new';

        $errors = validateFeedbackForm(['name' => $name, 'phone' => $phone, 'email' => $email, 'comment' => $comment]);

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE gym_feedback
                    SET name = ?, phone = ?, email = ?, comment = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $phone, $email, $comment, $status, $id]);
                $success = 'Данные заявки обновлены';

                $stmt = $pdo->prepare("SELECT * FROM gym_feedback WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'Ошибка базы данных';
            }
        } else {
            $error = implode(', ', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> - Bull Gym</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            font-family: 'Oswald', sans-serif;
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
            font-family: inherit;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
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

        .info-box {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .info-box p {
            color: #fff;
            margin: 5px 0;
        }

        .info-box strong {
            color: #b61815;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>✏️ <?= $title ?> #<?= $id ?></h1>

            <?php if ($success): ?>
                <div class="success">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error">❌ <?= $error ?></div>
            <?php endif; ?>

            <div class="info-box">
                <?php if ($type === 'user'): ?>
                    <p><strong>Логин:</strong> <?= htmlspecialchars($item['login']) ?></p>
                    <p><strong>Создана:</strong> <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></p>
                    <p><strong>Обновлена:</strong> <?= date('d.m.Y H:i', strtotime($item['updated_at'])) ?></p>
                <?php else: ?>
                    <p><strong>Создана:</strong> <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></p>
                <?php endif; ?>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($item['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($item['email']) ?>" required>
                </div>

                <?php if ($type === 'feedback'): ?>
                <div class="form-group">
                    <label>Комментарий</label>
                    <textarea name="comment" rows="5"><?= htmlspecialchars($item['comment']) ?></textarea>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Статус</label>
                    <select name="status">
                        <?php if ($type === 'user'): ?>
                            <option value="new" <?= $item['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                            <option value="processed" <?= $item['status'] == 'processed' ? 'selected' : '' ?>>В обработке</option>
                            <option value="completed" <?= $item['status'] == 'completed' ? 'selected' : '' ?>>Завершен</option>
                        <?php else: ?>
                            <option value="new" <?= $item['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                            <option value="read" <?= $item['status'] == 'read' ? 'selected' : '' ?>>Прочитан</option>
                            <option value="replied" <?= $item['status'] == 'replied' ? 'selected' : '' ?>>Отвечен</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit">💾 Сохранить</button>
                    <a href="<?= $backUrl ?>" class="back-btn">← Назад</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>