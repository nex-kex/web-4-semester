<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $pdo = getGymDBConnection();
        $stmt = $pdo->prepare("DELETE FROM gym_applications WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: index.php?message=' . urlencode('Заявка успешно удалена'));
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode('Ошибка при удалении'));
    }
} else {
    header('Location: index.php');
}
exit;
?>