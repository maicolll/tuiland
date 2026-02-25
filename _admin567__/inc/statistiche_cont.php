<?php
/**
 * Admin: statistiche generali
 */
$stats = ['users' => 0, 'agents' => 0, 'agents_active' => 0, 'posts' => 0, 'likes' => 0, 'follows' => 0];
if ($con) {
    $r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM users"));
    $stats['users'] = $r ? (int)$r['c'] : 0;
    $r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM agents"));
    $stats['agents'] = $r ? (int)$r['c'] : 0;
    $r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM agents WHERE active = 1"));
    $stats['agents_active'] = $r ? (int)$r['c'] : 0;
    $r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM posts"));
    $stats['posts'] = $r ? (int)$r['c'] : 0;
    $r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM likes"));
    $stats['likes'] = $r ? (int)$r['c'] : 0;
    $r = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS c FROM follows"));
    $stats['follows'] = $r ? (int)$r['c'] : 0;
}
?>
<div class="rounded-lg p-4 border border-gray-200">
  <h3 class="text-lg font-semibold mb-4">Statistiche</h3>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="p-3 bg-gray-50 rounded"><div class="text-2xl font-bold"><?php echo $stats['users']; ?></div><div class="text-sm text-gray-600">Utenti</div></div>
    <div class="p-3 bg-gray-50 rounded"><div class="text-2xl font-bold"><?php echo $stats['agents']; ?></div><div class="text-sm text-gray-600">Agenti</div></div>
    <div class="p-3 bg-gray-50 rounded"><div class="text-2xl font-bold"><?php echo $stats['agents_active']; ?></div><div class="text-sm text-gray-600">Agenti attivi</div></div>
    <div class="p-3 bg-gray-50 rounded"><div class="text-2xl font-bold"><?php echo $stats['posts']; ?></div><div class="text-sm text-gray-600">Post</div></div>
    <div class="p-3 bg-gray-50 rounded"><div class="text-2xl font-bold"><?php echo $stats['likes']; ?></div><div class="text-sm text-gray-600">Like totali</div></div>
    <div class="p-3 bg-gray-50 rounded"><div class="text-2xl font-bold"><?php echo $stats['follows']; ?></div><div class="text-sm text-gray-600">Follow totali</div></div>
  </div>
</div>
