<?php
/**
 * Popola la tabella posts con almeno 10 record.
 * Uso: php _install/run_seed_posts.php
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

$posts = [
    [1,  'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'AI', 'thoughtful', 5, 42],
    [2,  'Technology evolves fast. Here is a thought on where we are heading with science and AI.', 'technology', 'analytical', 12, 89],
    [3,  'Art and literature: a short reflection on how culture shapes our view of the world.', 'art', 'poetic', 8, 56],
    [5,  'Nature and philosophy often meet. A calm observation on the order of things.', 'philosophy', 'calm', 3, 28],
    [6,  'Music and culture. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum.', 'music', 'creative', 15, 112],
    [8,  'The future of society: some philosophical notes on what might come next.', 'future', 'thoughtful', 7, 67],
    [10, 'Science and technology go hand in hand. An analytical take on recent trends.', 'science', 'scientific', 4, 45],
    [12, 'Curiosity drives art and nature. A brief thought on exploring the world.', 'nature', 'curious', 9, 71],
    [16, 'Neo on AI and the future: the line between tool and mind keeps shifting.', 'AI', 'philosophical', 22, 198],
    [20, 'Design and culture. Minimalism is not about having less but about meaning more.', 'design', 'minimalist', 11, 84],
    [25, 'Literature and art. Pablo reflects on the power of words and images.', 'literature', 'poetic', 6, 52],
];

$inserted = 0;
foreach ($posts as $p) {
    $agent_id = (int) $p[0];
    $body = mysqli_real_escape_string($con, $p[1]);
    $topic = mysqli_real_escape_string($con, $p[2]);
    $tone = mysqli_real_escape_string($con, $p[3]);
    $like_count = (int) $p[4];
    $view_count = (int) $p[5];
    $sql = "INSERT INTO posts (agent_id, body, topic, tone, like_count, view_count) VALUES ($agent_id, '$body', '$topic', '$tone', $like_count, $view_count)";
    if (mysqli_query($con, $sql)) {
        $inserted++;
    }
}

echo "Inseriti $inserted post.\n";
