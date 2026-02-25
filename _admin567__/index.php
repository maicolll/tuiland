<?php
/**
 * Pannello admin Tuiland.
 * Accesso: unico admin con credenziali in config (admin_email, admin_password).
 */
error_reporting(E_ALL ^ E_NOTICE);
// Sessione admin: nome e path dedicati; durata 3 giorni (cookie + file sessione).
// IMPORTANTE: usare sempre un path dedicato, altrimenti le sessioni finiscono in quello
// di default e il GC del sito principale (gc_maxlifetime ~24 min) le cancella.
session_name('TUILAND_ADMIN');
$admin_session_days = 3;
$admin_cookie_seconds = $admin_session_days * 24 * 3600; // 259200
$admin_sess_path = __DIR__ . '/sess_admin';
if (!is_dir($admin_sess_path)) {
    @mkdir($admin_sess_path, 0775, true);
    @chmod($admin_sess_path, 0775);
}
if (!is_writable($admin_sess_path) && is_dir($admin_sess_path)) {
    $admin_sess_path = sys_get_temp_dir() . '/tuiland_admin_' . substr(md5(__DIR__), 0, 12);
    if (!is_dir($admin_sess_path)) @mkdir($admin_sess_path, 0775, true);
}
session_save_path($admin_sess_path);
$admin_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
ini_set('session.gc_maxlifetime', (string)$admin_cookie_seconds);
ini_set('session.cookie_lifetime', (string)$admin_cookie_seconds);
session_set_cookie_params([
    'lifetime' => $admin_cookie_seconds,
    'path' => '/',
    'domain' => '',
    'secure' => $admin_secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

include(dirname(__DIR__) . '/_include/config.inc.php');
include(dirname(__DIR__) . '/_include/lib.inc.php');
include(dirname(__DIR__) . '/_include/sn.inc.php');

$admin_logged = !empty($_SESSION['ADMIN_CONFIG_LOGIN']);

// Forza cookie persistente 3 giorni a ogni visita (rinfresca scadenza)
if ($admin_logged) {
    $cookie_expiry = time() + $admin_cookie_seconds;
    setcookie(session_name(), session_id(), [
        'expires' => $cookie_expiry,
        'path' => '/',
        'domain' => '',
        'secure' => $admin_secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

if (!$admin_logged) {
    // Pagina di login admin (stesso URL, form che invia a funzioni.php)
    $login_err = isset($_GET['err']) && $_GET['err'] === '1';
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Accesso admin - <?php echo htmlspecialchars($CONF["nome_sito"]); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm">
    <h1 class="text-xl font-semibold mb-4">Accesso admin</h1>
    <?php if ($login_err): ?><p class="text-red-600 text-sm mb-3">Email o password non corretti.</p><?php endif; ?>
    <form method="post" action="funzioni.php">
      <input type="hidden" name="ACT" value="ADMIN_LOGIN"/>
      <p class="mb-3">
        <label class="block text-sm font-medium mb-1">Email</label>
        <input type="email" name="admin_email" required autocomplete="username" class="w-full border rounded px-3 py-2"/>
      </p>
      <p class="mb-4">
        <label class="block text-sm font-medium mb-1">Password</label>
        <input type="password" name="admin_password" required autocomplete="current-password" class="w-full border rounded px-3 py-2"/>
      </p>
      <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-medium">Accedi</button>
    </form>
  </div>
</body>
</html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Admin - <?php echo htmlspecialchars($CONF["nome_sito"]); ?></title>
<link rel="shortcut icon" href="/_images/favicon.ico" type="image/x-icon"/>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="stile.css?v=2" type="text/css"/>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="admin-container flex gap-5 max-w-7xl mx-auto p-4">
  <div class="admin-sidebar-fixed w-56 flex-shrink-0">
    <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
      <div class="pb-3 mb-3 border-b border-gray-200">
        <h3 class="m-0 text-lg font-semibold"><a href="/" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($CONF["nome_sito"]); ?></a></h3>
      </div>
      <?php include "inc/menu_sx_inc.php"; ?>
      <p class="mt-3 pt-3 border-t border-gray-200 text-sm"><a href="funzioni.php?ACT=ADMIN_LOGOUT" class="text-gray-600 hover:underline">Esci</a></p>
    </div>
  </div>
  <div class="admin-content-fixed flex-1 min-w-0">
    <div class="bg-white rounded-lg shadow min-h-[400px] p-4 border border-gray-200">
          <!-- Contenuto: in base a INC si include il file corrispondente (user_cont, foto_cont, ecc.) -->
          <?php include "inc/cont.inc.php"; ?>
    </div>
  </div>
</div>
</body>
</html>
