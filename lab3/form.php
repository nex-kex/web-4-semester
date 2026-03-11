<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $langStmt = $pdo->query("SELECT id, name FROM programming_languages ORDER BY name");
    $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $languages = [
        ['id' => 1, 'name' => 'Pascal'],
        ['id' => 2, 'name' => 'C'],
        ['id' => 3, 'name' => 'C++'],
        ['id' => 4, 'name' => 'JavaScript'],
        ['id' => 5, 'name' => 'PHP'],
        ['id' => 6, 'name' => 'Python'],
        ['id' => 7, 'name' => 'Java'],
        ['id' => 8, 'name' => 'Haskell'],
        ['id' => 9, 'name' => 'Clojure'],
        ['id' => 10, 'name' => 'Prolog'],
        ['id' => 11, 'name' => 'Scala'],
        ['id' => 12, 'name' => 'Go']
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Анкета</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .form-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input[type="text"],
        input[type="tel"],
        input[type="email"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        select[multiple] {
            height: 150px;
        }
        .radio-group {
            display: flex;
            gap: 20px;
        }
        .radio-label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: normal;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background: #45a049;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .hint {
            font-size: 12px;
            color: #888;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Анкета</h1>

            <?php if (isset($_GET['success'])): ?>
                <div class="success">Данные успешно сохранены! ID: <?= htmlspecialchars($_GET['id']) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="error">Ошибка: <?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="form-group">
                    <label>ФИО *</label>
                    <input type="text" name="full_name" required
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    <div class="hint">Только буквы и пробелы, не более 150 символов</div>
                </div>

                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" required
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Дата рождения *</label>
                    <input type="date" name="birth_date" required
                           value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Пол *</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="male" required
                                <?= (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'checked' : '' ?>>
                            Мужской
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="female" required
                                <?= (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'checked' : '' ?>>
                            Женский
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Любимые языки программирования *</label>
                    <select name="languages[]" multiple required>
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?= $lang['id'] ?>"
                                <?= (isset($_POST['languages']) && in_array($lang['id'], $_POST['languages'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">Можно выбрать несколько (Ctrl+клик)</div>
                </div>

                <div class="form-group">
                    <label>Биография *</label>
                    <textarea name="biography" rows="5" required><?= htmlspecialchars($_POST['biography'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="contract" id="contract" required
                            <?= isset($_POST['contract']) ? 'checked' : '' ?>>
                        <label for="contract">Я ознакомлен с контрактом *</label>
                    </div>
                </div>

                <button type="submit">Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>