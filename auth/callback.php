<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
startSecureSession();

$error = fn(string $msg) => header('Location: ' . APP_URL . '/login.php?error=' . urlencode($msg)) ?: exit();

// Validate state
if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    $error('Invalid OAuth state.'); exit;
}
unset($_SESSION['oauth_state']);

if (!empty($_GET['error'])) {
    $error('Google login failed: ' . $_GET['error']); exit;
}

$code = $_GET['code'] ?? '';
if (!$code) {
    $error('No authorization code received.'); exit;
}

// Exchange code for tokens
function googlePost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

function googleGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

$tokens = googlePost('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (empty($tokens['access_token'])) {
    $error('Failed to get access token.'); exit;
}

$profile = googleGet('https://www.googleapis.com/oauth2/v2/userinfo', $tokens['access_token']);

if (empty($profile['email'])) {
    $error('Failed to get user profile.'); exit;
}

$email = strtolower($profile['email']);

if (!isEmailAllowed($email)) {
    $error('Access denied. Your Google account is not authorized.'); exit;
}

// Upsert user
$db   = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Update Google info
    $db->prepare('UPDATE users SET google_id=?, name=?, profile_image=? WHERE id=?')
       ->execute([$profile['id'], $profile['name'], $profile['picture'] ?? '', $user['id']]);
    $user['google_id']     = $profile['id'];
    $user['name']          = $profile['name'];
    $user['profile_image'] = $profile['picture'] ?? '';
} else {
    // New user
    $apiKey = generateApiKey();
    $db->prepare('INSERT INTO users (google_id,name,email,profile_image,api_key) VALUES (?,?,?,?,?)')
       ->execute([$profile['id'], $profile['name'], $email, $profile['picture'] ?? '', $apiKey]);
    $userId = $db->lastInsertId();

    // Seed default allowed senders
    $defaults = ['VK-GOOGLE', 'HDFCBK', 'SBIOTP', 'ICICIT', 'AXISBK'];
    $ins = $db->prepare('INSERT IGNORE INTO allowed_senders (user_id, sender_name) VALUES (?,?)');
    foreach ($defaults as $s) { $ins->execute([$userId, $s]); }

    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

loginUser($user);
header('Location: ' . APP_URL . '/dashboard.php');
exit;
