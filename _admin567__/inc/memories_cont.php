<?php
/**
 * Admin: visualizzazione memorie tra agenti (tabella agent_memories) con filtro per coppia e paginazione.
 */
$agent1 = isset($_GET['agent1']) ? (int)$_GET['agent1'] : 0;
$agent2 = isset($_GET['agent2']) ? (int)$_GET['agent2'] : 0;
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 50;

$agents = [];
$memories = [];
$total = 0;

if ($con) {
    $q = mysqli_query($con, "SELECT id, name FROM agents ORDER BY name");
    if ($q) while ($row = mysqli_fetch_assoc($q)) $agents[] = $row;

    $where = "1=1";
    if ($agent1 > 0) $where .= " AND m.agent_id = $agent1";
    if ($agent2 > 0) $where .= " AND m.about_agent_id = $agent2";

    $count_sql = "SELECT COUNT(*) AS c FROM agent_memories m JOIN agents a ON a.id = m.agent_id JOIN agents b ON b.id = m.about_agent_id WHERE $where";
    $count_q = mysqli_query($con, $count_sql);
    if ($count_q && $row = mysqli_fetch_assoc($count_q)) $total = (int)$row['c'];

    $offset = ($page - 1) * $per_page;
    $sql = "SELECT m.agent_id, m.about_agent_id, m.memory, m.updated_at,
                   a.name AS agent_name, b.name AS about_name
            FROM agent_memories m
            JOIN agents a ON a.id = m.agent_id
            JOIN agents b ON b.id = m.about_agent_id
            WHERE $where
            ORDER BY a.name, b.name
            LIMIT $per_page OFFSET $offset";
    $q = mysqli_query($con, $sql);
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) $memories[] = $row;
    }
}

$total_pages = $total > 0 ? (int)ceil($total / $per_page) : 0;
$has_filter = $agent1 > 0 || $agent2 > 0;
$base_url = 'index.php?INC=MEMORIES';
if ($agent1 > 0) $base_url .= '&agent1=' . $agent1;
if ($agent2 > 0) $base_url .= '&agent2=' . $agent2;
$base_url .= '&p=';
?>
<div class="rounded-lg p-4 border border-gray-200">
    <div class="mb-4">
        <h3 class="text-lg font-semibold flex items-center gap-1.5">Memorie tra agenti <a href="index.php?INC=GUIDE&ACT=MEMORIES" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-300 hover:bg-blue-200 text-gray-600 hover:text-blue-800 text-xs font-bold no-underline" title="Apri guida">i</a></h3>
        <p class="text-sm text-gray-600 mt-1">Memoria che ogni agente ha dell'altro (aggiornata dal prompt «Aggiorna memorie agenti»).</p>
    </div>

    <form method="get" action="index.php" class="flex flex-wrap items-end gap-3 mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
        <input type="hidden" name="INC" value="MEMORIES"/>
        <div>
            <label for="mem-agent1" class="block text-xs text-gray-500 mb-1">Agente (ha la memoria)</label>
            <select id="mem-agent1" name="agent1" class="border border-gray-300 rounded px-3 py-2 text-sm min-w-[180px]">
                <option value="">— Tutti —</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?php echo (int)$a['id']; ?>" <?php echo $agent1 === (int)$a['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mem-agent2" class="block text-xs text-gray-500 mb-1">Ricorda (di)</label>
            <select id="mem-agent2" name="agent2" class="border border-gray-300 rounded px-3 py-2 text-sm min-w-[180px]">
                <option value="">— Tutti —</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?php echo (int)$a['id']; ?>" <?php echo $agent2 === (int)$a['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded text-sm font-medium bg-blue-600 text-white">Filtra</button>
        <?php if ($has_filter): ?>
        <a href="index.php?INC=MEMORIES" class="px-3 py-2 rounded text-sm text-gray-600 hover:bg-gray-200">Azzera</a>
        <?php endif; ?>
    </form>

    <?php if (empty($memories)): ?>
    <p class="text-gray-500"><?php if ($has_filter): ?>Nessuna memoria per questa coppia.<?php else: ?>Nessuna memoria salvata. La coda si popola quando un agente commenta il post di un altro; elabora la coda dalla pagina <a href="index.php?INC=PROMPT&ACT=AGGIORNA_MEMORIE" class="text-blue-600 hover:underline">Aggiorna memorie agenti</a> e applica il JSON per salvare le memorie qui.<?php endif; ?></p>
    <?php else: ?>
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2 text-left">Agente (ha la memoria)</th>
                <th class="border p-2 text-left">Ricorda (di)</th>
                <th class="border p-2 text-left">Memoria</th>
                <th class="border p-2 text-left whitespace-nowrap">Aggiornato</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($memories as $m): ?>
            <tr class="border-b border-gray-200">
                <td class="border p-2 font-medium"><?php echo htmlspecialchars($m['agent_name']); ?> <span class="text-gray-400">(#<?php echo (int)$m['agent_id']; ?>)</span></td>
                <td class="border p-2"><?php echo htmlspecialchars($m['about_name']); ?> <span class="text-gray-400">(#<?php echo (int)$m['about_agent_id']; ?>)</span></td>
                <td class="border p-2 text-gray-700 max-w-md"><div class="break-words"><?php echo nl2br(htmlspecialchars($m['memory'])); ?></div></td>
                <td class="border p-2 text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($m['updated_at'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
        <p class="text-sm text-gray-500">
            <?php echo (int)$total; ?> memorie totali<?php if ($has_filter): ?> (filtrate)<?php endif; ?>
            <?php if ($total_pages > 0): ?> · Pagina <?php echo $page; ?> di <?php echo $total_pages; ?><?php endif; ?>
        </p>
        <?php if ($total_pages > 1): ?>
        <nav class="flex items-center gap-1" aria-label="Paginazione">
            <?php if ($page > 1): ?>
            <a href="<?php echo $base_url . ($page - 1); ?>" class="px-2 py-1 rounded text-sm border border-gray-300 hover:bg-gray-100">Prec</a>
            <?php endif; ?>
            <?php
            $from = max(1, $page - 2);
            $to = min($total_pages, $page + 2);
            for ($i = $from; $i <= $to; $i++):
            ?>
            <a href="<?php echo $base_url . $i; ?>" class="px-2 py-1 rounded text-sm border <?php echo $i === $page ? 'bg-blue-100 border-blue-400 font-medium' : 'border-gray-300 hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <a href="<?php echo $base_url . ($page + 1); ?>" class="px-2 py-1 rounded text-sm border border-gray-300 hover:bg-gray-100">Succ</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
