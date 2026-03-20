<?php
session_start();

if (!isset($_SESSION['gym_admin_logged_in']) || $_SESSION['gym_admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'user';

if ($id) {
    try {
        $pdo = getGymDBConnection();

        if ($type === 'user') {
            $stmt = $pdo->prepare("DELETE FROM gym_applications WHERE id = ?");
            $message = 'Пользователь успешно удален';
        } else {
            $stmt = $pdo->prepare("DELETE FROM gym_feedback WHERE id = ?");
            $message = 'Заявка успешно удалена';
        }

        $stmt->execute([$id]);
        header('Location: index.php?message=' . urlencode($message));
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode('Ошибка при удалении'));
    }
} else {
    header('Location: index.php');
}
exit;
?>