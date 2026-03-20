<?php
require_once __DIR__ . '/functions.php';

function authenticateUser($login, $password) {
    $pdo = getGymDBConnection();
    $stmt = $pdo->prepare("SELECT id, login, password_hash, name FROM gym_applications WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_start();
        $_SESSION['gym_user_id'] = $user['id'];
        $_SESSION['gym_user_login'] = $user['login'];
        $_SESSION['gym_user_name'] = $user['name'];
        return true;
    }

    return false;
}

function authenticateAdmin($username, $password) {
    $pdo = getGymDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM gym_admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_start();
        $_SESSION['gym_admin_logged_in'] = true;
        $_SESSION['gym_admin_username'] = $username;
        return true;
    }

    return false;
}

function isUserLoggedIn() {
    session_start();
    return isset($_SESSION['gym_user_id']) && !empty($_SESSION['gym_user_id']);
}

function isAdminLoggedIn() {
    session_start();
    return isset($_SESSION['gym_admin_logged_in']) && $_SESSION['gym_admin_logged_in'] === true;
}

function requireAuth() {
    if (!isUserLoggedIn()) {
        header('Location: /lab8/public/login.html');
        exit;
    }
}

function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: /lab8/admin/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isUserLoggedIn()) return null;

    $pdo = getGymDBConnection();
    $stmt = $pdo->prepare("SELECT id, login, name, phone, email, comment, status FROM gym_applications WHERE id = ?");
    $stmt->execute([$_SESSION['gym_user_id']]);
    return $stmt->fetch();
}
?>