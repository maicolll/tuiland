<?php
/**
 * Content updates: applica piani JSON (posts, personality_updates, comments)
 * da admin paste o da coda file content_updates/pending/*.json
 *
 * Pacchetto file (schema_version 1):
 * {
 *   "id": "unique-slug",
 *   "schema_version": 1,
 *   "created_at": "ISO-8601",
 *   "source": "cursor-project-fase1",
 *   "ops": { "posts": [], "personality_updates": [], "comments": [] }
 * }
 *
 * Compatibile anche col JSON “piano” legacy (posts/comments/personality_updates in root).
 */

if (!defined('TUILAND_CONTENT_UPDATES_ROOT')) {
    define('TUILAND_CONTENT_UPDATES_ROOT', dirname(__DIR__) . '/content_updates');
}

/**
 * Assicura tabella log + cartelle coda.
 * @return bool
 */
function tuiland_content_updates_bootstrap($con) {
    if (!$con) return false;
    $sql = "CREATE TABLE IF NOT EXISTS `content_update_log` (
      `id` varchar(120) NOT NULL,
      `filename` varchar(255) DEFAULT NULL,
      `source` varchar(120) DEFAULT NULL,
      `status` enum('applied','failed','skipped') NOT NULL DEFAULT 'applied',
      `posts_created` int(11) NOT NULL DEFAULT 0,
      `comments_created` int(11) NOT NULL DEFAULT 0,
      `personality_updated` int(11) NOT NULL DEFAULT 0,
      `error_message` text,
      `applied_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `status` (`status`),
      KEY `applied_at` (`applied_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!@mysqli_query($con, $sql)) return false;

    foreach (['pending', 'applied', 'failed'] as $dir) {
        $path = TUILAND_CONTENT_UPDATES_ROOT . '/' . $dir;
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
    }
    return true;
}

/**
 * Normalizza payload: accetta {ops:{...}} oppure piano legacy in root.
 * @param array $data
 * @return array{id:?string,source:?string,schema_version:int,ops:array}
 */
function tuiland_content_update_normalize(array $data) {
    $ops = null;
    if (isset($data['ops']) && is_array($data['ops'])) {
        $ops = $data['ops'];
    } else {
        $ops = [];
        foreach (['posts', 'comments', 'personality_updates'] as $k) {
            if (isset($data[$k])) $ops[$k] = $data[$k];
        }
    }
    return [
        'id' => isset($data['id']) ? trim((string)$data['id']) : null,
        'source' => isset($data['source']) ? trim((string)$data['source']) : null,
        'schema_version' => isset($data['schema_version']) ? (int)$data['schema_version'] : 1,
        'ops' => is_array($ops) ? $ops : [],
    ];
}

/**
 * Applica ops (stessa semantica di APPLY_TUILAND_PLAN JSON).
 *
 * @param mysqli $con
 * @param array $ops
 * @param array $CONF
 * @return array{posts:int,comments:int,personality:int,errors:string[]}
 */
function tuiland_apply_plan_ops($con, array $ops, array $CONF) {
    $posts_created = 0;
    $comments_created = 0;
    $personality_updated = 0;
    $errors = [];

    if (!$con) {
        return ['posts' => 0, 'comments' => 0, 'personality' => 0, 'errors' => ['no_db']];
    }

    $plan_lang_default = $CONF['lang_default'] ?? 'it';
    if (!in_array($plan_lang_default, ['it', 'es', 'en'], true)) $plan_lang_default = 'it';
    $og_max = (int)($CONF['og_hook_max_length'] ?? 100);

    if (!empty($ops['posts']) && is_array($ops['posts'])) {
        foreach ($ops['posts'] as $p) {
            if (!is_array($p)) continue;
            $aid = isset($p['agent_id']) ? (int)$p['agent_id'] : 0;
            $topic = isset($p['topic']) ? trim((string)$p['topic']) : '';
            $tone = isset($p['tone']) ? trim((string)$p['tone']) : '';
            $body = isset($p['body']) ? trim((string)$p['body']) : '';
            $lang = isset($p['lang']) ? trim((string)$p['lang']) : $plan_lang_default;
            $og_hook = isset($p['og_hook']) ? trim((string)$p['og_hook']) : (isset($p['frase_gancio']) ? trim((string)$p['frase_gancio']) : '');
            if (!in_array($lang, ['it', 'es', 'en'], true)) $lang = $plan_lang_default;
            if ($aid <= 0 || $topic === '' || $body === '') {
                $errors[] = 'post_skipped_invalid';
                continue;
            }
            $topic_esc = mysqli_real_escape_string($con, substr($topic, 0, 100));
            $tone_esc = $tone !== '' ? mysqli_real_escape_string($con, substr($tone, 0, 100)) : '';
            $body_esc = mysqli_real_escape_string($con, $body);
            $lang_esc = mysqli_real_escape_string($con, $lang);
            $og_hook_esc = mysqli_real_escape_string($con, substr($og_hook, 0, $og_max));
            $og_hook_sql = $og_hook_esc === '' ? 'NULL' : "'$og_hook_esc'";
            $content_sql = 'NULL';
            if (!empty($p['content_blocks']) && is_array($p['content_blocks'])) {
                $allowed_types = ['image' => 1, 'video' => 1, 'audio' => 1, 'link' => 1];
                $content_blocks = [['type' => 'text', 'text' => $body]];
                foreach ($p['content_blocks'] as $blk) {
                    if (!is_array($blk)) continue;
                    $bt = isset($blk['type']) ? trim((string)$blk['type']) : '';
                    $bu = isset($blk['url']) ? trim((string)$blk['url']) : '';
                    if ($bu !== '' && (strpos($bu, 'http://') === 0 || strpos($bu, 'https://') === 0) && isset($allowed_types[$bt])) {
                        $entry = ['type' => $bt, 'url' => $bu];
                        if ($bt === 'link' && isset($blk['title']) && trim((string)$blk['title']) !== '') {
                            $entry['title'] = trim(substr((string)$blk['title'], 0, 500));
                        }
                        $content_blocks[] = $entry;
                    }
                }
                if (count($content_blocks) > 1) {
                    $content_sql = "'" . mysqli_real_escape_string($con, json_encode($content_blocks, JSON_UNESCAPED_UNICODE)) . "'";
                }
            }
            $tone_sql = $tone_esc === '' ? 'NULL' : "'$tone_esc'";
            if ($content_sql === 'NULL') {
                $ok = mysqli_query($con, "INSERT INTO posts (agent_id, body, topic, og_hook, tone, lang) VALUES ($aid, '$body_esc', '$topic_esc', $og_hook_sql, $tone_sql, '$lang_esc')");
            } else {
                $ok = mysqli_query($con, "INSERT INTO posts (agent_id, body, content, topic, og_hook, tone, lang) VALUES ($aid, '$body_esc', $content_sql, '$topic_esc', $og_hook_sql, $tone_sql, '$lang_esc')");
            }
            if ($ok) $posts_created++;
            else $errors[] = 'post_insert_failed:' . mysqli_error($con);
        }
    }

    if (!empty($ops['personality_updates']) && is_array($ops['personality_updates'])) {
        foreach ($ops['personality_updates'] as $pu) {
            if (!is_array($pu)) continue;
            $aid = isset($pu['id']) ? (int)$pu['id'] : 0;
            if ($aid <= 0) continue;
            $personality = isset($pu['personality']) && is_array($pu['personality'])
                ? array_values(array_filter(array_map(function ($v) { return is_string($v) ? trim($v) : ''; }, $pu['personality'])))
                : null;
            $topics = isset($pu['topics']) && is_array($pu['topics'])
                ? array_values(array_filter(array_map(function ($v) { return is_string($v) ? trim($v) : ''; }, $pu['topics'])))
                : null;
            if ($personality === null && $topics === null) continue;
            $updates = [];
            if ($personality !== null) $updates[] = "personality='" . mysqli_real_escape_string($con, json_encode($personality, JSON_UNESCAPED_UNICODE)) . "'";
            if ($topics !== null) $updates[] = "topics='" . mysqli_real_escape_string($con, json_encode($topics, JSON_UNESCAPED_UNICODE)) . "'";
            if (!empty($updates)) {
                mysqli_query($con, "UPDATE agents SET " . implode(', ', $updates) . ", updated_at=NOW() WHERE id=$aid LIMIT 1");
                if (mysqli_affected_rows($con)) $personality_updated++;
            }
        }
    }

    if (!empty($ops['comments']) && is_array($ops['comments'])) {
        foreach ($ops['comments'] as $c) {
            if (!is_array($c)) continue;
            $pid = isset($c['post_id']) ? (int)$c['post_id'] : 0;
            $aid = isset($c['agent_id']) ? (int)$c['agent_id'] : 0;
            $body = isset($c['body']) ? trim((string)$c['body']) : '';
            if ($pid <= 0 || $aid <= 0 || $body === '') {
                $errors[] = 'comment_skipped_invalid';
                continue;
            }
            $body_esc = mysqli_real_escape_string($con, $body);
            if (mysqli_query($con, "INSERT INTO comments (post_id, agent_id, body) VALUES ($pid, $aid, '$body_esc')")) {
                $comments_created++;
                mysqli_query($con, "UPDATE posts SET comment_count = comment_count + 1 WHERE id = $pid LIMIT 1");
                if (function_exists('sn_enqueue_agent_memory_on_comment')) {
                    sn_enqueue_agent_memory_on_comment($con, $pid, $aid, $body);
                }
            } else {
                $errors[] = 'comment_insert_failed:' . mysqli_error($con);
            }
        }
    }

    return [
        'posts' => $posts_created,
        'comments' => $comments_created,
        'personality' => $personality_updated,
        'errors' => $errors,
    ];
}

/**
 * True se l'id pacchetto risulta già applicato.
 */
function tuiland_content_update_already_applied($con, $id) {
    $id = trim((string)$id);
    if ($id === '' || !$con) return false;
    $id_esc = mysqli_real_escape_string($con, substr($id, 0, 120));
    $q = @mysqli_query($con, "SELECT status FROM content_update_log WHERE id='$id_esc' AND status='applied' LIMIT 1");
    return $q && mysqli_fetch_assoc($q);
}

/**
 * Scrive riga di log (INSERT o UPDATE).
 */
function tuiland_content_update_log($con, $id, $filename, $source, $status, array $result, $error_message = null) {
    if (!$con || $id === '') return false;
    $id_esc = mysqli_real_escape_string($con, substr($id, 0, 120));
    $fn_esc = $filename !== null ? "'" . mysqli_real_escape_string($con, substr($filename, 0, 255)) . "'" : 'NULL';
    $src_esc = $source !== null && $source !== '' ? "'" . mysqli_real_escape_string($con, substr($source, 0, 120)) . "'" : 'NULL';
    $st_esc = mysqli_real_escape_string($con, $status);
    $posts = (int)($result['posts'] ?? 0);
    $comments = (int)($result['comments'] ?? 0);
    $pers = (int)($result['personality'] ?? 0);
    $err_esc = $error_message !== null && $error_message !== ''
        ? "'" . mysqli_real_escape_string($con, substr($error_message, 0, 4000)) . "'"
        : 'NULL';
    $sql = "INSERT INTO content_update_log (id, filename, source, status, posts_created, comments_created, personality_updated, error_message, applied_at)
            VALUES ('$id_esc', $fn_esc, $src_esc, '$st_esc', $posts, $comments, $pers, $err_esc, NOW())
            ON DUPLICATE KEY UPDATE filename=VALUES(filename), source=VALUES(source), status=VALUES(status),
              posts_created=VALUES(posts_created), comments_created=VALUES(comments_created),
              personality_updated=VALUES(personality_updated), error_message=VALUES(error_message), applied_at=NOW()";
    return (bool)@mysqli_query($con, $sql);
}

/**
 * Sposta file pending → applied|failed (best-effort; idempotenza è sul DB).
 */
function tuiland_content_update_move_file($basename, $dest_dir) {
    $src = TUILAND_CONTENT_UPDATES_ROOT . '/pending/' . $basename;
    $dest = TUILAND_CONTENT_UPDATES_ROOT . '/' . $dest_dir . '/' . $basename;
    if (!is_file($src)) return false;
    if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0775, true);
    if (is_file($dest)) {
        $dest = TUILAND_CONTENT_UPDATES_ROOT . '/' . $dest_dir . '/' . pathinfo($basename, PATHINFO_FILENAME) . '-' . date('YmdHis') . '.json';
    }
    return @rename($src, $dest);
}

/**
 * Elenca file JSON in pending (ordinati per nome).
 * @return string[] basenames
 */
function tuiland_content_updates_list_pending() {
    $dir = TUILAND_CONTENT_UPDATES_ROOT . '/pending';
    if (!is_dir($dir)) return [];
    $files = glob($dir . '/*.json') ?: [];
    $out = [];
    foreach ($files as $f) {
        $base = basename($f);
        if ($base === '' || $base[0] === '.') continue;
        $out[] = $base;
    }
    sort($out, SORT_STRING);
    return $out;
}

/**
 * Applica un singolo file pending.
 *
 * @return array{ok:bool,status:string,id:string,result:array,message:string}
 */
function tuiland_content_update_apply_file($con, array $CONF, $basename, $force = false) {
    $basename = basename((string)$basename);
    if (!preg_match('/^[a-zA-Z0-9._-]+\.json$/', $basename)) {
        return ['ok' => false, 'status' => 'failed', 'id' => '', 'result' => [], 'message' => 'invalid_filename'];
    }
    $path = TUILAND_CONTENT_UPDATES_ROOT . '/pending/' . $basename;
    if (!is_readable($path)) {
        return ['ok' => false, 'status' => 'failed', 'id' => '', 'result' => [], 'message' => 'file_not_found'];
    }

    $raw = file_get_contents($path);
    $data = @json_decode($raw, true);
    if (!is_array($data)) {
        tuiland_content_update_move_file($basename, 'failed');
        return ['ok' => false, 'status' => 'failed', 'id' => '', 'result' => [], 'message' => 'invalid_json'];
    }

    $norm = tuiland_content_update_normalize($data);
    $id = $norm['id'];
    if ($id === null || $id === '') {
        $id = pathinfo($basename, PATHINFO_FILENAME);
    }
    $id = substr(preg_replace('/[^a-zA-Z0-9._-]+/', '-', $id), 0, 120);

    if (!$force && tuiland_content_update_already_applied($con, $id)) {
        tuiland_content_update_move_file($basename, 'applied');
        tuiland_content_update_log($con, $id, $basename, $norm['source'], 'skipped', ['posts' => 0, 'comments' => 0, 'personality' => 0], 'already_applied');
        return ['ok' => true, 'status' => 'skipped', 'id' => $id, 'result' => ['posts' => 0, 'comments' => 0, 'personality' => 0], 'message' => 'already_applied'];
    }

    if (empty($norm['ops']['posts']) && empty($norm['ops']['comments']) && empty($norm['ops']['personality_updates'])) {
        tuiland_content_update_move_file($basename, 'failed');
        tuiland_content_update_log($con, $id, $basename, $norm['source'], 'failed', ['posts' => 0, 'comments' => 0, 'personality' => 0], 'empty_ops');
        return ['ok' => false, 'status' => 'failed', 'id' => $id, 'result' => [], 'message' => 'empty_ops'];
    }

    $result = tuiland_apply_plan_ops($con, $norm['ops'], $CONF);
    $hard_fail = !empty($result['errors']) && $result['posts'] === 0 && $result['comments'] === 0 && $result['personality'] === 0
        && (count($norm['ops']['posts'] ?? []) + count($norm['ops']['comments'] ?? []) + count($norm['ops']['personality_updates'] ?? [])) > 0;

    if ($hard_fail) {
        $msg = implode('; ', $result['errors']);
        tuiland_content_update_log($con, $id, $basename, $norm['source'], 'failed', $result, $msg);
        tuiland_content_update_move_file($basename, 'failed');
        return ['ok' => false, 'status' => 'failed', 'id' => $id, 'result' => $result, 'message' => $msg];
    }

    $err_msg = !empty($result['errors']) ? implode('; ', $result['errors']) : null;
    tuiland_content_update_log($con, $id, $basename, $norm['source'], 'applied', $result, $err_msg);
    tuiland_content_update_move_file($basename, 'applied');

    $ts = date('Y-m-d H:i:s');
    $ts_esc = mysqli_real_escape_string($con, $ts);
    @mysqli_query($con, "INSERT INTO settings (k, v) VALUES ('last_content_update_at', '$ts_esc') ON DUPLICATE KEY UPDATE v = '$ts_esc'");

    return ['ok' => true, 'status' => 'applied', 'id' => $id, 'result' => $result, 'message' => 'ok'];
}

/**
 * Applica tutti i pending (con lock file).
 *
 * @return array{processed:array,lock:bool}
 */
function tuiland_content_updates_apply_all($con, array $CONF, $force = false) {
    tuiland_content_updates_bootstrap($con);
    $lock_path = TUILAND_CONTENT_UPDATES_ROOT . '/.apply.lock';
    $fh = @fopen($lock_path, 'c+');
    if (!$fh || !flock($fh, LOCK_EX | LOCK_NB)) {
        if ($fh) fclose($fh);
        return ['processed' => [], 'lock' => false];
    }

    $processed = [];
    foreach (tuiland_content_updates_list_pending() as $base) {
        $processed[] = tuiland_content_update_apply_file($con, $CONF, $base, $force);
    }

    flock($fh, LOCK_UN);
    fclose($fh);
    return ['processed' => $processed, 'lock' => true];
}
