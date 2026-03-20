<?php
session_start();
session_destroy();
header('Location: /lab7/public/index.html');
exit;
?>