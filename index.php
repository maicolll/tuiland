<?php
/**
 * FRAMEWORK - Punto di ingresso principale
 *
 * In config.inc.php:
 * - abilita_area_pubblica + abilita_area_privata = entrambe (default): non loggato → public, loggato → private
 * - solo area pubblica (abilita_area_privata = false): sempre _content/public, nessun login
 * - solo area privata (abilita_area_pubblica = false): non loggato → pagina login, loggato → _content/private
 */

if (extension_loaded('zlib') &&
    isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
    strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

error_reporting(E_ALL ^ E_NOTICE);
header('Content-type: text/html; charset=utf-8');

include(__DIR__ . '/_include/config.inc.php');
include(__DIR__ . '/_include/lib.inc.php');
include(__DIR__ . '/_include/lang.inc.php');
include(__DIR__ . '/_include/translations.inc.php');

session_cache_limiter("must-revalidate");
session_start();

$abilita_pubblica = !empty($CONF["abilita_area_pubblica"]);
$abilita_privata  = !empty($CONF["abilita_area_privata"]);

// Solo area pubblica: nessuna area riservata, sempre pubblico
if ($abilita_pubblica && !$abilita_privata) {
    include(FRAMEWORK_ROOT . '/_content/public/index.php');
    return;
}

// Solo area privata: non loggato → pagina accedi (magic link), loggato → area privata
if (!$abilita_pubblica && $abilita_privata) {
    if (!isset($_SESSION["ID_SESSION"])) {
        $_GET['ACT'] = 'LOGIN';
        include(FRAMEWORK_ROOT . '/_content/public/index.php');
    } else {
        include(FRAMEWORK_ROOT . '/_content/private/index.php');
    }
    return;
}

// Entrambe le aree: comportamento classico
if (!isset($_SESSION["ID_SESSION"])) {
    include(FRAMEWORK_ROOT . '/_content/public/index.php');
} else {
    include(FRAMEWORK_ROOT . '/_content/private/index.php');
}
