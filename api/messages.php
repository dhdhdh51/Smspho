<?php
/**
 * AJAX – Fetch messages for the dashboard.
 * GET /api/messages.php?page=1&limit=50&sender=HDFCBK&q=otp&since_id=123
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

startSecureSession();

// Allow API key auth for mobile app
$apiKey = trim($_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '');
if ($apiKey) {
    require_once __DIR__ . '/../config/database.php';
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE api_key = ? LIMIT 1');
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch();
    if (!$user) { http_response_code(401); echo json_encode(['error' => 'Invalid API key']); exit; }
} elseif (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
} else {
    $user = currentUser();
}
$userId = $user['id'];
$db     = getDB();

$page    = max(1, (int)($_GET['page']    ?? 1));
$limit   = min(100, max(1, (int)($_GET['limit']   ?? 50)));
$offset  = ($page - 1) * $limit;
$sender  = trim($_GET['sender']   ?? '');
$q       = trim($_GET['q']        ?? '');
$sinceId = (int)($_GET['since_id'] ?? 0);

$where  = ['m.user_id = ?'];
$params = [$userId];

if ($sender) {
    $where[]  = 'm.sender = ?';
    $params[] = $sender;
}

if ($q) {
    $where[]  = '(m.message LIKE ? OR m.sender LIKE ?)';
    $like     = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($sinceId > 0) {
    $where[]  = 'm.id > ?';
    $params[] = $sinceId;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM sms_messages m {$whereSQL}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Messages
$msgStmt = $db->prepare(
    "SELECT m.id, m.sender, m.message, m.device_name, m.received_at
     FROM sms_messages m
     {$whereSQL}
     ORDER BY m.received_at DESC, m.id DESC
     LIMIT {$limit} OFFSET {$offset}"
);
$msgStmt->execute($params);
$messages = $msgStmt->fetchAll();

// Senders list
$sendersStmt = $db->prepare(
    'SELECT DISTINCT sender, COUNT(*) as cnt
     FROM sms_messages WHERE user_id = ?
     GROUP BY sender ORDER BY cnt DESC LIMIT 50'
);
$sendersStmt->execute([$userId]);
$senders = $sendersStmt->fetchAll();

echo json_encode([
    'messages' => $messages,
    'senders'  => $senders,
    'total'    => $total,
    'page'     => $page,
    'limit'    => $limit,
]);
