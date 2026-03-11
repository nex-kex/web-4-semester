<?php
// Загружаем языки из БД для отображения в форме
try {
    $pdo = new PDO('mysql:host=localhost;dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $langStmt = $pdo->query("SELECT id, name FROM programming_languages ORDER BY name");
    $languages = $langStmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $languages = [
        1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'JavaScript', 5 => 'PHP',
        6 => 'Python', 7 => 'Java', 8 => 'Haskell', 9 => 'Clojure',
        10 => 'Prolog', 11 => 'Scala', 12 => 'Go'
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета разработчика</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            margin-top: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2em;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 0.95em;
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
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 5px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .radio-label input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }

        select[multiple] {
            height: 150px;
            padding: 8px;
        }

        select[multiple] option {
            padding: 8px 12px;
            margin: 2px 0;
            border-radius: 4px;
        }

        select[multiple] option:checked {
            background: #667eea linear-gradient(0deg, #667eea 0%, #667eea 100%);
            color: white;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #5a67d8;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }

        .hint {
            font-size: 0.85em;
            color: #888;
            margin-top: 5px;
        }

        @media (max-width: 600px) {
            .form-card {
                padding: 20px;
            }

            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <h1>Анкета разработчика</h1>

            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    ✅ Данные успешно сохранены! ID анкеты: <?= htmlspecialchars($_GET['id']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    ❌ Ошибка: <?= htmlspecialchars(urldecode($_GET['error'])) ?>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="full_name">ФИО *</label>
                    <input type="text" id="full_name" name="full_name" required
                           placeholder="Иванов Иван Иванович"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    <div class="hint">Только буквы и пробелы, не более 150 символов</div>
                </div>

                <div class="form-group">
                    <label for="phone">Телефон *</label>
                    <input type="tel" id="phone" name="phone" required
                           placeholder="+7 (999) 123-45-67"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email" required
                           placeholder="ivan@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="birth_date">Дата рождения *</label>
                    <input type="date" id="birth_date" name="birth_date" required
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
                    <label for="languages">Любимые языки программирования * (можно выбрать несколько)</label>
                    <select name="languages[]" id="languages" multiple required size="6">
                        <?php foreach ($languages as $id => $name): ?>
                            <option value="<?= $id ?>"
                                <?= (isset($_POST['languages']) && in_array($id, $_POST['languages'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="biography">Биография *</label>
                    <textarea id="biography" name="biography" required
                              placeholder="Расскажите о себе..."><?= htmlspecialchars($_POST['biography'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="contract" name="contract" required
                               <?= (isset($_POST['contract'])) ? 'checked' : '' ?>>
                        <label for="contract">Я ознакомлен(а) с контрактом *</label>
                    </div>
                </div>

                <button type="submit">Сохранить анкету</button>
            </form>
        </div>
    </div>
</body>
</html>