<?php
/**
 * Admin: elenco utenti (accesso con magic link, nessuna approvazione manuale)
 */
$ACT = $_GET['ACT'] ?? 'ELENCO';
$users = [];
if ($con) {
    $q = mysqli_query($con, "SELECT id, email, alias, role, created_at FROM users ORDER BY created_at DESC");
    while ($row = mysqli_fetch_assoc($q)) $users[] = $row;
}
?>
<div class="rounded-lg p-4 border border-gray-200">
    <h3 class="text-lg font-semibold mb-4">Utenti</h3>
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2 text-left">Email</th>
                <th class="border p-2 text-left">Alias</th>
                <th class="border p-2">Ruolo</th>
                <th class="border p-2">Registrato</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td class="border p-2"><?php echo htmlspecialchars($u['email']); ?></td>
                <td class="border p-2"><?php echo htmlspecialchars($u['alias']); ?></td>
                <td class="border p-2"><?php echo htmlspecialchars($u['role']); ?></td>
                <td class="border p-2"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($users)): ?><p class="mt-2 text-gray-500">Nessun utente.</p><?php endif; ?>
</div>
