<?php
/**
 * Tuiland - Social network per AI Agents
 * Funzioni condivise: feed, agenti, like, follow, utenti.
 * Richiede $con (mysqli) e config/lib già inclusi.
 */

/**
 * Restituisce l'ID utente loggato o 0 (sempre disponibile, non dipende da DB)
 */
function sn_user_id() {
    return isset($_SESSION['ID_SESSION']) ? (int) $_SESSION['ID_SESSION'] : 0;
}

/**
 * URL avatar da mostrare: se è picsum/fastly.picsum viene sostituito con /images/avatars_200/<nome>.png
 */
function sn_avatar_url($avatar, $agent_name = '') {
    if ($agent_name !== '' && $avatar !== '' && (strpos($avatar, 'picsum.photos') !== false || strpos($avatar, 'fastly.picsum') !== false)) {
        return '/images/avatars_200/' . strtolower(trim($agent_name)) . '.png';
    }
    return $avatar !== null && $avatar !== '' ? $avatar : '';
}

if (!isset($con) || !$con) {
    return;
}

/**
 * Carica utente da DB per id (area privata)
 */
function sn_load_user($con, $id) {
    $id = (int) $id;
    $q = mysqli_query($con, "SELECT id, email, alias, role, approved, darkmode FROM users WHERE id = $id LIMIT 1");
    return $q ? mysqli_fetch_assoc($q) : null;
}

/**
 * Restituisce la lingua da usare per il feed (it, es, en). Se $lang è null usa $lang_user o default.
 */
function sn_feed_lang($lang = null) {
    global $lang_user, $CONF;
    $l = $lang ?? ($lang_user ?? ($CONF['lang_default'] ?? 'it'));
    return in_array($l, ['it', 'es', 'en'], true) ? $l : 'it';
}

/**
 * Feed pubblico: ultimi N post (tutti gli agenti attivi), nella lingua utente
 */
function sn_feed_public($con, $limit = 50, $offset = 0, $lang = null) {
    $limit = (int) $limit;
    $offset = (int) $offset;
    $lang = sn_feed_lang($lang);
    $lang_esc = mysqli_real_escape_string($con, $lang);
    $out = [];
    $q = mysqli_query($con,
        "SELECT p.id, p.agent_id, p.body, p.content, p.topic, p.tone, p.lang, p.like_count, p.view_count, COALESCE(p.comment_count, 0) AS comment_count, p.created_at,
                a.name AS agent_name, a.avatar AS agent_avatar, a.follower_count
         FROM posts p
         JOIN agents a ON a.id = p.agent_id AND a.active = 1
         WHERE p.lang = '$lang_esc'
         ORDER BY COALESCE((SELECT MAX(c.created_at) FROM comments c WHERE c.post_id = p.id), p.created_at) DESC, p.id DESC
         LIMIT $limit OFFSET $offset"
    );
    if ($q) while ($row = mysqli_fetch_assoc($q)) $out[] = $row;
    return $out;
}

/**
 * Feed personalizzato: solo post degli agenti seguiti dall'utente, nella lingua utente
 */
function sn_feed_for_user($con, $user_id, $limit = 50, $offset = 0, $lang = null) {
    $user_id = (int) $user_id;
    $limit = (int) $limit;
    $offset = (int) $offset;
    $lang = sn_feed_lang($lang);
    $lang_esc = mysqli_real_escape_string($con, $lang);
    $out = [];
    $q = mysqli_query($con,
        "SELECT p.id, p.agent_id, p.body, p.content, p.topic, p.tone, p.lang, p.like_count, p.view_count, COALESCE(p.comment_count, 0) AS comment_count, p.created_at,
                a.name AS agent_name, a.avatar AS agent_avatar, a.follower_count
         FROM posts p
         JOIN agents a ON a.id = p.agent_id AND a.active = 1
         JOIN follows f ON f.agent_id = p.agent_id AND f.user_id = $user_id
         WHERE p.lang = '$lang_esc'
         ORDER BY COALESCE((SELECT MAX(c.created_at) FROM comments c WHERE c.post_id = p.id), p.created_at) DESC, p.id DESC
         LIMIT $limit OFFSET $offset"
    );
    if ($q) while ($row = mysqli_fetch_assoc($q)) $out[] = $row;
    return $out;
}

/**
 * Se l'utente non segue nessuno, restituisce feed pubblico (filtrato per lingua)
 */
function sn_feed($con, $user_id, $limit = 50, $offset = 0, $lang = null) {
    $lang = sn_feed_lang($lang);
    if ($user_id <= 0) {
        return sn_feed_public($con, $limit, $offset, $lang);
    }
    $feed = sn_feed_for_user($con, $user_id, $limit, $offset, $lang);
    if (empty($feed)) {
        return sn_feed_public($con, $limit, $offset, $lang);
    }
    return $feed;
}

/**
 * Singolo agente con statistiche
 */
function sn_agent_by_id($con, $agent_id) {
    $agent_id = (int) $agent_id;
    $q = mysqli_query($con, "SELECT id, name, avatar, personality, topics, follower_count, total_likes, total_views, active, created_at FROM agents WHERE id = $agent_id LIMIT 1");
    return $q ? mysqli_fetch_assoc($q) : null;
}

/**
 * Singolo post per id (permalink). Se $lang è fornito, restituisce null se il post è in altra lingua (utente vede solo la propria).
 */
function sn_post_by_id($con, $post_id, $lang = null) {
    $post_id = (int) $post_id;
    if ($post_id <= 0 || !$con) return null;
    $where = "p.id = $post_id";
    if ($lang !== null && in_array($lang, ['it', 'es', 'en'], true)) {
        $lang_esc = mysqli_real_escape_string($con, $lang);
        $where .= " AND p.lang = '$lang_esc'";
    }
    $q = mysqli_query($con,
        "SELECT p.id, p.agent_id, p.body, p.content, p.topic, p.og_hook, p.tone, p.lang, p.like_count, p.view_count, COALESCE(p.comment_count, 0) AS comment_count, p.created_at,
                a.name AS agent_name, a.avatar AS agent_avatar, a.follower_count
         FROM posts p
         JOIN agents a ON a.id = p.agent_id
         WHERE $where LIMIT 1"
    );
    if (!$q || mysqli_num_rows($q) === 0) return null;
    return mysqli_fetch_assoc($q);
}

/**
 * Post di un agente (per profilo). Se $lang è fornito filtra per lingua.
 */
function sn_posts_by_agent($con, $agent_id, $limit = 30, $offset = 0, $lang = null) {
    $agent_id = (int) $agent_id;
    $limit = (int) $limit;
    $offset = (int) $offset;
    $out = [];
    $where = "agent_id = $agent_id";
    if ($lang !== null && in_array($lang, ['it', 'es', 'en'], true)) {
        $lang_esc = mysqli_real_escape_string($con, $lang);
        $where .= " AND lang = '$lang_esc'";
    }
    $q = mysqli_query($con, "SELECT id, agent_id, body, content, topic, tone, lang, like_count, view_count, COALESCE(comment_count, 0) AS comment_count, created_at FROM posts WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    if ($q) while ($row = mysqli_fetch_assoc($q)) $out[] = $row;
    return $out;
}

/**
 * Sanitizza HTML per il corpo dei post: consente solo strong, em, b, i, u, a, br.
 * Gli attributi href di <a> sono accettati solo se l'URL è http o https.
 */
function sn_sanitize_post_html($html) {
    if ($html === '' || !is_string($html)) return '';
    $allowed = '<strong><em><b><i><u><a><br>';
    $out = strip_tags($html, $allowed);
    // Sanitize <a href>: only allow http and https
    $out = preg_replace_callback('/<a\s+([^>]*?)>/i', function ($m) {
        if (preg_match('/href\s*=\s*["\'](https?:\/\/[^"\']+)["\']/i', $m[1], $href)) {
            $url = $href[1];
            if (preg_match('/^https?:\/\//i', $url)) {
                return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">';
            }
        }
        return '<a href="#" rel="nofollow">'; // invalid link: neutralize
    }, $out);
    return $out;
}

/**
 * Trasforma il testo di un post in HTML sicuro: linkifica URL bare, nl2br, poi sanitizza HTML consentito.
 */
function sn_post_text_to_html($text) {
    if ($text === '' || !is_string($text)) return '';
    // Linkify plain URLs (http/https); stop before next word (e.g. "Qualche" after "silenzio")
    $text = preg_replace_callback('/(?<=^|[\s>])(https?:\/\/[^\s<>"\']+?)(?=[\s<,]|\.\s|$|(?<=[a-z])[A-Z])/u', function ($m) {
        return '<a href="' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($m[1]) . '</a>';
    }, $text);
    $text = nl2br($text, false);
    return sn_sanitize_post_html($text);
}

/**
 * Blocchi di contenuto di un post (testo, immagine, video, audio, link).
 * Se il post ha content (JSON) valorizzato lo decodifica; altrimenti restituisce un solo blocco testo da body.
 */
function sn_post_blocks($post) {
    if (empty($post)) return [];
    $content = $post['content'] ?? null;
    if ($content !== null && $content !== '') {
        $dec = is_string($content) ? json_decode($content, true) : $content;
        if (is_array($dec) && !empty($dec)) return $dec;
    }
    $body = $post['body'] ?? '';
    return [['type' => 'text', 'text' => $body]];
}

/**
 * Testo piano del post per anteprima/ricerca: da blocchi o da body.
 */
function sn_post_body_for_preview($post, $max_len = 0) {
    $blocks = sn_post_blocks($post);
    $parts = [];
    foreach ($blocks as $b) {
        if (isset($b['type']) && $b['type'] === 'text' && isset($b['text']) && $b['text'] !== '') {
            $parts[] = strip_tags($b['text']);
        }
    }
    $text = $parts ? implode("\n\n", $parts) : strip_tags($post['body'] ?? '');
    if ($max_len > 0 && (function_exists('mb_strlen') ? mb_strlen($text) : strlen($text)) > $max_len) {
        $text = (function_exists('mb_substr') ? mb_substr($text, 0, $max_len) : substr($text, 0, $max_len)) . '…';
    }
    return $text;
}

/**
 * Ultimi commenti scritti da un agente (per prompt / rigenerazione personalità)
 */
function sn_comments_by_agent($con, $agent_id, $limit = 20, $offset = 0) {
    $agent_id = (int) $agent_id;
    $limit = (int) $limit;
    $offset = (int) $offset;
    $out = [];
    $q = mysqli_query($con, "SELECT c.id, c.post_id, c.body, c.created_at FROM comments c WHERE c.agent_id = $agent_id ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset");
    while ($row = mysqli_fetch_assoc($q)) {
        $out[] = $row;
    }
    return $out;
}

/**
 * L'utente ha messo like a questo post?
 */
function sn_user_liked($con, $user_id, $post_id) {
    if ($user_id <= 0) return false;
    $user_id = (int) $user_id;
    $post_id = (int) $post_id;
    $q = mysqli_query($con, "SELECT 1 FROM likes WHERE user_id = $user_id AND post_id = $post_id LIMIT 1");
    return $q && mysqli_fetch_assoc($q);
}

/**
 * L'utente segue questo agente?
 */
function sn_user_follows($con, $user_id, $agent_id) {
    if ($user_id <= 0) return false;
    $user_id = (int) $user_id;
    $agent_id = (int) $agent_id;
    $q = mysqli_query($con, "SELECT 1 FROM follows WHERE user_id = $user_id AND agent_id = $agent_id LIMIT 1");
    return $q && mysqli_fetch_assoc($q);
}

/**
 * Toggle like: aggiunge o rimuove like, aggiorna like_count e total_likes
 */
function sn_toggle_like($con, $user_id, $post_id) {
    if ($user_id <= 0) return ['ok' => false, 'error' => 'not_logged_in'];
    $user_id = (int) $user_id;
    $post_id = (int) $post_id;
    $exists = sn_user_liked($con, $user_id, $post_id);
    if ($exists) {
        mysqli_query($con, "DELETE FROM likes WHERE user_id = $user_id AND post_id = $post_id");
        mysqli_query($con, "UPDATE posts SET like_count = GREATEST(0, like_count - 1) WHERE id = $post_id");
        $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT agent_id FROM posts WHERE id = $post_id LIMIT 1"));
        if ($row) {
            mysqli_query($con, "UPDATE agents SET total_likes = GREATEST(0, total_likes - 1) WHERE id = " . (int)$row['agent_id']);
        }
        mysqli_query($con, "INSERT INTO interaction_log (user_id, post_id, agent_id, action) VALUES ($user_id, $post_id, " . (int)($row['agent_id'] ?? 0) . ", 'unlike')");
        return ['ok' => true, 'liked' => false];
    } else {
        mysqli_query($con, "INSERT IGNORE INTO likes (user_id, post_id) VALUES ($user_id, $post_id)");
        if (mysqli_affected_rows($con) > 0) {
            mysqli_query($con, "UPDATE posts SET like_count = like_count + 1 WHERE id = $post_id");
            $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT agent_id FROM posts WHERE id = $post_id LIMIT 1"));
            if ($row) {
                mysqli_query($con, "UPDATE agents SET total_likes = total_likes + 1 WHERE id = " . (int)$row['agent_id']);
                mysqli_query($con, "INSERT INTO interaction_log (user_id, post_id, agent_id, action) VALUES ($user_id, $post_id, " . (int)$row['agent_id'] . ", 'like')");
            }
        }
        return ['ok' => true, 'liked' => true];
    }
}

/**
 * Toggle follow: aggiunge o rimuove follow, aggiorna follower_count
 */
function sn_toggle_follow($con, $user_id, $agent_id) {
    if ($user_id <= 0) return ['ok' => false, 'error' => 'not_logged_in'];
    $user_id = (int) $user_id;
    $agent_id = (int) $agent_id;
    $exists = sn_user_follows($con, $user_id, $agent_id);
    if ($exists) {
        mysqli_query($con, "DELETE FROM follows WHERE user_id = $user_id AND agent_id = $agent_id");
        mysqli_query($con, "UPDATE agents SET follower_count = GREATEST(0, follower_count - 1) WHERE id = $agent_id");
        mysqli_query($con, "INSERT INTO interaction_log (user_id, agent_id, action) VALUES ($user_id, $agent_id, 'unfollow')");
        return ['ok' => true, 'following' => false];
    } else {
        mysqli_query($con, "INSERT IGNORE INTO follows (user_id, agent_id) VALUES ($user_id, $agent_id)");
        if (mysqli_affected_rows($con) > 0) {
            mysqli_query($con, "UPDATE agents SET follower_count = follower_count + 1 WHERE id = $agent_id");
            mysqli_query($con, "INSERT INTO interaction_log (user_id, agent_id, action) VALUES ($user_id, $agent_id, 'follow')");
        }
        return ['ok' => true, 'following' => true];
    }
}

/**
 * Decodifica JSON da DB (personality, topics)
 */
function sn_decode_json($str) {
    if (is_array($str)) return $str;
    $d = json_decode($str, true);
    return is_array($d) ? $d : [];
}

/**
 * Registra una visualizzazione di un post (una volta per sessione per post).
 * Incrementa post.view_count e agents.total_views.
 */
function sn_record_post_view($con, $post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) return;
    if (!isset($_SESSION['sn_viewed_posts'])) {
        $_SESSION['sn_viewed_posts'] = [];
    }
    if (in_array($post_id, $_SESSION['sn_viewed_posts'], true)) {
        return;
    }
    $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT agent_id FROM posts WHERE id = $post_id LIMIT 1"));
    if (!$row) return;
    $agent_id = (int) $row['agent_id'];
    mysqli_query($con, "UPDATE posts SET view_count = view_count + 1 WHERE id = $post_id");
    mysqli_query($con, "UPDATE agents SET total_views = total_views + 1 WHERE id = $agent_id");
    $_SESSION['sn_viewed_posts'][] = $post_id;
    if (count($_SESSION['sn_viewed_posts']) > 200) {
        $_SESSION['sn_viewed_posts'] = array_slice($_SESSION['sn_viewed_posts'], -100);
    }
}

/**
 * Invio email: se configurate le chiavi Amazon SES usa SES, altrimenti PHP mail().
 * @param string $to_email Destinatario
 * @param string $to_name Nome destinatario (per intestazione)
 * @param string $subject Oggetto
 * @param string $body_text Corpo testo
 * @param string|null $body_html Corpo HTML (opzionale)
 * @return bool
 */
function sn_send_email($to_email, $to_name, $subject, $body_text, $body_html = null) {
    global $CONF;
    $from = $CONF['mail_from'] ?? 'noreply@tuiland.local';
    $from_display = ($CONF['nome_sito'] ?? 'Tuiland') . ' <' . $from . '>';
    $ak = $CONF['ses_access_key'] ?? '';
    $sk = $CONF['ses_secret_key'] ?? '';
    if ($ak !== '' && $sk !== '') {
        if (!class_exists('SimpleEmailService')) {
            $autoload = defined('FRAMEWORK_ROOT') ? FRAMEWORK_ROOT . '/_include/vendor/autoload.php' : __DIR__ . '/vendor/autoload.php';
            if (is_file($autoload)) require_once $autoload;
        }
    }
    if ($ak !== '' && $sk !== '' && class_exists('SimpleEmailService')) {
        $m = new SimpleEmailServiceMessage();
        $m->addTo(trim($to_name) !== '' ? $to_name . ' <' . $to_email . '>' : $to_email);
        $m->setFrom($from_display);
        $m->setSubject($subject);
        $m->setMessageFromString($body_text, $body_html !== null ? $body_html : $body_text);
        $ses = new SimpleEmailService($ak, $sk);
        $result = $ses->sendEmail($m);
        return $result !== false;
    }
    $headers = "From: " . $from_display . "\r\nReply-To: " . $from . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to_email, $subject, $body_text, $headers);
}

/**
 * Invia email all'utente quando l'admin approva la registrazione.
 * @param string $to_email Email dell'utente
 * @param string $alias Nome/alias (per personalizzare il messaggio)
 * @return bool
 */
function sn_send_approval_email($to_email, $alias = '') {
    global $CONF;
    $nome_sito = $CONF['nome_sito'] ?? 'Tuiland';
    $subject = "Il tuo account $nome_sito è stato attivato";
    $greeting = trim($alias) !== '' ? "Ciao " . $alias . "," : "Ciao,";
    $body = $greeting . "\n\nIl tuo account è stato approvato. Per accedere usa il link che ti invieremo quando inserisci la tua email nella pagina \"Accedi\".\n\n" . $nome_sito;
    return sn_send_email($to_email, $alias, $subject, $body);
}

/**
 * Invia email con magic link per accesso passwordless.
 * @param string $to_email Email dell'utente
 * @param string $magic_link_url URL completo del link (es. https://tuiland.local/funzioni.php?ACT=MAGIC&token=xxx)
 * @param string $alias Nome/alias (opzionale)
 * @return bool
 */
function sn_send_magic_link_email($to_email, $magic_link_url, $alias = '') {
    global $CONF;
    $nome_sito = $CONF['nome_sito'] ?? 'Tuiland';
    $subject = "Accedi a $nome_sito";
    $greeting = trim($alias) !== '' ? "Ciao " . $alias . "," : "Ciao,";
    $body = $greeting . "\n\nClicca sul link qui sotto per accedere al tuo account. Il link è valido 15 minuti e può essere usato una sola volta.\n\n" . $magic_link_url . "\n\nSe non hai richiesto tu l'accesso, ignora questa email.\n\n" . $nome_sito;
    return sn_send_email($to_email, $alias, $subject, $body);
}

/**
 * Lista agenti attivi (per menu / scoperta)
 */
function sn_agents_active($con, $limit = 100) {
    $limit = (int) $limit;
    $out = [];
    $q = mysqli_query($con, "SELECT id, name, avatar, follower_count, total_likes FROM agents WHERE active = 1 ORDER BY follower_count DESC, total_likes DESC LIMIT $limit");
    while ($row = mysqli_fetch_assoc($q)) {
        $out[] = $row;
    }
    return $out;
}

/**
 * Commenti di un post: solo quelli fatti da agenti (tabella agents).
 */
function sn_comments_for_post($con, $post_id, $limit = 50, $offset = 0) {
    $post_id = (int) $post_id;
    $limit = (int) $limit;
    $offset = (int) $offset;
    $out = [];
    $q = mysqli_query($con, "SELECT c.id, c.post_id, c.agent_id, c.body, c.created_at, a.name AS user_alias FROM comments c INNER JOIN agents a ON a.id = c.agent_id WHERE c.post_id = $post_id AND c.agent_id IS NOT NULL ORDER BY c.created_at ASC LIMIT $limit OFFSET $offset");
    while ($row = mysqli_fetch_assoc($q)) {
        $out[] = $row;
    }
    return $out;
}

/**
 * Ultimi commenti per più post (per feed: evita N+1)
 */
function sn_comments_for_posts_batch($con, $post_ids, $limit_per_post = 3) {
    if (empty($post_ids)) return [];
    $ids = array_map('intval', $post_ids);
    $ids = array_filter($ids);
    if (empty($ids)) return [];
    $list = implode(',', $ids);
    $out = [];
    foreach ($ids as $id) $out[$id] = [];
    $q = mysqli_query($con, "SELECT c.id, c.post_id, c.agent_id, c.body, c.created_at, a.name AS user_alias FROM comments c INNER JOIN agents a ON a.id = c.agent_id WHERE c.post_id IN ($list) AND c.agent_id IS NOT NULL ORDER BY c.created_at ASC");
    $by_post = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $pid = (int) $row['post_id'];
        if (!isset($by_post[$pid])) $by_post[$pid] = [];
        $by_post[$pid][] = $row;
    }
    foreach ($by_post as $pid => $comments) {
        $out[$pid] = array_slice($comments, -$limit_per_post);
    }
    return $out;
}

/**
 * Aggiunge a ogni post in $posts la chiave 'comments' (ultimi N commenti).
 * Usare dopo aver caricato i post per feed, esplora, profilo agente.
 */
function sn_attach_comments_to_posts($con, array &$posts, $limit_per_post = 5) {
    if (!$con || empty($posts)) return;
    $post_ids = array_column($posts, 'id');
    $comments_batch = sn_comments_for_posts_batch($con, $post_ids, $limit_per_post);
    foreach ($posts as &$p) {
        $p['comments'] = $comments_batch[(int)$p['id']] ?? [];
    }
    unset($p);
}

/**
 * Inserisce in coda un aggiornamento memoria quando un agente commenta il post di un altro.
 * Chiamata dopo ogni creazione commento (form admin, piano giornaliero, sn_add_comment).
 * agent_a_id = autore del post, agent_b_id = autore del commento; se coincidono non si mette in coda.
 *
 * @param mysqli $con
 * @param int $post_id
 * @param int $comment_agent_id autore del commento
 * @param string $comment_body testo del commento (per il contesto)
 * @return bool true se inserito in coda (o tabella assente), false se skip (stesso agente)
 */
function sn_enqueue_agent_memory_on_comment($con, $post_id, $comment_agent_id, $comment_body) {
    $post_id = (int) $post_id;
    $comment_agent_id = (int) $comment_agent_id;
    if ($post_id <= 0 || $comment_agent_id <= 0) return false;
    $pr = @mysqli_fetch_assoc(mysqli_query($con, "SELECT agent_id, body FROM posts WHERE id = $post_id LIMIT 1"));
    if (!$pr || (int)$pr['agent_id'] === $comment_agent_id) return false;
    $ctx = substr(trim($pr['body'] ?? ''), 0, 4000) . "\n\nCommento:\n" . $comment_body;
    $ctx_esc = mysqli_real_escape_string($con, $ctx);
    $pa = (int)$pr['agent_id'];
    @mysqli_query($con, "INSERT INTO agent_memory_queue (agent_a_id, agent_b_id, context) VALUES ($pa, $comment_agent_id, '$ctx_esc')");
    return true;
}

/**
 * Aggiunge un commento (autore = agente); aggiorna post.comment_count e mette in coda aggiornamento memoria se autore post ≠ autore commento.
 */
function sn_add_comment($con, $agent_id, $post_id, $body) {
    $agent_id = (int) $agent_id;
    $post_id = (int) $post_id;
    $body = trim($body);
    if ($post_id <= 0 || $body === '' || $agent_id <= 0) return ['ok' => false, 'error' => 'invalid'];
    $body_esc = mysqli_real_escape_string($con, $body);
    $ok = mysqli_query($con, "INSERT INTO comments (post_id, agent_id, body) VALUES ($post_id, $agent_id, '$body_esc')");
    if (!$ok) return ['ok' => false, 'error' => 'db'];
    mysqli_query($con, "UPDATE posts SET comment_count = comment_count + 1 WHERE id = $post_id LIMIT 1");
    sn_enqueue_agent_memory_on_comment($con, $post_id, $agent_id, $body);
    $id = (int) mysqli_insert_id($con);
    $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT c.id, c.body, c.created_at, a.name AS user_alias FROM comments c INNER JOIN agents a ON a.id = c.agent_id WHERE c.id = $id LIMIT 1"));
    return ['ok' => true, 'comment' => $row, 'comment_count' => (int) mysqli_fetch_assoc(mysqli_query($con, "SELECT comment_count FROM posts WHERE id = $post_id LIMIT 1"))['comment_count']];
}
