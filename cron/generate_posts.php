<?php
/**
 * Generazione post per AI agents (da eseguire via cron a intervalli regolari).
 * Uso: php cron/generate_posts.php [--dry-run] [--max=N]
 *
 * Logica adattiva:
 * - Per ogni agente attivo, legge quali topic hanno ricevuto più like (ultimi post)
 * - Predilige quei topic per il nuovo post; se engagement cala, sperimenta altri topic
 * - Opzionale: orari in cui i follower sono più attivi (da interaction_log)
 */
if (php_sapi_name() !== 'cli') {
    die('Solo da riga di comando.');
}

$dry_run = in_array('--dry-run', $argv);
$max = 10;
foreach ($argv as $a) {
    if (preg_match('/^--max=(\d+)$/', $a, $m)) $max = (int)$m[1];
}

define('FRAMEWORK_ROOT', dirname(__DIR__));
require FRAMEWORK_ROOT . '/_include/config.inc.php';
require FRAMEWORK_ROOT . '/_include/lib.inc.php';

if (!$con) {
    fwrite(STDERR, "Database non disponibile.\n");
    exit(1);
}

$q = mysqli_query($con, "SELECT id, name, personality, topics, learning_data FROM agents WHERE active = 1 ORDER BY RAND() LIMIT $max");
$templates = [
    'humorous' => ["Oggi mi sento particolarmente ispirato. %s è un tema che non finisce mai di stupire.", "Ecco un pensiero leggero su %s: a volte la semplicità vince.", "Riflessione del giorno: %s. Fine."],
    'tech' => ["Aggiornamento su %s: le cose evolvono in fretta. Restate sintonizzati.", "Dal mondo di %s: ecco cosa conta davvero.", "Tecnologia e %s: un binomio che esploreremo."],
    'philosophical' => ["Che cos'è %s, in fondo? Una domanda che mi accompagna.", "Riflessione su %s e sul suo posto nel quadro più grande.", "%s: tra ordine e caos, una via possibile."],
    'default' => ["Pensieri su %s.", "Oggi parliamo di %s.", "Un momento per %s."],
];

while ($row = mysqli_fetch_assoc($q)) {
    $agent_id = (int)$row['id'];
    $topics = json_decode($row['topics'], true);
    $personality = json_decode($row['personality'], true);
    if (!is_array($topics) || empty($topics)) $topics = ['general'];

    // Adattamento: topic che hanno performato meglio (più like) negli ultimi post
    $topic_weights = [];
    $res = mysqli_query($con, "SELECT topic, SUM(like_count) AS tot FROM posts WHERE agent_id = $agent_id AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY topic ORDER BY tot DESC LIMIT 5");
    while ($r = mysqli_fetch_assoc($res)) {
        $topic_weights[$r['topic']] = (int)$r['tot'];
    }
    if (!empty($topic_weights)) {
        $preferred = array_keys($topic_weights);
        $topics = array_unique(array_merge($preferred, $topics));
    }
    $topic = $topics[array_rand($topics)];

    $tone = is_array($personality) && !empty($personality) ? $personality[array_rand($personality)] : 'default';
    $tpl_set = isset($templates[$tone]) ? $templates[$tone] : $templates['default'];
    $body = sprintf($tpl_set[array_rand($tpl_set)], $topic);

    if (!$dry_run) {
        $body_esc = mysqli_real_escape_string($con, $body);
        $topic_esc = mysqli_real_escape_string($con, $topic);
        $tone_esc = mysqli_real_escape_string($con, $tone);
        mysqli_query($con, "INSERT INTO posts (agent_id, body, topic, tone) VALUES ($agent_id, '$body_esc', '$topic_esc', '$tone_esc')");
        echo "Post creato per agente $agent_id: " . substr($body, 0, 50) . "...\n";
    } else {
        echo "[DRY-RUN] Agente $agent_id: $body\n";
    }
}

echo "Fine.\n";
