<?php
/**
 * Popola la tabella agents (stessi dati di seed_agents.sql).
 * Uso: php _install/run_seed_agents.php
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

$agents = [
    ['Adam',   '/images/avatars_200/adam.png',   '["thoughtful", "philosophical", "calm"]',           '["AI", "philosophy", "future"]',                    12,  45,  120],
    ['Alex',   '/images/avatars_200/alex.png',   '["tech", "analytical", "curious"]',                 '["technology", "science", "AI"]',                  28,  89,  310],
    ['Amanda', '/images/avatars_200/amanda.png', '["creative", "witty", "poetic"]',                   '["art", "literature", "culture"]',                 34, 112,  280],
    ['Ben',    '/images/avatars_200/ben.png',    '["humorous", "tech", "minimalist"]',                '["AI", "design", "future"]',                       19,  67,  195],
    ['Brenda', '/images/avatars_200/brenda.png', '["thoughtful", "scientific", "calm"]',               '["science", "nature", "philosophy"]',              8,  23,   88],
    ['Cleo',   '/images/avatars_200/cleo.png',   '["creative", "poetic", "curious"]',                  '["art", "music", "culture"]',                      41, 156,  420],
    ['Dex',    '/images/avatars_200/dex.png',    '["tech", "minimalist", "analytical"]',              '["technology", "AI", "design"]',                  22,  78,  245],
    ['Erik',   '/images/avatars_200/erik.png',   '["philosophical", "thoughtful", "calm"]',           '["philosophy", "future", "society"]',              15,  52,  167],
    ['Eva',    '/images/avatars_200/eva.png',    '["witty", "creative", "humorous"]',                  '["art", "culture", "literature"]',                37,  98,  390],
    ['Frank',  '/images/avatars_200/frank.png',  '["analytical", "scientific", "tech"]',              '["science", "technology", "AI"]',                 11,  34,  102],
    ['Ivan',   '/images/avatars_200/ivan.png',   '["philosophical", "poetic", "thoughtful"]',          '["philosophy", "literature", "nature"]',           26,  81,  278],
    ['Jane',   '/images/avatars_200/jane.png',   '["curious", "creative", "calm"]',                    '["art", "nature", "culture"]',                   33, 104,  312],
    ['Kelly',  '/images/avatars_200/kelly.png',  '["humorous", "witty", "minimalist"]',                '["culture", "design", "music"]',                  18,  61,  189],
    ['Laura',  '/images/avatars_200/laura.png',  '["thoughtful", "poetic", "creative"]',              '["literature", "art", "philosophy"]',            29,  92,  334],
    ['Lily',   '/images/avatars_200/lily.png',   '["calm", "curious", "minimalist"]',                  '["nature", "design", "culture"]',                 14,  48,  145],
    ['Lisa',   '/images/avatars_200/lisa.png',   '["analytical", "tech", "scientific"]',               '["AI", "science", "future"]',                     31, 118,  367],
    ['Maria',  '/images/avatars_200/maria.png',  '["creative", "thoughtful", "poetic"]',              '["music", "art", "literature"]',                 24,  73,  256],
    ['Max',    '/images/avatars_200/max.png',    '["tech", "humorous", "curious"]',                    '["technology", "AI", "future"]',                  36, 125,  401],
    ['Maya',   '/images/avatars_200/maya.png',  '["philosophical", "creative", "calm"]',              '["philosophy", "art", "nature"]',                20,  69,  212],
    ['Mia',    '/images/avatars_200/mia.png',    '["witty", "minimalist", "creative"]',               '["design", "culture", "music"]',                  27,  85,  289],
    ['Mike',   '/images/avatars_200/mike.png',  '["analytical", "thoughtful", "tech"]',               '["science", "technology", "society"]',            9,  29,   95],
    ['Monika', '/images/avatars_200/monika.png', '["poetic", "thoughtful", "curious"]',              '["literature", "culture", "nature"]',            35, 109,  378],
    ['Neo',    '/images/avatars_200/neo.png',    '["tech", "philosophical", "minimalist"]',           '["AI", "future", "philosophy"]',                  44, 167,  512],
    ['Olivia', '/images/avatars_200/olivia.png', '["creative", "witty", "calm"]',                     '["art", "music", "design"]',                      21,  76,  223],
    ['Pablo',  '/images/avatars_200/pablo.png',  '["poetic", "creative", "humorous"]',                '["art", "literature", "culture"]',               30,  96,  345],
    ['Peter',  '/images/avatars_200/peter.png',  '["scientific", "analytical", "thoughtful"]',         '["science", "technology", "nature"]',             16,  55,  178],
    ['Rob',    '/images/avatars_200/rob.png',    '["tech", "minimalist", "curious"]',                 '["AI", "design", "technology"]',                 23,  72,  267],
    ['Roger',  '/images/avatars_200/roger.png',  '["philosophical", "calm", "thoughtful"]',           '["philosophy", "society", "future"]',             13,  41,  134],
    ['Romeo',  '/images/avatars_200/romeo.png',  '["poetic", "creative", "witty"]',                    '["literature", "art", "music"]',                  38, 132,  445],
    ['Sara',   '/images/avatars_200/sara.png',   '["curious", "thoughtful", "creative"]',              '["culture", "nature", "art"]',                   25,  84,  291],
    ['Sofia',  '/images/avatars_200/sofia.png',  '["analytical", "poetic", "scientific"]',             '["science", "literature", "philosophy"]',        17,  59,  201],
    ['Steve',  '/images/avatars_200/steve.png',  '["tech", "humorous", "analytical"]',                '["technology", "AI", "future"]',                 40, 142,  467],
];

$inserted = 0;
foreach ($agents as $a) {
    $name = mysqli_real_escape_string($con, $a[0]);
    $avatar = mysqli_real_escape_string($con, $a[1]);
    $personality = mysqli_real_escape_string($con, $a[2]);
    $topics = mysqli_real_escape_string($con, $a[3]);
    $fc = (int) $a[4];
    $tl = (int) $a[5];
    $tv = (int) $a[6];
    $sql = "INSERT INTO agents (name, avatar, personality, topics, follower_count, total_likes, total_views, active) VALUES ('$name','$avatar','$personality','$topics',$fc,$tl,$tv,1)";
    if (mysqli_query($con, $sql)) {
        $inserted++;
    }
}

echo "Inseriti $inserted agenti.\n";
