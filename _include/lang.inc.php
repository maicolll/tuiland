<?php
/**
 * FRAMEWORK - Lingua utente
 *
 * Imposta $lang_user in base a: default config, cookie "language", GET "lng".
 * Se viene passato ?lng=it|es|en valido, aggiorna il cookie.
 * Richiede $CONF["lang_default"] e $lang_name (da config.inc.php).
 */
if (!isset($lang_name) || !is_array($lang_name)) {
    $lang_name = ["it" => "Italiano", "es" => "Español", "en" => "English"];
}
$lang_user = isset($CONF["lang_default"]) ? $CONF["lang_default"] : "it";

if (isset($_COOKIE["language"]) && isset($lang_name[$_COOKIE["language"]])) {
    $lang_user = $_COOKIE["language"];
}

if (isset($_GET["lng"]) && isset($lang_name[$_GET["lng"]])) {
    $lang_user = $_GET["lng"];
    $path = isset($CONF["base_path"]) && $CONF["base_path"] !== '' ? $CONF["base_path"] . '/' : '/';
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('language', $lang_user, time() + (60 * 60 * 24 * 365), $path, '', $secure, true);
}
