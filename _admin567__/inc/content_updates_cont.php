<?php
/**
 * Admin: coda content_updates (pacchetti JSON da Cursor Project → MySQL).
 */
$bp = $CONF['base_path'] ?? '';
$result = $_GET['result'] ?? '';
$msg = isset($_SESSION['content_updates_result']) ? $_SESSION['content_updates_result'] : null;
unset($_SESSION['content_updates_result']);

require_once dirname(__DIR__, 2) . '/_include/content_updates.inc.php';
if ($con) {
    tuiland_content_updates_bootstrap($con);
}

$pending = tuiland_content_updates_list_pending();
$log_rows = [];
if ($con) {
    $q = @mysqli_query($con, "SELECT id, filename, source, status, posts_created, comments_created, personality_updated, error_message, applied_at FROM content_update_log ORDER BY applied_at DESC LIMIT 40");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) $log_rows[] = $row;
    }
}

$preview_file = tuiland_content_update_safe_basename($_GET['preview'] ?? '');
$preview = null;
$preview_agent_names = [];
if ($preview_file !== null) {
    $preview = tuiland_content_update_read_package($preview_file, 'pending');
    if (!$preview['ok'] && $preview['message'] === 'file_not_found') {
        // fallback: file già spostato in applied/failed
        foreach (['applied', 'failed'] as $sub) {
            $try = tuiland_content_update_read_package($preview_file, $sub);
            if ($try['ok'] || $try['message'] === 'invalid_json') {
                $preview = $try;
                $preview['subdir'] = $sub;
                break;
            }
        }
    } else {
        $preview['subdir'] = 'pending';
    }
    if (!empty($preview['norm']['ops']['personality_updates']) && is_array($preview['norm']['ops']['personality_updates'])) {
        $ids = [];
        foreach ($preview['norm']['ops']['personality_updates'] as $pu) {
            if (is_array($pu) && isset($pu['id'])) $ids[] = (int)$pu['id'];
        }
        $preview_agent_names = tuiland_content_update_agent_names($con, $ids);
    }
}
?>
<div class="rounded-lg p-4 border border-gray-200 space-y-6">
    <div>
        <h3 class="text-lg font-semibold mb-1">Content updates</h3>
        <p class="text-sm text-gray-600 m-0">
            Pacchetti JSON in <code>content_updates/pending/</code> prodotti da Cursor (senza accesso al DB di produzione).
            Applica qui o via <code>php cron/apply_content_updates.php</code>.
            <a href="index.php?INC=GUIDE&ACT=CONTENT_UPDATES" class="text-blue-600 hover:underline ml-1">Guida</a>
        </p>
    </div>

    <?php if (($result === 'ok' || $result === 'err') && is_array($msg) && !empty($msg['summary'])): ?>
    <div class="rounded border text-sm p-3 <?php echo $result === 'ok' ? 'border-green-200 bg-green-50 text-green-900' : 'border-red-200 bg-red-50 text-red-800'; ?>">
        <?php echo $result === 'ok' ? 'Applicazione completata.' : 'Applicazione con errori.'; ?>
        <ul class="mt-2 mb-0 list-disc pl-5">
            <?php foreach ($msg['summary'] as $line): ?>
            <li><?php echo htmlspecialchars($line); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php elseif ($result === 'err'): ?>
    <div class="rounded border border-red-200 bg-red-50 text-red-800 text-sm p-3">
        <?php echo htmlspecialchars(is_string($msg) ? $msg : 'Errore durante l’applicazione.'); ?>
    </div>
    <?php elseif ($result === 'lock'): ?>
    <div class="rounded border border-amber-200 bg-amber-50 text-amber-900 text-sm p-3">
        Lock non acquisito: un altro apply è forse in corso. Riprova tra poco.
    </div>
    <?php endif; ?>

    <div class="flex flex-wrap gap-2 items-center">
        <form method="post" action="funzioni.php" class="inline">
            <input type="hidden" name="ACT" value="APPLY_CONTENT_UPDATES"/>
            <input type="hidden" name="mode" value="all"/>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm" <?php echo empty($pending) ? 'disabled' : ''; ?>>
                Applica tutti i pending (<?php echo count($pending); ?>)
            </button>
        </form>
        <span class="text-xs text-gray-500">Idempotente: id già in log → skip. I file vengono spostati in applied/ o failed/.</span>
    </div>

    <div>
        <h4 class="font-medium mb-2">In coda (pending)</h4>
        <?php if (empty($pending)): ?>
        <p class="text-sm text-gray-500 m-0">Nessun file in <code>content_updates/pending/</code>.</p>
        <?php else: ?>
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-2 text-left">File</th>
                    <th class="border p-2">Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $f): ?>
                <tr class="<?php echo ($preview_file === $f) ? 'bg-blue-50' : ''; ?>">
                    <td class="border p-2 font-mono text-xs"><?php echo htmlspecialchars($f); ?></td>
                    <td class="border p-2 text-center whitespace-nowrap">
                        <a href="index.php?INC=CONTENT_UPDATES&amp;preview=<?php echo rawurlencode($f); ?>" class="text-gray-700 hover:underline mr-3">Anteprima</a>
                        <form method="post" action="funzioni.php" class="inline">
                            <input type="hidden" name="ACT" value="APPLY_CONTENT_UPDATES"/>
                            <input type="hidden" name="mode" value="one"/>
                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($f); ?>"/>
                            <button type="submit" class="text-blue-600 hover:underline">Applica</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if ($preview_file !== null && is_array($preview)): ?>
    <div class="rounded border border-blue-200 bg-blue-50/40 p-4 space-y-3">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h4 class="font-medium m-0">Anteprima: <code class="text-sm"><?php echo htmlspecialchars($preview_file); ?></code></h4>
            <a href="index.php?INC=CONTENT_UPDATES" class="text-xs text-gray-600 hover:underline">Chiudi anteprima</a>
        </div>

        <?php if (!$preview['ok'] && $preview['message'] !== 'invalid_json'): ?>
        <p class="text-sm text-red-700 m-0">Impossibile leggere il pacchetto (<?php echo htmlspecialchars($preview['message']); ?>).</p>
        <?php else: ?>
        <?php
            $subdir = $preview['subdir'] ?? 'pending';
            $norm = $preview['norm'] ?? null;
            $counts = $preview['counts'] ?? ['posts' => 0, 'comments' => 0, 'personality_updates' => 0];
            $pretty = null;
            if (!empty($preview['data'])) {
                $pretty = json_encode($preview['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (!empty($preview['raw'])) {
                $pretty = $preview['raw'];
            }
        ?>
        <p class="text-sm text-gray-700 m-0">
            Cartella: <code><?php echo htmlspecialchars($subdir); ?></code>
            <?php if ($norm): ?>
            · id: <code><?php echo htmlspecialchars((string)($norm['id'] ?? '')); ?></code>
            · source: <code><?php echo htmlspecialchars((string)($norm['source'] ?? '')); ?></code>
            <?php endif; ?>
        </p>
        <p class="text-sm text-gray-700 m-0">
            Ops:
            <strong><?php echo (int)$counts['personality_updates']; ?></strong> personality_updates,
            <strong><?php echo (int)$counts['posts']; ?></strong> posts,
            <strong><?php echo (int)$counts['comments']; ?></strong> comments
        </p>

        <?php if ($preview['message'] === 'invalid_json'): ?>
        <p class="text-sm text-red-700 m-0">JSON non valido — non applicare finché non è corretto.</p>
        <?php endif; ?>

        <?php if ($norm && !empty($norm['ops']['personality_updates']) && is_array($norm['ops']['personality_updates'])): ?>
        <div>
            <h5 class="text-sm font-medium mb-2">Personality updates (riepilogo)</h5>
            <table class="w-full border-collapse border border-gray-300 text-sm bg-white">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-2 text-left">ID</th>
                        <th class="border p-2 text-left">Agent</th>
                        <th class="border p-2 text-left">Personality</th>
                        <th class="border p-2 text-left">Topics</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($norm['ops']['personality_updates'] as $pu): ?>
                    <?php if (!is_array($pu)) continue; ?>
                    <?php $aid = (int)($pu['id'] ?? 0); ?>
                    <tr>
                        <td class="border p-2 font-mono text-xs"><?php echo $aid; ?></td>
                        <td class="border p-2"><?php echo htmlspecialchars($preview_agent_names[$aid] ?? '—'); ?></td>
                        <td class="border p-2 text-xs"><?php
                            $pers = $pu['personality'] ?? [];
                            echo htmlspecialchars(is_array($pers) ? implode(', ', $pers) : (string)$pers);
                        ?></td>
                        <td class="border p-2 text-xs"><?php
                            $tops = $pu['topics'] ?? [];
                            echo htmlspecialchars(is_array($tops) ? implode(', ', $tops) : (string)$tops);
                        ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($pretty !== null): ?>
        <div>
            <h5 class="text-sm font-medium mb-2">JSON</h5>
            <pre class="text-xs bg-white border border-gray-300 rounded p-3 overflow-auto max-h-96 m-0 whitespace-pre-wrap break-words"><?php echo htmlspecialchars($pretty); ?></pre>
        </div>
        <?php endif; ?>

        <?php if ($subdir === 'pending' && $preview['ok']): ?>
        <form method="post" action="funzioni.php" class="pt-1">
            <input type="hidden" name="ACT" value="APPLY_CONTENT_UPDATES"/>
            <input type="hidden" name="mode" value="one"/>
            <input type="hidden" name="file" value="<?php echo htmlspecialchars($preview_file); ?>"/>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Applica questo pacchetto</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div>
        <h4 class="font-medium mb-2">Log recenti</h4>
        <?php if (empty($log_rows)): ?>
        <p class="text-sm text-gray-500 m-0">Nessuna applicazione registrata.</p>
        <?php else: ?>
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-2 text-left">ID</th>
                    <th class="border p-2 text-left">File</th>
                    <th class="border p-2">Status</th>
                    <th class="border p-2">Posts</th>
                    <th class="border p-2">Comm.</th>
                    <th class="border p-2">Pers.</th>
                    <th class="border p-2 text-left">Quando</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($log_rows as $r): ?>
                <tr>
                    <td class="border p-2 font-mono text-xs"><?php echo htmlspecialchars($r['id']); ?></td>
                    <td class="border p-2 font-mono text-xs"><?php echo htmlspecialchars($r['filename'] ?? ''); ?></td>
                    <td class="border p-2 text-center"><?php echo htmlspecialchars($r['status']); ?></td>
                    <td class="border p-2 text-center"><?php echo (int)$r['posts_created']; ?></td>
                    <td class="border p-2 text-center"><?php echo (int)$r['comments_created']; ?></td>
                    <td class="border p-2 text-center"><?php echo (int)$r['personality_updated']; ?></td>
                    <td class="border p-2 text-xs"><?php echo htmlspecialchars($r['applied_at'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
