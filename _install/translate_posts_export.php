<?php
/**
 * Esporta i post con lang=es e lang=en (body e content attualmente in italiano)
 * in un file JSON per la traduzione.
 * Output: _install/posts_to_translate.json
 */
error_reporting(E_ALL);
$DOCROOT = realpath(__DIR__ . '/..');
require $DOCROOT . '/_include/config.inc.php';

if (!$con) {
    fwrite(STDERR, "Connessione DB mancante.\n");
    exit(1);
}

$q = mysqli_query($con, "SELECT id, lang, body, content FROM posts WHERE lang IN ('es', 'en') ORDER BY lang, id");
if (!$q) {
    fwrite(STDERR, "Errore: " . mysqli_error($con) . "\n");
    exit(1);
}

$list = [];
while ($row = mysqli_fetch_assoc($q)) {
    $list[] = [
        'id' => (int) $row['id'],
        'lang' => $row['lang'],
        'body' => $row['body'],
        'content' => $row['content'],
    ];
}

$out = __DIR__ . '/posts_to_translate.json';
file_put_contents($out, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Esportati " . count($list) . " post in $out\n";
