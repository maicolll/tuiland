<?php
/**
 * Crea la tabella content_update_log.
 * Uso: php _install/run_schema_content_update_log.php
 */
error_reporting(E_ALL);
$DOCROOT = realpath(__DIR__ . '/..');
require $DOCROOT . '/_include/config.inc.php';
require $DOCROOT . '/_include/content_updates.inc.php';

if (!$con) {
    fwrite(STDERR, "Connessione DB mancante.\n");
    exit(1);
}

if (tuiland_content_updates_bootstrap($con)) {
    echo "OK: content_update_log + cartelle content_updates/.\n";
    exit(0);
}

fwrite(STDERR, "Errore bootstrap: " . mysqli_error($con) . "\n");
exit(1);
