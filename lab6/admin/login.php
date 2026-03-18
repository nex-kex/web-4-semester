<?php
// HTTP Basic Authentication
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Требуется авторизация';
    exit;
}

require_once __DIR__ . '/../config/database.php';

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

// Проверка в БД
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Неверный логин или пароль';
    exit;
}

// Успешная авторизация - запускаем сессию
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = $username;

// Перенаправляем на главную админки
header('Location: index.php');
exit;
?>