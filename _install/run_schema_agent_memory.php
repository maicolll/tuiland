<?php
/**
 * Crea le tabelle agent_memory_queue e agent_memories se non esistono.
 * Esegui: php _install/run_schema_agent_memory.php
 */
$DOCROOT = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
if (!defined('FRAMEWORK_ROOT')) define('FRAMEWORK_ROOT', $DOCROOT);
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'tuiland.local';
include FRAMEWORK_ROOT . '/_include/config.inc.php';

if (empty($con)) {
    fwrite(STDERR, "Errore: connessione DB fallita.\n");
    exit(1);
}
mysqli_set_charset($con, 'utf8mb4');

$schema = __DIR__ . '/schema_agent_memory.sql';
if (!is_file($schema)) {
    fwrite(STDERR, "File non trovato: $schema\n");
    exit(1);
}

$sql = file_get_contents($schema);
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = trim($sql);
if ($sql === '') {
    fwrite(STDERR, "Nessun SQL da eseguire.\n");
    exit(1);
}

if (!mysqli_multi_query($con, $sql)) {
    fwrite(STDERR, "Errore: " . mysqli_error($con) . "\n");
    exit(1);
}
do {
    if ($r = mysqli_store_result($con)) mysqli_free_result($r);
} while (mysqli_next_result($con));

echo "Tabelle agent_memory_queue e agent_memories create (o già esistenti).\n";
