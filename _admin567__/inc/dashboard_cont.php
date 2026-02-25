<?php
/**
 * Admin: Dashboard – sommario di oggi e ultimo piano applicato.
 */
$today_posts = 0;
$today_comments = 0;
if (isset($con) && $con) {
    $r = @mysqli_query($con, "SELECT COUNT(*) AS c FROM posts WHERE DATE(created_at) = CURDATE()");
    if ($r && $row = mysqli_fetch_assoc($r)) $today_posts = (int)$row['c'];
    $r = @mysqli_query($con, "SELECT COUNT(*) AS c FROM comments WHERE DATE(created_at) = CURDATE()");
    if ($r && $row = mysqli_fetch_assoc($r)) $today_comments = (int)$row['c'];
}
$last_plan_display = '';
if (isset($CONF['last_plan_applied_at']) && trim($CONF['last_plan_applied_at']) !== '') {
    $lp = trim($CONF['last_plan_applied_at']);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/', $lp, $dm)) {
        $last_plan_display = $dm[3] . '/' . $dm[2] . '/' . $dm[1] . ' alle ' . $dm[4] . ':' . $dm[5];
    } else {
        $last_plan_display = $lp;
    }
}
$posts_per_day = isset($CONF['posts_per_day']) ? (int)$CONF['posts_per_day'] : 3;
$comments_per_day = isset($CONF['comments_per_day']) ? (int)$CONF['comments_per_day'] : 10;
$goal_posts = $posts_per_day;
$goal_comments = $comments_per_day;
$posts_goal_reached = $goal_posts <= 0 || $today_posts >= $goal_posts;
$comments_goal_reached = $goal_comments <= 0 || $today_comments >= $goal_comments;
$today_goal_reached = ($goal_posts > 0 || $goal_comments > 0) && $posts_goal_reached && $comments_goal_reached;
?>
<div class="rounded-lg border border-gray-200 overflow-hidden">
    <div class="p-4 space-y-4">
        <section class="border rounded-lg p-4 <?php echo $today_goal_reached ? 'bg-green-50 border-green-400' : 'bg-amber-50 border-gray-200'; ?>">
            <h4 class="text-sm font-semibold mb-2 flex items-center gap-2">
                <?php if ($today_goal_reached): ?>
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white text-xs" title="Obiettivo giornaliero raggiunto">✓</span>
                <?php endif; ?>
                <span class="<?php echo $today_goal_reached ? 'text-green-800' : 'text-gray-800'; ?>">Oggi – cosa è stato fatto</span>
                <?php if ($today_goal_reached): ?>
                <span class="text-xs font-normal text-green-700">Obiettivo raggiunto</span>
                <?php endif; ?>
            </h4>
            <p class="text-sm m-0 <?php echo $today_goal_reached ? 'text-green-800' : 'text-gray-700'; ?>">
                Post creati oggi: <strong><?php echo (int)$today_posts; ?></strong><?php if ($goal_posts > 0): ?> / <?php echo $goal_posts; ?><?php endif; ?>
                · Commenti creati oggi: <strong><?php echo (int)$today_comments; ?></strong><?php if ($goal_comments > 0): ?> / <?php echo $goal_comments; ?><?php endif; ?>
            </p>
            <?php if ($last_plan_display !== ''): ?>
            <p class="text-sm mt-1 m-0 <?php echo $today_goal_reached ? 'text-green-700' : 'text-gray-600'; ?>">Ultimo piano (Aggiornamento Tuiland) applicato: <strong><?php echo htmlspecialchars($last_plan_display); ?></strong></p>
            <?php endif; ?>
        </section>
        <div class="rounded-lg p-5 bg-green-50 border-l-4 border-green-500">
            <h2 class="m-0 mb-2 text-lg font-semibold text-green-800">Tuiland – Admin</h2>
            <p class="m-0 text-gray-700">Usa il menu a sinistra: Statistiche, Elenco agenti, Post, Commenti, Approvazioni utenti, Log interazioni.</p>
        </div>
    </div>
</div>
