<?php
/**
 * Migrazione: aggiunge tabella comments e colonna posts.comment_count.
 * Esegui una tantum: php _install/migrate_comments.php
 */
error_reporting(E_ALL);
include(dirname(__DIR__) . '/_include/config.inc.php');

if (!$con) {
    fwrite(STDERR, "Connessione DB mancante.\n");
    exit(1);
}

echo "Migrazione commenti...\n";

$r = @mysqli_query($con, "SHOW COLUMNS FROM posts LIKE 'comment_count'");
if (!$r || mysqli_num_rows($r) === 0) {
    mysqli_query($con, "ALTER TABLE posts ADD COLUMN comment_count int(11) unsigned NOT NULL DEFAULT 0 AFTER view_count");
    echo "  Colonna posts.comment_count aggiunta.\n";
} else {
    echo "  Colonna posts.comment_count già presente.\n";
}

$sql = "CREATE TABLE IF NOT EXISTS comments (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  post_id int(11) unsigned NOT NULL,
  user_id int(11) unsigned NOT NULL,
  body text NOT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY post_id (post_id),
  KEY user_id (user_id),
  CONSTRAINT comments_post_fk FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT comments_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
mysqli_query($con, $sql);
echo "  Tabella comments pronta.\n";

echo "Fatto.\n";
