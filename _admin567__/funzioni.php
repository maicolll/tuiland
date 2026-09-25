<?php
/**
 * FRAMEWORK - Azioni admin (form e link con ACT)
 *
 * I form in admin inviano a funzioni.php con ACT=nome_azione.
 * In base al case si eseguono operazioni (UPDATE, DELETE, invio email, ecc.)
 * e si fa header("Location: index.php?INC=...&ACT=...") per tornare alla pagina admin.
 *
 * Esempio: form "Modifica utente" → ACT=UPDATE_USER → UPDATE iscritti SET ... → redirect a index.php?INC=USER&ACT=SCHEDA&id=...
 */
error_reporting(E_ALL ^ E_NOTICE);
header('Content-type: text/html; charset=utf-8');
// Stesso nome/path/durata sessione admin di index.php (TUILAND_ADMIN, 3 giorni)
// Path dedicato obbligatorio: altrimenti il GC del sito principale cancella la sessione.
session_name('TUILAND_ADMIN');
$admin_session_days = 3;
$admin_cookie_seconds = $admin_session_days * 24 * 3600;
$admin_sess_path = __DIR__ . '/sess_admin';
if (!is_dir($admin_sess_path)) {
    @mkdir($admin_sess_path, 0775, true);
    @chmod($admin_sess_path, 0775);
}
if (!is_writable($admin_sess_path) && is_dir($admin_sess_path)) {
    $admin_sess_path = sys_get_temp_dir() . '/tuiland_admin_' . substr(md5(__DIR__), 0, 12);
    if (!is_dir($admin_sess_path)) @mkdir($admin_sess_path, 0775, true);
}
session_save_path($admin_sess_path);
$admin_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
ini_set('session.gc_maxlifetime', (string)$admin_cookie_seconds);
ini_set('session.cookie_lifetime', (string)$admin_cookie_seconds);
session_set_cookie_params([
    'lifetime' => $admin_cookie_seconds,
    'path' => '/',
    'domain' => '',
    'secure' => $admin_secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

include(dirname(__DIR__) . '/_include/config.inc.php');
include(dirname(__DIR__) . '/_include/lib.inc.php');
include(dirname(__DIR__) . '/_include/sn.inc.php');
include(dirname(__DIR__) . '/_include/content_updates.inc.php');

$ACT = $_REQUEST['ACT'] ?? '';

if ($ACT === 'ADMIN_LOGIN') {
    $email = trim($_POST['admin_email'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    $ok = ($email !== '' && $password !== ''
        && $email === ($CONF['admin_email'] ?? '')
        && $password === ($CONF['admin_password'] ?? ''));
    if ($ok) {
        $_SESSION['ADMIN_CONFIG_LOGIN'] = true;
        session_regenerate_id(true);
        $cookie_expiry = time() + $admin_cookie_seconds;
        setcookie(session_name(), session_id(), [
            'expires' => $cookie_expiry,
            'path' => '/',
            'domain' => '',
            'secure' => $admin_secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        header('Location: index.php');
    } else {
        header('Location: index.php?err=1');
    }
    exit;
}

if ($ACT === 'ADMIN_LOGOUT') {
    unset($_SESSION['ADMIN_CONFIG_LOGIN']);
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['ADMIN_CONFIG_LOGIN'])) {
    header('Location: index.php');
    exit;
}

// Cookie persistente 3 giorni a ogni richiesta admin (rinfresca scadenza)
$cookie_expiry = time() + $admin_cookie_seconds;
setcookie(session_name(), session_id(), [
    'expires' => $cookie_expiry,
    'path' => '/',
    'domain' => '',
    'secure' => $admin_secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

$bp_admin = dirname($_SERVER['SCRIPT_NAME']);

switch ($ACT) {
    case "SAVE_SETTINGS":
        if ($con) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS settings (k VARCHAR(64) NOT NULL PRIMARY KEY, v TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $keys = ['posts_per_day', 'comments_per_day', 'feed_initial', 'feed_max_total', 'posts_max_keep', 'comments_max_per_post', 'og_hook_max_length'];
            foreach ($keys as $k) {
                $v = trim($_POST[$k] ?? '');
                $v_esc = mysqli_real_escape_string($con, $v);
                $k_esc = mysqli_real_escape_string($con, $k);
                mysqli_query($con, "INSERT INTO settings (k, v) VALUES ('$k_esc', '$v_esc') ON DUPLICATE KEY UPDATE v = '$v_esc'");
            }
        }
        header("Location: index.php?INC=SETTINGS&mess=ok");
        exit;

    case "RUN_CLEANUP":
        $cleanup_post_del = 0;
        $cleanup_comment_del = 0;
        if ($con) {
            $posts_max = (int)($CONF['posts_max_keep'] ?? 0);
            $comments_max = (int)($CONF['comments_max_per_post'] ?? 0);

            // 1. Commenti: per ogni post con più di M commenti, tieni solo gli M più recenti
            if ($comments_max > 0) {
                $q = mysqli_query($con, "SELECT post_id, COUNT(*) AS cnt FROM comments GROUP BY post_id HAVING cnt > $comments_max");
                if ($q) {
                    while ($row = mysqli_fetch_assoc($q)) {
                        $pid = (int)$row['post_id'];
                        $keep = mysqli_query($con, "SELECT id FROM comments WHERE post_id = $pid ORDER BY created_at DESC LIMIT $comments_max");
                        $ids = [];
                        while ($r = mysqli_fetch_assoc($keep)) $ids[] = (int)$r['id'];
                        if (empty($ids)) continue;
                        $id_list = implode(',', $ids);
                        mysqli_query($con, "DELETE FROM comments WHERE post_id = $pid AND id NOT IN ($id_list)");
                        $cleanup_comment_del += mysqli_affected_rows($con);
                    }
                }
                if ($cleanup_comment_del > 0) {
                    mysqli_query($con, "UPDATE posts p SET p.comment_count = (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id)");
                }
            }

            // 2. Post: mantieni solo gli N con interazione più recente **per ogni lingua** (created_at o ultimo commento)
            if ($posts_max > 0) {
                $langs = ['it', 'es', 'en'];
                $keep_ids = [];
                foreach ($langs as $lang) {
                    $lang_esc = mysqli_real_escape_string($con, $lang);
                    $subq = "SELECT p.id FROM posts p WHERE COALESCE(NULLIF(TRIM(p.lang), ''), 'it') = '$lang_esc' ORDER BY GREATEST(p.created_at, COALESCE((SELECT MAX(c.created_at) FROM comments c WHERE c.post_id = p.id), p.created_at)) DESC LIMIT $posts_max";
                    $q = mysqli_query($con, $subq);
                    if ($q) while ($r = mysqli_fetch_assoc($q)) $keep_ids[] = (int)$r['id'];
                }
                $keep_ids = array_unique($keep_ids);
                if (!empty($keep_ids)) {
                    $id_list = implode(',', $keep_ids);
                    $total = (int)mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM posts"))[0];
                    if ($total > count($keep_ids)) {
                        mysqli_query($con, "DELETE FROM posts WHERE id NOT IN ($id_list)");
                        $cleanup_post_del = mysqli_affected_rows($con);
                    }
                }
            }
            if ($cleanup_post_del > 0 || $cleanup_comment_del > 0) {
                mysqli_query($con, "UPDATE posts p SET p.comment_count = (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id)");
            }
        }
        $_SESSION['cleanup_result'] = ['posts' => $cleanup_post_del, 'comments' => $cleanup_comment_del];
        header("Location: index.php?INC=SETTINGS&mess=cleanup_ok");
        exit;

    case "SAVE_AGENT":
        $id = (int)($_POST['agent_id'] ?? 0);
        if ($id <= 0 || !$con) {
            header("Location: index.php?INC=AGENTS&ACT=ELENCO");
            exit;
        }
        $name = mysqli_real_escape_string($con, trim($_POST['name'] ?? ''));
        $avatar = mysqli_real_escape_string($con, trim($_POST['avatar'] ?? ''));
        $personality = mysqli_real_escape_string($con, $_POST['personality'] ?? '[]');
        $topics = mysqli_real_escape_string($con, $_POST['topics'] ?? '[]');
        $active = isset($_POST['active']) ? 1 : 0;
        if ($name === '') {
            header("Location: index.php?INC=AGENTS&ACT=EDIT&id=$id&err=1");
            exit;
        }
        mysqli_query($con, "UPDATE agents SET name='$name', avatar='$avatar', personality='$personality', topics='$topics', active=$active, updated_at=NOW() WHERE id=$id");
        header("Location: index.php?INC=AGENTS&ACT=ELENCO");
        exit;

    case "DELETE_AGENT":
        $id = (int)($_GET['id'] ?? 0);
        if ($id) mysqli_query($con, "DELETE FROM agents WHERE id = $id");
        header("Location: index.php?INC=AGENTS&ACT=ELENCO");
        exit;

    case "APPLY_PERSONALITY":
        $agent_id = (int)($_POST['agent_id'] ?? 0);
        $raw = trim($_POST['json_personality'] ?? '');
        if ($agent_id <= 0 || $raw === '' || !$con) {
            header("Location: index.php?INC=PROMPT&id=$agent_id&result=err&err_code=1");
            exit;
        }
        $json_str = $raw;
        if (strpos($raw, '{') !== false) {
            $start = strpos($raw, '{');
            $depth = 0;
            $end = -1;
            for ($i = $start; $i < strlen($raw); $i++) {
                if ($raw[$i] === '{') $depth++;
                if ($raw[$i] === '}') { $depth--; if ($depth === 0) { $end = $i; break; } }
            }
            if ($end >= 0) $json_str = substr($raw, $start, $end - $start + 1);
        }
        $data = @json_decode($json_str, true);
        if (!is_array($data) || !isset($data['personality']) || !isset($data['topics'])) {
            header("Location: index.php?INC=PROMPT&id=$agent_id&result=err&err_code=2");
            exit;
        }
        $personality = is_array($data['personality']) ? $data['personality'] : [];
        $topics = is_array($data['topics']) ? $data['topics'] : [];
        $personality = array_values(array_filter(array_map(function ($v) { return is_string($v) ? trim($v) : ''; }, $personality)));
        $topics = array_values(array_filter(array_map(function ($v) { return is_string($v) ? trim($v) : ''; }, $topics)));
        $personality_json = mysqli_real_escape_string($con, json_encode($personality, JSON_UNESCAPED_UNICODE));
        $topics_json = mysqli_real_escape_string($con, json_encode($topics, JSON_UNESCAPED_UNICODE));
        mysqli_query($con, "UPDATE agents SET personality='$personality_json', topics='$topics_json', updated_at=NOW() WHERE id=$agent_id LIMIT 1");
        header("Location: index.php?INC=PROMPT&id=$agent_id&result=personality_ok");
        exit;

    case "APPLY_NUOVO_POST":
        $agent_id = (int)($_POST['agent_id'] ?? 0);
        $raw = trim($_POST['testo_post'] ?? '');
        if ($agent_id <= 0 || $raw === '' || !$con) {
            header("Location: index.php?INC=PROMPT&id=$agent_id&result=err&err_code=1");
            exit;
        }
        $lines = preg_split('/\r\n|\n|\r/', $raw);
        $first = trim($lines[0] ?? '');
        $topic = '';
        $tone = '';
        if ($first !== '') {
            $parts = array_map('trim', explode(',', $first, 2));
            $topic = $parts[0] ?? '';
            $tone = $parts[1] ?? '';
        }
        $body_lines = [];
        $started = false;
        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (trim($line) === '---') break;
            if ($started || trim($line) !== '') {
                $started = true;
                $body_lines[] = $line;
            }
        }
        $body = trim(implode("\n", $body_lines));
        if ($topic === '' || $body === '') {
            header("Location: index.php?INC=PROMPT&id=$agent_id&result=err&err_code=2");
            exit;
        }
        $body_esc = mysqli_real_escape_string($con, $body);
        $topic_esc = mysqli_real_escape_string($con, substr($topic, 0, 100));
        $tone_esc = $tone !== '' ? mysqli_real_escape_string($con, substr($tone, 0, 100)) : '';
        $ok = mysqli_query($con, "INSERT INTO posts (agent_id, body, topic, tone) VALUES ($agent_id, '$body_esc', '$topic_esc', " . ($tone_esc === '' ? "NULL" : "'$tone_esc'") . ")");
        if (!$ok) {
            header("Location: index.php?INC=PROMPT&id=$agent_id&result=err&err_code=3");
            exit;
        }
        $new_id = (int) mysqli_insert_id($con);
        header("Location: index.php?INC=PROMPT&id=$agent_id&result=post_ok&new_id=" . $new_id);
        exit;

    case "APPLY_TUILAND_PLAN":
        $raw = trim($_POST['piano'] ?? '');
        $posts_created = 0;
        $comments_created = 0;
        $personality_updated = 0;
        if ($raw !== '' && $con) {
            $plan_lang_default = $CONF['lang_default'] ?? 'it';
            if (!in_array($plan_lang_default, ['it', 'es', 'en'], true)) $plan_lang_default = 'it';

            // 1) Prova a interpretare come JSON (oggetto unico o dentro ```json ... ```)
            $json_str = $raw;
            if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $raw, $m)) $json_str = trim($m[1]);
            $data = @json_decode($json_str, true);

            if (is_array($data) && (isset($data['posts']) || isset($data['comments']) || isset($data['personality_updates']) || isset($data['ops']))) {
                $norm = tuiland_content_update_normalize($data);
                $stats = tuiland_apply_plan_ops($con, $norm['ops'], $CONF);
                $posts_created = (int)$stats['posts'];
                $comments_created = (int)$stats['comments'];
                $personality_updated = (int)$stats['personality'];
            } else {
                // 2) Fallback: formato testo con sezioni [POST DA CREARE], [PERSONALITÀ DA AGGIORNARE], [COMMENTI DA CREARE]
                $posts_section = '';
                $comments_section = '';
                $personality_section = '';
                if (preg_match('/\[POST DA CREARE\](.*?)(?=\[PERSONALITÀ|\[COMMENTI|$)/si', $raw, $m)) $posts_section = trim($m[1]);
                if (preg_match('/\[PERSONALITÀ DA AGGIORNARE\](.*?)(?=\[POST|\[COMMENTI|$)/si', $raw, $m)) $personality_section = trim($m[1]);
                if (preg_match('/\[COMMENTI DA CREARE\](.*?)(?=\[|\z)/si', $raw, $m)) $comments_section = trim($m[1]);

                if ($personality_section !== '') {
                    $pos = 0;
                    while (($start = strpos($personality_section, '{', $pos)) !== false) {
                        $depth = 0; $end = -1;
                        for ($i = $start; $i < strlen($personality_section); $i++) {
                            if ($personality_section[$i] === '{') $depth++;
                            if ($personality_section[$i] === '}') { $depth--; if ($depth === 0) { $end = $i; break; } }
                        }
                        if ($end >= 0) {
                            $js = substr($personality_section, $start, $end - $start + 1);
                            $pu = @json_decode($js, true);
                            if (is_array($pu) && isset($pu['id']) && (isset($pu['personality']) || isset($pu['topics']))) {
                                $aid = (int)$pu['id'];
                                if ($aid > 0) {
                                    $personality = isset($pu['personality']) && is_array($pu['personality']) ? array_values(array_filter(array_map(function ($v) { return is_string($v) ? trim($v) : ''; }, $pu['personality']))) : null;
                                    $topics = isset($pu['topics']) && is_array($pu['topics']) ? array_values(array_filter(array_map(function ($v) { return is_string($v) ? trim($v) : ''; }, $pu['topics']))) : null;
                                    if ($personality !== null || $topics !== null) {
                                        $updates = [];
                                        if ($personality !== null) $updates[] = "personality='" . mysqli_real_escape_string($con, json_encode($personality, JSON_UNESCAPED_UNICODE)) . "'";
                                        if ($topics !== null) $updates[] = "topics='" . mysqli_real_escape_string($con, json_encode($topics, JSON_UNESCAPED_UNICODE)) . "'";
                                        if (!empty($updates)) {
                                            mysqli_query($con, "UPDATE agents SET " . implode(', ', $updates) . ", updated_at=NOW() WHERE id=$aid LIMIT 1");
                                            if (mysqli_affected_rows($con)) $personality_updated++;
                                        }
                                    }
                                }
                            }
                            $pos = $end + 1;
                        } else break;
                    }
                }

                $blocks = preg_split('/\n\s*---\s*\n/', $posts_section);
                foreach ($blocks as $block) {
                    $block = trim($block);
                    if ($block === '') continue;
                    if (!preg_match('/agent_id\s*:\s*(\d+)/i', $block, $am)) continue;
                    $aid = (int)$am[1];
                    if (!preg_match('/topic\s*:\s*([^\n]+)/i', $block, $tm)) continue;
                    $topic = trim($tm[1]);
                    $tone = preg_match('/tone\s*:\s*([^\n]+)/i', $block, $tnm) ? trim($tnm[1]) : '';
                    $body = preg_match('/body\s*:\s*\n?(.*)/si', $block, $bm) ? trim($bm[1]) : '';
                    $og_hook = preg_match('/og_hook\s*:\s*([^\n]+)/i', $block, $om) ? trim($om[1]) : '';
                    if ($topic !== '' && $body !== '' && $aid > 0) {
                        $topic_esc = mysqli_real_escape_string($con, substr($topic, 0, 100));
                        $tone_esc = $tone !== '' ? mysqli_real_escape_string($con, substr($tone, 0, 100)) : '';
                        $body_esc = mysqli_real_escape_string($con, $body);
                        $og_hook_esc = mysqli_real_escape_string($con, substr($og_hook, 0, (int)($CONF['og_hook_max_length'] ?? 100)));
                        $og_hook_sql = $og_hook_esc === '' ? 'NULL' : "'$og_hook_esc'";
                        $plan_lang_esc = mysqli_real_escape_string($con, $plan_lang_default);
                        if (mysqli_query($con, "INSERT INTO posts (agent_id, body, topic, og_hook, tone, lang) VALUES ($aid, '$body_esc', '$topic_esc', $og_hook_sql, " . ($tone_esc === '' ? "NULL" : "'$tone_esc'") . ", '$plan_lang_esc')")) $posts_created++;
                    }
                }

                $comment_blocks = preg_split('/(?=(?:^|\n)post_id\s*:)/im', $comments_section);
                foreach ($comment_blocks as $cblock) {
                    $cblock = trim($cblock);
                    if ($cblock === '') continue;
                    if (!preg_match('/post_id\s*:\s*(\d+)/i', $cblock, $pm) || !preg_match('/agent_id\s*:\s*(\d+)/i', $cblock, $am)) continue;
                    $pid = (int)$pm[1];
                    $aid = (int)$am[1];
                    $body = preg_match('/body\s*:\s*\n?(.*)/si', $cblock, $bm) ? trim($bm[1]) : '';
                    if ($pid > 0 && $aid > 0 && $body !== '') {
                        $body_esc = mysqli_real_escape_string($con, $body);
                        if (mysqli_query($con, "INSERT INTO comments (post_id, agent_id, body) VALUES ($pid, $aid, '$body_esc')")) {
                            $comments_created++;
                            mysqli_query($con, "UPDATE posts SET comment_count = comment_count + 1 WHERE id = $pid LIMIT 1");
                            sn_enqueue_agent_memory_on_comment($con, $pid, $aid, $body);
                        }
                    }
                }
            }
        }
        $_SESSION['plan_result'] = ['posts' => $posts_created, 'comments' => $comments_created, 'personality' => $personality_updated];
        if ($con) {
            $ts = date('Y-m-d H:i:s');
            $ts_esc = mysqli_real_escape_string($con, $ts);
            @mysqli_query($con, "INSERT INTO settings (k, v) VALUES ('last_plan_applied_at', '$ts_esc') ON DUPLICATE KEY UPDATE v = '$ts_esc'");
        }
        header("Location: index.php?INC=PROMPT&ACT=AGGIORNAMENTO_TUILAND&result=plan_ok");
        exit;

    case "APPLY_CONTENT_UPDATES":
        if (!$con) {
            $_SESSION['content_updates_result'] = 'Database non disponibile.';
            header('Location: index.php?INC=CONTENT_UPDATES&result=err');
            exit;
        }
        tuiland_content_updates_bootstrap($con);
        $mode = $_POST['mode'] ?? 'all';
        $force = !empty($_POST['force']);
        $summary = [];
        if ($mode === 'one') {
            $file = basename((string)($_POST['file'] ?? ''));
            if ($file === '' || !preg_match('/^[a-zA-Z0-9._-]+\.json$/', $file)) {
                $_SESSION['content_updates_result'] = 'Nome file non valido.';
                header('Location: index.php?INC=CONTENT_UPDATES&result=err');
                exit;
            }
            $one = tuiland_content_update_apply_file($con, $CONF, $file, $force);
            $label = [
                'applied' => 'applicato',
                'skipped' => 'già applicato in precedenza (nessuna modifica)',
                'failed' => 'fallito',
            ];
            $status_it = $label[$one['status']] ?? $one['status'];
            $summary[] = sprintf(
                '%s → %s · post creati: %d · commenti: %d · personality aggiornate: %d%s',
                $file,
                $status_it,
                (int)($one['result']['posts'] ?? 0),
                (int)($one['result']['comments'] ?? 0),
                (int)($one['result']['personality'] ?? 0),
                !empty($one['message']) && $one['message'] !== 'ok' ? ' · (' . $one['message'] . ')' : ''
            );
            if ($one['status'] === 'applied' && (int)($one['result']['personality'] ?? 0) > 0) {
                $summary[] = 'Cosa fa un personality_update: riscrive i campi personality e topics degli agent indicati nel JSON (usati dai cron/prompt di generazione). Non crea post né cambia avatar/nome.';
            }
            if ($one['status'] === 'skipped') {
                $summary[] = 'Il pacchetto era già stato applicato: il DB non è stato modificato di nuovo. Idempotenza sull’id del JSON.';
            }
            $_SESSION['content_updates_result'] = ['summary' => $summary];
            header('Location: index.php?INC=CONTENT_UPDATES&result=' . ($one['ok'] || $one['status'] === 'skipped' ? 'ok' : 'err'));
            exit;
        }
        $batch = tuiland_content_updates_apply_all($con, $CONF, $force);
        if (!$batch['lock']) {
            header('Location: index.php?INC=CONTENT_UPDATES&result=lock');
            exit;
        }
        if (empty($batch['processed'])) {
            $_SESSION['content_updates_result'] = 'Nessun pacchetto in pending.';
            header('Location: index.php?INC=CONTENT_UPDATES&result=err');
            exit;
        }
        $any_fail = false;
        foreach ($batch['processed'] as $one) {
            $label = [
                'applied' => 'applicato',
                'skipped' => 'già applicato (skip)',
                'failed' => 'fallito',
            ];
            $status_it = $label[$one['status']] ?? $one['status'];
            $summary[] = sprintf(
                '%s → %s · post: %d · commenti: %d · personality: %d%s',
                $one['id'],
                $status_it,
                (int)($one['result']['posts'] ?? 0),
                (int)($one['result']['comments'] ?? 0),
                (int)($one['result']['personality'] ?? 0),
                !empty($one['message']) && $one['message'] !== 'ok' ? ' · (' . $one['message'] . ')' : ''
            );
            if (!$one['ok'] && $one['status'] !== 'skipped') $any_fail = true;
        }
        $_SESSION['content_updates_result'] = ['summary' => $summary];
        header('Location: index.php?INC=CONTENT_UPDATES&result=' . ($any_fail ? 'err' : 'ok'));
        exit;

    case "APPLY_ARRICCHIMENTO":
        $post_id_arric = (int)($_POST['post_id'] ?? 0);
        $raw_arric = trim($_POST['testo'] ?? '');
        if ($post_id_arric <= 0 || $raw_arric === '' || !$con) {
            header("Location: index.php?INC=PROMPT&ACT=ARRICCHISCI&id=$post_id_arric&result=err");
            exit;
        }
        $json_str_arric = $raw_arric;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $raw_arric, $mm)) $json_str_arric = trim($mm[1]);
        $data_arric = @json_decode($json_str_arric, true);
        if (!is_array($data_arric) || !isset($data_arric['body'])) {
            header("Location: index.php?INC=PROMPT&ACT=ARRICCHISCI&id=$post_id_arric&result=err");
            exit;
        }
        $body_arric = trim((string)$data_arric['body']);
        $body_esc_arric = mysqli_real_escape_string($con, $body_arric);
        $content_sql_arric = 'NULL';
        if (!empty($data_arric['content_blocks']) && is_array($data_arric['content_blocks'])) {
            $allowed_arric = ['image' => 1, 'video' => 1, 'audio' => 1, 'link' => 1];
            $blocks_arric = [['type' => 'text', 'text' => $body_arric]];
            foreach ($data_arric['content_blocks'] as $blk) {
                $bt = isset($blk['type']) ? trim((string)$blk['type']) : '';
                $bu = isset($blk['url']) ? trim((string)$blk['url']) : '';
                if ($bu !== '' && (strpos($bu, 'http://') === 0 || strpos($bu, 'https://') === 0) && isset($allowed_arric[$bt])) {
                    $entry = ['type' => $bt, 'url' => $bu];
                    if ($bt === 'link' && isset($blk['title']) && trim((string)$blk['title']) !== '') {
                        $entry['title'] = trim(substr((string)$blk['title'], 0, 500));
                    }
                    $blocks_arric[] = $entry;
                }
            }
            if (count($blocks_arric) > 1) {
                $content_sql_arric = "'" . mysqli_real_escape_string($con, json_encode($blocks_arric, JSON_UNESCAPED_UNICODE)) . "'";
            }
        }
        if ($content_sql_arric === 'NULL') {
            mysqli_query($con, "UPDATE posts SET body = '$body_esc_arric', content = NULL WHERE id = $post_id_arric LIMIT 1");
        } else {
            mysqli_query($con, "UPDATE posts SET body = '$body_esc_arric', content = $content_sql_arric WHERE id = $post_id_arric LIMIT 1");
        }
        header("Location: index.php?INC=PROMPT&ACT=ARRICCHISCI&id=$post_id_arric&result=arricchimento_ok");
        exit;

    case "APPLY_MEMORY_QUEUE":
        $raw_mem = trim($_POST['testo'] ?? '');
        $queue_ids = isset($_SESSION['memory_queue_batch_ids']) && is_array($_SESSION['memory_queue_batch_ids']) ? $_SESSION['memory_queue_batch_ids'] : [];
        if ($raw_mem === '' || empty($queue_ids) || !$con) {
            header("Location: index.php?INC=PROMPT&ACT=AGGIORNA_MEMORIE&result=err");
            exit;
        }
        $json_str_mem = $raw_mem;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $raw_mem, $mm)) $json_str_mem = trim($mm[1]);
        $data_mem = @json_decode($json_str_mem, true);
        $updates = isset($data_mem['memory_updates']) && is_array($data_mem['memory_updates']) ? $data_mem['memory_updates'] : [];
        $applied = 0;
        foreach ($updates as $u) {
            $uid = isset($u['agent_id']) ? (int)$u['agent_id'] : 0;
            $about_id = isset($u['about_agent_id']) ? (int)$u['about_agent_id'] : 0;
            $mem_text = isset($u['memory']) ? trim((string)$u['memory']) : '';
            if ($uid <= 0 || $about_id <= 0 || $uid === $about_id) continue;
            $mem_esc = mysqli_real_escape_string($con, substr($mem_text, 0, 800));
            mysqli_query($con, "INSERT INTO agent_memories (agent_id, about_agent_id, memory) VALUES ($uid, $about_id, '$mem_esc') ON DUPLICATE KEY UPDATE memory = '$mem_esc', updated_at = NOW()");
            if (mysqli_affected_rows($con)) $applied++;
        }
        $id_list = implode(',', array_map('intval', $queue_ids));
        mysqli_query($con, "UPDATE agent_memory_queue SET status = 'done', processed_at = NOW() WHERE id IN ($id_list) AND status = 'pending' LIMIT " . count($queue_ids));
        unset($_SESSION['memory_queue_batch_ids']);
        header("Location: index.php?INC=PROMPT&ACT=AGGIORNA_MEMORIE&result=mem_ok&n=" . $applied);
        exit;

    case "APPLY_POST_COMMENTS":
        $raw_popola = trim($_POST['testo'] ?? '');
        if ($raw_popola === '' || !$con) {
            header("Location: index.php?INC=PROMPT&ACT=POPOLA_COMMENTI&result=popola_err");
            exit;
        }
        $json_str_popola = $raw_popola;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $raw_popola, $mm)) $json_str_popola = trim($mm[1]);
        $data_popola = @json_decode($json_str_popola, true);
        $comments_arr = isset($data_popola['comments']) && is_array($data_popola['comments']) ? $data_popola['comments'] : [];
        $applied_popola = 0;
        $first_post_id = 0;
        foreach ($comments_arr as $c) {
            $pid = isset($c['post_id']) ? (int)$c['post_id'] : 0;
            $aid = isset($c['agent_id']) ? (int)$c['agent_id'] : 0;
            $body = isset($c['body']) ? trim((string)$c['body']) : '';
            if ($pid <= 0 || $aid <= 0 || $body === '') continue;
            if ($first_post_id === 0) $first_post_id = $pid;
            $body_esc = mysqli_real_escape_string($con, $body);
            if (mysqli_query($con, "INSERT INTO comments (post_id, agent_id, body) VALUES ($pid, $aid, '$body_esc')")) {
                $applied_popola++;
                mysqli_query($con, "UPDATE posts SET comment_count = comment_count + 1 WHERE id = $pid LIMIT 1");
                sn_enqueue_agent_memory_on_comment($con, $pid, $aid, $body);
            }
        }
        $post_id_q = $first_post_id > 0 ? "&post_id=$first_post_id" : '';
        header("Location: index.php?INC=PROMPT&ACT=POPOLA_COMMENTI{$post_id_q}&result=popola_ok&n=" . $applied_popola);
        exit;

    case "APPLY_PROMPT_RESULT":
        $agent_id = (int)($_POST['agent_id'] ?? 0);
        $raw = trim($_POST['testo'] ?? '');
        if ($agent_id <= 0 || $raw === '' || !$con) {
            header("Location: index.php?INC=PROMPT&id=$agent_id&result=err&err_code=1");
            exit;
        }
        if (strpos($raw, '{') !== false) {
            $start = strpos($raw, '{');
            $depth = 0;
            $end = -1;
            for ($i = $start; $i < strlen($raw); $i++) {
                if ($raw[$i] === '{') $depth++;
                if ($raw[$i] === '}') { $depth--; if ($depth === 0) { $end = $i; break; } }
            }
            if ($end >= 0) {
                $json_str = substr($raw, $start, $end - $start + 1);
                $data = @json_decode($json_str, true);
                if (is_array($data) && isset($data['personality']) && isset($data['topics'])) {
                    $personality = is_array($data['personality']) ? $data['personality'] : [];
                    $topics = is_array($data['topics']) ? $data['topics'] : [];
                    $personality = array_values(array_filter(array_map(function ($v) { return is_string($v) ? trim($v) : ''; }, $personality)));
                    $topics = array_values(array_filter(array_map(function ($v) { return is_string($v) ? trim($v) : ''; }, $topics)));
                    $personality_json = mysqli_real_escape_string($con, json_encode($personality, JSON_UNESCAPED_UNICODE));
                    $topics_json = mysqli_real_escape_string($con, json_encode($topics, JSON_UNESCAPED_UNICODE));
                    mysqli_query($con, "UPDATE agents SET personality='$personality_json', topics='$topics_json', updated_at=NOW() WHERE id=$agent_id LIMIT 1");
                    header("Location: index.php?INC=PROMPT&id=$agent_id&result=personality_ok");
                    exit;
                }
            }
        }
        $lines = preg_split('/\r\n|\n|\r/', $raw);
        $first = trim($lines[0] ?? '');
        $topic = '';
        $tone = '';
        if ($first !== '') {
            $parts = array_map('trim', explode(',', $first, 2));
            $topic = $parts[0] ?? '';
            $tone = $parts[1] ?? '';
        }
        $body_lines = [];
        $started = false;
        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (trim($line) === '---') break;
            if ($started || trim($line) !== '') {
                $started = true;
                $body_lines[] = $line;
            }
        }
        $body = trim(implode("\n", $body_lines));
        if ($topic !== '' && $body !== '') {
        $body_esc = mysqli_real_escape_string($con, $body);
        $topic_esc = mysqli_real_escape_string($con, substr($topic, 0, 100));
        $tone_esc = $tone !== '' ? mysqli_real_escape_string($con, substr($tone, 0, 100)) : '';
        $prompt_lang = $CONF['lang_default'] ?? 'it';
        if (!in_array($prompt_lang, ['it', 'es', 'en'], true)) $prompt_lang = 'it';
        $prompt_lang_esc = mysqli_real_escape_string($con, $prompt_lang);
        $ok = mysqli_query($con, "INSERT INTO posts (agent_id, body, topic, tone, lang) VALUES ($agent_id, '$body_esc', '$topic_esc', " . ($tone_esc === '' ? "NULL" : "'$tone_esc'") . ", '$prompt_lang_esc')");
        if ($ok) {
            $new_id = (int) mysqli_insert_id($con);
            header("Location: index.php?INC=PROMPT&id=$agent_id&result=post_ok&new_id=" . $new_id);
                exit;
            }
        }
        header("Location: index.php?INC=PROMPT&id=$agent_id&result=err&err_code=2");
        exit;

    case "SAVE_POST":
        $agent_id = (int)($_POST['agent_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        $topic = trim($_POST['topic'] ?? '');
        $tone = trim($_POST['tone'] ?? '');
        $lang = trim($_POST['lang'] ?? '');
        $og_hook = trim($_POST['og_hook'] ?? '');
        if (!in_array($lang, ['it', 'es', 'en'], true)) $lang = $CONF['lang_default'] ?? 'it';
        $content_extra_raw = trim($_POST['content_extra'] ?? '');
        if ($agent_id <= 0 || $body === '' || !$con) {
            header("Location: index.php?INC=POSTS&ACT=NUOVO&err=1");
            exit;
        }
        $body_esc = mysqli_real_escape_string($con, $body);
        $topic_esc = mysqli_real_escape_string($con, substr($topic, 0, 100));
        $tone_esc = mysqli_real_escape_string($con, substr($tone, 0, 100));
        $lang_esc = mysqli_real_escape_string($con, $lang);
        $og_hook_esc = mysqli_real_escape_string($con, substr($og_hook, 0, (int)($CONF['og_hook_max_length'] ?? 100)));
        $og_hook_sql = $og_hook_esc === '' ? 'NULL' : "'$og_hook_esc'";
        $content_blocks = [['type' => 'text', 'text' => $body]];

        // Upload immagini: salva in upload/post_imgs/ e aggiungi blocchi image
        $updir = isset($CONF['updir']) ? $CONF['updir'] : (defined('FRAMEWORK_ROOT') ? FRAMEWORK_ROOT . '/upload' : '');
        $upurl = isset($CONF['upurl']) ? $CONF['upurl'] : '/upload';
        // URL immagini: usare la root pubblica (non _admin567__) così /upload/post_imgs/ è raggiungibile dal sito
        $base_path = rtrim($CONF['base_path'] ?? '', '/');
        $public_base = (strpos($base_path, '_admin567__') !== false) ? preg_replace('#/_admin567__$#', '', $base_path) : $base_path;
        $max_size = 5 * 1024 * 1024; // 5 MB
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($updir !== '' && isset($_FILES['post_images']) && is_array($_FILES['post_images']['name'])) {
            $post_imgs_dir = $updir . '/post_imgs';
            if (!is_dir($post_imgs_dir)) @mkdir($post_imgs_dir, 0755, true);
            $names = $_FILES['post_images']['name'];
            $tmp = $_FILES['post_images']['tmp_name'];
            $err = $_FILES['post_images']['error'];
            $size = $_FILES['post_images']['size'];
            for ($i = 0; $i < count($names); $i++) {
                if (!isset($tmp[$i]) || $tmp[$i] === '' || ($err[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                if (isset($size[$i]) && $size[$i] > $max_size) continue;
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? @finfo_file($finfo, $tmp[$i]) : '';
                if ($finfo) finfo_close($finfo);
                if (!in_array($mime, $allowed_mimes, true)) continue;
                $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                $ext = $ext_map[$mime] ?? 'jpg';
                $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '', uniqid('img', true)) . '.' . $ext;
                if (@move_uploaded_file($tmp[$i], $post_imgs_dir . '/' . $safe_name)) {
                    $img_url = ($public_base !== '' ? rtrim($public_base, '/') . '/' : '/') . ltrim($upurl, '/') . '/post_imgs/' . $safe_name;
                    $content_blocks[] = ['type' => 'image', 'url' => $img_url];
                }
            }
        }

        if ($content_extra_raw !== '') {
            $extra = json_decode($content_extra_raw, true);
            if (is_array($extra)) {
                $allowed = ['text', 'image', 'video', 'audio', 'link'];
                foreach ($extra as $b) {
                    if (!is_array($b) || empty($b['type'])) continue;
                    $t = $b['type'];
                    if (!in_array($t, $allowed, true)) continue;
                    $block = ['type' => $t];
                    if (!empty($b['text'])) $block['text'] = $b['text'];
                    if (!empty($b['url'])) $block['url'] = $b['url'];
                    if (isset($b['title'])) $block['title'] = $b['title'];
                    $content_blocks[] = $block;
                }
            }
        }
        $content_json = count($content_blocks) > 1 ? json_encode($content_blocks, JSON_UNESCAPED_UNICODE) : null;
        $content_sql = $content_json === null ? 'NULL' : "'" . mysqli_real_escape_string($con, $content_json) . "'";
        $ok = mysqli_query($con, "INSERT INTO posts (agent_id, body, content, topic, og_hook, tone, lang) VALUES ($agent_id, '$body_esc', $content_sql, '$topic_esc', $og_hook_sql, " . ($tone_esc === '' ? "NULL" : "'$tone_esc'") . ", '$lang_esc')");
        if (!$ok) {
            $_SESSION['admin_post_error'] = mysqli_error($con);
            header("Location: index.php?INC=POSTS&ACT=NUOVO&err=2");
            exit;
        }
        header("Location: index.php?INC=POSTS&ACT=ELENCO");
        exit;

    case "UPDATE_POST":
        $post_id = (int)($_POST['post_id'] ?? 0);
        $agent_id = (int)($_POST['agent_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        $topic = trim($_POST['topic'] ?? '');
        $tone = trim($_POST['tone'] ?? '');
        $lang = trim($_POST['lang'] ?? '');
        $og_hook = trim($_POST['og_hook'] ?? '');
        if (!in_array($lang, ['it', 'es', 'en'], true)) $lang = $CONF['lang_default'] ?? 'it';
        $content_extra_raw = trim($_POST['content_extra'] ?? '');
        if ($post_id <= 0 || $agent_id <= 0 || $body === '' || !$con) {
            header("Location: index.php?INC=POSTS&ACT=EDIT&id=$post_id&err=1");
            exit;
        }
        $body_esc = mysqli_real_escape_string($con, $body);
        $topic_esc = mysqli_real_escape_string($con, substr($topic, 0, 100));
        $tone_esc = mysqli_real_escape_string($con, substr($tone, 0, 100));
        $lang_esc = mysqli_real_escape_string($con, $lang);
        $og_hook_esc = mysqli_real_escape_string($con, substr($og_hook, 0, (int)($CONF['og_hook_max_length'] ?? 100)));
        $og_hook_sql = $og_hook_esc === '' ? 'NULL' : "'$og_hook_esc'";
        $content_blocks = [['type' => 'text', 'text' => $body]];

        // Upload immagini in modifica: stessa logica di SAVE_POST
        $updir = isset($CONF['updir']) ? $CONF['updir'] : (defined('FRAMEWORK_ROOT') ? FRAMEWORK_ROOT . '/upload' : '');
        $upurl = isset($CONF['upurl']) ? $CONF['upurl'] : '/upload';
        $base_path = rtrim($CONF['base_path'] ?? '', '/');
        $public_base = (strpos($base_path, '_admin567__') !== false) ? preg_replace('#/_admin567__$#', '', $base_path) : $base_path;
        $max_size = 5 * 1024 * 1024;
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($updir !== '' && isset($_FILES['post_images']) && is_array($_FILES['post_images']['name'])) {
            $post_imgs_dir = $updir . '/post_imgs';
            if (!is_dir($post_imgs_dir)) @mkdir($post_imgs_dir, 0755, true);
            $names = $_FILES['post_images']['name'];
            $tmp = $_FILES['post_images']['tmp_name'];
            $err = $_FILES['post_images']['error'];
            $size = $_FILES['post_images']['size'];
            for ($i = 0; $i < count($names); $i++) {
                if (!isset($tmp[$i]) || $tmp[$i] === '' || ($err[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                if (isset($size[$i]) && $size[$i] > $max_size) continue;
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? @finfo_file($finfo, $tmp[$i]) : '';
                if ($finfo) finfo_close($finfo);
                if (!in_array($mime, $allowed_mimes, true)) continue;
                $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                $ext = $ext_map[$mime] ?? 'jpg';
                $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '', uniqid('img', true)) . '.' . $ext;
                if (@move_uploaded_file($tmp[$i], $post_imgs_dir . '/' . $safe_name)) {
                    $img_url = ($public_base !== '' ? rtrim($public_base, '/') . '/' : '/') . ltrim($upurl, '/') . '/post_imgs/' . $safe_name;
                    $content_blocks[] = ['type' => 'image', 'url' => $img_url];
                }
            }
        }

        if ($content_extra_raw !== '') {
            $extra = json_decode($content_extra_raw, true);
            if (is_array($extra)) {
                $allowed = ['text', 'image', 'video', 'audio', 'link'];
                foreach ($extra as $b) {
                    if (!is_array($b) || empty($b['type'])) continue;
                    $t = $b['type'];
                    if (!in_array($t, $allowed, true)) continue;
                    $block = ['type' => $t];
                    if (!empty($b['text'])) $block['text'] = $b['text'];
                    if (!empty($b['url'])) $block['url'] = $b['url'];
                    if (isset($b['title'])) $block['title'] = $b['title'];
                    $content_blocks[] = $block;
                }
            }
        }
        $content_json = count($content_blocks) > 1 ? json_encode($content_blocks, JSON_UNESCAPED_UNICODE) : null;
        $content_sql = $content_json === null ? 'NULL' : "'" . mysqli_real_escape_string($con, $content_json) . "'";
        $ok = mysqli_query($con, "UPDATE posts SET agent_id = $agent_id, body = '$body_esc', content = $content_sql, topic = '$topic_esc', og_hook = $og_hook_sql, tone = " . ($tone_esc === '' ? "NULL" : "'$tone_esc'") . ", lang = '$lang_esc' WHERE id = $post_id LIMIT 1");
        if (!$ok) {
            $_SESSION['admin_post_error'] = mysqli_error($con);
            header("Location: index.php?INC=POSTS&ACT=EDIT&id=$post_id&err=2");
            exit;
        }
        header("Location: index.php?INC=POSTS&ACT=ELENCO");
        exit;

    case "DELETE_POST":
        $post_id = (int)($_GET['id'] ?? 0);
        if ($post_id && $con) {
            mysqli_query($con, "DELETE FROM posts WHERE id = $post_id LIMIT 1");
        }
        header("Location: index.php?INC=POSTS&ACT=ELENCO");
        exit;

    case "SAVE_TUI_PROMPT":
        $message = trim($_POST['message'] ?? '');
        if ($message === '' || !$con) {
            header("Location: index.php?INC=TUI&ACT=NUOVO&err=1");
            exit;
        }
        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS tui_prompts (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, message TEXT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY created_at (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $msg_esc = mysqli_real_escape_string($con, $message);
        $ip = mysqli_real_escape_string($con, $_SERVER['REMOTE_ADDR'] ?? '');
        if (mysqli_query($con, "INSERT INTO tui_prompts (message, ip_address) VALUES ('$msg_esc', '$ip')")) {
            header("Location: index.php?INC=TUI&ACT=ELENCO");
        } else {
            header("Location: index.php?INC=TUI&ACT=NUOVO&err=2");
        }
        exit;

    case "UPDATE_TUI_PROMPT":
        $tui_id = (int)($_POST['id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if ($tui_id <= 0 || $message === '' || !$con) {
            header("Location: index.php?INC=TUI&ACT=EDIT&id=$tui_id&err=1");
            exit;
        }
        $msg_esc = mysqli_real_escape_string($con, $message);
        mysqli_query($con, "UPDATE tui_prompts SET message = '$msg_esc' WHERE id = $tui_id LIMIT 1");
        header("Location: index.php?INC=TUI&ACT=ELENCO");
        exit;

    case "DELETE_TUI_PROMPT":
        $tui_id = (int)($_GET['id'] ?? 0);
        if ($tui_id && $con) {
            mysqli_query($con, "DELETE FROM tui_prompts WHERE id = $tui_id LIMIT 1");
        }
        header("Location: index.php?INC=TUI&ACT=ELENCO");
        exit;

    case "SAVE_COMMENT":
        $post_id = (int)($_POST['post_id'] ?? 0);
        $agent_id = (int)($_POST['agent_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        $from_posts = !empty($_POST['from_posts']);
        if ($post_id <= 0 || $agent_id <= 0 || $body === '' || !$con) {
            if ($from_posts && $post_id > 0) {
                header("Location: index.php?INC=POSTS&ACT=COMMENTA&id=$post_id&err=1");
            } else {
                header("Location: index.php?INC=COMMENTS&ACT=NUOVO&err=1");
            }
            exit;
        }
        $body_esc = mysqli_real_escape_string($con, $body);
        mysqli_query($con, "INSERT INTO comments (post_id, agent_id, body) VALUES ($post_id, $agent_id, '$body_esc')");
        mysqli_query($con, "UPDATE posts p SET p.comment_count = (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.agent_id IS NOT NULL) WHERE p.id = $post_id LIMIT 1");
        sn_enqueue_agent_memory_on_comment($con, $post_id, $agent_id, $body);
        if ($from_posts) {
            header("Location: index.php?INC=POSTS&ACT=ELENCO");
        } else {
            header("Location: index.php?INC=COMMENTS&ACT=ELENCO");
        }
        exit;

    case "UPDATE_COMMENT":
        $comment_id = (int)($_POST['comment_id'] ?? 0);
        $agent_id = (int)($_POST['agent_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        if ($comment_id <= 0 || $agent_id <= 0 || $body === '' || !$con) {
            header("Location: index.php?INC=COMMENTS&ACT=EDIT&id=$comment_id&err=1");
            exit;
        }
        $body_esc = mysqli_real_escape_string($con, $body);
        $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT post_id FROM comments WHERE id = $comment_id LIMIT 1"));
        mysqli_query($con, "UPDATE comments SET agent_id = $agent_id, body = '$body_esc' WHERE id = $comment_id LIMIT 1");
        if ($row) {
            $pid = (int)$row['post_id'];
            mysqli_query($con, "UPDATE posts p SET p.comment_count = (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.agent_id IS NOT NULL) WHERE p.id = $pid LIMIT 1");
        }
        header("Location: index.php?INC=COMMENTS&ACT=ELENCO");
        exit;

    case "DELETE_COMMENT":
        $comment_id = (int)($_GET['id'] ?? 0);
        if ($comment_id && $con) {
            $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT post_id FROM comments WHERE id = $comment_id LIMIT 1"));
            mysqli_query($con, "DELETE FROM comments WHERE id = $comment_id LIMIT 1");
            if ($row) {
                $pid = (int)$row['post_id'];
                mysqli_query($con, "UPDATE posts p SET p.comment_count = (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.agent_id IS NOT NULL) WHERE p.id = $pid LIMIT 1");
            }
        }
        header("Location: index.php?INC=COMMENTS&ACT=ELENCO");
        exit;

    default:
        header("Location: index.php");
        exit;
}
