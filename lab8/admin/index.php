<?php
session_start();

// Подключаем functions.php (там есть getGymDBConnection)
require_once __DIR__ . '/../includes/functions.php';

// Проверка авторизации админа
if (!isset($_SESSION['gym_admin_logged_in']) || $_SESSION['gym_admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$pdo = getGymDBConnection();
$stmt = $pdo->query("SELECT id, login, name, phone, email, comment, status, created_at FROM gym_applications ORDER BY id DESC");
$users = $stmt->fetchAll();

$stats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
        SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM gym_applications
")->fetch();

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Bull Gym</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; background: #0a0a0a; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: #1a1a1a; padding: 20px; border-radius: 10px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #b61815; }
        .header h1 { color: #b61815; }
        .logout-btn { background: #b61815; color: white; padding: 8px 20px; text-decoration: none; border-radius: 5px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #1a1a1a; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #3a3a3a; }
        .stat-number { font-size: 32px; font-weight: bold; color: #b61815; }
        .stat-label { color: #888; margin-top: 5px; }
        .message { background: #28a745; color: white; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #b61815; color: white; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; background: #1a1a1a; border-radius: 10px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3a3a3a; color: #fff; }
        th { background: #2a2a2a; color: #b61815; }
        tr:hover { background: #2a2a2a; }
        .status-new { background: #b61815; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; display: inline-block; }
        .status-processed { background: #ffc107; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 12px; display: inline-block; }
        .status-completed { background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; display: inline-block; }
        .btn-edit, .btn-delete { padding: 4px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; margin: 0 2px; display: inline-block; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-delete { background: #b61815; color: white; }
        .btn-edit:hover, .btn-delete:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐂 Bull Gym - Админ-панель</h1>
            <a href="logout.php" class="logout-btn">Выйти</a>
        </div>

        <?php if ($message): ?>
            <div class="message">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Всего заявок</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['new'] ?></div>
                <div class="stat-label">Новые</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['processed'] ?></div>
                <div class="stat-label">В обработке</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['completed'] ?></div>
                <div class="stat-label">Завершено</div>
            </div>
        </div>

        <h2 style="color: #fff; margin-bottom: 20px;">Все заявки</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['login']) ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="status-<?= $user['status'] ?>">
                                <?= $user['status'] == 'new' ? 'Новый' : ($user['status'] == 'processed' ? 'В обработке' : 'Завершен') ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $user['id'] ?>" class="btn-edit">Ред.</a>
                            <a href="delete.php?id=<?= $user['id'] ?>" class="btn-delete" onclick="return confirm('Удалить заявку?')">Уд.</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>