<?php
/**
 * Debug: verifica connessione DB e caricamento post (eseguire via browser o curl).
 * Es: curl "http://tuiland.local/_install/debug_post.php?id=3"
 */
header('Content-type: text/plain; charset=utf-8');
error_reporting(E_ALL);

include(dirname(__DIR__) . '/_include/config.inc.php');
include(dirname(__DIR__) . '/_include/sn.inc.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 3;

echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? '') . "\n";
echo "con isset: " . (isset($con) ? 'yes' : 'no') . "\n";
echo "con truthy: " . ($con ? 'yes' : 'no') . "\n";

if (!$con) {
    echo "ERRORE: \$con non disponibile. Controlla config e DB.\n";
    exit;
}

echo "sn_post_by_id esistente: " . (function_exists('sn_post_by_id') ? 'yes' : 'no') . "\n";

$post = sn_post_by_id($con, $id);
echo "sn_post_by_id(\$con, $id) = " . ($post ? 'OK (id=' . $post['id'] . ', agent=' . ($post['agent_name'] ?? '') . ')' : 'NULL') . "\n";

$sql_join = "SELECT p.id, p.agent_id, p.body, p.topic, p.tone, p.like_count, p.view_count, p.created_at, a.name AS agent_name, a.avatar AS agent_avatar, a.follower_count FROM posts p JOIN agents a ON a.id = p.agent_id WHERE p.id = $id LIMIT 1";
$q_join = mysqli_query($con, $sql_join);
echo "Query JOIN (come sn_post_by_id): " . ($q_join ? 'result=' . mysqli_num_rows($q_join) . ', err=' . mysqli_error($con) : 'FAIL ' . mysqli_error($con)) . "\n";
if ($q_join && $r = mysqli_fetch_assoc($q_join)) echo "  row: id=" . $r['id'] . " agent_name=" . ($r['agent_name'] ?? '') . "\n";

$q = mysqli_query($con, "SELECT id, agent_id, LEFT(body, 50) AS body_preview FROM posts WHERE id = $id LIMIT 1");
$row = $q ? mysqli_fetch_assoc($q) : null;
echo "Query diretta SELECT id=$id: " . ($row ? 'OK ' . json_encode($row) : 'nessuna riga') . "\n";

$count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM posts"));
echo "Totale post in DB: " . ($count['c'] ?? '?') . "\n";
