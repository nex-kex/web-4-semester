<?php
session_start();
session_destroy();
header('Location: /~u82269/web4/lab6/admin/login.php');
exit;
?>