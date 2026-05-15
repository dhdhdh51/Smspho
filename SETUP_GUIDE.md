# Private SMS Dashboard – Setup Guide

## Requirements
- PHP 8.0+ with extensions: `pdo_mysql`, `curl`, `gd`, `json`, `openssl`
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite`, `mod_headers` enabled
- HTTPS (required for PWA + Service Worker)
- cPanel hosting (or any shared/VPS)

---

## Step 1 – Upload Files

Upload the entire project to your `public_html/` directory via:
- cPanel File Manager → Upload ZIP → Extract
- FTP client (FileZilla)
- SSH: `git clone`

---

## Step 2 – Create MySQL Database

1. In cPanel → **MySQL Databases**
2. Create database: `smspho_db`
3. Create user with strong password
4. Assign user to database (All Privileges)
5. Note: host, dbname, username, password

---

## Step 3 – Configure the App

Edit `config/config.php`:

```php
define('DB_HOST',   'localhost');
define('DB_NAME',   'smspho_db');
define('DB_USER',   'your_cpanel_db_user');
define('DB_PASS',   'your_secure_password');

define('GOOGLE_CLIENT_ID',     'xxxx.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your_secret');
define('GOOGLE_REDIRECT_URI',  'https://yourdomain.com/auth/callback.php');

define('APP_URL',    'https://yourdomain.com');
define('MASTER_API_KEY', 'generate_a_random_64_char_string_here');

define('ALLOWED_EMAILS', 'your_email@gmail.com');
```

Generate a secure API key (run in terminal):
```bash
php -r "echo 'sms_' . bin2hex(random_bytes(32)) . PHP_EOL;"
```

---

## Step 4 – Install Database Tables

Visit: `https://yourdomain.com/setup/install.php`

Fill in your DB credentials and click **Install Database**.

**Delete this file after installation!**

---

## Step 5 – Generate PWA Icons

Visit or run via CLI:
```
https://yourdomain.com/icons/generate-icons.php
```
Or via SSH:
```bash
php icons/generate-icons.php
```

**Delete `generate-icons.php` after use.**

---

## Step 6 – Google OAuth Setup

1. Go to: https://console.cloud.google.com/
2. Create a new project (or select existing)
3. Enable **Google+ API** and **Google Identity**
4. Go to **APIs & Services → Credentials**
5. Click **Create Credentials → OAuth 2.0 Client ID**
6. Application type: **Web application**
7. Authorized redirect URIs: `https://yourdomain.com/auth/callback.php`
8. Copy **Client ID** and **Client Secret** → paste into `config/config.php`

---

## Step 7 – Configure Android SMS Forwarder

Install one of these apps on your Android:
- **SMS Forwarder** (by bogkonstantin)
- **SMS Forward** by Delicious Inc.
- **MacroDroid** (advanced)

### App Configuration:
| Field       | Value                                             |
|-------------|---------------------------------------------------|
| URL         | `https://yourdomain.com/api/receive.php`          |
| Method      | POST                                              |
| api_key     | Your API key (from dashboard → API Settings)     |
| sender      | `%from%` (or `{sender}` depending on app)        |
| message     | `%body%` (or `{message}`)                        |
| device      | Your phone name (optional, e.g. `Pixel 8`)       |

---

## Step 8 – Add Allowed Senders

Log into the dashboard → sidebar → **Allowed Senders** → Add senders like:
- `VK-GOOGLE`
- `HDFCBK`
- `SBIOTP`
- `ICICIT`
- `AXISBK`

Messages from other senders are **silently ignored**.

---

## Step 9 – Install as Android App

1. Open Chrome on Android
2. Visit your dashboard URL
3. The **Install App** banner appears at the bottom
4. Tap **Install** → Add to Home Screen
5. App launches in full-screen mode like a native app!

---

## Step 10 – Push Notifications (Optional)

Generate VAPID keys at: https://vapidkeys.com/

Or via CLI:
```bash
npx web-push generate-vapid-keys
```

Paste both keys into `config/config.php`.

---

## Security Checklist

- [ ] HTTPS enabled (required for PWA + OAuth)
- [ ] `MASTER_API_KEY` is at least 32 characters, random
- [ ] `ALLOWED_EMAILS` contains only your email
- [ ] `setup/install.php` deleted after setup
- [ ] `icons/generate-icons.php` deleted after use
- [ ] Database credentials are not the same as cPanel login
- [ ] Error display is OFF in production (set in `.htaccess`)
- [ ] File backups are configured in cPanel

---

## Webhook Testing

Test the API endpoint with cURL:
```bash
curl -X POST https://yourdomain.com/api/receive.php \
  -H "Content-Type: application/json" \
  -d '{
    "api_key":  "your_api_key_here",
    "sender":   "HDFCBK",
    "message":  "Your OTP is 482619. Valid for 10 mins. HDFC Bank.",
    "device":   "Test Device"
  }'
```

Expected response: `{"status":"ok","id":1}`

---

## Troubleshooting

**"Database connection failed"** → Check DB host/user/pass in config.php

**"Google login shows error"** → Verify redirect URI matches exactly in Google Console

**"Install banner not showing"** → Must be HTTPS + Chrome on Android + not already installed

**"SMS not appearing"** → Check if sender is in Allowed Senders list; test API with cURL

**Service worker not updating** → Hard refresh (Ctrl+Shift+R) or clear site data in Chrome DevTools

---

## File Structure

```
public_html/
├── .htaccess                  Apache config + security
├── index.php                  Redirect to login/dashboard
├── login.php                  Login page (Google + Email)
├── dashboard.php              Main SMS dashboard
├── logout.php                 Session destroy
├── manifest.json              PWA manifest
├── service-worker.js          Service worker (offline + push)
├── offline.html               Offline fallback page
│
├── config/
│   ├── config.php             ← EDIT THIS with your credentials
│   ├── database.php           PDO connection helper
│   └── auth.php               Session/auth helpers
│
├── auth/
│   ├── google.php             Initiate Google OAuth
│   └── callback.php           OAuth callback handler
│
├── api/
│   ├── receive.php            ← SMS webhook endpoint
│   ├── messages.php           Fetch messages (AJAX)
│   ├── delete.php             Delete message (AJAX)
│   ├── senders.php            Manage allowed senders
│   └── push-subscribe.php     Push notification subscription
│
├── icons/
│   ├── generate-icons.php     ← Run once, then delete
│   ├── icon-72.png            (generated)
│   ├── icon-192.png           (generated)
│   └── icon-512.png           (generated)
│
└── setup/
    ├── install.php            ← Run once, then delete
    └── install.sql            Raw SQL schema
```
