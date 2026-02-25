<?php
/**
 * Verifica che il feed includa i commenti nell'HTML.
 * Uso: php _install/check_feed_comments.php
 */
if (php_sapi_name() !== 'cli') {
    die('Solo da riga di comando.');
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'tuiland.local';
$_GET['ACT'] = '';
define('FRAMEWORK_ROOT', dirname(__DIR__));

ob_start();
include(FRAMEWORK_ROOT . '/index.php');
$html = ob_get_clean();

$has_section = (substr_count($html, 'feed-comments') > 0);
$has_collapsed = (substr_count($html, 'feed-comments-collapsed') > 0);
$has_list = (substr_count($html, 'feed-comments-list') > 0);
$has_li_comment = (preg_match('/<li class="text-gray-700[^"]*">.*?<strong>/s', $html));
$comment_count_btns = substr_count($html, 'feed-comment-count-btn');

echo "=== Verifica feed commenti ===\n";
echo "feed-comments (sezioni): " . substr_count($html, 'feed-comments') . "\n";
echo "feed-comments-collapsed: $has_collapsed\n";
echo "feed-comments-list: $has_list (count: " . substr_count($html, 'feed-comments-list') . ")\n";
echo "comments-post- (id sezioni): " . substr_count($html, 'comments-post-') . "\n";
echo "Pulsanti 'N commenti' (feed-comment-count-btn): $comment_count_btns\n";
echo "Tag <li> con commento (strong): " . (preg_match_all('/<li class="text-gray-700[^"]*">/s', $html, $m) ? count($m[0]) : 0) . "\n";

// Estrai un pezzo di HTML dove dovrebbero esserci i commenti
if (preg_match('/<section[^>]*feed-comments[^>]*>.*?<ul[^>]*feed-comments-list[^>]*>(.*?)<\/ul>/s', $html, $m)) {
    $inner = $m[1];
    $li_count = substr_count($inner, '<li ');
    echo "Contenuto interno di una sezione commenti: $li_count <li> trovati\n";
    if ($li_count > 0) {
        echo "--- Primo blocco commenti (trim) ---\n";
        echo trim(substr($inner, 0, 500)) . "\n";
    }
} else {
    echo "Nessuna sezione <section> con feed-comments + ul.feed-comments-list trovata nell'HTML.\n";
}

// Verifica DB: quanti commenti ci sono
include(FRAMEWORK_ROOT . '/_include/config.inc.php');
if ($con) {
    $q = mysqli_query($con, "SELECT COUNT(*) AS n FROM comments");
    $r = mysqli_fetch_assoc($q);
    // Salva un estratto HTML per ispezione
$snippet = preg_replace('/\s+/', ' ', preg_match('/<section[^>]*comments-post-[0-9]+[^>]*>/', $html, $m) ? $m[0] : '');
file_put_contents(FRAMEWORK_ROOT . '/_install/feed_snippet.txt', substr($html, strpos($html, 'feed-comment-count-btn'), 1200));
echo "\nEstratto HTML salvato in _install/feed_snippet.txt\n";
echo "\nCommenti in DB: " . ($r['n'] ?? 0) . "\n";
    $q2 = mysqli_query($con, "SELECT post_id, COUNT(*) AS c FROM comments GROUP BY post_id LIMIT 5");
    echo "Commenti per post (primi 5): ";
    while ($row = mysqli_fetch_assoc($q2)) { echo "post " . $row['post_id'] . "=" . $row['c'] . " "; }
    echo "\n";
}
