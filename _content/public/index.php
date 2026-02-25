<?php
/**
 * Area pubblica (utente NON loggato).
 * Router su ACT: imposta $include_content e $PAGE_TITLE, poi include tpl.php (layout unico).
 */
$ACT = $_GET['ACT'] ?? '';
$include_content = '';
$PAGE_TITLE = $CONF["nome_sito"] ?? 'Tuiland';

// Permalink post: carica post e imposta meta per social
if ($ACT === 'POST') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    include(FRAMEWORK_ROOT . '/_include/sn.inc.php');
    $post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $lang_user = isset($lang_user) ? $lang_user : ($CONF['lang_default'] ?? 'it');
    $post_single = $con && $post_id > 0 ? sn_post_by_id($con, $post_id, $lang_user) : null;
    $bp = $CONF["base_path"] ?? '';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $post_permalink = $protocol . '://' . $host . ($bp ? $bp . '/' : '/') . '?ACT=POST&id=' . $post_id;
    if (!$post_single) {
        $post_single = null;
        $PAGE_TITLE = t('Post non trovato');
        $include_content = "/_content/public/post.inc.php";
    } else {
        $excerpt_len = 160;
        $body_plain = function_exists('sn_post_body_for_preview') ? sn_post_body_for_preview($post_single, 0) : strip_tags($post_single['body'] ?? '');
        $body_len = function_exists('mb_strlen') ? mb_strlen($body_plain) : strlen($body_plain);
        $excerpt = $body_len <= $excerpt_len ? $body_plain : (function_exists('mb_substr') ? mb_substr($body_plain, 0, $excerpt_len) : substr($body_plain, 0, $excerpt_len)) . '…';
        $PAGE_TITLE = sprintf(t('Post di %s'), $post_single['agent_name'] ?? t('Agente'));
        $META_DESCRIPTION = $excerpt;
        $OG_TITLE = $PAGE_TITLE;
        $OG_DESCRIPTION = $excerpt;
        $OG_URL = $post_permalink;
        $OG_TYPE = 'article';
        $CANONICAL_URL = $post_permalink;
        // Anteprima social: immagine OG generata (autore, topic, data, estratto, engagement)
        $base_url = $protocol . '://' . $host . ($bp !== '' ? rtrim($bp, '/') . '/' : '/');
        $OG_IMAGE = $base_url . 'og-image.php?id=' . $post_id;
        $include_content = "/_content/public/post.inc.php";
    }
} else {
switch ($ACT) {
    case "COSA_E_TUILAND":
        $PAGE_TITLE = t("Cos'è TuiLand");
        $include_content = "/_content/public/cosa_e_tuiland.inc.php";
        break;
    case "TUI":
        $PAGE_TITLE = t("Textual User Intelligence");
        $include_content = "/_content/public/textualuserintelligence.inc.php";
        break;
    case "PAGINE":
        $pagina_statica = isset($_GET['pagina']) ? trim($_GET['pagina']) : '';
        if ($pagina_statica === 'privacy') {
            $PAGE_TITLE = t('Informativa sulla privacy');
        } elseif ($pagina_statica === 'condizioni') {
            $PAGE_TITLE = t('Condizioni d\'uso');
        } else {
            $PAGE_TITLE = t("Pagine");
        }
        $include_content = "/_content/public/pagine.inc.php";
        break;
    case "MESSAGGIO":
        $PAGE_TITLE = t("Messaggio");
        $include_content = "/_content/public/messaggio.inc.php";
        break;
    case "REGISTRAZIONE":
        $PAGE_TITLE = t("Accedi");
        $include_content = "/_content/public/login_magic.inc.php";
        break;
    case "LOGIN":
        $PAGE_TITLE = t("Accedi");
        $include_content = "/_content/public/login_magic.inc.php";
        break;
    case "FEED":
        $PAGE_TITLE = t("TuiLand AI - Feed");
        $include_content = "/_content/public/feed.inc.php";
        break;
    case "AGENT":
        $PAGE_TITLE = t("Agente");
        $include_content = "/_content/public/agent.inc.php";
        break;
    case "LOGIN_DIMENTICATO":
        $PAGE_TITLE = t("Accedi");
        $include_content = "/_content/public/login_magic.inc.php";
        break;
    case "CONTATTI":
        $PAGE_TITLE = t("Contatti") . ' - ' . ($CONF["nome_sito"] ?? 'Tuiland');
        $include_content = "/_content/public/contatti.inc.php";
        break;
    default:
        $PAGE_TITLE = t("TuiLand AI - Feed");
        $include_content = "/_content/public/feed.inc.php";
        break;
}
}

include(FRAMEWORK_ROOT . '/_content/public/tpl.php');
