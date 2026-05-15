<?php
/**
 * One-time web installer.
 * Visit https://yourdomain.com/setup/install.php, run it, then DELETE this file.
 */

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = trim($_POST['db_host']   ?? 'localhost');
    $dbname = trim($_POST['db_name']   ?? '');
    $user   = trim($_POST['db_user']   ?? '');
    $pass   = $_POST['db_pass']        ?? '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbname}`");
        $sql = file_get_contents(__DIR__ . '/install.sql');
        // Remove all comment lines and CREATE DATABASE / USE statements
        $sql = preg_replace('/--[^\n]*/m', '', $sql);
        $sql = preg_replace('/^\s*(CREATE\s+DATABASE|USE)\s+[^;]+;/mi', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            $pdo->exec($stmt);
        }
        $success = true;
    } catch (PDOException $e) {
        $errors[] = 'DB Error: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install – Private SMS Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-950 text-white flex items-center justify-center p-4">
<div class="w-full max-w-md bg-gray-900 rounded-2xl p-8 shadow-2xl">
  <h1 class="text-2xl font-bold text-blue-400 mb-6">⚙️ Setup Installer</h1>

  <?php if ($success): ?>
    <div class="bg-green-900/50 border border-green-500 rounded-xl p-4 mb-4 text-green-300">
      ✅ Database tables created successfully!<br>
      <strong>Next steps:</strong>
      <ol class="list-decimal pl-5 mt-2 text-sm space-y-1">
        <li>Update <code>config/config.php</code> with your credentials.</li>
        <li>Set up Google OAuth (see SETUP_GUIDE.md).</li>
        <li><strong>Delete this file</strong> (<code>setup/install.php</code>).</li>
        <li><a href="../login.php" class="underline text-blue-400">Go to Login →</a></li>
      </ol>
    </div>
  <?php else: ?>

    <?php foreach ($errors as $e): ?>
      <div class="bg-red-900/50 border border-red-500 rounded-xl p-3 mb-4 text-red-300 text-sm"><?= $e ?></div>
    <?php endforeach; ?>

    <form method="POST" class="space-y-4">
      <?php foreach ([
        ['db_host', 'DB Host',     'text',     'localhost'],
        ['db_name', 'DB Name',     'text',     'smspho_db'],
        ['db_user', 'DB Username', 'text',     ''],
        ['db_pass', 'DB Password', 'password', ''],
      ] as [$name, $label, $type, $placeholder]): ?>
        <div>
          <label class="block text-sm text-gray-400 mb-1"><?= $label ?></label>
          <input type="<?= $type ?>" name="<?= $name ?>" value="<?= htmlspecialchars($_POST[$name] ?? $placeholder) ?>"
            placeholder="<?= $placeholder ?>"
            class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500">
        </div>
      <?php endforeach; ?>
      <button type="submit"
        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-xl transition-colors">
        🚀 Install Database
      </button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
