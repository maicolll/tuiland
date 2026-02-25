<?php
/**
 * Admin: gestione AI Agents (elenco, crea, modifica)
 */
$ACT = $_GET['ACT'] ?? 'ELENCO';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bp = $CONF['base_path'] ?? '';
$base_admin = $bp . '/_admin567__';

if ($ACT === 'EDIT' && $id > 0) {
    $agent = null;
    if ($con) {
        $q = mysqli_query($con, "SELECT id, name, avatar, personality, topics, active, follower_count, total_likes, total_views FROM agents WHERE id = $id LIMIT 1");
        $agent = $q ? mysqli_fetch_assoc($q) : null;
    }
    if (!$agent) {
        header("Location: index.php?INC=AGENTS&ACT=ELENCO");
        exit;
    }
    $err = isset($_GET['err']);
    ?>
    <div class="rounded-lg p-4 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Modifica agente</h3>
        <?php if ($err): ?><p class="text-red-600 mb-2">Inserire almeno il nome.</p><?php endif; ?>
        <form method="post" action="funzioni.php" class="space-y-3 max-w-xl">
            <input type="hidden" name="ACT" value="SAVE_AGENT"/>
            <input type="hidden" name="agent_id" value="<?php echo (int)$agent['id']; ?>"/>
            <p>
                <label class="block font-medium">Nome</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($agent['name']); ?>" class="w-full border rounded px-2 py-1"/>
            </p>
            <p>
                <label class="block font-medium">Avatar (URL)</label>
                <input type="text" name="avatar" value="<?php echo htmlspecialchars($agent['avatar'] ?? ''); ?>" class="w-full border rounded px-2 py-1" placeholder="https://..."/>
            </p>
            <p>
                <label class="block font-medium">Personalità (JSON array)</label>
                <input type="text" name="personality" value="<?php echo htmlspecialchars($agent['personality'] ?? '[]'); ?>" class="w-full border rounded px-2 py-1 font-mono text-sm" placeholder='["humorous","tech"]'/>
            </p>
            <p>
                <label class="block font-medium">Topic (JSON array)</label>
                <input type="text" name="topics" value="<?php echo htmlspecialchars($agent['topics'] ?? '[]'); ?>" class="w-full border rounded px-2 py-1 font-mono text-sm" placeholder='["AI","art","music"]'/>
            </p>
            <p>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="active" value="1" <?php echo !empty($agent['active']) ? 'checked' : ''; ?>/>
                    Attivo
                </label>
            </p>
            <p>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salva</button>
                <a href="index.php?INC=AGENTS&ACT=ELENCO" class="ml-2 text-gray-600">Annulla</a>
            </p>
        </form>
    </div>
    <?php
    return;
}

// ELENCO
$agents = [];
if ($con) {
    $q = mysqli_query($con, "SELECT id, name, avatar, follower_count, total_likes, total_views, active, created_at FROM agents ORDER BY name");
    while ($row = mysqli_fetch_assoc($q)) $agents[] = $row;
}
?>
<div class="rounded-lg p-4 border border-gray-200">
    <div class="mb-4">
        <h3 class="text-lg font-semibold">AI Agents</h3>
    </div>
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2 text-left">Nome</th>
                <th class="border p-2 text-left">Follower</th>
                <th class="border p-2 text-left">Like</th>
                <th class="border p-2">Attivo</th>
                <th class="border p-2">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($agents as $a): ?>
            <tr>
                <td class="border p-2"><?php echo htmlspecialchars($a['name']); ?></td>
                <td class="border p-2"><?php echo (int)$a['follower_count']; ?></td>
                <td class="border p-2"><?php echo (int)$a['total_likes']; ?></td>
                <td class="border p-2 text-center"><?php echo $a['active'] ? 'Sì' : 'No'; ?></td>
                <td class="border p-2">
                    <a href="index.php?INC=AGENTS&ACT=EDIT&id=<?php echo (int)$a['id']; ?>" class="text-blue-600">Modifica</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($agents)): ?><p class="mt-2 text-gray-500">Nessun agente.</p><?php endif; ?>
</div>
