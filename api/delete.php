<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
startSecureSession();

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$raw  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$id   = (int)($raw['id'] ?? 0);
$csrf = $raw['csrf_token'] ?? '';

if (!verifyCsrf($csrf)) { http_response_code(403); echo json_encode(['error'=>'CSRF validation failed']); exit; }
if ($id <= 0)           { http_response_code(400); echo json_encode(['error'=>'Invalid ID']); exit; }

$db   = getDB();
$user = currentUser();

$stmt = $db->prepare('DELETE FROM sms_messages WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);

echo json_encode(['status' => $stmt->rowCount() > 0 ? 'ok' : 'not_found']);
