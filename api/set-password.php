<?php
/**
 * Set/change password for logged-in user (web session auth only)
 * POST with JSON: {"password":"newpass","confirm":"newpass"}
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

startSecureSession();

if (!isLoggedIn()) {
    http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$pass    = $data['password'] ?? '';
$confirm = $data['confirm']  ?? '';

if (strlen($pass) < 6) {
    http_response_code(400); echo json_encode(['error' => 'Password kam se kam 6 characters ka hona chahiye']); exit;
}
if ($pass !== $confirm) {
    http_response_code(400); echo json_encode(['error' => 'Passwords match nahi kar rahe']); exit;
}

$hash   = password_hash($pass, PASSWORD_BCRYPT);
$user   = currentUser();
$db     = getDB();
$stmt   = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
$stmt->execute([$hash, $user['id']]);

echo json_encode(['success' => true, 'message' => 'Password set ho gaya! Ab app mein login karo.']);
