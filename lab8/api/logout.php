<?php
session_start();
session_destroy();
header('Location: /web4/lab8/public/index.html');
exit;
?>