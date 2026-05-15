<?php
require_once __DIR__ . '/config/auth.php';
startSecureSession();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
setcookie('remember_token', '', time() - 3600, '/', '', true, true);
header('Location: ' . APP_URL . '/login.php');
exit;
