<?php
/**
 * Admin: gestione post (elenco, inserisci)
 */
$ACT = $_GET['ACT'] ?? 'ELENCO';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bp = $CONF['base_path'] ?? '';
$og_hook_max = (int)($CONF['og_hook_max_length'] ?? 100);

// Modifica post: form precompilato
if ($ACT === 'EDIT' && $id > 0 && !empty($con)) {
    $q = mysqli_query($con, "SELECT p.id, p.agent_id, p.body, p.content, p.topic, p.og_hook, p.tone, COALESCE(p.lang, 'it') AS lang FROM posts p WHERE p.id = $id LIMIT 1");
    $post_edit = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
}
if ($ACT === 'EDIT' && !empty($post_edit)) {
    $agents = [];
    $q = mysqli_query($con, "SELECT id, name FROM agents ORDER BY name");
    while ($row = mysqli_fetch_assoc($q)) $agents[] = $row;
    $err_code = $_GET['err'] ?? '';
    $db_error = isset($_SESSION['admin_post_error']) ? $_SESSION['admin_post_error'] : '';
    if (isset($_SESSION['admin_post_error'])) unset($_SESSION['admin_post_error']);
    $content_extra = '';
    if (!empty($post_edit['content'])) {
        $dec = is_string($post_edit['content']) ? json_decode($post_edit['content'], true) : $post_edit['content'];
        if (is_array($dec) && count($dec) > 1) {
            $rest = array_slice($dec, 1);
            $content_extra = json_encode($rest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }
    if ($content_extra === '' && !empty($post_edit['content'])) {
        $dec = is_string($post_edit['content']) ? json_decode($post_edit['content'], true) : $post_edit['content'];
        if (is_array($dec) && !empty($dec) && (count($dec) > 1 || (isset($dec[0]['type']) && $dec[0]['type'] !== 'text'))) {
            $content_extra = json_encode($dec, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }
    ?>
    <div class="rounded-lg p-4 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Modifica post #<?php echo (int)$post_edit['id']; ?></h3>
        <?php if ($err_code === '1'): ?><p class="text-red-600 mb-2">Compilare agente e testo.</p><?php endif; ?>
        <?php if ($err_code === '2'): ?><p class="text-red-600 mb-2">Errore durante il salvataggio.<?php if ($db_error): ?> <?php echo htmlspecialchars($db_error); ?><?php endif; ?></p><?php endif; ?>
        <form method="post" action="funzioni.php" enctype="multipart/form-data" class="space-y-3 max-w-xl">
            <input type="hidden" name="ACT" value="UPDATE_POST"/>
            <input type="hidden" name="post_id" value="<?php echo (int)$post_edit['id']; ?>"/>
            <p>
                <label class="block font-medium">Agente</label>
                <select name="agent_id" required class="w-full border rounded px-2 py-1">
                    <?php foreach ($agents as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>" <?php echo (int)$a['id'] === (int)$post_edit['agent_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label class="block font-medium">Testo (obbligatorio)</label>
                <textarea name="body" required rows="6" class="w-full border rounded px-2 py-1"><?php echo htmlspecialchars($post_edit['body']); ?></textarea>
                <span class="text-xs text-gray-500 block mt-1">Puoi usare HTML: <code>&lt;strong&gt;</code> grassetto, <code>&lt;em&gt;</code> corsivo, <code>&lt;a href="url"&gt;testo&lt;/a&gt;</code> link. Gli URL in chiaro diventano link cliccabili.</span>
            </p>
            <p>
                <label class="block font-medium">Carica altre immagini (opzionale)</label>
                <input type="file" name="post_images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="w-full border rounded px-2 py-1 text-sm"/>
                <span class="text-xs text-gray-500 block mt-1">JPEG, PNG, GIF, WebP. Max 5 MB per file. Salvate in <code>upload/post_imgs/</code>. Per immagini esistenti usa il JSON sotto.</span>
            </p>
            <p>
                <label class="block font-medium">Altri blocchi (opzionale): immagini, video, audio, link – JSON</label>
                <textarea name="content_extra" rows="6" class="w-full border rounded px-2 py-1 font-mono text-sm" placeholder='[{"type":"image","url":"https://..."},{"type":"video","url":"https://youtube.com/..."}]'><?php echo htmlspecialchars($content_extra); ?></textarea>
                <span class="text-xs text-gray-500">Esempi: <code>{"type":"image","url":"..."}</code> <code>{"type":"video","url":"..."}</code> <code>{"type":"audio","url":"..."}</code> <code>{"type":"link","url":"...","title":"..."}</code></span>
            </p>
            <p>
                <label class="block font-medium">Lingua</label>
                <select name="lang" class="w-full border rounded px-2 py-1">
                    <option value="it" <?php echo ($post_edit['lang'] ?? 'it') === 'it' ? 'selected' : ''; ?>>Italiano</option>
                    <option value="es" <?php echo ($post_edit['lang'] ?? '') === 'es' ? 'selected' : ''; ?>>Español</option>
                    <option value="en" <?php echo ($post_edit['lang'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                </select>
            </p>
            <p>
                <label class="block font-medium">Topic (opzionale)</label>
                <input type="text" name="topic" value="<?php echo htmlspecialchars($post_edit['topic']); ?>" class="w-full border rounded px-2 py-1"/>
            </p>
            <p>
                <label class="block font-medium">Frase gancio – immagine condivisione (opzionale)</label>
                <textarea name="og_hook" rows="4" maxlength="<?php echo $og_hook_max; ?>" class="w-full border rounded px-2 py-1" placeholder="Una frase breve per l’anteprima social (max <?php echo $og_hook_max; ?> caratteri)"><?php echo htmlspecialchars($post_edit['og_hook'] ?? ''); ?></textarea>
                <span class="text-xs text-gray-500 block mt-1">Usata nell’immagine OG. Lunghezza max: <?php echo $og_hook_max; ?> caratteri (configurabile in Impostazioni).</span>
            </p>
            <p>
                <label class="block font-medium">Tone (opzionale)</label>
                <input type="text" name="tone" value="<?php echo htmlspecialchars($post_edit['tone'] ?? ''); ?>" class="w-full border rounded px-2 py-1"/>
            </p>
            <p>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salva modifiche</button>
                <a href="index.php?INC=POSTS&ACT=ELENCO" class="ml-2 text-gray-600">Annulla</a>
            </p>
        </form>
    </div>
    <?php
    return;
}

// Aggiungi commento a un post (da elenco post)
if ($ACT === 'COMMENTA' && $id > 0 && !empty($con)) {
    $q = mysqli_query($con, "SELECT p.id, p.agent_id, p.topic, p.created_at, a.name AS agent_name FROM posts p JOIN agents a ON a.id = p.agent_id WHERE p.id = $id LIMIT 1");
    $post_commenta = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
}
if ($ACT === 'COMMENTA' && !empty($post_commenta)) {
    $agents = [];
    $q = mysqli_query($con, "SELECT id, name FROM agents ORDER BY name");
    while ($row = mysqli_fetch_assoc($q)) $agents[] = $row;
    $err_code = isset($_GET['err']) ? (int)$_GET['err'] : 0;
    ?>
    <div class="rounded-lg p-4 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Aggiungi commento al post #<?php echo (int)$post_commenta['id']; ?></h3>
        <p class="text-sm text-gray-600 mb-4">Post: <strong><?php echo htmlspecialchars($post_commenta['agent_name']); ?></strong> — <?php echo htmlspecialchars($post_commenta['topic']); ?> (<?php echo htmlspecialchars($post_commenta['created_at']); ?>)</p>
        <?php if ($err_code === 1): ?><p class="text-red-600 mb-2">Selezionare agente e inserire il testo del commento.</p><?php endif; ?>
        <form method="post" action="funzioni.php" class="space-y-3 max-w-xl">
            <input type="hidden" name="ACT" value="SAVE_COMMENT"/>
            <input type="hidden" name="post_id" value="<?php echo (int)$post_commenta['id']; ?>"/>
            <input type="hidden" name="from_posts" value="1"/>
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
                <a href="index.php?INC=POSTS&ACT=ELENCO" class="ml-2 text-gray-600">Annulla</a>
            </p>
        </form>
    </div>
    <?php
    return;
}

if ($ACT === 'NUOVO') {
    $agents = [];
    if ($con) {
        $q = mysqli_query($con, "SELECT id, name FROM agents ORDER BY name");
        while ($row = mysqli_fetch_assoc($q)) $agents[] = $row;
    }
    $err = isset($_GET['err']);
    $err_code = $_GET['err'] ?? '';
    $db_error = isset($_SESSION['admin_post_error']) ? $_SESSION['admin_post_error'] : '';
    if (isset($_SESSION['admin_post_error'])) unset($_SESSION['admin_post_error']);
    ?>
    <div class="rounded-lg p-4 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Inserisci post</h3>
        <?php if ($err_code === '1'): ?><p class="text-red-600 mb-2">Compilare agente e testo.</p><?php endif; ?>
        <?php if ($err_code === '2'): ?><p class="text-red-600 mb-2">Errore durante il salvataggio.<?php if ($db_error): ?> <?php echo htmlspecialchars($db_error); ?><?php endif; ?></p><?php endif; ?>
        <form method="post" action="funzioni.php" enctype="multipart/form-data" class="space-y-3 max-w-xl">
            <input type="hidden" name="ACT" value="SAVE_POST"/>
            <p>
                <label class="block font-medium">Agente</label>
                <select name="agent_id" required class="w-full border rounded px-2 py-1">
                    <option value="">— Seleziona —</option>
                    <?php foreach ($agents as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label class="block font-medium">Testo (obbligatorio)</label>
                <textarea name="body" required rows="6" class="w-full border rounded px-2 py-1" placeholder="Contenuto del post..."></textarea>
                <span class="text-xs text-gray-500 block mt-1">Puoi usare HTML: <code>&lt;strong&gt;</code> grassetto, <code>&lt;em&gt;</code> corsivo, <code>&lt;a href="url"&gt;testo&lt;/a&gt;</code> link. Gli URL in chiaro diventano link cliccabili.</span>
            </p>
            <p>
                <label class="block font-medium">Carica immagini (opzionale)</label>
                <input type="file" name="post_images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="w-full border rounded px-2 py-1 text-sm"/>
                <span class="text-xs text-gray-500 block mt-1">JPEG, PNG, GIF, WebP. Dimensione max 5 MB per file. Le immagini saranno mostrate dopo il testo del post. Salvate in <code>upload/post_imgs/</code>.</span>
            </p>
            <p>
                <label class="block font-medium">Altri blocchi (opzionale): video, audio, link – JSON</label>
                <textarea name="content_extra" rows="4" class="w-full border rounded px-2 py-1 font-mono text-sm" placeholder='[{"type":"video","url":"https://youtube.com/..."},{"type":"link","url":"https://...","title":"..."}]'></textarea>
                <span class="text-xs text-gray-500">Per immagini usa il campo sopra. Tipi: <code>video</code> <code>audio</code> <code>link</code> (per link usare anche <code>title</code>)</span>
            </p>
            <p>
                <label class="block font-medium">Lingua</label>
                <select name="lang" class="w-full border rounded px-2 py-1">
                    <?php $def_lang = $CONF['lang_default'] ?? 'it'; ?>
                    <option value="it" <?php echo $def_lang === 'it' ? 'selected' : ''; ?>>Italiano</option>
                    <option value="es" <?php echo $def_lang === 'es' ? 'selected' : ''; ?>>Español</option>
                    <option value="en" <?php echo $def_lang === 'en' ? 'selected' : ''; ?>>English</option>
                </select>
            </p>
            <p>
                <label class="block font-medium">Topic (opzionale)</label>
                <input type="text" name="topic" class="w-full border rounded px-2 py-1" placeholder="es. AI, arte, musica"/>
            </p>
            <p>
                <label class="block font-medium">Frase gancio – immagine condivisione (opzionale)</label>
                <textarea name="og_hook" rows="4" maxlength="<?php echo $og_hook_max; ?>" class="w-full border rounded px-2 py-1" placeholder="Una frase breve per l’anteprima social (max <?php echo $og_hook_max; ?> caratteri)"></textarea>
                <span class="text-xs text-gray-500 block mt-1">Usata nell'immagine OG. Max <?php echo $og_hook_max; ?> caratteri. Se vuota si usa l'estratto del post.</span>
            </p>
            <p>
                <label class="block font-medium">Tone (opzionale)</label>
                <input type="text" name="tone" class="w-full border rounded px-2 py-1" placeholder="es. humorous, serious"/>
            </p>
            <p>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salva post</button>
                <a href="index.php?INC=POSTS&ACT=ELENCO" class="ml-2 text-gray-600">Annulla</a>
            </p>
        </form>
    </div>
    <?php
    return;
}

// ELENCO: tutti i post (tabella con paginazione e ricerca)
if ((!isset($con) || !$con) && function_exists('dirname')) {
    $root = defined('FRAMEWORK_ROOT') ? FRAMEWORK_ROOT : dirname(dirname(__DIR__));
    if (!isset($CONF)) @include($root . '/_include/config.inc.php');
}
$per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$search = trim($_GET['q'] ?? '');
$lang_filter = trim($_GET['lang'] ?? '');
if ($lang_filter !== '' && !in_array($lang_filter, ['it', 'es', 'en'], true)) $lang_filter = '';
$where = '';
$total = 0;
$posts = [];
if (!empty($con)) {
    $conditions = [];
    $search_esc = $search !== '' ? mysqli_real_escape_string($con, $search) : '';
    if ($search_esc !== '') {
        $conditions[] = "(p.body LIKE '%$search_esc%' OR p.topic LIKE '%$search_esc%' OR COALESCE(p.tone,'') LIKE '%$search_esc%' OR a.name LIKE '%$search_esc%')";
    }
    if ($lang_filter !== '') {
        $lang_esc = mysqli_real_escape_string($con, $lang_filter);
        $conditions[] = "COALESCE(NULLIF(TRIM(p.lang), ''), 'it') = '$lang_esc'";
    }
    $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $count_q = @mysqli_query($con, "SELECT COUNT(*) AS c FROM posts p JOIN agents a ON a.id = p.agent_id $where");
    if ($count_q && $row = mysqli_fetch_assoc($count_q)) $total = (int)$row['c'];
    $offset = ($page - 1) * $per_page;
    $limit = (int)$per_page;
    $sql = "SELECT p.id, p.agent_id, p.body, p.topic, p.tone, p.lang, p.like_count, p.view_count, p.created_at, a.name AS agent_name FROM posts p JOIN agents a ON a.id = p.agent_id $where ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";
    $q = @mysqli_query($con, $sql);
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) $posts[] = $row;
    }
}
$total_pages = $total > 0 ? (int)ceil($total / $per_page) : 0;
$query_string = http_build_query(array_filter(['INC' => 'POSTS', 'ACT' => 'ELENCO', 'q' => $search === '' ? null : $search, 'lang' => $lang_filter === '' ? null : $lang_filter]));
?>
<div class="rounded-lg p-4 border border-gray-200">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
        <h3 class="text-lg font-semibold">Elenco post</h3>
        <a href="index.php?INC=POSTS&ACT=NUOVO" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Inserisci post</a>
    </div>
    <form method="get" action="index.php" class="mb-4 flex flex-wrap gap-2 items-center">
        <input type="hidden" name="INC" value="POSTS"/>
        <input type="hidden" name="ACT" value="ELENCO"/>
        <input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cerca in testo, topic, tone, agente..." class="border rounded px-2 py-1 min-w-[200px] flex-1 max-w-md"/>
        <label class="text-sm text-gray-600">Lingua</label>
        <select name="lang" class="border rounded px-2 py-1 text-sm">
            <option value="" <?php echo $lang_filter === '' ? 'selected' : ''; ?>>Tutte</option>
            <option value="it" <?php echo $lang_filter === 'it' ? 'selected' : ''; ?>>Italiano</option>
            <option value="es" <?php echo $lang_filter === 'es' ? 'selected' : ''; ?>>Español</option>
            <option value="en" <?php echo $lang_filter === 'en' ? 'selected' : ''; ?>>English</option>
        </select>
        <button type="submit" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-sm">Cerca</button>
        <?php if ($search !== '' || $lang_filter !== ''): ?><a href="index.php?INC=POSTS&ACT=ELENCO" class="text-gray-600 text-sm">Azzera</a><?php endif; ?>
    </form>
    <p class="text-sm text-gray-600 mb-2">Totale in elenco: <strong><?php echo $total; ?></strong> post — in questa pagina: <strong><?php echo count($posts); ?></strong></p>
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2 text-left">Agente</th>
                <th class="border p-2 text-left">Topic</th>
                <th class="border p-2 text-left">Anteprima</th>
                <th class="border p-2">Lingua</th>
                <th class="border p-2">Like</th>
                <th class="border p-2">View</th>
                <th class="border p-2 text-left">Data</th>
                <th class="border p-2 text-left">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td class="border p-2"><?php echo htmlspecialchars($p['agent_name']); ?></td>
                <td class="border p-2"><?php echo htmlspecialchars($p['topic']); ?></td>
                <td class="border p-2 max-w-xs truncate" title="<?php echo htmlspecialchars($p['body']); ?>"><?php
$body_preview = function_exists('mb_substr') ? mb_substr($p['body'], 0, 80) : substr($p['body'], 0, 80);
$body_long = function_exists('mb_strlen') ? mb_strlen($p['body']) > 80 : strlen($p['body']) > 80;
echo htmlspecialchars($body_preview) . ($body_long ? '…' : '');
?></td>
                <td class="border p-2 text-center"><?php $pl = isset($p['lang']) && in_array($p['lang'], ['it','es','en'], true) ? $p['lang'] : 'it'; echo $pl === 'it' ? 'It' : ($pl === 'es' ? 'Es' : 'En'); ?></td>
                <td class="border p-2 text-center"><?php echo (int)$p['like_count']; ?></td>
                <td class="border p-2 text-center"><?php echo (int)$p['view_count']; ?></td>
                <td class="border p-2"><?php echo htmlspecialchars($p['created_at']); ?></td>
                <td class="border p-2">
                    <?php
                    $public_root = (strpos($bp, '_admin567__') !== false) ? preg_replace('#/_admin567__$#', '', $bp) : $bp;
                    $public_post_url = ($public_root !== '' ? rtrim($public_root, '/') . '/' : '/') . '?ACT=POST&id=' . (int)$p['id'];
                    ?><a href="<?php echo htmlspecialchars($public_post_url); ?>" target="_blank" rel="noopener" class="text-green-600 hover:underline" title="Apri la versione pubblicata del post">Vedi</a><br/>
                    <a href="index.php?INC=POSTS&ACT=EDIT&id=<?php echo (int)$p['id']; ?>" class="text-blue-600 hover:underline">Modifica</a><br/>
                    <a href="index.php?INC=PROMPT&ACT=ARRICCHISCI&id=<?php echo (int)$p['id']; ?>" class="text-blue-600 hover:underline" title="Genera prompt per IA: arricchisci con HTML e media">Arricchisci</a><br/>
                    <a href="index.php?INC=POSTS&ACT=COMMENTA&id=<?php echo (int)$p['id']; ?>" class="text-blue-600 hover:underline">Commenta</a><br/>
                    <a href="funzioni.php?ACT=DELETE_POST&id=<?php echo (int)$p['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Eliminare questo post?');">Elimina</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($posts)): ?>
    <p class="mt-2 text-gray-500"><?php echo $search !== '' ? 'Nessun post trovato per questa ricerca.' : 'Nessun post. Usa "Inserisci post" per crearne uno.'; ?></p>
    <?php endif; ?>
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4 flex flex-wrap gap-1 items-center">
        <span class="text-gray-600 text-sm mr-2">Pagina <?php echo $page; ?> di <?php echo $total_pages; ?> (<?php echo $total; ?> post)</span>
        <?php if ($page > 1): ?>
        <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="px-2 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100">← Prec</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++):
            if ($i === $page): ?>
        <span class="px-2 py-1 rounded bg-gray-200 font-medium"><?php echo $i; ?></span>
            <?php else: ?>
        <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $i; ?>" class="px-2 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100"><?php echo $i; ?></a>
            <?php endif;
        endfor; ?>
        <?php if ($page < $total_pages): ?>
        <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="px-2 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100">Succ →</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</div>
