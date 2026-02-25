<?php
/**
 * FRAMEWORK - Logout
 * Distrugge sessione, poi redirect alla home (rispetta base_path).
 */
session_start();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();
include(__DIR__ . '/_include/config.inc.php');
$base = $CONF["base_path"] ?? '';
header("Location: " . ($base ? $base . '/' : '/'));
exit;
