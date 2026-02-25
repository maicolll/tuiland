<?php
/**
 * Admin: Impostazioni Tuiland (post/commenti al giorno, feed, retention, ecc.)
 */
$mess = isset($_GET['mess']) && $_GET['mess'] === 'ok';
$mess_cleanup = isset($_GET['mess']) && $_GET['mess'] === 'cleanup_ok';
$cleanup_result = isset($_SESSION['cleanup_result']) ? $_SESSION['cleanup_result'] : null;
if (isset($_SESSION['cleanup_result'])) unset($_SESSION['cleanup_result']);

$posts_per_day = isset($CONF['posts_per_day']) ? $CONF['posts_per_day'] : '3';
$comments_per_day = isset($CONF['comments_per_day']) ? $CONF['comments_per_day'] : '10';
$feed_initial = isset($CONF['feed_initial']) ? $CONF['feed_initial'] : '10';
$feed_max_total = isset($CONF['feed_max_total']) ? $CONF['feed_max_total'] : '50';
$posts_max_keep = isset($CONF['posts_max_keep']) ? $CONF['posts_max_keep'] : '0';
$langs_count = isset($lang_name) && is_array($lang_name) ? count($lang_name) : 3;
$posts_total_cap = (int)$posts_max_keep * $langs_count;
$comments_max_per_post = isset($CONF['comments_max_per_post']) ? $CONF['comments_max_per_post'] : '0';
$og_hook_max_length = isset($CONF['og_hook_max_length']) ? $CONF['og_hook_max_length'] : '100';

$posts_total_count = 0;
if (isset($con) && $con) {
    $r = @mysqli_query($con, "SELECT COUNT(*) AS c FROM posts");
    if ($r && $row = mysqli_fetch_assoc($r)) $posts_total_count = (int)$row['c'];
}
?>
<div class="rounded-lg border border-gray-200 overflow-hidden">
    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 m-0">Impostazioni Tuiland</h3>
        <p class="text-sm text-gray-600 mt-1 m-0">Obiettivi di pubblicazione, feed e limiti di retention per tenere il sito leggero.</p>
    </div>
    <div class="p-4">
        <?php if ($mess): ?><p class="text-sm text-green-700 bg-green-50 px-3 py-2 rounded mb-4">Impostazioni salvate.</p><?php endif; ?>
        <?php if ($mess_cleanup): ?>
        <p class="text-sm text-green-700 bg-green-50 px-3 py-2 rounded mb-4">
            Pulizia eseguita.
            <?php if ($cleanup_result): ?>
            <?php if ((int)$cleanup_result['posts'] > 0): ?> Post rimossi: <?php echo (int)$cleanup_result['posts']; ?>.<?php endif; ?>
            <?php if ((int)$cleanup_result['comments'] > 0): ?> Commenti rimossi: <?php echo (int)$cleanup_result['comments']; ?>.<?php endif; ?>
            <?php if ((int)$cleanup_result['posts'] === 0 && (int)$cleanup_result['comments'] === 0): ?> Nessun dato da rimuovere con i limiti attuali.<?php endif; ?>
            <?php endif; ?>
        </p>
        <?php endif; ?>
        <form method="post" action="funzioni.php" class="space-y-4 max-w-xl">
            <input type="hidden" name="ACT" value="SAVE_SETTINGS"/>
            <section class="border border-gray-200 rounded-lg p-4 bg-white">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Pubblicazione (obiettivi giornalieri)</h4>
                <p class="text-sm text-gray-600 mb-3">Quanti post e commenti il sistema deve programmare per la pubblicazione in un giorno (es. per cron o generazione automatica).</p>
                <div class="flex flex-wrap gap-4">
                    <p class="min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Post da pubblicare al giorno</label>
                        <input type="number" name="posts_per_day" min="0" max="100" value="<?php echo htmlspecialchars($posts_per_day); ?>" class="w-full border border-gray-300 rounded px-3 py-2"/>
                    </p>
                    <p class="min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Commenti da pubblicare al giorno</label>
                        <input type="number" name="comments_per_day" min="0" max="500" value="<?php echo htmlspecialchars($comments_per_day); ?>" class="w-full border border-gray-300 rounded px-3 py-2"/>
                    </p>
                </div>
            </section>
            <section class="border border-gray-200 rounded-lg p-4 bg-white">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Feed (visibilità)</h4>
                <p class="text-sm text-gray-600 mb-3">Quanti post mostrare al primo caricamento e massimo totale con «Carica altri».</p>
                <div class="flex flex-wrap gap-4">
                    <p class="min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Post iniziali nel feed</label>
                        <input type="number" name="feed_initial" min="1" max="50" value="<?php echo htmlspecialchars($feed_initial); ?>" class="w-full border border-gray-300 rounded px-3 py-2"/>
                    </p>
                    <p class="min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Massimo post totali (con Carica altri)</label>
                        <input type="number" name="feed_max_total" min="1" max="200" value="<?php echo htmlspecialchars($feed_max_total); ?>" class="w-full border border-gray-300 rounded px-3 py-2"/>
                    </p>
                </div>
            </section>
            <section class="border border-gray-200 rounded-lg p-4 bg-white">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                    Retention (mantieni il sito leggero)
                    <a href="index.php?INC=GUIDE&ACT=RETENTION" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-300 hover:bg-blue-200 text-gray-600 hover:text-blue-800 text-xs font-bold no-underline" title="Apri guida">i</a>
                </h4>
                <p class="text-sm text-gray-600 mb-3">Limiti massimi da mantenere. 0 = nessun limite. Usa «Esegui pulizia ora» per applicare; puoi anche schedulare uno script che chiami la stessa azione.</p>
                <p class="text-sm font-medium text-gray-700 mb-3">Post attualmente nel sistema: <strong><?php echo (int)$posts_total_count; ?></strong></p>
                <div class="flex flex-wrap gap-4">
                    <p class="min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Post massimi da mantenere per ogni lingua</label>
                        <input type="number" name="posts_max_keep" min="0" max="5000" value="<?php echo htmlspecialchars($posts_max_keep); ?>" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="0 = tutti"/>
                        <span class="text-xs text-gray-500">Si mantengono gli N post con <strong>interazione più recente</strong> per ciascuna lingua (creazione o ultimo commento).</span>
                        <?php if ((int)$posts_max_keep > 0): ?>
                        <span class="text-xs text-gray-600 block mt-1">Con <?php echo $langs_count; ?> lingue: <strong><?php echo (int)$posts_max_keep; ?> × <?php echo $langs_count; ?> = <?php echo $posts_total_cap; ?> post totali</strong>.</span>
                        <?php endif; ?>
                    </p>
                    <p class="min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Commenti massimi per post</label>
                        <input type="number" name="comments_max_per_post" min="0" max="1000" value="<?php echo htmlspecialchars($comments_max_per_post); ?>" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="0 = tutti"/>
                        <span class="text-xs text-gray-500">Per ogni post, i commenti più vecchi oltre questo numero vengono rimossi.</span>
                    </p>
                </div>
                <p class="mt-2">
                    <button type="submit" form="form-cleanup" class="bg-amber-600 text-white px-4 py-2 rounded text-sm font-medium">Esegui pulizia ora</button>
                    <span class="text-xs text-gray-500 ml-2">Applica i limiti sopra (salva prima le impostazioni se le hai modificate).</span>
                </p>
            </section>
            <section class="border border-gray-200 rounded-lg p-4 bg-white">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Immagine di condivisione (OG)</h4>
                <p class="text-sm text-gray-600 mb-3">Lunghezza massima della frase gancio (caratteri) usata nell'anteprima social. Valore attuale: <strong><?php echo (int)$og_hook_max_length; ?></strong> caratteri.</p>
                <p class="min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frase gancio – massimo caratteri</label>
                    <input type="number" name="og_hook_max_length" min="50" max="500" value="<?php echo htmlspecialchars($og_hook_max_length); ?>" class="w-full border border-gray-300 rounded px-3 py-2"/>
                </p>
            </section>
            <p>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium">Salva impostazioni</button>
            </p>
        </form>
        <form id="form-cleanup" method="post" action="funzioni.php" class="hidden">
            <input type="hidden" name="ACT" value="RUN_CLEANUP"/>
        </form>
    </div>
</div>
