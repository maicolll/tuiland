<?php
/**
 * FRAMEWORK - Libreria condivisa
 *
 * Funzioni usate in tutto il progetto (front, admin, funzioni.php).
 * Aggiungere qui solo funzioni generiche; le funzioni specifiche del progetto
 * possono stare in funzioni_interne.php o in file per sezione.
 */

/**
 * Invia la pagina con supporto ETag (cache): se il client ha già la stessa versione, risponde 304.
 * Chiamare dopo ob_start() e prima di echo del contenuto; il contenuto viene passato come argomento.
 */
function http_modified($pageContent) {
    $indexPath = defined('FRAMEWORK_ROOT') ? FRAMEWORK_ROOT . '/index.php' : $_SERVER["DOCUMENT_ROOT"] . '/index.php';
    $identifier = $pageContent . "v1" . filemtime($indexPath);
    $etag = '"' . md5($identifier) . '"';
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
        header('HTTP/1.1 304 Not Modified');
        header('Connection: close');
        while (ob_get_level()) ob_end_clean();
        exit;
    }
    header("Etag: " . $etag);
    header('Cache-Control: private, must-revalidate');
    echo $pageContent;
}

function isValidEmail($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Escape per output in HTML (uso in query: usare db_input).
 */
function db_output($string) {
    return $string;
}

/**
 * Escape per uso in query SQL (protezione base). Preferire mysqli_real_escape_string($con, $x) dove possibile.
 */
function db_input($string) {
    $string = substr($string, 0, 2000);
    return addslashes($string);
}

function revDate($str) {
    if (strlen($str) < 10) return $str;
    $y = substr($str, 0, 4);
    $m = substr($str, 5, 2);
    $d = substr($str, 8, 2);
    return $d . "-" . $m . "-" . $y;
}

/**
 * Determina se il dark mode è attivo.
 * @param string|null $user_darkmode_preference Preferenza da DB: 'Y' (Dark), 'N' (Light), 'S' (System), null = non loggato (usa cookie sistema)
 * @return bool
 */
function is_dark_mode_active($user_darkmode_preference = null) {
    if ($user_darkmode_preference === 'Y') return true;
    if ($user_darkmode_preference === 'N') return false;
    $cookie = $_COOKIE['system_darkmode'] ?? null;
    if ($cookie !== null) return ($cookie === 'true' || $cookie === 'Y');
    return false;
}

/**
 * Traduzioni multilingua (sistema come cosmopoli.linkberri.com).
 * $TRANSLATION[key][lang] = traduzione. Inizializzato qui se non già definito.
 */
if (!isset($GLOBALS['TRANSLATION']) || !is_array($GLOBALS['TRANSLATION'])) {
    $GLOBALS['TRANSLATION'] = [];
}

/**
 * Set translation: registra una stringa per una lingua.
 * @param string $lng Codice lingua (it, es, en)
 * @param string $key Chiave (es. testo in italiano o identificativo)
 * @param string $translation Testo tradotto per $lng
 */
function st($lng, $key, $translation) {
    $GLOBALS['TRANSLATION'][$key][$lng] = $translation;
}

/**
 * Get translation: restituisce la stringa nella lingua corrente ($lang_user).
 * Se lingua it e chiave assente restituisce la chiave; altrimenti fallback sulla chiave evidenziata.
 * @param string $key Chiave della traduzione
 * @return string
 */
function t($key) {
    global $TRANSLATION, $lang_user, $CONF;
    if (!isset($lang_user)) $lang_user = isset($CONF["lang_default"]) ? $CONF["lang_default"] : 'it';
    if ($lang_user === 'it' && empty($TRANSLATION[$key][$lang_user])) {
        return $key;
    }
    if (!empty($TRANSLATION[$key][$lang_user])) {
        return $TRANSLATION[$key][$lang_user];
    }
    return '<span class="tr-missing" title="' . htmlspecialchars($key) . '">' . htmlspecialchars($key) . '</span>';
}

/**
 * Lingua del browser (Accept-Language), prime 2 lettere.
 * @return string
 */
function get_browser_language() {
    $browser_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
    $parts = explode(',', $browser_lang);
    $lang = strtolower(substr(trim($parts[0]), 0, 2));
    return $lang;
}

/**
 * Aggiunge il parametro lingua a un URL (per mantenere la lingua nella navigazione).
 * @param string $url URL o path (es. / o ?ACT=CONTATTI)
 * @return string URL con ?lng=xx o &lng=xx
 */
function url_lang($url = '') {
    global $lang_user, $CONF;
    if (empty($lang_user) || $lang_user === ($CONF["lang_default"] ?? 'it')) {
        return $url;
    }
    $sep = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $sep . 'lng=' . $lang_user;
}
