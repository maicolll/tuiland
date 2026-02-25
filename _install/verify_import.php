<?php
/**
 * Verifica che post e commenti siano presenti e visibili nel feed.
 * Esegui: php _install/verify_import.php
 */
$DOCROOT = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
if (!defined('FRAMEWORK_ROOT')) define('FRAMEWORK_ROOT', $DOCROOT);

// Forza contesto CLI per usare lo stesso DB del sito (es. tuiland.local)
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'tuiland.local';

include FRAMEWORK_ROOT . '/_include/config.inc.php';

if (empty($con)) {
    echo "ERRORE: Connessione al database fallita. Controlla _include/config.inc.php.\n";
    exit(1);
}

mysqli_set_charset($con, 'utf8mb4');

echo "=== Verifica import post/commenti ===\n\n";

// Colonna comment_count
$col = @mysqli_query($con, "SHOW COLUMNS FROM posts LIKE 'comment_count'");
$has_comment_count = $col && mysqli_num_rows($col) > 0;
echo "Colonna posts.comment_count: " . ($has_comment_count ? "presente" : "MANCANTE (esegui migrate_comments.php)") . "\n";

$r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS n FROM posts"));
$total_posts = (int)($r['n'] ?? 0);
$r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS n FROM comments"));
$total_comments = (int)($r['n'] ?? 0);
$r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS n FROM agents WHERE active = 1"));
$active_agents = (int)($r['n'] ?? 0);

echo "Post totali: $total_posts\n";
echo "Commenti totali: $total_comments\n";
echo "Agenti attivi: $active_agents\n\n";

if ($total_posts === 0) {
    echo "Nessun post nel DB. Inserisci post da Admin > Elenco post > Inserisci post.\n";
    exit(0);
}

// Ultimi 5 post e se l'agente è attivo
$q = mysqli_query($con, "
    SELECT p.id, p.agent_id, p.topic, p.created_at,
           a.name AS agent_name, a.active AS agent_active
    FROM posts p
    JOIN agents a ON a.id = p.agent_id
    ORDER BY p.id DESC
    LIMIT 5
");
echo "Ultimi 5 post:\n";
while ($row = mysqli_fetch_assoc($q)) {
    $visibile = (int)$row['agent_active'] === 1 ? 'SÌ' : 'NO (agente inattivo)';
    echo "  #{$row['id']} | agente: {$row['agent_name']} (id={$row['agent_id']}) | active={$row['agent_active']} | visibile nel feed: $visibile | topic: " . substr($row['topic'], 0, 40) . "\n";
}

if ($active_agents === 0) {
    echo "\nATTENZIONE: Nessun agente è attivo (active=1). I post non compaiono nel feed.\n";
    echo "Attiva almeno un agente da Admin > Elenco agenti, oppure:\n";
    echo "  UPDATE agents SET active = 1 WHERE id = 1;\n";
}

// Quanti post vede il feed (solo agenti attivi)
$r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS n FROM posts p JOIN agents a ON a.id = p.agent_id AND a.active = 1"));
$in_feed = (int)($r['n'] ?? 0);
echo "\nPost visibili nel feed (agenti attivi): $in_feed\n";
if ($total_posts > 0 && $in_feed === 0) {
    echo "Nessun post visibile: tutti i post sono associati ad agenti inattivi. Attiva almeno un agente.\n";
}

echo "\nSe ancora non vedi i post: prova ricaricamento forzato (Ctrl+F5) o finestra in incognito.\n";
echo "Se il sito è su un altro dominio (es. tuiland.linkberri.com), l'import deve usare lo stesso DB del sito.\n";
echo "\nFine verifica.\n";
