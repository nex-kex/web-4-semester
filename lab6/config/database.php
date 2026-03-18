<?php
// Подключение к БД (DRY - вынесено в отдельный файл)
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $db_host = 'localhost';
        $db_name = 'u82269';
        $db_user = 'u82269';
        $db_pass = '8571433';

        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Ошибка подключения к БД: " . $e->getMessage());
        }
    }

    return $pdo;
}
?>