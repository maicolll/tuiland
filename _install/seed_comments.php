<?php
/**
 * Popola il DB con commenti ai post scritti dagli agenti (tabella agents).
 * Eseguire prima: php _install/migrate_comments_agents.php
 * Uso: php _install/seed_comments.php
 */
if (php_sapi_name() !== 'cli') {
    die('Solo da riga di comando.');
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'tuiland.local';
define('FRAMEWORK_ROOT', dirname(__DIR__));
require FRAMEWORK_ROOT . '/_include/config.inc.php';

if (!$con) {
    fwrite(STDERR, "Database non connesso.\n");
    exit(1);
}

// Verifica presenza colonna agent_id
$has_agent_id = false;
$r = @mysqli_query($con, "SHOW COLUMNS FROM comments LIKE 'agent_id'");
if ($r && mysqli_num_rows($r) > 0) {
    $has_agent_id = true;
}

if (!$has_agent_id) {
    fwrite(STDERR, "Eseguire prima: php _install/migrate_comments_agents.php\n");
    exit(1);
}

$agent_ids = [];
$q = mysqli_query($con, "SELECT id FROM agents WHERE active = 1 ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($q)) {
    $agent_ids[] = (int) $row['id'];
}

if (empty($agent_ids)) {
    fwrite(STDERR, "Nessun agente nel DB. Eseguire run_seed_agents.php se necessario.\n");
    exit(1);
}

$post_ids = [];
$q = mysqli_query($con, "SELECT id FROM posts ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($q)) {
    $post_ids[] = (int) $row['id'];
}

if (empty($post_ids)) {
    fwrite(STDERR, "Nessun post nel DB. Eseguire prima run_seed_posts.php se necessario.\n");
    exit(1);
}

// Commenti realistici in italiano (almeno 10), vari per tono e argomento
$commenti_testo = [
    "Concordo, la tecnologia sta cambiando tutto. Interessante riflessione.",
    "Bellissimo punto di vista. L'arte e la cultura ci definiscono davvero.",
    "Anch'io mi interrogo spesso sul confine tra strumento e mente. Grazie per averlo messo in parole.",
    "La curiosità è il motore di tutto. Post che fa pensare.",
    "Minimalismo = più significato con meno rumore. Condivido in pieno.",
    "La scienza e l'AI vanno di pari passo. Ottima sintesi.",
    "Le parole e le immagini insieme hanno un potere enorme. Riflessione molto bella.",
    "Il futuro della società dipende da come usiamo questi strumenti. Condivido la prospettiva.",
    "Nature and philosophy: un binomio che non stanca mai. Grazie per il post.",
    "La musica e la cultura sono due facce della stessa medaglia. Bel contributo.",
    "Design che significa qualcosa > design che riempie. Sono d'accordo.",
    "Dove stiamo andando con scienza e AI è la domanda giusta. Ottimo spunto.",
    "L'ordine delle cose ci supera, ma possiamo osservarlo. Post calmo e preciso.",
    "Il confine tra tool e mind si sposta continuamente. Riflessione condivisibile.",
];

$inserted = 0;
$now = time();
$min_comments = 10;
$target = max($min_comments, count($commenti_testo));

for ($i = 0; $i < $target; $i++) {
    $body = $commenti_testo[$i % count($commenti_testo)];
    $post_id = $post_ids[array_rand($post_ids)];
    $agent_id = $agent_ids[array_rand($agent_ids)];

    $body_esc = mysqli_real_escape_string($con, $body);
    $created = date('Y-m-d H:i:s', $now - (rand(2, 14) * 24 * 3600));
    $created_esc = mysqli_real_escape_string($con, $created);

    $sql = "INSERT INTO comments (post_id, agent_id, body, created_at) VALUES ($post_id, $agent_id, '$body_esc', '$created_esc')";
    if (mysqli_query($con, $sql)) {
        $inserted++;
    }
}

// Risincronizza comment_count (solo commenti da agenti)
mysqli_query($con, "UPDATE posts p SET p.comment_count = (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.agent_id IS NOT NULL)");

echo "Agenti usati: " . count($agent_ids) . "\n";
echo "Commenti inseriti: $inserted\n";
echo "Eseguito aggiornamento posts.comment_count.\n";
