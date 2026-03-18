<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    require_once 'login.php';
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $pdo = getDBConnection();

        // Языки удалятся каскадно благодаря FOREIGN KEY
        $stmt = $pdo->prepare("DELETE FROM application WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            header("Location: index.php?message=" . urlencode('Пользователь успешно удален'));
        } else {
            header("Location: index.php?error=" . urlencode('Пользователь не найден'));
        }
    } catch (Exception $e) {
        header("Location: index.php?error=" . urlencode('Ошибка при удалении'));
    }
} else {
    header('Location: index.php');
}
exit;
?>