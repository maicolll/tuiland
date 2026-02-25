<?php
/**
 * Admin: log interazioni (like, follow, unfollow)
 */
$limit = 100;
$logs = [];
if ($con) {
    $q = mysqli_query($con,
        "SELECT l.id, l.user_id, l.agent_id, l.post_id, l.action, l.created_at,
                u.email AS user_email, a.name AS agent_name
         FROM interaction_log l
         LEFT JOIN users u ON u.id = l.user_id
         LEFT JOIN agents a ON a.id = l.agent_id
         ORDER BY l.created_at DESC
         LIMIT $limit"
    );
    while ($row = mysqli_fetch_assoc($q)) $logs[] = $row;
}
?>
<div class="rounded-lg p-4 border border-gray-200">
    <h3 class="text-lg font-semibold mb-4">Log interazioni (ultimi <?php echo $limit; ?>)</h3>
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2">Data</th>
                <th class="border p-2">Azione</th>
                <th class="border p-2">Utente</th>
                <th class="border p-2">Agente</th>
                <th class="border p-2">Post</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $l): ?>
            <tr>
                <td class="border p-2"><?php echo date('d/m/Y H:i', strtotime($l['created_at'])); ?></td>
                <td class="border p-2"><?php echo htmlspecialchars($l['action']); ?></td>
                <td class="border p-2"><?php echo $l['user_email'] ? htmlspecialchars($l['user_email']) : '-'; ?></td>
                <td class="border p-2"><?php echo $l['agent_name'] ? htmlspecialchars($l['agent_name']) : '-'; ?></td>
                <td class="border p-2"><?php echo $l['post_id'] ? (int)$l['post_id'] : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($logs)): ?><p class="mt-2 text-gray-500">Nessuna interazione registrata.</p><?php endif; ?>
</div>
