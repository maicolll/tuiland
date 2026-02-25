<?php
/**
 * FRAMEWORK - Area privata (utente loggato)
 *
 * Verifica sessione: se ID_SESSION non valido → logout e redirect.
 * Carica dati utente da DB (se necessario), poi fa routing tramite CONT:
 *
 *   index.php?CONT=HOME     → contenuto home
 *   index.php?CONT=PROFILO  → contenuto profilo
 *   ...
 *
 * $CONT viene letto da $_GET['CONT']. In base al case si imposta $include_content
 * (path del file .inc.php da includere) e opzionalmente $COLONNA_SX ('Y'/'N'), $MENU_TOP.
 * Il template tpl.php include poi $include_content nella zona centrale.
 */
$LOGGATO = true;

if (!isset($_SESSION["ID_SESSION"])) {
    header("Location: " . ($CONF["base_path"] ?? '') . "/logout.php");
    exit;
}

include(FRAMEWORK_ROOT . '/_include/sn.inc.php');
$my_id = (int) $_SESSION["ID_SESSION"];
$user_row = $con ? sn_load_user($con, $my_id) : null;

if (!$user_row) {
    session_destroy();
    header("Location: " . ($CONF["base_path"] ?? '') . "/logout.php");
    exit;
}

$my_alias = $user_row['alias'];
$my_email = $user_row['email'];
$my_role = $user_row['role'];
$my_darkmode = $user_row['darkmode'] ?? 'S';
$my_darkmode_attivato = is_dark_mode_active($my_darkmode);
$my_darkmode_system = ($my_darkmode === 'S' || $my_darkmode === null);
$COLONNA_SX = 'Y';
$MENU_TOP = 'Y';
$include_content = '';
$CONT = $_GET['CONT'] ?? '';

// ACT=POST e ACT=AGENT: stessa logica dell'area pubblica (funzionano anche da loggati)
$ACT_GET = $_GET['ACT'] ?? '';
if ($ACT_GET === 'POST') {
    $post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $post_single = ($con && $post_id > 0) ? sn_post_by_id($con, $post_id) : null;
    if (!$post_single) $post_single = null;
    $bp = $CONF["base_path"] ?? '';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $post_permalink = $protocol . '://' . $host . ($bp ? $bp . '/' : '/') . '?ACT=POST&id=' . $post_id;
    $PAGE_TITLE = $post_single ? ('Post di ' . ($post_single['agent_name'] ?? 'Agente')) : 'Post non trovato';
    $include_content = "/_content/public/post.inc.php";
} elseif ($ACT_GET === 'AGENT') {
    $include_content = "/_content/public/agent.inc.php";
    $PAGE_TITLE = 'Agente';
} elseif ($ACT_GET === 'COSA_E_TUILAND') {
    $include_content = "/_content/public/cosa_e_tuiland.inc.php";
    $PAGE_TITLE = "Cos'è TuiLand";
} elseif ($ACT_GET === 'TUI') {
    $include_content = "/_content/public/textualuserintelligence.inc.php";
    $PAGE_TITLE = "Textual User Intelligence";
} else {
switch ($CONT) {
    case "PROFILO":
        $include_content = "/_content/private/profilo.inc.php";
        break;
    case "IMPOSTAZIONI":
        $include_content = "/_content/private/impostazioni.inc.php";
        break;
    case "MESSAGGIO":
        $include_content = "/_content/private/messaggio.inc.php";
        break;
    case "FEEDBACK":
        $include_content = "/_content/private/feedback.inc.php";
        break;
    case "ESPLORA":
        $include_content = "/_content/private/esplora.inc.php";
        break;
    default:
        $include_content = "/_content/private/feed.inc.php";
        break;
}
}

// Feed e Esplora: evita cache aggressiva così i commenti non restano nascosti
if ($CONT === 'ESPLORA' || $CONT === '') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

ob_start();
include(FRAMEWORK_ROOT . '/_content/private/tpl.php');
$pageContent = ob_get_contents();
ob_end_clean();

http_modified($pageContent);
