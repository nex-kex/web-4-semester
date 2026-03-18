<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

// Получаем данные
$users = getAllUsers();
$stats = getLanguageStats();
$languages = getLanguages();

// Сообщения
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .header .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .lang-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .stat-card .user-count {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-card .user-count.small {
            font-size: 14px;
            color: #888;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .users-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-edit:hover { background: #e0a800; }
        .btn-delete:hover { background: #c82333; }
        .btn-view:hover { background: #138496; }

        .badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .edited-badge {
            background: #ffc107;
            color: #333;
        }

        .gender-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            min-width: 80px;
        }

        .gender-badge.male {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .gender-badge.female {
            background: #fce4ec;
            color: #c2185b;
            border: 1px solid #f8bbd0;
        }

        /* Убираем лишнюю ширину */
        td .gender-badge {
            width: auto;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            td {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Админ-панель</h1>
            <div class="user-info">
                <span>👤 <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                <a href="logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2 style="margin-bottom: 15px;">📊 Статистика по языкам</h2>
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <div class="lang-name"><?= htmlspecialchars($stat['name']) ?></div>
                    <div class="user-count"><?= $stat['user_count'] ?></div>
                    <div class="user-count small">пользователей</div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 style="margin-bottom: 15px;">👥 Все пользователи (<?= count($users) ?>)</h2>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Языки</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['login'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td><?= date('d.m.Y', strtotime($user['birth_date'])) ?></td>
                            <td>
                                <span class="gender-badge <?= $user['gender'] ?>">
                                    <?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?>
                                </span>
                            </td>
                            <td>
                                <div style="max-width: 200px;">
                                    <?= htmlspecialchars($user['languages_list'] ?? '—') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['is_edited']): ?>
                                    <span class="badge edited-badge">✏️ Изменено</span>
                                <?php else: ?>
                                    <span class="badge">✅ Новый</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="edit.php?id=<?= $user['id'] ?>" class="btn-edit">✏️ Ред.</a>
                                    <a href="delete.php?id=<?= $user['id'] ?>"
                                       class="btn-delete"
                                       onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['full_name']) ?>?')">🗑️ Уд.</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>