<?php
require_once __DIR__ . '/config/auth.php';
startSecureSession();

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';

// Email/Password login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = strtolower(trim($_POST['email']    ?? ''));
        $password = $_POST['password'] ?? '';

        if (!isEmailAllowed($email)) {
            $error = 'Access denied. This email is not authorized.';
        } else {
            require_once __DIR__ . '/config/database.php';
            $db   = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['password'] && password_verify($password, $user['password'])) {
                loginUser($user);
                if (!empty($_POST['remember'])) {
                    setcookie('remember_token', session_id(), time() + 86400 * 30, '/', '', true, true);
                }
                header('Location: ' . APP_URL . '/dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

$csrf = generateCsrf();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#0f172a">
  <title>Login – <?= APP_NAME ?></title>
  <link rel="manifest" href="/manifest.json">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={darkMode:'class'}</script>
  <style>
    body { background: radial-gradient(ellipse at top, #1e3a5f 0%, #0f172a 60%); }
    .glass { background: rgba(255,255,255,0.04); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); }
    .btn-google { background: linear-gradient(135deg,#4285f4,#34a853); }
    @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
    .logo-float { animation: float 3s ease-in-out infinite; }
    input:-webkit-autofill { -webkit-box-shadow: 0 0 0 100px #1e293b inset !important; -webkit-text-fill-color: #f1f5f9 !important; }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

  <!-- Animated background blobs -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl animate-pulse" style="animation-delay:.7s"></div>
  </div>

  <div class="relative w-full max-w-sm">

    <!-- Logo -->
    <div class="text-center mb-8">
      <div class="logo-float inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-2xl shadow-blue-500/40 mb-4">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white">Private SMS</h1>
      <p class="text-gray-400 text-sm">Secure Dashboard</p>
    </div>

    <div class="glass rounded-3xl p-6 shadow-2xl">

      <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 mb-5 text-red-400 text-sm text-center">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Google Login -->
      <a href="/auth/google.php"
         class="btn-google flex items-center justify-center gap-3 w-full py-3.5 rounded-2xl text-white font-semibold shadow-lg shadow-blue-500/20 transition-transform active:scale-95 mb-5">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="white">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Continue with Google
      </a>

      <div class="flex items-center gap-3 mb-5">
        <div class="flex-1 h-px bg-gray-700"></div>
        <span class="text-gray-500 text-xs">or email</span>
        <div class="flex-1 h-px bg-gray-700"></div>
      </div>

      <!-- Email/Password Form -->
      <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div>
          <label class="block text-xs text-gray-400 mb-1.5">Email</label>
          <input type="email" name="email" required autocomplete="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            class="w-full bg-gray-800/60 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
            placeholder="you@example.com">
        </div>

        <div>
          <label class="block text-xs text-gray-400 mb-1.5">Password</label>
          <div class="relative">
            <input type="password" id="pwInput" name="password" required autocomplete="current-password"
              class="w-full bg-gray-800/60 border border-gray-700 rounded-xl px-4 py-3 pr-11 text-white text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
              placeholder="••••••••">
            <button type="button" onclick="togglePw()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
              <svg id="pwEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="remember" class="rounded border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500">
            <span class="text-xs text-gray-400">Remember me</span>
          </label>
        </div>

        <button type="submit"
          class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold py-3.5 rounded-2xl transition-all shadow-lg shadow-blue-500/25 active:scale-95">
          Sign In
        </button>
      </form>

    </div>

    <p class="text-center text-xs text-gray-600 mt-4">🔒 End-to-end secure • Private access only</p>
  </div>

  <script>
    function togglePw() {
      const i = document.getElementById('pwInput');
      i.type = i.type === 'password' ? 'text' : 'password';
    }
    // PWA install prompt
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/service-worker.js').catch(console.error);
    }
  </script>
</body>
</html>
