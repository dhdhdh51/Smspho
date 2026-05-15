<?php
/**
 * Server-Sent Events stream endpoint
 * GET /api/stream.php?since_id=N
 * Keeps connection open and pushes new messages instantly
 */
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId  = (int) $_SESSION['user_id'];
$sinceId = (int) ($_GET['since_id'] ?? 0);
$db      = getDB();

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // disable nginx buffering
header('Connection: keep-alive');

// Disable output buffering
if (ob_get_level()) ob_end_clean();

function sendEvent(array $data): void {
    echo 'data: ' . json_encode($data) . "\n\n";
    flush();
}

// Send heartbeat so browser knows connection is alive
sendEvent(['type' => 'connected']);

$maxTime  = 55;   // seconds before reconnect (before PHP/server timeout)
$interval = 2;    // poll DB every 2 seconds
$start    = time();

while (true) {
    // Check if client disconnected
    if (connection_aborted()) break;

    // Time limit
    if (time() - $start >= $maxTime) {
        sendEvent(['type' => 'reconnect']);
        break;
    }

    // Query for new messages
    $stmt = $db->prepare(
        'SELECT id, sender, message, device_name, received_at
         FROM sms_messages
         WHERE user_id = ? AND id > ?
         ORDER BY id ASC
         LIMIT 20'
    );
    $stmt->execute([$userId, $sinceId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $sinceId = max($sinceId, (int) $row['id']);
        sendEvent([
            'type'        => 'sms',
            'id'          => (int) $row['id'],
            'sender'      => $row['sender'],
            'message'     => $row['message'],
            'device_name' => $row['device_name'],
            'received_at' => $row['received_at'],
        ]);
    }

    sleep($interval);
}
