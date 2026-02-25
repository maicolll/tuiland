<?php
/**
 * Endpoint per "Carica altri" del feed. Restituisce JSON: { html, has_more, next_offset }.
 * Usa $CONF["feed_initial"] e $CONF["feed_max_total"]. Parametri: offset, context (public | private_feed | esplora)
 */
session_start();
$DOCROOT = realpath(__DIR__);
if (!defined('FRAMEWORK_ROOT')) define('FRAMEWORK_ROOT', $DOCROOT);
include FRAMEWORK_ROOT . '/_include/config.inc.php';
include FRAMEWORK_ROOT . '/_include/lib.inc.php';
include FRAMEWORK_ROOT . '/_include/lang.inc.php';
include FRAMEWORK_ROOT . '/_include/translations.inc.php';
include FRAMEWORK_ROOT . '/_include/sn.inc.php';

header('Content-Type: application/json; charset=utf-8');

$feed_initial = (int)($CONF['feed_initial'] ?? 10);
$feed_max = (int)($CONF['feed_max_total'] ?? 50);
if ($feed_initial <= 0) $feed_initial = 10;
if ($feed_max < $feed_initial) $feed_max = $feed_initial;

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$context = $_GET['context'] ?? 'public';
// Quanti chiedere in questa richiesta: non superare il massimo totale
$limit = min($feed_initial, max(0, $feed_max - $offset));
if ($limit <= 0 || $offset >= $feed_max) {
    echo json_encode(['ok' => true, 'html' => '', 'has_more' => false, 'next_offset' => $offset]);
    exit;
}

$user_id = sn_user_id();
$logged_in = ($user_id > 0);

if ($context === 'private_feed' || $context === 'esplora') {
    if (!$logged_in) {
        echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
        exit;
    }
}

if (!$con) {
    echo json_encode(['ok' => false, 'error' => 'db']);
    exit;
}

$page_size = $limit;
$posts = [];
if ($context === 'esplora') {
    $posts = sn_feed_public($con, $page_size + 1, $offset);
} else {
    $uid = ($context === 'private_feed') ? $user_id : $user_id;
    $posts = sn_feed($con, $uid, $page_size + 1, $offset);
}

$has_more = count($posts) > $page_size;
if ($has_more) {
    array_pop($posts);
}
$next_offset = $offset + count($posts);
// Rispetta il massimo totale da config
if ($next_offset >= $feed_max) {
    $has_more = false;
}

sn_attach_comments_to_posts($con, $posts, 4);
$bp = $CONF['base_path'] ?? '';

if (empty($posts)) {
    $html = '';
} else {
    ob_start();
    include FRAMEWORK_ROOT . '/_content/public/feed_posts.inc.php';
    $html = ob_get_clean();
}

echo json_encode([
    'ok' => true,
    'html' => $html,
    'has_more' => $has_more,
    'next_offset' => $next_offset
]);
