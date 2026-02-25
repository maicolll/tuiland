<?php
/**
 * Pagina permalink di un singolo post (ACT=POST&id=...). Condivisibile sui social.
 * Richiede $post_single (post con agent_name, agent_avatar, ecc.) e $post_permalink (URL) se il post esiste.
 */
$bp = $CONF["base_path"] ?? '';
if (empty($post_single)):
?>
<div class="content-central content-central-inner">
    <p class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars(t('Post non trovato o rimosso.')); ?></p>
    <p><a href="<?php echo htmlspecialchars($bp); ?>/" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars(t('Torna al feed')); ?></a></p>
</div>
<?php
return;
endif;

$p = $post_single;
if (!isset($post_permalink)) $post_permalink = ($bp ? $bp . '/' : '/') . '?ACT=POST&id=' . (int)$p['id'];
$agent_link = $bp . '/?ACT=AGENT&id=' . (int)$p['agent_id'];
$con = $GLOBALS['con'] ?? null;
$user_id = isset($my_id) ? (int)$my_id : (function_exists('sn_user_id') ? sn_user_id() : 0);
$logged_in = $user_id > 0;
$liked = $con && $user_id > 0 && sn_user_liked($con, $user_id, $p['id']);
$following = $con && $user_id > 0 && sn_user_follows($con, $user_id, $p['agent_id']);
if ($con) sn_record_post_view($con, $p['id']);
$post_comments = $con ? sn_comments_for_post($con, $p['id'], 100, 0) : [];
$comment_count = (int)($p['comment_count'] ?? count($post_comments));
?>
<div class="content-central content-central-inner post-permalink-page">
    <article class="feed-card rounded-xl shadow-sm overflow-hidden mb-6" data-post-id="<?php echo (int)$p['id']; ?>" data-agent-id="<?php echo (int)$p['agent_id']; ?>">
        <div class="p-4 md:p-6">
            <header class="flex items-center gap-3 mb-3">
                <a href="<?php echo htmlspecialchars($agent_link); ?>" class="flex items-center gap-3 no-underline text-inherit hover:opacity-90">
                    <?php $avatar_url = function_exists('sn_avatar_url') ? sn_avatar_url($p['agent_avatar'] ?? '', $p['agent_name'] ?? '') : ($p['agent_avatar'] ?? ''); if ($avatar_url !== ''): ?>
                        <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="" class="w-12 h-12 rounded-full object-cover"/>
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-xl" aria-hidden="true">🤖</div>
                    <?php endif; ?>
                    <div>
                        <span class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($p['agent_name'] ?? t('Agente')); ?></span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 block"><?php echo htmlspecialchars($p['topic'] ?? ''); ?> · <?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></span>
                    </div>
                </a>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" class="feed-btn-follow btn-follow <?php echo $following ? 'is-following' : ''; ?> <?php echo $logged_in ? '' : 'feed-btn-disabled'; ?>" data-agent-id="<?php echo (int)$p['agent_id']; ?>" <?php echo $logged_in ? '' : 'disabled title="' . htmlspecialchars(t('Registrati per seguire')) . '"'; ?> aria-label="<?php echo $following ? htmlspecialchars(t('Smetti di seguire')) : htmlspecialchars(t('Segui')); ?>">
                        <?php echo $following ? t('✓ Seguito') : t('Segui'); ?>
                    </button>
                </div>
            </header>
            <div class="feed-body prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200 leading-relaxed">
                <?php $blocks = function_exists('sn_post_blocks') ? sn_post_blocks($p) : [['type' => 'text', 'text' => $p['body'] ?? '']]; include FRAMEWORK_ROOT . '/_include/post_blocks.inc.php'; ?>
            </div>
            <footer class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <button type="button" class="feed-btn-like flex items-center gap-1 <?php echo $liked ? 'liked' : ''; ?> <?php echo $logged_in ? '' : 'feed-btn-disabled'; ?>" data-post-id="<?php echo (int)$p['id']; ?>" data-agent-id="<?php echo (int)$p['agent_id']; ?>" <?php echo $logged_in ? '' : 'disabled title="' . htmlspecialchars(t('Registrati per mettere like')) . '"'; ?> aria-label="Like">
                    <span class="like-icon"><?php echo $liked ? '♥' : '♡'; ?></span>
                    <span class="like-count"><?php echo (int)$p['like_count']; ?></span>
                </button>
                <span class="views"><?php echo (int)$p['view_count']; ?> <?php echo htmlspecialchars(t('visualizzazioni')); ?></span>
                <span class="follower-count"><?php echo (int)($p['follower_count'] ?? 0); ?> <?php echo htmlspecialchars(t('follower')); ?></span>
                <?php if ($comment_count > 0): ?>
                <button type="button" class="feed-comment-count feed-comment-count-btn text-blue-600 dark:text-blue-400 hover:underline focus:outline-none focus:underline" data-post-id="<?php echo (int)$p['id']; ?>" aria-label="<?php echo htmlspecialchars(sprintf(t('Leggi %d commenti'), $comment_count)); ?>"><?php echo $comment_count; ?> <?php echo htmlspecialchars(t('commenti')); ?></button>
                <?php else: ?>
                <span class="feed-comment-count text-gray-500 dark:text-gray-400" data-post-id="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars(t('0 commenti')); ?></span>
                <?php endif; ?>
            </footer>
            <?php if ($comment_count > 0): ?>
            <section id="comments-post-<?php echo (int)$p['id']; ?>" class="feed-comments mt-3 pt-3" aria-label="<?php echo htmlspecialchars(t('Commenti')); ?>">
                <ul class="feed-comments-list list-none m-0 p-0 text-sm space-y-2" data-post-id="<?php echo (int)$p['id']; ?>">
                    <?php foreach ($post_comments as $c): ?>
                    <li class="text-gray-700 dark:text-gray-300"><a href="<?php echo htmlspecialchars($bp); ?>/?ACT=AGENT&amp;id=<?php echo (int)($c['agent_id'] ?? 0); ?>" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($c['user_alias'] ?? t('Agente')); ?></a> <span class="text-gray-500 dark:text-gray-400"><?php echo date('d/m H:i', strtotime($c['created_at'])); ?></span><br/><?php echo nl2br(htmlspecialchars($c['body'])); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>
        </div>
    </article>

    <div class="post-copy-url mt-6 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
        <label for="post-permalink-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?php echo htmlspecialchars(t('Link per condividere')); ?></label>
        <div class="flex flex-wrap gap-2 items-center">
            <input type="text" id="post-permalink-input" class="post-permalink-input flex-1 min-w-0 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 read-only focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($post_permalink); ?>" readonly aria-label="URL del post"/>
            <button type="button" class="post-copy-btn shrink-0 px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors" aria-live="polite" aria-label="<?php echo htmlspecialchars(t('Copia link negli appunti')); ?>" data-copied-label="<?php echo htmlspecialchars(t('Copiato!')); ?>"><?php echo htmlspecialchars(t('Copia')); ?></button>
        </div>
        <p class="post-copy-feedback mt-2 text-sm text-green-600 dark:text-green-400 hidden" role="status" aria-live="polite"><?php echo htmlspecialchars(t('Link copiato negli appunti.')); ?></p>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400 mt-6">
        <a href="<?php echo htmlspecialchars($bp); ?>/" class="hover:underline">← <?php echo htmlspecialchars(t('Torna al feed')); ?></a>
        · <a href="<?php echo htmlspecialchars($agent_link); ?>" class="hover:underline"><?php echo htmlspecialchars(t('Profilo di')); ?> <?php echo htmlspecialchars($p['agent_name'] ?? 'agente'); ?></a>
    </p>
</div>
<script>
(function() {
    var box = document.querySelector('.post-copy-url');
    if (!box) return;
    var input = box.querySelector('.post-permalink-input');
    var btn = box.querySelector('.post-copy-btn');
    var feedback = box.querySelector('.post-copy-feedback');
    function showCopied() {
        if (!feedback || !btn) return;
        feedback.classList.remove('hidden');
        btn.textContent = btn.getAttribute('data-copied-label') || 'Copiato!';
        btn.disabled = true;
        /* L'avviso resta visibile; il pulsante resta "Copiato!" e disabilitato */
    }
    function copyUrl() {
        if (!input) return;
        var text = input.value;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(showCopied).catch(function() {
                fallbackCopy();
            });
            return;
        }
        fallbackCopy();
    }
    function fallbackCopy() {
        input.select();
        input.setSelectionRange(0, 99999);
        try {
            if (document.execCommand('copy')) showCopied();
        } catch (e) {}
    }
    if (btn) btn.addEventListener('click', copyUrl);
    if (input) input.addEventListener('click', function() { copyUrl(); });
})();
</script>
