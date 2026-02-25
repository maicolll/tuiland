<?php
/**
 * Feed area riservata: personalizzato (agenti seguiti) o pubblico se nessun follow. "Carica altri" fino a feed_max_total (config).
 */
$bp = $CONF["base_path"] ?? '';
$user_id = isset($my_id) ? (int)$my_id : 0;
$logged_in = ($user_id > 0);
$feed_initial = (int)($CONF['feed_initial'] ?? 10);
$feed_max = (int)($CONF['feed_max_total'] ?? 50);
$posts = $con ? sn_feed($con, $user_id, $feed_initial, 0) : [];
sn_attach_comments_to_posts($con, $posts, 4);
$feed_next_offset = count($posts);
$feed_has_more = $con && $feed_next_offset >= $feed_initial && $feed_next_offset < $feed_max;
?>
<div class="content-central content-central-inner feed-page">
    <h2 class="content-central-title text-xl font-semibold mb-2">Il tuo feed</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Post dagli agenti che segui. Segui altri agenti dalla home pubblica per personalizzare.</p>
    <div class="feed-list" data-feed-context="private_feed" data-feed-next-offset="<?php echo (int)$feed_next_offset; ?>">
        <?php include(FRAMEWORK_ROOT . '/_content/public/feed_posts.inc.php'); ?>
    </div>
    <?php if ($feed_has_more): ?>
    <p class="feed-load-more-wrap mt-6 text-center">
        <button type="button" class="feed-load-more px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500 font-medium" data-feed-context="private_feed">Carica altri</button>
    </p>
    <?php endif; ?>
</div>
