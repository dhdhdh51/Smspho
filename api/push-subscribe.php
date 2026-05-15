<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
startSecureSession();

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$raw  = json_decode(file_get_contents('php://input'), true) ?? [];
$csrf = $raw['csrf_token'] ?? '';
if (!verifyCsrf($csrf)) { http_response_code(403); echo json_encode(['error'=>'CSRF']); exit; }

$sub  = $raw['subscription'] ?? null;
if (!$sub || empty($sub['endpoint'])) { http_response_code(400); echo json_encode(['error'=>'Invalid subscription']); exit; }

$endpoint = $sub['endpoint'];
$p256dh   = $sub['keys']['p256dh'] ?? '';
$auth     = $sub['keys']['auth']   ?? '';

$db   = getDB();
$user = currentUser();

$db->prepare(
    'INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key)
     VALUES (?,?,?,?)
     ON DUPLICATE KEY UPDATE p256dh_key=VALUES(p256dh_key), auth_key=VALUES(auth_key)'
)->execute([$user['id'], $endpoint, $p256dh, $auth]);

echo json_encode(['status' => 'ok']);
