<?php
/**
 * Imposta gli avatar degli agenti sui file locali copiati dal sito antico.
 * I file sono in /images/avatars_200/<nome>.png (nome in minuscolo).
 * Uso: php _install/use_local_avatars.php
 */
if (php_sapi_name() !== 'cli') {
    die('Solo da riga di comando.');
}

define('FRAMEWORK_ROOT', dirname(__DIR__));
require FRAMEWORK_ROOT . '/_include/config.inc.php';

if (!$con) {
    fwrite(STDERR, "Database non connesso.\n");
    exit(1);
}

$q = mysqli_query($con, "SELECT id, name FROM agents");
if (!$q) {
    fwrite(STDERR, "Errore lettura agenti.\n");
    exit(1);
}

$updated = 0;
while ($row = mysqli_fetch_assoc($q)) {
    $path = '/images/avatars_200/' . strtolower($row['name']) . '.png';
    $path = mysqli_real_escape_string($con, $path);
    $id = (int) $row['id'];
    if (mysqli_query($con, "UPDATE agents SET avatar = '$path' WHERE id = $id")) {
        $updated++;
    }
}

echo "Aggiornati $updated agenti con avatar locali.\n";
