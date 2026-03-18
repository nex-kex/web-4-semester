<?php
// Список языков (id совпадает с таблицей language)
$languages = [
    1  => 'Pascal',   2  => 'C',          3  => 'C++',
    4  => 'JavaScript', 5 => 'PHP',        6  => 'Python',
    7  => 'Java',     8  => 'Haskell',    9  => 'Clojure',
    10 => 'Prolog',   11 => 'Scala',      12 => 'Go',
];

// Хелперы
function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function fieldVal($key) { global $values; return h($values[$key] ?? ''); }
function hasErr($key)   { global $errors; return !empty($errors[$key]); }
function errMsg($key)   { global $errors; return h($errors[$key] ?? ''); }
function isCheckedRadio($key, $val) {
    global $values;
    return ($values[$key] ?? '') === $val ? 'checked' : '';
}
function isSelectedLang($id) {
    global $values;
    return in_array($id, (array)($values['languages'] ?? [])) ? 'selected' : '';
}

// Определяем, вошёл ли пользователь (переменная $sessionUser приходит из index.php)
$isLoggedIn = !empty($sessionUser);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Анкета</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --sage: #7D9B76; --sage-light: #B2C9AD; --sage-pale: #EEF3ED;
      --sage-dark: #4F6B4A; --text: #2E3A2C; --muted: #6B7F69;
      --err-bg: #fdf0ef; --err-border: #e8b4b0; --err-text: #C0392B;
      --err-field: #e05c4a; --radius: 8px;
    }
    body {
      font-family: 'Segoe UI', Arial, sans-serif; background: var(--sage-pale);
      color: var(--text); min-height: 100vh;
      display: flex; justify-content: center; align-items: flex-start; padding: 2rem 1rem;
    }
    .card {
      background: #fff; border-radius: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
      padding: 2.4rem 2.8rem; width: 100%; max-width: 600px;
    }
    .card-header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 1.8rem;
    }
    h1 { font-size: 1.5rem; font-weight: 600; color: var(--sage-dark); }

    /* Статус-бар авторизации */
    .auth-bar {
      font-size: .85rem; color: var(--muted);
      display: flex; align-items: center; gap: .6rem;
    }
    .auth-bar strong { color: var(--sage-dark); }
    .btn-logout {
      font-size: .82rem; padding: .3rem .65rem;
      background: transparent; border: 1.5px solid var(--sage-light);
      border-radius: 6px; color: var(--muted); cursor: pointer;
      text-decoration: none; transition: border-color .15s, color .15s;
    }
    .btn-logout:hover { border-color: var(--sage); color: var(--sage-dark); }

    /* Уведомления */
    .msg-success {
      background: #f0f6ef; border: 1.5px solid var(--sage-light);
      border-radius: var(--radius); padding: .85rem 1.1rem;
      margin-bottom: 1rem; color: var(--sage-dark); font-weight: 500;
    }
    .msg-credentials {
      background: #f5f8f4; border: 1.5px solid var(--sage);
      border-radius: var(--radius); padding: .85rem 1.1rem;
      margin-bottom: 1.1rem; font-size: .92rem; color: var(--text);
      line-height: 1.5;
    }
    .msg-credentials a { color: var(--sage-dark); }
    .msg-error-hint {
      background: var(--err-bg); border: 1.5px solid var(--err-border);
      border-radius: var(--radius); padding: .75rem 1rem;
      margin-bottom: 1.2rem; color: var(--err-text); font-size: .92rem;
    }

    /* Поля */
    .field { margin-bottom: 1.2rem; display: flex; flex-direction: column; gap: .3rem; }
    .field-label { font-size: .88rem; font-weight: 500; color: var(--muted); }
    .field-label .req { color: var(--sage); margin-left: 2px; }
    input[type="text"], input[type="tel"], input[type="email"], input[type="date"],
    textarea, select {
      width: 100%; padding: .55rem .8rem;
      border: 1.5px solid var(--sage-light); border-radius: var(--radius);
      font-size: .95rem; font-family: inherit; color: var(--text);
      background: #fff; outline: none; transition: border-color .18s, box-shadow .18s;
    }
    input:focus, textarea:focus, select:focus {
      border-color: var(--sage); box-shadow: 0 0 0 3px rgba(125,155,118,.18);
    }
    .field-error input, .field-error textarea, .field-error select {
      border-color: var(--err-field);
    }
    .field-error input:focus, .field-error textarea:focus, .field-error select:focus {
      box-shadow: 0 0 0 3px rgba(224,92,74,.15);
    }
    .err-msg { font-size: .82rem; color: var(--err-text); margin-top: .1rem; }
    textarea { resize: vertical; min-height: 100px; }
    select[multiple] { min-height: 160px; }
    .radio-group { display: flex; gap: 1.4rem; margin-top: .1rem; }
    .radio-group label {
      display: flex; align-items: center; gap: .4rem;
      cursor: pointer; color: var(--text); font-weight: 400; font-size: .95rem;
    }
    input[type="radio"] { accent-color: var(--sage); width: 16px; height: 16px; }
    .field-error .radio-group label { color: var(--err-text); }
    .checkbox-label {
      display: flex; align-items: center; gap: .55rem;
      cursor: pointer; font-size: .92rem; color: var(--text);
    }
    input[type="checkbox"] { accent-color: var(--sage); width: 17px; height: 17px; flex-shrink: 0; }
    .field-error .checkbox-label { color: var(--err-text); }
    small { font-size: .78rem; color: var(--muted); }

    /* Кнопка */
    .btn-save {
      display: block; width: 100%; margin-top: 1.6rem; padding: .75rem;
      background: var(--sage); color: #fff; border: none;
      border-radius: var(--radius); font-size: 1rem; font-weight: 600;
      cursor: pointer; letter-spacing: .03em;
      box-shadow: 0 2px 6px rgba(79,107,74,.25);
      transition: background .18s, transform .14s, box-shadow .14s;
    }
    .btn-save:hover { background: var(--sage-dark); transform: translateY(-2px);
                      box-shadow: 0 6px 14px rgba(79,107,74,.30); }
    .btn-save:active { transform: translateY(0); }

    /* Ссылка на вход */
    .login-hint {
      margin-top: 1.1rem; text-align: center;
      font-size: .85rem; color: var(--muted);
    }
    .login-hint a { color: var(--sage-dark); }
  </style>
</head>
<body>
<div class="card">

  <div class="card-header">
    <h1>Анкета участника</h1>
    <?php if ($isLoggedIn): ?>
      <div class="auth-bar">
        <span>Вы вошли как <strong><?= h($sessionUser['login']) ?></strong></span>
        <a href="login.php?logout=1" class="btn-logout">Выйти</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($messages['success'])): ?>
    <div class="msg-success"><?= h($messages['success']) ?></div>
  <?php endif; ?>

  <?php if (!empty($messages['credentials'])): ?>
    <div class="msg-credentials">🔑 <?= $messages['credentials'] /* уже содержит safe HTML */ ?></div>
  <?php endif; ?>

  <?php if (!empty($messages['error_hint'])): ?>
    <div class="msg-error-hint"><?= h($messages['error_hint']) ?></div>
  <?php endif; ?>

  <form action="index.php" method="POST">

    <!-- 1. ФИО -->
    <div class="field <?= hasErr('fio') ? 'field-error' : '' ?>">
      <label class="field-label" for="fio">ФИО <span class="req">*</span></label>
      <input type="text" id="fio" name="fio" placeholder="Иванов Иван Иванович"
             maxlength="150" value="<?= fieldVal('fio') ?>">
      <?php if (hasErr('fio')): ?><span class="err-msg"><?= errMsg('fio') ?></span><?php endif; ?>
    </div>

    <!-- 2. Телефон -->
    <div class="field <?= hasErr('phone') ? 'field-error' : '' ?>">
      <label class="field-label" for="phone">Телефон <span class="req">*</span></label>
      <input type="tel" id="phone" name="phone" placeholder="+7 (999) 123-45-67"
             value="<?= fieldVal('phone') ?>">
      <?php if (hasErr('phone')): ?><span class="err-msg"><?= errMsg('phone') ?></span><?php endif; ?>
    </div>

    <!-- 3. E-mail -->
    <div class="field <?= hasErr('email') ? 'field-error' : '' ?>">
      <label class="field-label" for="email">E-mail <span class="req">*</span></label>
      <input type="email" id="email" name="email" placeholder="example@mail.ru"
             value="<?= fieldVal('email') ?>">
      <?php if (hasErr('email')): ?><span class="err-msg"><?= errMsg('email') ?></span><?php endif; ?>
    </div>

    <!-- 4. Дата рождения -->
    <div class="field <?= hasErr('birthdate') ? 'field-error' : '' ?>">
      <label class="field-label" for="birthdate">Дата рождения <span class="req">*</span></label>
      <input type="date" id="birthdate" name="birthdate" value="<?= fieldVal('birthdate') ?>">
      <?php if (hasErr('birthdate')): ?><span class="err-msg"><?= errMsg('birthdate') ?></span><?php endif; ?>
    </div>

    <!-- 5. Пол -->
    <div class="field <?= hasErr('gender') ? 'field-error' : '' ?>">
      <label class="field-label">Пол <span class="req">*</span></label>
      <div class="radio-group">
        <label>
          <input type="radio" name="gender" value="male"   <?= isCheckedRadio('gender','male') ?>> Мужской
        </label>
        <label>
          <input type="radio" name="gender" value="female" <?= isCheckedRadio('gender','female') ?>> Женский
        </label>
      </div>
      <?php if (hasErr('gender')): ?><span class="err-msg"><?= errMsg('gender') ?></span><?php endif; ?>
    </div>

    <!-- 6. Языки программирования -->
    <div class="field <?= hasErr('languages') ? 'field-error' : '' ?>">
      <label class="field-label" for="languages">
        Любимый язык программирования <span class="req">*</span>
      </label>
      <select id="languages" name="languages[]" multiple="multiple">
        <?php foreach ($languages as $id => $name): ?>
          <option value="<?= $id ?>" <?= isSelectedLang($id) ?>><?= h($name) ?></option>
        <?php endforeach; ?>
      </select>
      <small>Удерживайте Ctrl (⌘ на Mac) для выбора нескольких</small>
      <?php if (hasErr('languages')): ?><span class="err-msg"><?= errMsg('languages') ?></span><?php endif; ?>
    </div>

    <!-- 7. Биография -->
    <div class="field <?= hasErr('biography') ? 'field-error' : '' ?>">
      <label class="field-label" for="biography">Биография <span class="req">*</span></label>
      <textarea id="biography" name="biography"
                placeholder="Расскажите о себе..."><?= fieldVal('biography') ?></textarea>
      <?php if (hasErr('biography')): ?><span class="err-msg"><?= errMsg('biography') ?></span><?php endif; ?>
    </div>

    <!-- 8. Согласие -->
    <div class="field <?= hasErr('agreed') ? 'field-error' : '' ?>">
      <label class="checkbox-label">
        <input type="checkbox" name="agreed" value="1">
        С контрактом ознакомлен(а) <span class="req">*</span>
      </label>
      <?php if (hasErr('agreed')): ?><span class="err-msg"><?= errMsg('agreed') ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn-save">
      <?= $isLoggedIn ? 'Сохранить изменения' : 'Сохранить' ?>
    </button>

  </form>

  <?php if (!$isLoggedIn): ?>
    <p class="login-hint">Уже отправляли анкету? <a href="login.php">Войдите</a>, чтобы изменить данные.</p>
  <?php endif; ?>

</div>
</body>
</html>
