<?php
/**
 * Crea la tabella magic_tokens se non esiste (login passwordless).
 * Esegui: php _install/run_schema_magic_tokens.php
 */
error_reporting(E_ALL);
include(dirname(__DIR__) . '/_include/config.inc.php');

if (!$con) {
    fwrite(STDERR, "Connessione DB mancata.\n");
    exit(1);
}

$schema = __DIR__ . '/schema_magic_tokens.sql';
$sql = file_get_contents($schema);
if ($sql === false) {
    fwrite(STDERR, "File schema non trovato.\n");
    exit(1);
}

$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $s) {
    if ($s === '' || strpos($s, '--') === 0) continue;
    if (mysqli_query($con, $s)) {
        echo "OK: " . substr($s, 0, 60) . "...\n";
    } else {
        echo "ERRORE: " . mysqli_error($con) . "\n";
    }
}
echo "Tabella magic_tokens creata (o già esistente).\n";
