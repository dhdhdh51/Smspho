<?php
/**
 * Mobile login endpoint
 * POST /api/login.php
 * Body: {"email":"x@x.com","password":"pass"}
 * Returns: {"api_key":"sms_xxx","name":"John"}
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$email = trim($data['email'] ?? '');
$pass  = $data['password']   ?? '';

if (!$email || !$pass) {
    http_response_code(400); echo json_encode(['error' => 'Email and password required']); exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401); echo json_encode(['error' => 'Email registered nahi hai']); exit;
}
if (!$user['password']) {
    http_response_code(401); echo json_encode(['error' => 'Aapka account Google se linked hai. Dashboard mein jaake Settings > Set Password karo, phir yahan login karo.']); exit;
}
if (!password_verify($pass, $user['password'])) {
    http_response_code(401); echo json_encode(['error' => 'Password galat hai']); exit;
}

echo json_encode([
    'api_key' => $user['api_key'],
    'name'    => $user['name'],
    'email'   => $user['email'],
]);
