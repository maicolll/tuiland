<?php
/**
 * Applica pacchetti JSON da content_updates/pending/ al database.
 * Uso (CLI): php cron/apply_content_updates.php [--dry-run] [--force] [--file=name.json]
 *
 * --dry-run  elenca i pending senza applicare
 * --force    riapplica anche se id già in content_update_log (applied)
 * --file=X   applica solo quel file in pending/
 */
if (php_sapi_name() !== 'cli') {
    die('Solo da riga di comando.' . PHP_EOL);
}

$dry_run = in_array('--dry-run', $argv, true);
$force = in_array('--force', $argv, true);
$file = null;
foreach ($argv as $a) {
    if (preg_match('/^--file=(.+)$/', $a, $m)) {
        $file = basename($m[1]);
    }
}

define('FRAMEWORK_ROOT', dirname(__DIR__));
require FRAMEWORK_ROOT . '/_include/config.inc.php';
require FRAMEWORK_ROOT . '/_include/lib.inc.php';
require FRAMEWORK_ROOT . '/_include/sn.inc.php';
require FRAMEWORK_ROOT . '/_include/content_updates.inc.php';

if (!$con) {
    fwrite(STDERR, "Database non disponibile.\n");
    exit(1);
}

tuiland_content_updates_bootstrap($con);

if ($dry_run) {
    $list = $file ? [$file] : tuiland_content_updates_list_pending();
    if (empty($list)) {
        echo "Nessun pacchetto in pending.\n";
        exit(0);
    }
    echo "Pending (" . count($list) . "):\n";
    foreach ($list as $b) {
        echo "  - $b\n";
    }
    exit(0);
}

if ($file !== null) {
    $pending = tuiland_content_updates_list_pending();
    if (!in_array($file, $pending, true)) {
        fwrite(STDERR, "File non in pending: $file\n");
        exit(1);
    }
    $one = tuiland_content_update_apply_file($con, $CONF, $file, $force);
    echo sprintf(
        "[%s] id=%s posts=%d comments=%d personality=%d msg=%s\n",
        $one['status'],
        $one['id'],
        (int)($one['result']['posts'] ?? 0),
        (int)($one['result']['comments'] ?? 0),
        (int)($one['result']['personality'] ?? 0),
        $one['message']
    );
    exit($one['ok'] || $one['status'] === 'skipped' ? 0 : 1);
}

$batch = tuiland_content_updates_apply_all($con, $CONF, $force);
if (!$batch['lock']) {
    fwrite(STDERR, "Lock non acquisito (altro apply in corso?).\n");
    exit(2);
}

if (empty($batch['processed'])) {
    echo "Nessun pacchetto in pending.\n";
    exit(0);
}

$fail = 0;
foreach ($batch['processed'] as $one) {
    echo sprintf(
        "[%s] id=%s posts=%d comments=%d personality=%d msg=%s\n",
        $one['status'],
        $one['id'],
        (int)($one['result']['posts'] ?? 0),
        (int)($one['result']['comments'] ?? 0),
        (int)($one['result']['personality'] ?? 0),
        $one['message']
    );
    if (!$one['ok'] && $one['status'] !== 'skipped') $fail++;
}

echo "Fine. Falliti: $fail\n";
exit($fail > 0 ? 1 : 0);
