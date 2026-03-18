<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db_host = 'localhost';
$db_name = 'u82269';
$db_user = 'u82269';
$db_pass = '8571433';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT a.*, GROUP_CONCAT(al.language_id) as language_ids
        FROM application a
        LEFT JOIN application_languages al ON a.id = al.application_id
        WHERE a.id = ?
        GROUP BY a.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        session_destroy();
        header('Location: login.php?error=' . urlencode('Пользователь не найден'));
        exit;
    }

    $langStmt = $pdo->query("SELECT id, name FROM programming_languages ORDER BY name");
    $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);

    $userLanguages = $userData['language_ids'] ? explode(',', $userData['language_ids']) : [];

} catch (PDOException $e) {
    die("Ошибка загрузки данных");
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование анкеты</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .user-info {
            color: #333;
        }

        .user-info strong {
            color: #667eea;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .form-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .edited-badge {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-left: 10px;
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
            padding: 8px;
        }

        select[multiple] option {
            padding: 8px 12px;
        }

        select[multiple] option:checked {
            background: #667eea linear-gradient(0deg, #667eea 0%, #667eea 100%);
            color: white;
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
            transition: background 0.3s ease;
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

        .hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                👤 <strong><?= htmlspecialchars($userData['full_name']) ?></strong>
                (ID: <?= $userData['id'] ?>)
                <?php if ($userData['is_edited']): ?>
                    <span class="edited-badge">✏️ Редактировалось</span>
                <?php endif; ?>
            </div>
            <a href="logout.php" class="logout-btn">Выйти</a>
        </div>

        <div class="form-box">
            <h1>Редактирование анкеты</h1>
            <div class="subtitle">
                Вы можете изменить свои данные
            </div>

            <?php if ($error): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="update.php" method="POST">
                <input type="hidden" name="user_id" value="<?= $userData['id'] ?>">

                <div class="form-group">
                    <label>ФИО *</label>
                    <input type="text" name="full_name" required
                           value="<?= htmlspecialchars($userData['full_name']) ?>">
                </div>

                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" required
                           value="<?= htmlspecialchars($userData['phone']) ?>">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required
                           value="<?= htmlspecialchars($userData['email']) ?>">
                </div>

                <div class="form-group">
                    <label>Дата рождения *</label>
                    <input type="date" name="birth_date" required
                           value="<?= htmlspecialchars($userData['birth_date']) ?>">
                </div>

                <div class="form-group">
                    <label>Пол *</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="male" required
                                <?= $userData['gender'] == 'male' ? 'checked' : '' ?>>
                            Мужской
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="female" required
                                <?= $userData['gender'] == 'female' ? 'checked' : '' ?>>
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
                </div>

                <div class="form-group">
                    <label>Биография *</label>
                    <textarea name="biography" rows="6" required><?= htmlspecialchars($userData['biography']) ?></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="contract" id="contract" required checked>
                        <label for="contract">Подтверждаю согласие с контрактом *</label>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="save-btn">💾 Сохранить изменения</button>
                    <a href="form.php" class="cancel-btn">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>