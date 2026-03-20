<?php
session_start();
session_destroy();
header('Location: /lab8/public/index.html');
exit;
?>