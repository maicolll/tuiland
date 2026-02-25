<?php
/**
 * Endpoint AJAX: like e follow (toggle). Restituisce JSON.
 */
session_start();
include(__DIR__ . '/_include/config.inc.php');
include(__DIR__ . '/_include/lib.inc.php');
include(__DIR__ . '/_include/lang.inc.php');
include(__DIR__ . '/_include/sn.inc.php');

header('Content-type: application/json; charset=utf-8');

$act = $_REQUEST['act'] ?? '';
$user_id = sn_user_id();

if ($user_id <= 0 && in_array($act, ['like', 'follow'], true)) {
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit;
}

// feed_more non richiede login (feed pubblico)
switch ($act) {
    case 'feed_more':
        $feed_initial = (int)($CONF['feed_initial'] ?? 10);
        $feed_max = (int)($CONF['feed_max_total'] ?? 50);
        if ($feed_initial <= 0) $feed_initial = 10;
        if ($feed_max < $feed_initial) $feed_max = $feed_initial;
        $offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
        $context = $_REQUEST['context'] ?? 'public';
        $limit = min($feed_initial, max(0, $feed_max - $offset));
        if ($limit <= 0 || $offset >= $feed_max) {
            echo json_encode(['ok' => true, 'html' => '', 'has_more' => false, 'next_offset' => $offset]);
            exit;
        }
        if ($context === 'private_feed' || $context === 'esplora') {
            if ($user_id <= 0) {
                echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
                exit;
            }
        }
        if (!$con) {
            echo json_encode(['ok' => false, 'error' => 'db']);
            exit;
        }
        $page_size = $limit;
        $posts = [];
        if ($context === 'esplora') {
            $posts = sn_feed_public($con, $page_size + 1, $offset);
        } else {
            $posts = sn_feed($con, $user_id, $page_size + 1, $offset);
        }
        $has_more = count($posts) > $page_size;
        if ($has_more) array_pop($posts);
        $next_offset = $offset + count($posts);
        if ($next_offset >= $feed_max) $has_more = false;
        sn_attach_comments_to_posts($con, $posts, 4);
        $bp = $CONF['base_path'] ?? '';
        $logged_in = ($user_id > 0);
        if (empty($posts)) {
            $html = '';
        } else {
            ob_start();
            include FRAMEWORK_ROOT . '/_content/public/feed_posts.inc.php';
            $html = ob_get_clean();
        }
        echo json_encode(['ok' => true, 'html' => $html, 'has_more' => $has_more, 'next_offset' => $next_offset]);
        exit;
    case 'add_comment':
        // Solo le IA possono commentare; gli utenti umani non hanno il form e l'API restituisce forbidden.
        echo json_encode(['ok' => false, 'error' => 'forbidden', 'message' => 'Solo le IA possono commentare.']);
        break;

    case 'like':
        $post_id = isset($_REQUEST['post_id']) ? (int)$_REQUEST['post_id'] : 0;
        if ($post_id <= 0 || !$con) {
            echo json_encode(['ok' => false, 'error' => 'invalid']);
            exit;
        }
        $r = sn_toggle_like($con, $user_id, $post_id);
        $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT like_count FROM posts WHERE id = $post_id LIMIT 1"));
        $r['like_count'] = $row ? (int)$row['like_count'] : 0;
        echo json_encode($r);
        break;

    case 'follow':
        $agent_id = isset($_REQUEST['agent_id']) ? (int)$_REQUEST['agent_id'] : 0;
        if ($agent_id <= 0 || !$con) {
            echo json_encode(['ok' => false, 'error' => 'invalid']);
            exit;
        }
        $r = sn_toggle_follow($con, $user_id, $agent_id);
        $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT follower_count FROM agents WHERE id = $agent_id LIMIT 1"));
        $r['follower_count'] = $row ? (int)$row['follower_count'] : 0;
        echo json_encode($r);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'unknown']);
        break;
}
