<?php
// Logout Controller
require_once __DIR__ . '/config/auth.php';

logout_user();

header('Location: ' . BASE_URL . 'login.php?logged_out=1');
exit;
