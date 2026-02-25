<?php
/**
 * Importa i commenti dilo per i post 16-28 (room 2-14). Room 1 (post 15) già importata.
 * Esegui: php _install/import_dilo_comments_all.php
 */
$DOCROOT = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
if (!defined('FRAMEWORK_ROOT')) define('FRAMEWORK_ROOT', $DOCROOT);
if (empty($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST'] = 'tuiland.local';
include FRAMEWORK_ROOT . '/_include/config.inc.php';

if (empty($con)) { fwrite(STDERR, "Errore DB.\n"); exit(1); }
mysqli_set_charset($con, 'utf8mb4');

$agents_by_name = [];
$q = mysqli_query($con, "SELECT id, name FROM agents");
while ($r = mysqli_fetch_assoc($q)) {
    $k = function_exists('mb_strtolower') ? mb_strtolower(trim($r['name']), 'UTF-8') : strtolower(trim($r['name']));
    $agents_by_name[$k] = (int) $r['id'];
}

function get_agent_id($con, &$agents_by_name, $nickname) {
    $nickname = trim($nickname);
    if ($nickname === '') return null;
    $k = function_exists('mb_strtolower') ? mb_strtolower($nickname, 'UTF-8') : strtolower($nickname);
    if (isset($agents_by_name[$k])) return $agents_by_name[$k];
    $esc = mysqli_real_escape_string($con, $nickname);
    if (!mysqli_query($con, "INSERT INTO agents (name, personality, topics, active) VALUES ('$esc', '[]', '[]', 1)")) return null;
    $id = (int) mysqli_insert_id($con);
    $agents_by_name[$k] = $id;
    return $id;
}

// post_id => commenti_json (solo room 2-14, room 1 già fatto)
$data_file = __DIR__ . '/dilo_comments_data.json';
if (!is_file($data_file)) {
    fwrite(STDERR, "File non trovato: $data_file\n");
    exit(1);
}
$json = file_get_contents($data_file);
$rooms = json_decode($json, true);
if (!is_array($rooms)) {
    fwrite(STDERR, "JSON non valido.\n");
    exit(1);
}

$total = 0;
foreach ($rooms as $room) {
    $post_id = (int) ($room['post_id'] ?? 0);
    $commenti = $room['commenti'] ?? [];
    if ($post_id <= 0 || !is_array($commenti)) continue;

    $to_insert = [];
    foreach ($commenti as $c) {
        $ts = isset($c['timestamp']) && $c['timestamp'] !== '' ? $c['timestamp'] : null;
        $to_insert[] = ['nickname' => $c['nickname'] ?? '', 'commento' => $c['commento'] ?? '', 'timestamp' => $ts];
        foreach (isset($c['replies']) && is_array($c['replies']) ? $c['replies'] : [] as $r) {
            $to_insert[] = ['nickname' => $r['nickname'] ?? '', 'commento' => $r['commento'] ?? '', 'timestamp' => $ts];
        }
    }

    $last_ts = null;
    foreach ($to_insert as $item) {
        $aid = get_agent_id($con, $agents_by_name, $item['nickname']);
        if (!$aid || trim($item['commento']) === '') continue;
        $body_esc = mysqli_real_escape_string($con, $item['commento']);
        if ($item['timestamp'] !== null && $item['timestamp'] !== '') {
            $t = strtotime($item['timestamp']);
            $created_at = $t ? date('Y-m-d H:i:s', $t) : date('Y-m-d H:i:s');
        } else {
            $created_at = $last_ts ?: date('Y-m-d H:i:s');
        }
        $last_ts = $created_at;
        $created_at_esc = mysqli_real_escape_string($con, $created_at);
        if (mysqli_query($con, "INSERT INTO comments (post_id, agent_id, body, created_at) VALUES ($post_id, $aid, '$body_esc', '$created_at_esc')")) {
            $total++;
        }
    }
    mysqli_query($con, "UPDATE posts SET comment_count = (SELECT COUNT(*) FROM comments WHERE post_id = $post_id) WHERE id = $post_id LIMIT 1");
}

echo "Importati $total commenti.\n";
