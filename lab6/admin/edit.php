<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    require_once 'login.php';
    exit;
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

$id = $_GET['id'] ?? 0;
$user = getUserById($id);

if (!$user) {
    header('Location: index.php?error=' . urlencode('Пользователь не найден'));
    exit;
}

$languages = getLanguages();
$userLanguages = $user['language_ids'] ? explode(',', $user['language_ids']) : [];

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $validation = validateForm($_POST);

    if (!empty($validation['errors'])) {
        $error = implode(', ', $validation['errors']);
        header("Location: edit.php?id=$id&error=" . urlencode($error));
        exit;
    }

    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        // Обновление данных пользователя
        $stmt = $pdo->prepare("
            UPDATE application
            SET full_name = ?, phone = ?, email = ?, birth_date = ?,
                gender = ?, biography = ?, contract_accepted = ?, is_edited = 1
            WHERE id = ?
        ");

        $stmt->execute([
            trim($_POST['full_name']),
            trim($_POST['phone']),
            trim($_POST['email']),
            $_POST['birth_date'],
            $_POST['gender'],
            trim($_POST['biography']),
            1,
            $id
        ]);

        // Обновление языков
        $deleteStmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $deleteStmt->execute([$id]);

        $langStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($_POST['languages'] as $lang_id) {
            $langStmt->execute([$id, $lang_id]);
        }

        $pdo->commit();

        header("Location: index.php?message=" . urlencode('Данные пользователя обновлены'));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = urlencode('Ошибка: ' . $e->getMessage());
        header("Location: edit.php?id=$id&error=$error");
        exit;
    }
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование пользователя</title>
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
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
        }

        .form-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        input[type="tel"],
        input[type="email"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select[multiple] {
            height: 180px;
        }

        .radio-group {
            display: flex;
            gap: 30px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        textarea {
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .save-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            flex: 2;
        }

        .save-btn:hover {
            background: #218838;
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            flex: 1;
            text-decoration: none;
            text-align: center;
        }

        .cancel-btn:hover {
            background: #5a6268;
        }

        .info {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✏️ Редактирование пользователя #<?= $id ?></h2>
            <a href="index.php" class="back-btn">← Назад</a>
        </div>

        <div class="form-box">
            <?php if ($error): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>ФИО *</label>
                    <input type="text" name="full_name" required
                           value="<?= htmlspecialchars($user['full_name']) ?>">
                </div>

                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" required
                           value="<?= htmlspecialchars($user['phone']) ?>">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required
                           value="<?= htmlspecialchars($user['email']) ?>">
                </div>

                <div class="form-group">
                    <label>Дата рождения *</label>
                    <input type="date" name="birth_date" required
                           value="<?= htmlspecialchars($user['birth_date']) ?>">
                </div>

                <div class="form-group">
                    <label>Пол *</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="male" required
                                <?= $user['gender'] == 'male' ? 'checked' : '' ?>>
                            Мужской
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="female" required
                                <?= $user['gender'] == 'female' ? 'checked' : '' ?>>
                            Женский
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Любимые языки программирования *</label>
                    <select name="languages[]" multiple required>
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?= $lang['id'] ?>"
                                <?= in_array($lang['id'], $userLanguages) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="info">Для выбора нескольких используйте Ctrl+клик</div>
                </div>

                <div class="form-group">
                    <label>Биография *</label>
                    <textarea name="biography" rows="6" required><?= htmlspecialchars($user['biography']) ?></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="contract" id="contract" required checked>
                        <label for="contract">Подтверждаю согласие с контрактом *</label>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="save-btn">💾 Сохранить изменения</button>
                    <a href="index.php" class="cancel-btn">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>