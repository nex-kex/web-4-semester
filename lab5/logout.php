<?php
session_start();
session_destroy();
header('Location: login.php?success=' . urlencode('Вы успешно вышли из системы'));
exit;