<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$user = currentUser();
$csrf = generateCsrf();
?>
<!DOCTYPE html>
<html lang="en" id="htmlRoot" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" id="themeColorMeta" content="#0f172a">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="SMS Dashboard">
  <meta name="mobile-web-app-capable" content="yes">
  <title><?= APP_NAME ?></title>
  <link rel="manifest" href="/manifest.json">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={darkMode:'class'}</script>
  <style>
    /* ── Base ───────────────────────────────────────────── */
    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; overscroll-behavior: none; }

    /* ── Dark theme ─────────────────────────────────────── */
    .dark body { background: #0f172a; color: #f1f5f9; }
    .dark .sidebar { background: #111827; border-color: #1f2937; }
    .dark .msg-card { background: #1e293b; border-color: #334155; }
    .dark .msg-card:hover { background: #253347; }
    .dark .topbar { background: rgba(15,23,42,0.95); border-color: #1f2937; }
    .dark .glass { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); }
    .dark input, .dark textarea, .dark select { background: #1e293b; border-color: #334155; color: #f1f5f9; }

    /* ── Light theme ────────────────────────────────────── */
    :root.light body { background: #f8fafc; color: #0f172a; }
    :root.light .sidebar { background: #ffffff; border-color: #e2e8f0; }
    :root.light .msg-card { background: #ffffff; border-color: #e2e8f0; }
    :root.light .msg-card:hover { background: #f1f5f9; }
    :root.light .topbar { background: rgba(248,250,252,0.95); border-color: #e2e8f0; }
    :root.light .glass { background: rgba(0,0,0,0.03); border-color: rgba(0,0,0,0.08); }
    :root.light input, :root.light select { background: #f1f5f9; border-color: #cbd5e1; color: #0f172a; }

    /* ── Layout ─────────────────────────────────────────── */
    .app-layout { display: flex; height: 100dvh; overflow: hidden; }
    .sidebar { width: 280px; flex-shrink: 0; border-right: 1px solid; overflow-y: auto; display: flex; flex-direction: column; }
    .main-area { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
    .topbar { position: sticky; top: 0; z-index: 20; backdrop-filter: blur(12px); border-bottom: 1px solid; padding: .75rem 1rem; }

    /* ── Message cards ──────────────────────────────────── */
    .msg-card { border: 1px solid; border-radius: 1rem; padding: 1rem; transition: all .15s ease; cursor: pointer; }
    .msg-card:active { transform: scale(.99); }
    .sender-badge { display: inline-flex; align-items: center; gap: .35rem; font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: .2rem .55rem; border-radius: 999px; }
    .otp-highlight { display: inline-block; background: linear-gradient(135deg,#3b82f6,#6366f1); color: #fff; font-weight: 800; font-size: 1.4rem; letter-spacing: .15em; padding: .4rem 1rem; border-radius: .65rem; margin: .4rem 0; box-shadow: 0 4px 15px rgba(99,102,241,.4); }
    .copy-btn { display: inline-flex; align-items: center; gap: .4rem; font-size: .75rem; font-weight: 600; padding: .35rem .8rem; border-radius: 999px; background: rgba(99,102,241,.15); color: #818cf8; border: 1px solid rgba(99,102,241,.3); transition: all .15s; cursor: pointer; }
    .copy-btn:hover { background: rgba(99,102,241,.3); }
    .copy-btn.copied { background: rgba(34,197,94,.15); color: #4ade80; border-color: rgba(34,197,94,.3); }

    /* ── Sidebar sender items ───────────────────────────── */
    .sender-item { display: flex; align-items: center; gap: .6rem; padding: .55rem .75rem; border-radius: .75rem; cursor: pointer; transition: background .12s; font-size: .85rem; }
    .sender-item:hover, .sender-item.active { background: rgba(59,130,246,.15); color: #60a5fa; }
    .sender-item.active { font-weight: 700; }

    /* ── Install banner ─────────────────────────────────── */
    #installBanner { display: none; position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;
      background: linear-gradient(135deg,#1e3a5f,#1e1b4b);
      border-top: 1px solid rgba(99,102,241,.3);
      padding: 1rem 1.25rem 1.5rem; box-shadow: 0 -8px 32px rgba(0,0,0,.4); }

    /* ── Mobile ─────────────────────────────────────────── */
    @media (max-width: 768px) {
      .sidebar { position: fixed; inset-y: 0; left: -100%; width: 85%; max-width: 320px; z-index: 50; transition: left .25s ease; border-right: 1px solid; }
      .sidebar.open { left: 0; }
      .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 40; }
      .sidebar-overlay.show { display: block; }
      .main-area { width: 100%; }
    }

    /* ── Animations ─────────────────────────────────────── */
    @keyframes slideUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:none} }
    .msg-animate { animation: slideUp .2s ease; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .spin { animation: spin .8s linear infinite; }

    /* ── Scrollbar ──────────────────────────────────────── */
    ::-webkit-scrollbar { width: 4px; } ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #334155; border-radius: 999px; }

    /* ── Dot indicator ──────────────────────────────────── */
    .live-dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }
  </style>
</head>
<body>

<!-- Install Banner -->
<div id="installBanner">
  <div class="flex items-center gap-4">
    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
    </div>
    <div class="flex-1 min-w-0">
      <p class="text-white font-semibold text-sm">Install Private SMS Dashboard</p>
      <p class="text-gray-400 text-xs">Add to home screen for the best experience</p>
    </div>
    <div class="flex gap-2 flex-shrink-0">
      <button onclick="dismissInstall()" class="text-gray-500 text-xs px-3 py-2 rounded-xl">Not now</button>
      <button onclick="triggerInstall()" class="bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-xl">Install</button>
    </div>
  </div>
</div>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="app-layout">

  <!-- ═══════════ SIDEBAR ═══════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="p-4 border-b border-gray-800 dark:border-gray-800">
      <!-- Logo -->
      <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        <div>
          <p class="font-bold text-sm text-white">SMS Dashboard</p>
          <div class="flex items-center gap-1.5 mt-0.5">
            <div class="live-dot"></div>
            <span class="text-xs text-gray-500">Live</span>
          </div>
        </div>
      </div>

      <!-- User profile -->
      <div class="flex items-center gap-3 glass rounded-xl p-2.5 border">
        <?php if ($user['profile_image']): ?>
          <img src="<?= htmlspecialchars($user['profile_image']) ?>" class="w-9 h-9 rounded-full flex-shrink-0" alt="">
        <?php else: ?>
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0 text-white font-bold text-sm"><?= htmlspecialchars(strtoupper(substr($user['name'],0,1))) ?></div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold truncate text-white"><?= htmlspecialchars($user['name']) ?></p>
          <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($user['email']) ?></p>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="p-3 flex-1 overflow-y-auto">
      <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider px-2 mb-2">Senders</p>
      <div class="sender-item active" data-sender="" onclick="filterBySender('')">
        <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
        <span>All Messages</span>
        <span id="totalBadge" class="ml-auto text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full"></span>
      </div>
      <div id="senderList" class="space-y-0.5 mt-1"></div>

      <div class="mt-4 border-t border-gray-800 pt-3">
        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider px-2 mb-2">Manage</p>
        <button onclick="openSendersModal()" class="sender-item w-full text-left">
          <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Allowed Senders
        </button>
        <button onclick="openApiModal()" class="sender-item w-full text-left">
          <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          API Settings
        </button>
        <button onclick="openPasswordModal()" class="sender-item w-full text-left">
          <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Set Password (App Login)
        </button>
        <button onclick="toggleTheme()" class="sender-item w-full text-left">
          <svg id="themeIcon" class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
          Toggle Theme
        </button>
        <a href="/logout.php" class="sender-item text-red-500 hover:text-red-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Logout
        </a>
      </div>
    </nav>

    <!-- API Key hint -->
    <div class="p-3 border-t border-gray-800">
      <p class="text-xs text-gray-600">API Key</p>
      <p class="text-xs font-mono text-gray-500 truncate"><?= htmlspecialchars(substr($user['api_key'],0,20)) ?>…</p>
    </div>
  </aside>

  <!-- ═══════════ MAIN ═══════════ -->
  <main class="main-area">

    <!-- Topbar -->
    <div class="topbar">
      <div class="flex items-center gap-3">
        <!-- Mobile menu button -->
        <button onclick="toggleSidebar()" class="md:hidden p-2 rounded-xl hover:bg-gray-800/50 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <!-- Search -->
        <div class="flex-1 relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="search" id="searchInput" placeholder="Search messages…"
            class="w-full pl-10 pr-4 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            oninput="debounceSearch()">
        </div>

        <!-- Refresh button -->
        <button onclick="loadMessages()" id="refreshBtn" class="p-2.5 rounded-xl hover:bg-blue-500/10 text-blue-400 transition-colors" title="Refresh">
          <svg class="w-5 h-5" id="refreshIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
      </div>

      <!-- Active filter badge -->
      <div id="filterBadge" class="hidden mt-2 flex items-center gap-2">
        <span class="text-xs text-gray-500">Filtering:</span>
        <span id="filterLabel" class="text-xs font-semibold bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full"></span>
        <button onclick="filterBySender('')" class="text-xs text-gray-600 hover:text-gray-400">✕ Clear</button>
      </div>
    </div>

    <!-- Messages list -->
    <div id="messagesList" class="p-4 space-y-3 flex-1">
      <div class="flex items-center justify-center h-40">
        <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full spin"></div>
      </div>
    </div>

    <!-- Load more -->
    <div id="loadMoreArea" class="p-4 text-center hidden">
      <button onclick="loadMore()" class="text-sm text-blue-400 hover:text-blue-300 font-medium">Load more →</button>
    </div>

  </main>
</div>

<!-- ═══════════ ALLOWED SENDERS MODAL ═══════════ -->
<div id="sendersModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeSendersModal()"></div>
  <div class="relative w-full max-w-md dark:bg-gray-900 bg-white rounded-2xl shadow-2xl overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b border-gray-800">
      <h2 class="font-bold text-lg">Allowed Senders</h2>
      <button onclick="closeSendersModal()" class="text-gray-500 hover:text-gray-300 p-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-5">
      <div id="allowedSendersList" class="space-y-2 mb-4 max-h-60 overflow-y-auto"></div>
      <div class="flex gap-2">
        <input type="text" id="newSenderInput" placeholder="e.g. HDFCBK" maxlength="100"
          class="flex-1 rounded-xl border px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
        <button onclick="addSender()" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-500">Add</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════ API SETTINGS MODAL ═══════════ -->
<div id="apiModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('apiModal').classList.add('hidden')"></div>
  <div class="relative w-full max-w-md dark:bg-gray-900 bg-white rounded-2xl shadow-2xl overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b border-gray-800">
      <h2 class="font-bold text-lg">API / Webhook Settings</h2>
      <button onclick="document.getElementById('apiModal').classList.add('hidden')" class="text-gray-500 p-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div>
        <p class="text-xs text-gray-500 mb-1.5 font-semibold uppercase tracking-wide">Your Webhook URL</p>
        <div class="flex gap-2">
          <input readonly id="webhookUrl" value="<?= APP_URL ?>/api/receive.php"
            class="flex-1 rounded-xl border px-3 py-2.5 text-sm font-mono bg-opacity-50">
          <button onclick="copyText('webhookUrl')" class="copy-btn">Copy</button>
        </div>
      </div>
      <div>
        <p class="text-xs text-gray-500 mb-1.5 font-semibold uppercase tracking-wide">Your API Key</p>
        <div class="flex gap-2">
          <input readonly id="apiKeyField" value="<?= htmlspecialchars($user['api_key']) ?>"
            class="flex-1 rounded-xl border px-3 py-2.5 text-sm font-mono bg-opacity-50">
          <button onclick="copyText('apiKeyField')" class="copy-btn">Copy</button>
        </div>
      </div>
      <div class="glass border rounded-xl p-4 text-xs space-y-1.5 text-gray-400">
        <p class="font-semibold text-gray-300 mb-2">SMS Forwarder App Setup</p>
        <p>• URL: <span class="text-blue-400"><?= APP_URL ?>/api/receive.php</span></p>
        <p>• Method: POST</p>
        <p>• Field mapping: <span class="font-mono">api_key, sender, message, device</span></p>
      </div>
    </div>
  </div>
</div>

<!-- Set Password Modal -->
<div id="passwordModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closePasswordModal()"></div>
  <div class="relative w-full max-w-md dark:bg-gray-900 bg-white rounded-2xl shadow-2xl overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b border-gray-800">
      <h2 class="font-bold text-lg">App ke liye Password Set Karo</h2>
      <button onclick="closePasswordModal()" class="text-gray-500 p-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-gray-400">Yeh password Android app mein login karne ke liye use hoga. Email hoga: <span class="text-blue-400"><?= htmlspecialchars($user['email']) ?></span></p>
      <input type="password" id="pwNew" placeholder="Naya password (min 6 char)" class="w-full rounded-xl border px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-700">
      <input type="password" id="pwConfirm" placeholder="Password dobara daalo" class="w-full rounded-xl border px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-700">
      <button onclick="setPassword()" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2.5 rounded-xl text-sm">Password Save Karo</button>
      <p id="pwMsg" class="text-sm text-center hidden"></p>
    </div>
  </div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
let currentSender = '';
let currentPage   = 1;
let lastId        = 0;
let polling       = null;
let deferredPrompt = null;

// ── PWA Install ──────────────────────────────────────────
window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredPrompt = e;
  if (!localStorage.getItem('installDismissed')) {
    document.getElementById('installBanner').style.display = 'block';
  }
});
window.addEventListener('appinstalled', () => {
  document.getElementById('installBanner').style.display = 'none';
  deferredPrompt = null;
});
function triggerInstall() {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  deferredPrompt.userChoice.then(r => {
    if (r.outcome === 'accepted') document.getElementById('installBanner').style.display = 'none';
    deferredPrompt = null;
  });
}
function dismissInstall() {
  document.getElementById('installBanner').style.display = 'none';
  localStorage.setItem('installDismissed', '1');
}

// ── Service Worker ───────────────────────────────────────
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js').then(reg => {
    // Push notification subscription
    if ('PushManager' in window && Notification.permission !== 'denied') {
      Notification.requestPermission().then(p => {
        if (p === 'granted') subscribePush(reg);
      });
    }
  }).catch(console.error);
}
async function subscribePush(reg) {
  try {
    const vapidKey = '<?= VAPID_PUBLIC_KEY ?>';
    if (!vapidKey || vapidKey === 'YOUR_VAPID_PUBLIC_KEY') return;
    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapidKey),
    });
    await fetch('/api/push-subscribe.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({csrf_token: CSRF, subscription: sub}),
    });
  } catch (_) {}
}
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  return new Uint8Array([...rawData].map(c => c.charCodeAt(0)));
}

// ── Theme ────────────────────────────────────────────────
function applyTheme(dark) {
  const root = document.getElementById('htmlRoot');
  const meta = document.getElementById('themeColorMeta');
  root.className = dark ? 'dark' : 'light';
  meta.content   = dark ? '#0f172a' : '#f8fafc';
}
function toggleTheme() {
  const isDark = document.getElementById('htmlRoot').className === 'dark';
  const next   = !isDark;
  localStorage.setItem('theme', next ? 'dark' : 'light');
  applyTheme(next);
}
applyTheme(localStorage.getItem('theme') !== 'light');

// ── Sidebar ──────────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
}

// ── Messages ─────────────────────────────────────────────
function extractOtp(text) {
  const patterns = [
    /\b([0-9]{4,8})\b(?=.*(?:OTP|otp|code|Code|PIN|pin|password|verify|verification|auth))/,
    /(?:OTP|otp|code|Code|PIN|pin|password|verify|auth)[^\d]*([0-9]{4,8})/i,
    /\b([0-9]{6})\b/,
    /\b([0-9]{4})\b/,
  ];
  for (const re of patterns) {
    const m = text.match(re);
    if (m) return m[1];
  }
  return null;
}

function highlightOtp(text) {
  const otp = extractOtp(text);
  if (!otp) return escHtml(text);
  const escaped = escHtml(text);
  return escaped.replace(
    new RegExp(`\\b${otp}\\b`, 'g'),
    `<span class="otp-highlight">${otp}</span>`
  );
}

function escHtml(t) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(t));
  return d.innerHTML;
}

function timeAgo(dt) {
  const s = Math.floor((Date.now() - new Date(dt).getTime()) / 1000);
  if (s < 60)   return 'just now';
  if (s < 3600) return `${Math.floor(s/60)}m ago`;
  if (s < 86400) return `${Math.floor(s/3600)}h ago`;
  return new Date(dt).toLocaleDateString();
}

const senderColors = ['#3b82f6','#8b5cf6','#ec4899','#10b981','#f59e0b','#06b6d4','#ef4444'];
function senderColor(name) {
  let h = 0; for (const c of name) h = (h * 31 + c.charCodeAt(0)) & 0xffffffff;
  return senderColors[Math.abs(h) % senderColors.length];
}

function renderMessage(m) {
  const otp  = extractOtp(m.message);
  const col  = senderColor(m.sender);
  return `
  <div class="msg-card msg-animate" data-id="${m.id}">
    <div class="flex items-start justify-between gap-3 mb-2">
      <div class="flex items-center gap-2 flex-wrap">
        <span class="sender-badge" style="background:${col}22;color:${col}">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/><path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/></svg>
          ${escHtml(m.sender)}
        </span>
        ${m.device_name ? `<span class="text-xs text-gray-600">📱 ${escHtml(m.device_name)}</span>` : ''}
      </div>
      <div class="flex items-center gap-2 flex-shrink-0">
        <span class="text-xs text-gray-500">${timeAgo(m.received_at)}</span>
        <button onclick="deleteMsg(${m.id},this)" class="text-gray-600 hover:text-red-400 p-1 transition-colors" title="Delete">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
    </div>
    <div class="text-sm leading-relaxed mb-2" style="color:inherit">${highlightOtp(m.message)}</div>
    ${otp ? `
    <div class="flex items-center gap-2">
      <button onclick="copyOtp('${otp}',this)" class="copy-btn">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        Copy OTP: ${otp}
      </button>
    </div>` : ''}
  </div>`;
}

async function loadMessages(reset = true) {
  if (reset) { currentPage = 1; }
  const icon = document.getElementById('refreshIcon');
  icon.classList.add('spin');

  const q       = document.getElementById('searchInput').value.trim();
  const params  = new URLSearchParams({ page: currentPage, limit: 50 });
  if (currentSender) params.set('sender', currentSender);
  if (q)             params.set('q', q);

  try {
    const res  = await fetch('/api/messages.php?' + params);
    const data = await res.json();

    if (reset) {
      const list = document.getElementById('messagesList');
      if (!data.messages || data.messages.length === 0) {
        list.innerHTML = `
          <div class="flex flex-col items-center justify-center h-64 text-gray-600">
            <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <p class="text-sm">No messages yet</p>
            <p class="text-xs mt-1">Messages will appear here once forwarded from your phone</p>
          </div>`;
      } else {
        list.innerHTML = data.messages.map(renderMessage).join('');
        lastId = data.messages[0]?.id ?? 0;
      }
    } else {
      // Append for load-more
      document.getElementById('messagesList').insertAdjacentHTML('beforeend',
        data.messages.map(renderMessage).join(''));
    }

    // Update sender sidebar
    renderSenderList(data.senders ?? [], data.total ?? 0);

    // Show/hide load more
    const loaded = (currentPage * 50);
    document.getElementById('loadMoreArea').classList.toggle('hidden', loaded >= (data.total ?? 0));

  } catch (e) { console.error(e); }
  icon.classList.remove('spin');
}

async function pollNewMessages() {
  if (!lastId) return;
  try {
    const params = new URLSearchParams({ since_id: lastId, limit: 50 });
    if (currentSender) params.set('sender', currentSender);
    const res  = await fetch('/api/messages.php?' + params);
    const data = await res.json();
    if (data.messages && data.messages.length > 0) {
      const list = document.getElementById('messagesList');
      const html = data.messages.map(renderMessage).join('');
      list.insertAdjacentHTML('afterbegin', html);
      lastId = data.messages[0].id;
      // Show notification if page hidden
      if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
        new Notification('New SMS from ' + data.messages[0].sender, {
          body: data.messages[0].message.substring(0, 100),
          icon: '/icons/icon-192.png',
        });
      }
    }
  } catch (_) {}
}

function loadMore() {
  currentPage++;
  loadMessages(false);
}

function renderSenderList(senders, total) {
  document.getElementById('totalBadge').textContent = total;
  const list = document.getElementById('senderList');
  list.innerHTML = senders.map(s => `
    <div class="sender-item ${s.sender === currentSender ? 'active' : ''}" data-sender="${escHtml(s.sender)}" onclick="filterBySender('${escHtml(s.sender)}')">
      <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:${senderColor(s.sender)}"></span>
      <span class="truncate">${escHtml(s.sender)}</span>
      <span class="ml-auto text-xs text-gray-600">${s.cnt}</span>
    </div>`).join('');
}

function filterBySender(sender) {
  currentSender = sender;
  document.querySelectorAll('.sender-item').forEach(el => {
    el.classList.toggle('active', (el.dataset.sender ?? '') === sender);
  });
  const badge = document.getElementById('filterBadge');
  if (sender) {
    badge.classList.remove('hidden');
    document.getElementById('filterLabel').textContent = sender;
  } else {
    badge.classList.add('hidden');
  }
  loadMessages(true);
  closeSidebar();
}

let searchTimer;
function debounceSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadMessages(true), 400);
}

// ── Copy helpers ─────────────────────────────────────────
function copyOtp(otp, btn) {
  navigator.clipboard.writeText(otp).then(() => {
    btn.classList.add('copied');
    const orig = btn.innerHTML;
    btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!`;
    setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = orig; }, 2000);
  });
}
function copyText(fieldId) {
  const el = document.getElementById(fieldId);
  el.select(); navigator.clipboard.writeText(el.value);
}

// ── Delete ───────────────────────────────────────────────
async function deleteMsg(id, btn) {
  if (!confirm('Delete this message?')) return;
  const res  = await fetch('/api/delete.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({id, csrf_token: CSRF}),
  });
  const data = await res.json();
  if (data.status === 'ok') {
    const card = btn.closest('.msg-card');
    card.style.opacity = '0'; card.style.transform = 'scale(.95)'; card.style.transition = '.2s';
    setTimeout(() => card.remove(), 200);
  }
}

// ── Allowed Senders Modal ────────────────────────────────
async function openSendersModal() {
  document.getElementById('sendersModal').classList.remove('hidden');
  await refreshSendersList();
}
function closeSendersModal() { document.getElementById('sendersModal').classList.add('hidden'); }

async function refreshSendersList() {
  const res  = await fetch('/api/senders.php');
  const data = await res.json();
  const list = document.getElementById('allowedSendersList');
  list.innerHTML = (data.senders ?? []).map(s => `
    <div class="flex items-center justify-between gap-2 p-2.5 dark:bg-gray-800 bg-gray-100 rounded-xl">
      <span class="font-mono text-sm font-semibold" style="color:${senderColor(s.sender_name)}">${escHtml(s.sender_name)}</span>
      <button onclick="removeSender(${s.id},this)" class="text-xs text-red-500 hover:text-red-400 px-2 py-1 rounded-lg hover:bg-red-500/10">Remove</button>
    </div>`).join('') || '<p class="text-sm text-gray-500 text-center py-4">No senders configured</p>';
}

async function addSender() {
  const input = document.getElementById('newSenderInput');
  const name  = input.value.trim().toUpperCase();
  if (!name) return;
  await fetch('/api/senders.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action:'add', sender_name: name, csrf_token: CSRF}),
  });
  input.value = '';
  await refreshSendersList();
}
async function removeSender(id) {
  await fetch('/api/senders.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action:'remove', id, csrf_token: CSRF}),
  });
  await refreshSendersList();
}

function openApiModal() { document.getElementById('apiModal').classList.remove('hidden'); }

function openPasswordModal() { document.getElementById('passwordModal').classList.remove('hidden'); }
function closePasswordModal() { document.getElementById('passwordModal').classList.add('hidden'); }

async function setPassword() {
  const pass    = document.getElementById('pwNew').value;
  const confirm = document.getElementById('pwConfirm').value;
  const msg     = document.getElementById('pwMsg');
  msg.className = 'text-sm text-center';
  msg.classList.remove('hidden');

  if (pass.length < 6) { msg.textContent = 'Password kam se kam 6 characters ka hona chahiye'; msg.classList.add('text-red-400'); return; }
  if (pass !== confirm) { msg.textContent = 'Passwords match nahi kar rahe'; msg.classList.add('text-red-400'); return; }

  msg.textContent = 'Saving...'; msg.classList.add('text-gray-400');
  try {
    const r = await fetch('/api/set-password.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({password: pass, confirm})
    });
    const d = await r.json();
    if (d.success) {
      msg.textContent = '✅ ' + d.message;
      msg.classList.add('text-green-400');
      document.getElementById('pwNew').value = '';
      document.getElementById('pwConfirm').value = '';
    } else {
      msg.textContent = '❌ ' + (d.error || 'Error');
      msg.classList.add('text-red-400');
    }
  } catch(e) {
    msg.textContent = '❌ Network error'; msg.classList.add('text-red-400');
  }
}

// ── Init ─────────────────────────────────────────────────
loadMessages().then(() => startStream());

// ── Server-Sent Events (real-time) ───────────────────────
let evtSource = null;
function startStream() {
  if (evtSource) evtSource.close();
  evtSource = new EventSource('/api/stream.php?since_id=' + lastId);

  evtSource.onmessage = e => {
    const data = JSON.parse(e.data);
    if (data.type === 'sms') {
      // Only show if no sender filter, or matches current filter
      if (!currentSender || currentSender === data.sender) {
        const list = document.getElementById('messagesList');
        list.insertAdjacentHTML('afterbegin', renderMessage(data));
      }
      lastId = Math.max(lastId, data.id);
      // Browser notification
      if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
        new Notification('New SMS from ' + data.sender, {
          body: data.message.substring(0, 100),
          icon: '/icons/icon-192.png',
        });
      }
    }
    if (data.type === 'reconnect') startStream();
  };

  evtSource.onerror = () => {
    evtSource.close();
    // Fallback: retry SSE after 5 seconds
    setTimeout(startStream, 5000);
  };
}

// Keep full list fresh every 2 minutes (for counts/sidebar)
setInterval(() => loadMessages(true), 120000);

// Keyboard shortcut: / to focus search
document.addEventListener('keydown', e => {
  if (e.key === '/' && document.activeElement !== document.getElementById('searchInput')) {
    e.preventDefault();
    document.getElementById('searchInput').focus();
  }
});
</script>
</body>
</html>
