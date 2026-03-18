<?php
$savedData = [];
if (isset($_COOKIE['form_data'])) {
    $savedData = json_decode($_COOKIE['form_data'], true) ?: [];
}

$fieldErrors = [];
if (isset($_COOKIE['form_errors'])) {
    $fieldErrors = json_decode($_COOKIE['form_errors'], true) ?: [];
    setcookie('form_errors', '', time() - 3600, '/');
}

$formData = $_POST ?: $savedData;

try {
    $pdo = new PDO('mysql:host=localhost;dbname=u82269', 'u82269', '8571433');
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

function hasError($fieldName, $fieldErrors) {
    return isset($fieldErrors[$fieldName]) ? 'error-field' : '';
}

function getErrorMessage($fieldName, $fieldErrors) {
    return $fieldErrors[$fieldName] ?? '';
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
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .error-field {
            border-color: #dc3545 !important;
            background-color: #fff8f8;
        }

        .field-error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .field-error-message::before {
            content: '⚠';
            font-weight: bold;
        }

        select[multiple] {
            height: 180px;
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
        .radio-group {
            display: flex;
            gap: 30px;
            margin-top: 5px;
        }
        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            cursor: pointer;
        }
        .radio-label input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #5a67d8;
        }

        .error-summary {
            background: #fee;
            color: #c33;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #c33;
        }

        .error-summary h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .error-summary ul {
            margin: 0;
            padding-left: 20px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #155724;
        }

        .hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }

        .hint strong {
            color: #667eea;
        }

        .saved-indicator {
            font-size: 12px;
            color: #28a745;
            margin-left: 10px;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Анкета</h1>

            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    ✅ Данные успешно сохранены! ID анкеты: <?= htmlspecialchars($_GET['id']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($fieldErrors)): ?>
                <div class="error-summary">
                    <h3>❌ Пожалуйста, исправьте следующие ошибки:</h3>
                    <ul>
                        <?php foreach ($fieldErrors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($savedData) && empty($_POST) && !isset($_GET['error'])): ?>
                <div class="success-message" style="background: #e3f2fd; color: #0d47a1; border-left-color: #2196f3;">
                    ℹ️ Загружены сохраненные данные из cookies
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <!-- ФИО -->
                <div class="form-group">
                    <label>
                        ФИО *
                        <?php if (isset($savedData['full_name']) && empty($_POST)): ?>
                            <span class="saved-indicator">(из cookies)</span>
                        <?php endif; ?>
                    </label>
                    <input type="text"
                           name="full_name"
                           class="<?= hasError('full_name', $fieldErrors) ?>"
                           value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>"
                           placeholder="Иванов Иван Иванович">
                    <div class="hint">
                        <strong>Допустимо:</strong> только буквы (русские/английские), пробелы и дефис
                    </div>
                    <?php if ($msg = getErrorMessage('full_name', $fieldErrors)): ?>
                        <div class="field-error-message"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Телефон -->
                <div class="form-group">
                    <label>
                        Телефон *
                        <?php if (isset($savedData['phone']) && empty($_POST)): ?>
                            <span class="saved-indicator">(из cookies)</span>
                        <?php endif; ?>
                    </label>
                    <input type="tel"
                           name="phone"
                           class="<?= hasError('phone', $fieldErrors) ?>"
                           value="<?= htmlspecialchars($formData['phone'] ?? '') ?>"
                           placeholder="+7 (999) 123-45-67">
                    <div class="hint">
                        <strong>Допустимо:</strong> цифры, пробелы, дефисы, скобки, знак +
                    </div>
                    <?php if ($msg = getErrorMessage('phone', $fieldErrors)): ?>
                        <div class="field-error-message"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>
                        Email *
                        <?php if (isset($savedData['email']) && empty($_POST)): ?>
                            <span class="saved-indicator">(из cookies)</span>
                        <?php endif; ?>
                    </label>
                    <input type="email"
                           name="email"
                           class="<?= hasError('email', $fieldErrors) ?>"
                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                           placeholder="ivan@example.com">
                    <div class="hint">
                        <strong>Допустимо:</strong> стандартный формат email (name@domain.com)
                    </div>
                    <?php if ($msg = getErrorMessage('email', $fieldErrors)): ?>
                        <div class="field-error-message"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Дата рождения -->
                <div class="form-group">
                    <label>
                        Дата рождения *
                        <?php if (isset($savedData['birth_date']) && empty($_POST)): ?>
                            <span class="saved-indicator">(из cookies)</span>
                        <?php endif; ?>
                    </label>
                    <input type="date"
                           name="birth_date"
                           class="<?= hasError('birth_date', $fieldErrors) ?>"
                           value="<?= htmlspecialchars($formData['birth_date'] ?? '') ?>"
                           max="<?= date('Y-m-d') ?>">
                    <div class="hint">
                        <strong>Формат:</strong> ГГГГ-ММ-ДД (например, 1990-05-15)<br>
                        <strong>Важно:</strong> дата не может быть в будущем
                    </div>
                    <?php if ($msg = getErrorMessage('birth_date', $fieldErrors)): ?>
                        <div class="field-error-message"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Пол -->
                <div class="form-group">
                    <label>
                        Пол *
                        <?php if (isset($savedData['gender']) && empty($_POST)): ?>
                            <span class="saved-indicator">(из cookies)</span>
                        <?php endif; ?>
                    </label>
                    <div class="radio-group <?= hasError('gender', $fieldErrors) ?>">
                        <label class="radio-label">
                            <input type="radio"
                                   name="gender"
                                   value="male"
                                   <?= (isset($formData['gender']) && $formData['gender'] == 'male') ? 'checked' : '' ?>>
                            Мужской
                        </label>
                        <label class="radio-label">
                            <input type="radio"
                                   name="gender"
                                   value="female"
                                   <?= (isset($formData['gender']) && $formData['gender'] == 'female') ? 'checked' : '' ?>>
                            Женский
                        </label>
                    </div>
                    <?php if ($msg = getErrorMessage('gender', $fieldErrors)): ?>
                        <div class="field-error-message"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Языки программирования -->
                <div class="form-group">
                    <label>
                        Любимые языки программирования *
                        <?php if (isset($savedData['languages']) && empty($_POST)): ?>
                            <span class="saved-indicator">(из cookies)</span>
                        <?php endif; ?>
                    </label>
                    <select name="languages[]"
                            multiple
                            class="<?= hasError('languages', $fieldErrors) ?>">
                        <?php foreach ($languages as $lang):
                            $selected = false;
                            if (isset($formData['languages']) && is_array($formData['languages'])) {
                                $selected = in_array($lang['id'], $formData['languages']);
                            }
                        ?>
                            <option value="<?= $lang['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">
                        <strong>Доступные языки:</strong> Pascal, C, C++, JavaScript, PHP, Python, Java, Haskell, Clojure, Prolog, Scala, Go<br>
                        Для выбора нескольких используйте Ctrl+клик (Cmd+клик на Mac)
                    </div>
                    <?php if ($msg = getErrorMessage('languages', $fieldErrors)): ?>
                        <div class="field-error-message"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Биография -->
                <div class="form-group">
                    <label>
                        Биография *
                        <?php if (isset($savedData['biography']) && empty($_POST)): ?>
                            <span class="saved-indicator">(из cookies)</span>
                        <?php endif; ?>
                    </label>
                    <textarea name="biography"
                              rows="6"
                              class="<?= hasError('biography', $fieldErrors) ?>"
                              placeholder="Расскажите о своем опыте программирования..."><?= htmlspecialchars($formData['biography'] ?? '') ?></textarea>
                    <?php if ($msg = getErrorMessage('biography', $fieldErrors)): ?>
                        <div class="field-error-message"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Контракт -->
                <div class="form-group">
                    <div class="checkbox-group <?= hasError('contract', $fieldErrors) ?>">
                        <input type="checkbox"
                               name="contract"
                               id="contract"
                               <?= isset($formData['contract']) ? 'checked' : '' ?>>
                        <label for="contract">Я ознакомлен с контрактом *</label>
                    </div>
                    <?php if ($msg = getErrorMessage('contract', $fieldErrors)): ?>
                        <div class="field-error-message"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit">Сохранить анкету</button>
            </form>

            <div style="text-align: center; margin-top: 20px; color: #888; font-size: 12px;">
                * Обязательные поля
            </div>
        </div>
    </div>
<div style="text-align: center; margin-top: 20px;">
    <a href="login.php" style="color: #667eea; text-decoration: none; font-size: 14px;">
        🔑 Уже есть аккаунт? Войти для редактирования
    </a>
</div>
</body>
</html>