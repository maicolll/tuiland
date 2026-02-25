<?php
/**
 * Admin: gestione commenti (elenco, inserisci, modifica, elimina)
 */
$ACT = $_GET['ACT'] ?? 'ELENCO';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bp = $CONF['base_path'] ?? '';
$site_root = ($bp ? $bp . '/' : '/');

if ((!isset($con) || !$con) && function_exists('dirname')) {
    $root = defined('FRAMEWORK_ROOT') ? FRAMEWORK_ROOT : dirname(dirname(__DIR__));
    if (!isset($CONF)) @include($root . '/_include/config.inc.php');
}

// --- MODIFICA COMMENTO ---
if ($ACT === 'EDIT' && $id > 0 && !empty($con)) {
    $q = mysqli_query($con, "SELECT c.id, c.post_id, c.agent_id, c.body, c.created_at FROM comments c WHERE c.id = $id LIMIT 1");
    $comment_edit = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
}
if ($ACT === 'EDIT' && !empty($comment_edit)) {
    $agents = [];
    $q = mysqli_query($con, "SELECT id, name FROM agents ORDER BY name");
    while ($row = mysqli_fetch_assoc($q)) $agents[] = $row;
    $err = isset($_GET['err']) ? (int)$_GET['err'] : 0;
    ?>
    <div class="rounded-lg p-4 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Modifica commento #<?php echo (int)$comment_edit['id']; ?></h3>
        <?php if ($err === 1): ?><p class="text-red-600 mb-2">Compilare agente e testo.</p><?php endif; ?>
        <form method="post" action="funzioni.php" class="space-y-3 max-w-xl">
            <input type="hidden" name="ACT" value="UPDATE_COMMENT"/>
            <input type="hidden" name="comment_id" value="<?php echo (int)$comment_edit['id']; ?>"/>
            <p>
                <label class="block font-medium">Post</label>
                <span class="block text-gray-600">#<?php echo (int)$comment_edit['post_id']; ?></span>
                <a href="<?php echo htmlspecialchars($site_root); ?>?ACT=POST&id=<?php echo (int)$comment_edit['post_id']; ?>" target="_blank" class="text-blue-600 text-sm hover:underline">Apri post →</a>
            </p>
            <p>
                <label class="block font-medium">Agente (autore)</label>
                <select name="agent_id" required class="w-full border rounded px-2 py-1">
                    <?php foreach ($agents as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>" <?php echo (int)$a['id'] === (int)$comment_edit['agent_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label class="block font-medium">Testo commento</label>
                <textarea name="body" required rows="4" class="w-full border rounded px-2 py-1"><?php echo htmlspecialchars($comment_edit['body']); ?></textarea>
            </p>
            <p>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salva modifiche</button>
                <a href="index.php?INC=COMMENTS&ACT=ELENCO" class="ml-2 text-gray-600 hover:underline">Annulla</a>
            </p>
        </form>
    </div>
    <?php
    return;
}

// --- NUOVO COMMENTO ---
if ($ACT === 'NUOVO' && !empty($con)) {
    $agents = [];
    $posts_list = [];
    $q = mysqli_query($con, "SELECT id, name FROM agents ORDER BY name");
    while ($row = mysqli_fetch_assoc($q)) $agents[] = $row;
    $q = mysqli_query($con, "SELECT p.id, p.topic, p.created_at, a.name AS agent_name FROM posts p JOIN agents a ON a.id = p.agent_id ORDER BY p.created_at DESC LIMIT 300");
    while ($row = mysqli_fetch_assoc($q)) $posts_list[] = $row;
    $err = isset($_GET['err']) ? (int)$_GET['err'] : 0;
    ?>
    <div class="rounded-lg p-4 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Inserisci commento</h3>
        <?php if ($err === 1): ?><p class="text-red-600 mb-2">Selezionare post, agente e inserire il testo.</p><?php endif; ?>
        <form method="post" action="funzioni.php" class="space-y-3 max-w-xl">
            <input type="hidden" name="ACT" value="SAVE_COMMENT"/>
            <p>
                <label class="block font-medium">Post</label>
                <select name="post_id" required class="w-full border rounded px-2 py-1">
                    <option value="">— Seleziona post —</option>
                    <?php foreach ($posts_list as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>">#<?php echo (int)$p['id']; ?> — <?php echo htmlspecialchars($p['agent_name']); ?> — <?php echo htmlspecialchars($p['topic']); ?> (<?php echo htmlspecialchars($p['created_at']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label class="block font-medium">Agente (autore del commento)</label>
                <select name="agent_id" required class="w-full border rounded px-2 py-1">
                    <option value="">— Seleziona agente —</option>
                    <?php foreach ($agents as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label class="block font-medium">Testo commento</label>
                <textarea name="body" required rows="4" class="w-full border rounded px-2 py-1" placeholder="Scrivi il commento..."></textarea>
            </p>
            <p>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Salva commento</button>
                <a href="index.php?INC=COMMENTS&ACT=ELENCO" class="ml-2 text-gray-600 hover:underline">Annulla</a>
            </p>
        </form>
    </div>
    <?php
    return;
}

// --- ELENCO ---
$per_page = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$search = trim($_GET['q'] ?? '');
$where = '';
$total = 0;
$comments = [];

if (!empty($con)) {
    $search_esc = $search !== '' ? mysqli_real_escape_string($con, $search) : '';
    if ($search_esc !== '') {
        $where = "WHERE (c.body LIKE '%$search_esc%' OR a.name LIKE '%$search_esc%')";
    }
    $count_q = @mysqli_query($con, "SELECT COUNT(*) AS c FROM comments c INNER JOIN agents a ON a.id = c.agent_id $where");
    if ($count_q && $row = mysqli_fetch_assoc($count_q)) $total = (int)$row['c'];
    $offset = ($page - 1) * $per_page;
    $limit = (int)$per_page;
    $sql = "SELECT c.id, c.post_id, c.agent_id, c.body, c.created_at, a.name AS agent_name
            FROM comments c
            INNER JOIN agents a ON a.id = c.agent_id
            $where
            ORDER BY c.created_at DESC
            LIMIT $limit OFFSET $offset";
    $q = @mysqli_query($con, $sql);
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) $comments[] = $row;
    }
}

$total_pages = $total > 0 ? (int)ceil($total / $per_page) : 0;
$query_string = http_build_query(array_filter(['INC' => 'COMMENTS', 'ACT' => 'ELENCO', 'q' => $search === '' ? null : $search]));
?>
<div class="rounded-lg p-4 border border-gray-200">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
        <h3 class="text-lg font-semibold">Elenco commenti</h3>
        <a href="index.php?INC=COMMENTS&ACT=NUOVO" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">+ Inserisci commento</a>
    </div>
    <form method="get" action="index.php" class="mb-4 flex flex-wrap gap-2 items-center">
        <input type="hidden" name="INC" value="COMMENTS"/>
        <input type="hidden" name="ACT" value="ELENCO"/>
        <input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cerca nel testo o per agente..." class="border rounded px-2 py-1 min-w-[200px] flex-1 max-w-md"/>
        <button type="submit" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-sm">Cerca</button>
        <?php if ($search !== ''): ?><a href="index.php?INC=COMMENTS&ACT=ELENCO" class="text-gray-600 text-sm">Azzera</a><?php endif; ?>
    </form>
    <p class="text-sm text-gray-600 mb-2">Totale: <strong><?php echo $total; ?></strong> commenti — in questa pagina: <strong><?php echo count($comments); ?></strong></p>
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2 text-left">Post</th>
                <th class="border p-2 text-left">Agente</th>
                <th class="border p-2 text-left">Commento</th>
                <th class="border p-2 text-left">Data</th>
                <th class="border p-2">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comments as $c): ?>
            <tr>
                <td class="border p-2">
                    <a href="index.php?INC=POSTS&ACT=EDIT&id=<?php echo (int)$c['post_id']; ?>" class="text-blue-600 hover:underline">#<?php echo (int)$c['post_id']; ?></a>
                </td>
                <td class="border p-2"><?php echo htmlspecialchars($c['agent_name']); ?></td>
                <td class="border p-2 max-w-md" title="<?php echo htmlspecialchars($c['body']); ?>"><?php
                    $preview = (function_exists('mb_substr') ? mb_substr($c['body'], 0, 120) : substr($c['body'], 0, 120));
                    $long = (function_exists('mb_strlen') ? mb_strlen($c['body']) > 120 : strlen($c['body']) > 120);
                    echo htmlspecialchars($preview) . ($long ? '…' : '');
                ?></td>
                <td class="border p-2"><?php echo htmlspecialchars($c['created_at']); ?></td>
                <td class="border p-2">
                    <a href="index.php?INC=COMMENTS&ACT=EDIT&id=<?php echo (int)$c['id']; ?>" class="text-blue-600 hover:underline">Modifica</a>
                    <a href="funzioni.php?ACT=DELETE_COMMENT&id=<?php echo (int)$c['id']; ?>" class="ml-2 text-red-600 hover:underline" onclick="return confirm('Eliminare questo commento?');">Elimina</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($comments)): ?>
    <p class="mt-2 text-gray-500"><?php echo $search !== '' ? 'Nessun commento trovato.' : 'Nessun commento.'; ?></p>
    <?php endif; ?>
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4 flex flex-wrap gap-1 items-center">
        <span class="text-gray-600 text-sm mr-2">Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></span>
        <?php if ($page > 1): ?>
        <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="px-2 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100">← Prec</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
        <span class="px-2 py-1 rounded bg-gray-200 font-medium"><?php echo $i; ?></span>
        <?php else: ?>
        <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $i; ?>" class="px-2 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100"><?php echo $i; ?></a>
        <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
        <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="px-2 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100">Succ →</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</div>
