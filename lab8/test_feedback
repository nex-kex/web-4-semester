<?php
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Тест сохранения обратной связи</h1>";

try {
    $pdo = getGymDBConnection();

    // Проверяем таблицу
    $result = $pdo->query("SHOW TABLES LIKE 'gym_feedback'");
    if ($result->rowCount() == 0) {
        echo "<p style='color:red'>❌ Таблица gym_feedback не существует</p>";

        // Создаем таблицу
        $pdo->exec("
            CREATE TABLE gym_feedback (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(100) NOT NULL,
                comment TEXT,
                status ENUM('new', 'processing', 'completed') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )
        ");
        echo "<p style='color:green'>✅ Таблица создана</p>";
    } else {
        echo "<p style='color:green'>✅ Таблица существует</p>";
    }

    // Тестовая вставка
    $stmt = $pdo->prepare("
        INSERT INTO gym_feedback (name, phone, email, comment, status)
        VALUES ('Тестовый пользователь', '+7 (999) 123-45-67', 'test@example.com', 'Тестовое сообщение для проверки', 'new')
    ");

    if ($stmt->execute()) {
        $id = $pdo->lastInsertId();
        echo "<p style='color:green'>✅ Тестовая запись добавлена (ID: $id)</p>";
    } else {
        echo "<p style='color:red'>❌ Ошибка при вставке</p>";
    }

    // Показываем последние записи
    $stmt = $pdo->query("SELECT * FROM gym_feedback ORDER BY id DESC LIMIT 10");
    $feedbacks = $stmt->fetchAll();

    echo "<h2>Последние заявки:</h2>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background:#333; color:#fff;'><th>ID</th><th>Имя</th><th>Телефон</th><th>Email</th><th>Комментарий</th><th>Статус</th><th>Дата</th></tr>";
    foreach ($feedbacks as $fb) {
        echo "<tr>";
        echo "<td>{$fb['id']}</td>";
        echo "<td>" . htmlspecialchars($fb['name']) . "</td>";
        echo "<td>" . htmlspecialchars($fb['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($fb['email']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($fb['comment'], 0, 50)) . "</td>";
        echo "<td>{$fb['status']}</td>";
        echo "<td>{$fb['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Ошибка: " . $e->getMessage() . "</p>";
}
?>