<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
startSecureSession();

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

$db   = getDB();
$user = currentUser();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT id, sender_name FROM allowed_senders WHERE user_id = ? ORDER BY sender_name');
    $stmt->execute([$user['id']]);
    echo json_encode(['senders' => $stmt->fetchAll()]);

} elseif ($method === 'POST') {
    $raw    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $raw['action']      ?? '';
    $csrf   = $raw['csrf_token']  ?? '';
    if (!verifyCsrf($csrf)) { http_response_code(403); echo json_encode(['error'=>'CSRF']); exit; }

    if ($action === 'add') {
        $name = mb_strtoupper(trim($raw['sender_name'] ?? ''));
        if (!$name) { http_response_code(400); echo json_encode(['error'=>'Empty name']); exit; }
        $db->prepare('INSERT IGNORE INTO allowed_senders (user_id, sender_name) VALUES (?,?)')->execute([$user['id'], $name]);
        echo json_encode(['status'=>'ok']);

    } elseif ($action === 'remove') {
        $id = (int)($raw['id'] ?? 0);
        $db->prepare('DELETE FROM allowed_senders WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
        echo json_encode(['status'=>'ok']);
    }
} else {
    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
}
