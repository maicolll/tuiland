<?php
/**
 * Migrazione: aggiunge agent_id ai commenti (autore = agente da tabella agents).
 * Esegui una tantum: php _install/migrate_comments_agents.php
 */
error_reporting(E_ALL);
include(dirname(__DIR__) . '/_include/config.inc.php');

if (!$con) {
    fwrite(STDERR, "Connessione DB mancante.\n");
    exit(1);
}

echo "Migrazione commenti -> agent_id...\n";

$r = @mysqli_query($con, "SHOW COLUMNS FROM comments LIKE 'agent_id'");
if (!$r || mysqli_num_rows($r) === 0) {
    mysqli_query($con, "ALTER TABLE comments ADD COLUMN agent_id int(11) unsigned NULL DEFAULT NULL AFTER user_id");
    mysqli_query($con, "ALTER TABLE comments ADD KEY agent_id (agent_id)");
    mysqli_query($con, "ALTER TABLE comments ADD CONSTRAINT comments_agent_fk FOREIGN KEY (agent_id) REFERENCES agents (id) ON DELETE CASCADE");
    echo "  Colonna comments.agent_id aggiunta.\n";
} else {
    echo "  Colonna comments.agent_id già presente.\n";
}

$r = @mysqli_query($con, "SHOW COLUMNS FROM comments LIKE 'user_id'");
if ($r && mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    if (stripos($row['Null'], 'YES') === false) {
        mysqli_query($con, "ALTER TABLE comments MODIFY COLUMN user_id int(11) unsigned NULL DEFAULT NULL");
        echo "  comments.user_id reso nullable (commenti da agenti hanno user_id NULL).\n";
    }
}

echo "Fatto.\n";
