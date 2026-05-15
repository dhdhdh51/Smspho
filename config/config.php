<?php
// ─── Database ──────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'smspho_db');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// ─── Google OAuth ──────────────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'https://yourdomain.com/auth/callback.php');

// ─── App ───────────────────────────────────────────────────────────────────
define('APP_NAME',   'Private SMS Dashboard');
define('APP_URL',    'https://yourdomain.com');   // no trailing slash
define('APP_VERSION', '1.0.0');

// ─── Security ──────────────────────────────────────────────────────────────
define('SESSION_TIMEOUT',  3600);          // seconds (1 hour)
define('MASTER_API_KEY',   'CHANGE_THIS_TO_A_LONG_RANDOM_STRING_MIN_32_CHARS');

// ─── Access control ────────────────────────────────────────────────────────
// Comma-separated list of Google e-mails allowed to log in.
define('ALLOWED_EMAILS', 'your_email@gmail.com');

// ─── Push Notifications (VAPID) ────────────────────────────────────────────
// Generate at: https://vapidkeys.com/
define('VAPID_PUBLIC_KEY',  'YOUR_VAPID_PUBLIC_KEY');
define('VAPID_PRIVATE_KEY', 'YOUR_VAPID_PRIVATE_KEY');
