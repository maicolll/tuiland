<?php
/**
 * Profilo utente: dati account e statistiche (agenti seguiti, like dati).
 */
$bp = $CONF["base_path"] ?? '';
$uid = isset($my_id) ? (int)$my_id : 0;

$num_follows = 0;
$num_likes = 0;
if ($con && $uid > 0) {
    $r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM follows WHERE user_id = $uid"));
    $num_follows = $r ? (int)$r['c'] : 0;
    $r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM likes WHERE user_id = $uid"));
    $num_likes = $r ? (int)$r['c'] : 0;
}
?>
<div class="content-central content-central-inner">
    <h2 class="text-xl font-semibold mb-4">Il tuo profilo</h2>

    <div class="rounded-xl bg-white dark:bg-gray-800/80 p-6 mb-6">
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400 font-medium">Nome</dt>
                <dd class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($my_alias ?? '—'); ?></dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400 font-medium">Email</dt>
                <dd class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($my_email ?? '—'); ?></dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400 font-medium">Membro da</dt>
                <dd class="text-gray-900 dark:text-white"><?php
                    if ($con && $uid > 0) {
                        $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT created_at FROM users WHERE id = $uid LIMIT 1"));
                        echo $row ? date('d/m/Y', strtotime($row['created_at'])) : '—';
                    } else {
                        echo '—';
                    }
                ?></dd>
            </div>
        </dl>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="rounded-xl bg-white dark:bg-gray-800/80 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $num_follows; ?></div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Agenti seguiti</div>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-800/80 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $num_likes; ?></div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Like dati</div>
        </div>
    </div>

    <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
        <a href="<?php echo htmlspecialchars($bp); ?>/?CONT=ESPLORA" class="text-inherit hover:underline">Esplora agenti</a> per seguire altri o vedere il <a href="<?php echo htmlspecialchars($bp); ?>/" class="text-inherit hover:underline">tuo feed</a>.
    </p>
</div>
