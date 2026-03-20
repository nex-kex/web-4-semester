<?php
session_start();

if (!isset($_SESSION['gym_admin_logged_in']) || $_SESSION['gym_admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$pdo = getGymDBConnection();

// Пользователи (зарегистрированные)
$usersStmt = $pdo->query("SELECT id, login, name, phone, email, status, created_at, updated_at FROM gym_applications ORDER BY id DESC");
$users = $usersStmt->fetchAll();

// Статистика по пользователям
$usersStats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
        SUM(CASE WHEN status = 'edited' THEN 1 ELSE 0 END) as edited
    FROM gym_applications
")->fetch();

// Обратная связь (заявки с главной)
$feedbackStmt = $pdo->query("SELECT id, name, phone, email, comment, status, created_at FROM gym_feedback ORDER BY id DESC");
$feedbacks = $feedbackStmt->fetchAll();

// Статистика по обратной связи
$feedbackStats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM gym_feedback
")->fetch();

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Функция для обрезки текста (без mb_substr)
function truncateText($text, $length = 50) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Bull Gym</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #b61815;
        }

        .header h1 {
            color: #b61815;
            font-family: 'Oswald', sans-serif;
        }

        .logout-btn {
            background: #b61815;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
        }

        .logout-btn:hover {
            background: #8f1310;
        }

        .section-title {
            color: #b61815;
            margin: 30px 0 20px 0;
            font-family: 'Oswald', sans-serif;
            border-left: 4px solid #b61815;
            padding-left: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #3a3a3a;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #b61815;
        }

        .stat-label {
            color: #888;
            margin-top: 5px;
        }

        .message {
            background: #28a745;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #b61815;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            background: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #3a3a3a;
            color: #fff;
        }

        th {
            background: #2a2a2a;
            color: #b61815;
        }

        tr:hover {
            background: #2a2a2a;
        }

        .status-new {
            background: #b61815;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }

        .status-edited {
            background: #ffc107;
            color: #333;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }

        .status-processing {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }

        .status-completed {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }

        .btn-edit, .btn-delete {
            padding: 4px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            margin: 0 2px;
            display: inline-block;
        }

        .btn-edit {
            background: #ffc107;
            color: #333;
        }

        .btn-delete {
            background: #b61815;
            color: white;
        }

        .btn-edit:hover, .btn-delete:hover {
            opacity: 0.8;
        }

        .comment-cell {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            th, td {
                font-size: 12px;
                padding: 8px;
            }

            .comment-cell {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐂 Bull Gym - Панель управления</h1>
            <a href="logout.php" class="logout-btn">Выйти</a>
        </div>

        <?php if ($message): ?>
            <div class="message">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- СТАТИСТИКА ПОЛЬЗОВАТЕЛЕЙ (зарегистрированные) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $usersStats['total'] ?></div>
                <div class="stat-label">Всего пользователей</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $usersStats['new'] ?></div>
                <div class="stat-label">Новые</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $usersStats['edited'] ?></div>
                <div class="stat-label">Редактировали</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $feedbackStats['total'] ?></div>
                <div class="stat-label">Всего заявок</div>
            </div>
        </div>

        <!-- ТАБЛИЦА ЗАРЕГИСТРИРОВАННЫХ ПОЛЬЗОВАТЕЛЕЙ -->
        <h2 class="section-title">👥 Зарегистрированные пользователи (<?= count($users) ?>)</h2>
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
                        <th>Дата регистрации</th>
                        <th>Обновлен</th>
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
                                <?= $user['status'] == 'new' ? 'Новый' : 'Редактирован' ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($user['updated_at'])) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $user['id'] ?>&type=user" class="btn-edit">Ред.</a>
                            <a href="delete.php?id=<?= $user['id'] ?>&type=user" class="btn-delete" onclick="return confirm('Удалить пользователя?')">Уд.</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- СТАТИСТИКА ОБРАТНОЙ СВЯЗИ -->
        <h2 class="section-title">📝 Обратная связь с сайта (<?= $feedbackStats['total'] ?>)</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $feedbackStats['new'] ?></div>
                <div class="stat-label">Новые</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $feedbackStats['processing'] ?></div>
                <div class="stat-label">В обработке</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $feedbackStats['completed'] ?></div>
                <div class="stat-label">Завершено</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $feedbackStats['total'] ?></div>
                <div class="stat-label">Всего заявок</div>
            </div>
        </div>

        <!-- ТАБЛИЦА ОБРАТНОЙ СВЯЗИ -->
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Комментарий</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedbacks as $fb): ?>
                    <tr>
                        <td>#<?= $fb['id'] ?></td>
                        <td><?= htmlspecialchars($fb['name']) ?></td>
                        <td><?= htmlspecialchars($fb['phone']) ?></td>
                        <td><?= htmlspecialchars($fb['email']) ?></td>
                        <td class="comment-cell" title="<?= htmlspecialchars($fb['comment']) ?>">
                            <?= htmlspecialchars(truncateText($fb['comment'], 50)) ?>
                        </td>
                        <td>
                            <span class="status-<?= $fb['status'] ?>">
                                <?= $fb['status'] == 'new' ? 'Новый' : ($fb['status'] == 'processing' ? 'В обработке' : 'Завершен') ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($fb['created_at'])) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $fb['id'] ?>&type=feedback" class="btn-edit">Ред.</a>
                            <a href="delete.php?id=<?= $fb['id'] ?>&type=feedback" class="btn-delete" onclick="return confirm('Удалить заявку?')">Уд.</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>