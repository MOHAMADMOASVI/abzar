<?php
require __DIR__ . '/auth.php';
abzar_logout();
header('Location: login.php');
exit;
