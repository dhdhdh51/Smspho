<?php
/**
 * SMS Receive Endpoint
 * POST https://yourdomain.com/api/receive.php
 *
 * Required params (POST body or JSON):
 *   api_key  – user's API key
 *   sender   – SMS sender name/number
 *   message  – SMS body
 *   device   – (optional) device name
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';

// Accept JSON or form-encoded body
$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$post = $json ?? $_POST;

function respond(int $code, array $body): never {
    http_response_code($code);
    echo json_encode($body);
    exit;
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

$apiKey  = trim($post['api_key']  ?? '');
$sender  = trim($post['sender']   ?? '');
$message = trim($post['message']  ?? '');
$device  = trim($post['device']   ?? 'Unknown');

if (!$apiKey || !$sender || !$message) {
    respond(400, ['error' => 'Missing required fields: api_key, sender, message']);
}

// Validate API key – try MASTER_API_KEY first, then per-user keys
$db   = getDB();
$user = null;

if ($apiKey === MASTER_API_KEY) {
    // Master key: find first (only) user in DB
    $stmt = $db->query('SELECT * FROM users ORDER BY id ASC LIMIT 1');
    $user = $stmt->fetch();
} else {
    $stmt = $db->prepare('SELECT * FROM users WHERE api_key = ? LIMIT 1');
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch();
}

if (!$user) {
    respond(401, ['error' => 'Invalid API key']);
}

$userId = $user['id'];

// Check allowed_senders — if list is empty, accept all; otherwise filter
$countStmt = $db->prepare('SELECT COUNT(*) FROM allowed_senders WHERE user_id = ?');
$countStmt->execute([$userId]);
$allowedCount = (int) $countStmt->fetchColumn();

if ($allowedCount > 0) {
    $stmt = $db->prepare(
        'SELECT 1 FROM allowed_senders WHERE user_id = ? AND LOWER(sender_name) = LOWER(?) LIMIT 1'
    );
    $stmt->execute([$userId, $sender]);
    if (!$stmt->fetch()) {
        respond(200, ['status' => 'ignored', 'reason' => 'Sender not in allowlist']);
    }
}

// Sanitize
$sender  = mb_substr($sender,  0, 100);
$message = mb_substr($message, 0, 10000);
$device  = mb_substr($device,  0, 150);

// Insert
$stmt = $db->prepare(
    'INSERT INTO sms_messages (user_id, sender, message, device_name, received_at)
     VALUES (?, ?, ?, ?, NOW())'
);
$stmt->execute([$userId, $sender, $message, $device]);
$msgId = $db->lastInsertId();

respond(200, ['status' => 'ok', 'id' => (int)$msgId]);
