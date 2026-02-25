<?php
/**
 * Admin: gestione Post TUI (prompt inviati dalla pagina ?ACT=TUI).
 * CRUD su tabella tui_prompts (id, message, ip_address, created_at).
 */
$ACT = $_GET['ACT'] ?? 'ELENCO';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bp = $CONF['base_path'] ?? '';

$tui_table_exists = false;
if ($con) {
    $tui_table_exists = (bool)@mysqli_query($con, "SELECT 1 FROM tui_prompts LIMIT 1");
    if (!$tui_table_exists) {
        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS tui_prompts (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, message TEXT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY created_at (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tui_table_exists = (bool)@mysqli_query($con, "SELECT 1 FROM tui_prompts LIMIT 1");
    }
}

// --- EDIT: form modifica
if ($ACT === 'EDIT' && $id > 0 && $con && $tui_table_exists) {
    $q = mysqli_query($con, "SELECT id, message, ip_address, created_at FROM tui_prompts WHERE id = $id LIMIT 1");
    $tui_edit = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
}
if ($ACT === 'EDIT' && !empty($tui_edit)) {
    $err_code = $_GET['err'] ?? '';
    ?>
    <div class="rounded-lg p-4 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Modifica TUI #<?php echo (int)$tui_edit['id']; ?></h3>
        <?php if ($err_code === '1'): ?><p class="text-red-600 mb-2">Inserire il messaggio.</p><?php endif; ?>
        <form method="post" action="funzioni.php" class="space-y-3 max-w-xl">
            <input type="hidden" name="ACT" value="UPDATE_TUI_PROMPT"/>
            <input type="hidden" name="id" value="<?php echo (int)$tui_edit['id']; ?>"/>
            <p>
                <label class="block font-medium">Messaggio</label>
                <textarea name="message" required rows="8" class="w-full border rounded px-2 py-1"><?php echo htmlspecialchars($tui_edit['message']); ?></textarea>
            </p>
            <p class="text-sm text-gray-500">IP: <?php echo htmlspecialchars($tui_edit['ip_address'] ?? '—'); ?> · Data: <?php echo htmlspecialchars($tui_edit['created_at'] ?? ''); ?></p>
            <p>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salva</button>
                <a href="index.php?INC=TUI&ACT=ELENCO" class="ml-2 text-gray-600">Annulla</a>
            </p>
        </form>
    </div>
    <?php
    return;
}

// --- NUOVO: form inserimento
if ($ACT === 'NUOVO') {
    $err_code = $_GET['err'] ?? '';
    ?>
    <div class="rounded-lg p-4 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Nuova TUI</h3>
        <?php if ($err_code === '1'): ?><p class="text-red-600 mb-2">Inserire il messaggio.</p><?php endif; ?>
        <?php if ($err_code === '2'): ?><p class="text-red-600 mb-2">Errore durante il salvataggio.</p><?php endif; ?>
        <form method="post" action="funzioni.php" class="space-y-3 max-w-xl">
            <input type="hidden" name="ACT" value="SAVE_TUI_PROMPT"/>
            <p>
                <label class="block font-medium">Messaggio</label>
                <textarea name="message" required rows="8" class="w-full border rounded px-2 py-1" placeholder="Prompt narrativo TUI..."></textarea>
            </p>
            <p>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Salva TUI</button>
                <a href="index.php?INC=TUI&ACT=ELENCO" class="ml-2 text-gray-600">Annulla</a>
            </p>
        </form>
    </div>
    <?php
    return;
}

// --- ELENCO
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$total = 0;
$tui_list = [];
if ($con && $tui_table_exists) {
    $count_q = @mysqli_query($con, "SELECT COUNT(*) AS c FROM tui_prompts");
    if ($count_q && $row = mysqli_fetch_assoc($count_q)) $total = (int)$row['c'];
    $offset = ($page - 1) * $per_page;
    $q = @mysqli_query($con, "SELECT id, message, ip_address, created_at FROM tui_prompts ORDER BY created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset);
    if ($q) while ($row = mysqli_fetch_assoc($q)) $tui_list[] = $row;
}
$total_pages = $total > 0 ? (int)ceil($total / $per_page) : 0;
?>
<div class="rounded-lg p-4 border border-gray-200">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
        <h3 class="text-lg font-semibold">Elenco TUI (prompt dalla pagina TUI)</h3>
        <a href="index.php?INC=TUI&ACT=NUOVO" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Nuova TUI</a>
    </div>
    <?php if (!$tui_table_exists): ?>
    <p class="text-gray-600">La tabella <code>tui_prompts</code> non è disponibile.</p>
    <?php elseif (empty($tui_list)): ?>
    <p class="text-gray-500">Nessun prompt TUI. I visitatori possono inviarne dalla pagina pubblica <a href="<?php echo htmlspecialchars($bp ? $bp . '/' : '/'); ?>?ACT=TUI" class="text-blue-600 hover:underline" target="_blank" rel="noopener">?ACT=TUI</a>.</p>
    <?php else: ?>
    <p class="text-sm text-gray-600 mb-2">Totale: <strong><?php echo $total; ?></strong> — Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></p>
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2 text-left w-16">ID</th>
                <th class="border p-2 text-left">Messaggio</th>
                <th class="border p-2 text-left w-32">IP</th>
                <th class="border p-2 text-left w-40">Data</th>
                <th class="border p-2 text-left w-32">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tui_list as $t): ?>
            <tr>
                <td class="border p-2"><?php echo (int)$t['id']; ?></td>
                <td class="border p-2 max-w-md">
                    <?php
                    $msg = $t['message'] ?? '';
                    $preview = (function_exists('mb_strlen') ? mb_strlen($msg) : strlen($msg)) > 120 ? (function_exists('mb_substr') ? mb_substr($msg, 0, 117) : substr($msg, 0, 117)) . '…' : $msg;
                    echo htmlspecialchars($preview);
                    ?>
                </td>
                <td class="border p-2 text-gray-600"><?php echo htmlspecialchars($t['ip_address'] ?? '—'); ?></td>
                <td class="border p-2"><?php echo htmlspecialchars($t['created_at'] ?? ''); ?></td>
                <td class="border p-2">
                    <a href="index.php?INC=TUI&ACT=EDIT&id=<?php echo (int)$t['id']; ?>" class="text-blue-600 hover:underline">Modifica</a><br/>
                    <a href="funzioni.php?ACT=DELETE_TUI_PROMPT&id=<?php echo (int)$t['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Eliminare questa TUI?');">Elimina</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4 flex flex-wrap gap-1 items-center">
        <?php if ($page > 1): ?>
        <a href="index.php?INC=TUI&ACT=ELENCO&page=<?php echo $page - 1; ?>" class="px-2 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100">← Prec</a>
        <?php endif; ?>
        <span class="text-gray-600 text-sm mx-2"><?php echo $page; ?> / <?php echo $total_pages; ?></span>
        <?php if ($page < $total_pages): ?>
        <a href="index.php?INC=TUI&ACT=ELENCO&page=<?php echo $page + 1; ?>" class="px-2 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100">Succ →</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>
