<?php
session_start();

$db_host = 'localhost';
$db_name = 'u82269';
$db_user = 'u82269';
$db_pass = '8571433';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД");
}

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        header('Location: login.php?error=' . urlencode('Заполните все поля'));
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM application WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['login_time'] = time();

        header('Location: edit.php');
        exit;
    } else {
        header('Location: login.php?error=' . urlencode('Неверный логин или пароль'));
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}