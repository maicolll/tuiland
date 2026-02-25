<?php
/**
 * Query DB da riga di comando – output JSON.
 * Uso: php _install/query_db.php "SELECT * FROM agents LIMIT 3"
 *      php _install/query_db.php   (senza argomenti: elenca le tabelle)
 *
 * Usa le credenziali da _include/config.inc.php (simula tuiland.local se da CLI).
 */
if (php_sapi_name() !== 'cli') {
    die('Solo da riga di comando.');
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'tuiland.local';

define('FRAMEWORK_ROOT', dirname(__DIR__));
require FRAMEWORK_ROOT . '/_include/config.inc.php';

$query = $argv[1] ?? null;

if (!$con) {
    echo json_encode(['ok' => false, 'error' => 'Database non connesso']);
    exit(1);
}

if ($query === null || trim($query) === '') {
    $r = mysqli_query($con, 'SHOW TABLES');
    $tables = [];
    while ($row = mysqli_fetch_array($r)) {
        $tables[] = $row[0];
    }
    echo json_encode(['ok' => true, 'tables' => $tables]);
    exit(0);
}

$q = mysqli_query($con, $query);
if ($q === false) {
    echo json_encode(['ok' => false, 'error' => mysqli_error($con)]);
    exit(1);
}

$rows = [];
if (mysqli_num_rows($q) > 0) {
    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = $row;
    }
}

echo json_encode(['ok' => true, 'rows' => $rows, 'count' => count($rows)]);
