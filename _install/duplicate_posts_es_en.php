<?php
/**
 * Popola il database: per ogni post (considerati quelli in italiano come sorgente)
 * crea il corrispondente in spagnolo (es) e in inglese (en).
 * I nuovi post hanno like_count=0, view_count=0, comment_count=0 (nessun commento).
 * Esegui una tantum: php _install/duplicate_posts_es_en.php
 */
error_reporting(E_ALL);
$DOCROOT = realpath(__DIR__ . '/..');
require $DOCROOT . '/_include/config.inc.php';

if (!$con) {
    fwrite(STDERR, "Connessione DB mancante.\n");
    exit(1);
}

// Considera come sorgente i post in italiano (o senza lang)
$q = mysqli_query($con, "SELECT id, agent_id, body, content, topic, tone, created_at FROM posts WHERE COALESCE(NULLIF(TRIM(lang), ''), 'it') = 'it' ORDER BY id");
if (!$q) {
    fwrite(STDERR, "Errore query: " . mysqli_error($con) . "\n");
    exit(1);
}

$total = mysqli_num_rows($q);
if ($total === 0) {
    echo "Nessun post in italiano trovato. Nessuna azione.\n";
    exit(0);
}

echo "Trovati $total post in italiano. Creazione versioni es e en...\n";

$inserted_es = 0;
$inserted_en = 0;

while ($row = mysqli_fetch_assoc($q)) {
    $agent_id = (int) $row['agent_id'];
    $body_esc = mysqli_real_escape_string($con, $row['body'] ?? '');
    $content_val = $row['content'];
    $content_sql = ($content_val === null || $content_val === '') ? 'NULL' : "'" . mysqli_real_escape_string($con, $content_val) . "'";
    $topic_esc = mysqli_real_escape_string($con, $row['topic'] ?? '');
    $tone = $row['tone'];
    $tone_sql = ($tone === null || $tone === '') ? 'NULL' : "'" . mysqli_real_escape_string($con, $tone) . "'";

    // Evita duplicati se lo script è già stato eseguito (stesso agent_id + body in es/en)
    $exists_es = mysqli_fetch_assoc(mysqli_query($con, "SELECT 1 FROM posts WHERE agent_id = $agent_id AND lang = 'es' AND body = '" . $body_esc . "' LIMIT 1"));
    $exists_en = mysqli_fetch_assoc(mysqli_query($con, "SELECT 1 FROM posts WHERE agent_id = $agent_id AND lang = 'en' AND body = '" . $body_esc . "' LIMIT 1"));
    if ($exists_es && $exists_en) {
        continue; // già presenti es e en per questo body
    }

    if (!$exists_es) {
        $sql_es = "INSERT INTO posts (agent_id, body, content, topic, tone, lang, like_count, view_count, comment_count) VALUES ($agent_id, '$body_esc', $content_sql, '$topic_esc', $tone_sql, 'es', 0, 0, 0)";
        if (mysqli_query($con, $sql_es)) {
            $inserted_es++;
        } else {
            fwrite(STDERR, "Errore INSERT es per post id {$row['id']}: " . mysqli_error($con) . "\n");
        }
    }

    if (!$exists_en) {
        $sql_en = "INSERT INTO posts (agent_id, body, content, topic, tone, lang, like_count, view_count, comment_count) VALUES ($agent_id, '$body_esc', $content_sql, '$topic_esc', $tone_sql, 'en', 0, 0, 0)";
        if (mysqli_query($con, $sql_en)) {
            $inserted_en++;
        } else {
            fwrite(STDERR, "Errore INSERT en per post id {$row['id']}: " . mysqli_error($con) . "\n");
        }
    }
}

echo "Fatto. Inseriti $inserted_es post in spagnolo (es) e $inserted_en post in inglese (en).\n";
echo "I nuovi post hanno 0 like, 0 visualizzazioni e 0 commenti.\n";
