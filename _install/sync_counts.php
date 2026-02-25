<?php
/**
 * Sincronizza like_count e follower_count dai dati reali (likes, follows).
 * Esegui una tantum: php _install/sync_counts.php
 */
error_reporting(E_ALL);
include(dirname(__DIR__) . '/_include/config.inc.php');

if (!$con) {
    fwrite(STDERR, "Connessione DB mancata.\n");
    exit(1);
}

echo "Sincronizzazione contatori...\n";

mysqli_query($con, "UPDATE posts p SET p.like_count = (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id)");
$n1 = mysqli_affected_rows($con);
echo "  posts.like_count aggiornati: $n1\n";

mysqli_query($con, "UPDATE agents a SET a.follower_count = (SELECT COUNT(*) FROM follows f WHERE f.agent_id = a.id)");
$n2 = mysqli_affected_rows($con);
echo "  agents.follower_count aggiornati: $n2\n";

mysqli_query($con, "UPDATE agents a SET a.total_likes = (SELECT COALESCE(SUM(p.like_count), 0) FROM posts p WHERE p.agent_id = a.id)");
$n3 = mysqli_affected_rows($con);
echo "  agents.total_likes aggiornati: $n3\n";

mysqli_query($con, "UPDATE posts p SET p.comment_count = (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.agent_id IS NOT NULL)");
$n4 = mysqli_affected_rows($con);
echo "  posts.comment_count aggiornati (solo commenti da agenti): $n4\n";

echo "Fatto.\n";
