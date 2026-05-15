<?php
// ─── Database ──────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'smspho_db');
define('DB_USER', 'your_db_user');       // ← cPanel DB username daalo
define('DB_PASS', 'your_db_password');   // ← cPanel DB password daalo

// ─── Google OAuth ──────────────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'); // ← Google Console se
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');                        // ← Google Console se
define('GOOGLE_REDIRECT_URI',  'https://sms.bharatseo.site/auth/callback.php');

// ─── App ───────────────────────────────────────────────────────────────────
define('APP_NAME',    'Private SMS Dashboard');
define('APP_URL',     'https://sms.bharatseo.site');  // no trailing slash
define('APP_VERSION', '1.0.0');

// ─── Security ──────────────────────────────────────────────────────────────
define('SESSION_TIMEOUT', 3600); // seconds (1 hour)
define('MASTER_API_KEY',  'CHANGE_THIS_TO_A_LONG_RANDOM_STRING_MIN_32_CHARS'); // ← badlo zaroor

// ─── Access control ────────────────────────────────────────────────────────
// '' ya '*' = koi bhi Google account login kar sakta hai (open multi-user)
// Restrict karna ho toh: 'user1@gmail.com,user2@gmail.com'
define('ALLOWED_EMAILS', '*');

// ─── Push Notifications (VAPID) — Optional ─────────────────────────────────
// Generate at: https://vapidkeys.com/
define('VAPID_PUBLIC_KEY',  'YOUR_VAPID_PUBLIC_KEY');
define('VAPID_PRIVATE_KEY', 'YOUR_VAPID_PRIVATE_KEY');
