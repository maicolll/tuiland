<?php
/**
 * Applica le traduzioni dal file posts_translations.json ai post nel database.
 * Formato file: [ { "id": 1, "body": "traduzione...", "content": null o "..." }, ... ]
 * Esegui: php _install/translate_posts_apply.php
 */
error_reporting(E_ALL);
$DOCROOT = realpath(__DIR__ . '/..');
require $DOCROOT . '/_include/config.inc.php';

if (!$con) {
    fwrite(STDERR, "Connessione DB mancante.\n");
    exit(1);
}

$file = __DIR__ . '/posts_translations.json';
if (!is_readable($file)) {
    fwrite(STDERR, "File non trovato: $file\n");
    exit(1);
}

$json = file_get_contents($file);
$list = json_decode($json, true);
if (!is_array($list)) {
    fwrite(STDERR, "File JSON non valido.\n");
    exit(1);
}

$updated = 0;
foreach ($list as $item) {
    $id = (int) ($item['id'] ?? 0);
    if ($id <= 0) continue;
    $body = $item['body'] ?? '';
    $content = isset($item['content']) && $item['content'] !== null ? $item['content'] : null;

    $body_esc = mysqli_real_escape_string($con, $body);
    $content_sql = $content === null ? 'NULL' : "'" . mysqli_real_escape_string($con, $content) . "'";
    $ok = mysqli_query($con, "UPDATE posts SET body = '$body_esc', content = $content_sql WHERE id = $id LIMIT 1");
    if ($ok && mysqli_affected_rows($con) > 0) {
        $updated++;
    }
}

echo "Aggiornati $updated post.\n";
