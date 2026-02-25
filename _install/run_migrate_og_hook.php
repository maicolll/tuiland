<?php
/**
 * Esegue migrate_posts_og_hook.sql (aggiunge colonna og_hook a posts).
 * Da riga di comando: php _install/run_migrate_og_hook.php
 */
if (php_sapi_name() !== 'cli') {
    die('Eseguire da riga di comando: php run_migrate_og_hook.php');
}
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'tuiland.local';
$base = dirname(__DIR__);
require $base . '/_include/config.inc.php';
if (!$con) {
    fwrite(STDERR, "Errore: connessione al database fallita.\n");
    exit(1);
}
$sql = "ALTER TABLE `posts` ADD COLUMN `og_hook` VARCHAR(250) NULL DEFAULT NULL AFTER `topic`";
if (mysqli_query($con, $sql)) {
    echo "OK: colonna og_hook aggiunta alla tabella posts.\n";
    exit(0);
}
$err = mysqli_error($con);
if (strpos($err, 'Duplicate column name') !== false) {
    echo "OK: colonna og_hook già presente (nessuna modifica).\n";
    exit(0);
}
fwrite(STDERR, "Errore SQL: $err\n");
exit(1);
