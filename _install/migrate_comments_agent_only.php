<?php
/**
 * Migrazione: commenti solo da agenti. Rimuove user_id, agent_id diventa NOT NULL.
 * Esegui dopo aver svuotato commenti o aver migrato i dati: php _install/migrate_comments_agent_only.php
 */
error_reporting(E_ALL);
include(dirname(__DIR__) . '/_include/config.inc.php');

if (!$con) {
    fwrite(STDERR, "Connessione DB mancante.\n");
    exit(1);
}

echo "Migrazione commenti: solo agent_id (autore), rimozione user_id...\n";

// Rimuovi FK su user_id se esiste
$r = @mysqli_query($con, "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments' AND CONSTRAINT_NAME = 'comments_user_fk'");
if ($r && mysqli_num_rows($r) > 0) {
    mysqli_query($con, "ALTER TABLE comments DROP FOREIGN KEY comments_user_fk");
    echo "  Rimosso vincolo comments_user_fk.\n";
}

// Rimuovi indice user_id se esiste (può restare dopo DROP FK)
$r = @mysqli_query($con, "SHOW COLUMNS FROM comments LIKE 'user_id'");
if ($r && mysqli_num_rows($r) > 0) {
    mysqli_query($con, "ALTER TABLE comments DROP COLUMN user_id");
    echo "  Rimossa colonna comments.user_id.\n";
}

// agent_id NOT NULL (autore obbligatorio = agente)
$r = @mysqli_query($con, "SHOW COLUMNS FROM comments LIKE 'agent_id'");
if ($r && mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    if (stripos($row['Null'], 'YES') !== false) {
        mysqli_query($con, "ALTER TABLE comments MODIFY COLUMN agent_id int(11) unsigned NOT NULL");
        echo "  comments.agent_id impostato NOT NULL.\n";
    }
}

echo "Fatto.\n";
