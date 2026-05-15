<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    if (empty($_SESSION['user_id'])) return false;
    if (empty($_SESSION['last_activity'])) return false;
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'            => $_SESSION['user_id']      ?? null,
        'name'          => $_SESSION['user_name']    ?? '',
        'email'         => $_SESSION['user_email']   ?? '',
        'profile_image' => $_SESSION['profile_image'] ?? '',
        'api_key'       => $_SESSION['api_key']      ?? '',
    ];
}

function generateCsrf(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isEmailAllowed(string $email): bool {
    $allowed = array_map('trim', explode(',', ALLOWED_EMAILS));
    return in_array(strtolower($email), array_map('strtolower', $allowed), true);
}

function generateApiKey(): string {
    return 'sms_' . bin2hex(random_bytes(24));
}

function loginUser(array $user): void {
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? '';
    $_SESSION['api_key']       = $user['api_key']       ?? '';
    $_SESSION['last_activity'] = time();
}
