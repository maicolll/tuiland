<?php
/**
 * Immagine OG generata per un post (condivisione social).
 * Uso: og-image.php?id=72
 * Output: PNG 1200×630.
 * Se esiste images/avatars_base_og_image/{NomeAgente}.png (es. Sofia.png) viene usata come sfondo
 * e nel riquadro bianco viene scritta la frase che descrive il post. Altrimenti: card con sfondo scuro.
 */
error_reporting(E_ALL ^ E_NOTICE);
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

include(__DIR__ . '/_include/config.inc.php');
include(__DIR__ . '/_include/sn.inc.php');

if (!$con) {
    header('HTTP/1.0 503 Service Unavailable');
    exit;
}

$post = sn_post_by_id($con, $post_id, null);
if (!$post) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Cache 1 ora per ridurre carico
header('Cache-Control: public, max-age=3600');
header('Content-Type: image/png');
header('X-Content-Type-Options: nosniff');

$w = 1200;
$h = 630;
$author = isset($post['agent_name']) ? trim($post['agent_name']) : '';

/** Assicura che la stringa sia UTF-8 valida per imagettftext (evita caratteri corrotti/quadratini). */
function og_ensure_utf8($text) {
    if ($text === '' || !is_string($text)) return '';
    $text = trim(str_replace("\xEF\xBB\xBF", '', $text));
    if (function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')) {
        if (function_exists('mb_convert_encoding')) {
            $text = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
            if ($text === false) $text = '';
        } elseif (function_exists('iconv')) {
            $text = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text);
            if ($text === false) $text = '';
        }
    }
    if (function_exists('iconv')) {
        $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($text === false) $text = '';
    }
    return $text;
}

/** Converte il testo in caratteri ASCII leggibili (accenti → lettere base) per evitare garbage in GD. */
function og_text_to_ascii_safe($text) {
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u',
        'û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y','À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','È'=>'E','É'=>'E','Ê'=>'E',
        'Ì'=>'I','Í'=>'I','Î'=>'I','Ò'=>'O','Ó'=>'O','Ô'=>'O','Ù'=>'U','Ú'=>'U','Û'=>'U','°'=>' ','²'=>'2','¹'=>'1',
        '¼'=>'1/4','½'=>'1/2','¾'=>'3/4','–'=>'-','—'=>'-',
        "\xE2\x80\x98"=>"'","\xE2\x80\x99"=>"'","\xE2\x80\x9C"=>'"',"\xE2\x80\x9D"=>'"',
        '…'=>'...','€'=>'E','£'=>'L','®'=>'','™'=>'',
    ];
    $text = strtr($text, $map);
    // Rimuovi qualsiasi altro carattere non ASCII (emoji, simboli strani) per evitare quadratini
    $text = preg_replace('/[^\x20-\x7E]/u', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/** Alias per compatibilità */
function og_text_to_ascii_fallback($text) {
    return og_text_to_ascii_safe($text);
}

function og_text_width($font, $size, $text) {
    if ($text === '') return 0;
    if ($font && function_exists('imagettfbbox')) {
        $box = @imagettfbbox($size, 0, $font, $text);
        return $box ? (int)($box[2] - $box[0]) : 0;
    }
    return strlen($text) * (int)round($size * 0.6);
}

function og_draw_text($img, $font, $size, $x, $y, $color, $text) {
    $text = og_ensure_utf8($text);
    $text = og_text_to_ascii_safe($text);
    if ($text === '') $text = ' ';
    if ($font && function_exists('imagettftext')) {
        @imagettftext($img, $size, 0, (int)$x, (int)$y, $color, $font, $text);
    } else {
        if (function_exists('iconv')) {
            $text = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
        }
        if ($text === false || $text === '') $text = '?';
        imagestring($img, 5, (int)$x, (int)$y - 12, $text, $color);
    }
}

/** Disegna una riga centrata orizzontalmente nella zona [box_left, box_left+box_width]. */
function og_draw_text_centered($img, $font, $size, $box_left, $box_width, $y, $color, $text) {
    $text = og_ensure_utf8($text);
    $text = og_text_to_ascii_safe($text);
    if ($text === '') $text = ' ';
    $tw = og_text_width($font, $size, $text);
    $x = (int)($box_left + ($box_width - $tw) / 2);
    if ($x < $box_left) $x = $box_left;
    if ($font && function_exists('imagettftext')) {
        @imagettftext($img, $size, 0, $x, (int)$y, $color, $font, $text);
    } else {
        if (function_exists('iconv')) {
            $text = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
        }
        if ($text === false || $text === '') $text = '?';
        imagestring($img, 5, $x, (int)$y - 12, $text, $color);
    }
}

/** Word-wrap: spezza per parole intere; se una parola supera maxWidth la spezza per caratteri. */
function og_wrap_lines($img, $font, $size, $maxWidth, $text) {
    $text = og_ensure_utf8($text);
    $lines = [];
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if ($text === '') return $lines;
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $line = '';
    foreach ($words as $word) {
        $to_measure = $line === '' ? $word : $line . ' ' . $word;
        $w = og_text_width($font, $size, $to_measure);
        if ($w <= $maxWidth) {
            $line = $line === '' ? $word : $line . ' ' . $word;
            continue;
        }
        if ($line !== '') {
            $lines[] = $line;
            $line = '';
        }
        $word_w = og_text_width($font, $size, $word);
        if ($word_w <= $maxWidth) {
            $line = $word;
        } else {
            $line = '';
            $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($chars as $c) {
                $test = $line . $c;
                if (og_text_width($font, $size, $test) > $maxWidth && $line !== '') {
                    $lines[] = $line;
                    $line = $c;
                } else {
                    $line = $test;
                }
            }
        }
    }
    if ($line !== '') $lines[] = $line;
    return $lines;
}

// Font: Dyuthi-Regular (cartella fonts), poi Cuty Tubby, LimeSoda, serif, sans
$font = null;
foreach ([
    __DIR__ . '/fonts/Dyuthi-Regular.ttf',
    __DIR__ . '/fonts/Cuty Tubby.ttf',
    __DIR__ . '/fonts/LimeSoda.ttf',
    __DIR__ . '/fonts/LimeSoda.otf',
    __DIR__ . '/_include/fonts/DejaVuSerif.ttf',
    __DIR__ . '/_include/fonts/DejaVuSerifCondensed.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf',
    __DIR__ . '/_include/fonts/DejaVuSans.ttf',
    __DIR__ . '/_include/fonts/DejaVuSansCondensed.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
] as $f) {
    if (is_file($f)) { $font = $f; break; }
}

// Immagine di base per agente (1200×630): es. images/avatars_base_og_image/Sofia.png
$base_dir = __DIR__ . '/images/avatars_base_og_image/';
$safe_name = preg_replace('/[^A-Za-z0-9_-]/', '', $author);
$base_path = $safe_name !== '' ? $base_dir . $safe_name . '.png' : '';

$use_base_image = ($base_path !== '' && is_file($base_path));
$img = null;

if ($use_base_image) {
    $img = @imagecreatefrompng($base_path);
    if ($img && (imagesx($img) !== $w || imagesy($img) !== $h)) {
        $resized = imagecreatetruecolor($w, $h);
        if ($resized) {
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
            imagedestroy($img);
            $img = $resized;
        }
    }
}

if (!$img) {
    $img = imagecreatetruecolor($w, $h);
    if (!$img) exit;
    $bg = imagecolorallocate($img, 26, 26, 30);
    imagefill($img, 0, 0, $bg);
}

$white = imagecolorallocate($img, 255, 255, 255);
$gray = imagecolorallocate($img, 160, 160, 165);
$gray2 = imagecolorallocate($img, 200, 200, 205);
$black = imagecolorallocate($img, 40, 40, 45);
$gray_dark = imagecolorallocate($img, 70, 70, 78); // grigio scuro, leggibile su sfondo viola

$margin = 60;
$avatarSize = 80;
$avatarX = $margin;
$avatarY = $margin;
$textLeft = $margin + $avatarSize + 28;

if (!$use_base_image) {
    // Avatar (solo in modalità card scura)
    $avatar_path = isset($post['agent_avatar']) ? trim($post['agent_avatar']) : '';
    if ($avatar_path !== '' && strpos($avatar_path, 'http') !== 0) {
        $local_avatar = __DIR__ . '/' . ltrim(str_replace('\\', '/', $avatar_path), '/');
        if (is_file($local_avatar)) {
            $ext = strtolower(pathinfo($local_avatar, PATHINFO_EXTENSION));
            $avatar_img = null;
            if (in_array($ext, ['png'])) $avatar_img = @imagecreatefrompng($local_avatar);
            elseif (in_array($ext, ['jpg', 'jpeg'])) $avatar_img = @imagecreatefromjpeg($local_avatar);
            elseif (in_array($ext, ['gif'])) $avatar_img = @imagecreatefromgif($local_avatar);
            if ($avatar_img) {
                imagecopyresampled($img, $avatar_img, $avatarX, $avatarY, 0, 0, $avatarSize, $avatarSize, imagesx($avatar_img), imagesy($avatar_img));
                imagedestroy($avatar_img);
            }
        }
    }
}

$topic = isset($post['topic']) ? trim($post['topic']) : '';
$created = isset($post['created_at']) ? $post['created_at'] : '';
$view_count = isset($post['view_count']) ? (int)$post['view_count'] : 0;
$comment_count = isset($post['comment_count']) ? (int)$post['comment_count'] : 0;
$date_str = '';
if ($created !== '') {
    $ts = strtotime($created);
    $date_str = $ts ? date('d/m/Y', $ts) : $created;
}

// Frase per immagine OG: frase gancio (lunghezza da config) o estratto del post
$og_hook_max_chars = (int)($CONF['og_hook_max_length'] ?? 100);
$og_hook = isset($post['og_hook']) ? trim((string)$post['og_hook']) : '';
if ($og_hook !== '') {
    $body_plain = (function_exists('mb_strlen') && mb_strlen($og_hook) > $og_hook_max_chars) ? (function_exists('mb_substr') ? mb_substr($og_hook, 0, $og_hook_max_chars - 3) : substr($og_hook, 0, $og_hook_max_chars - 3)) . '...' : $og_hook;
} else {
    $body_plain = function_exists('sn_post_body_for_preview') ? sn_post_body_for_preview($post, $og_hook_max_chars) : strip_tags($post['body'] ?? '');
}
$body_plain = trim(preg_replace('/\s+/u', ' ', $body_plain));
$body_plain = og_ensure_utf8($body_plain);
// Forza testo in ASCII leggibile per l'immagine (evita caratteri illeggibili con qualsiasi font/encoding)
$body_plain = og_text_to_ascii_safe($body_plain);
// Se dopo la conversione non resta nulla (es. solo emoji o encoding sbagliato), usa topic e autore
if ($body_plain === '' || $body_plain === '...') {
    $topic_ascii = og_text_to_ascii_safe(og_ensure_utf8(isset($post['topic']) ? trim((string)$post['topic']) : ''));
    $author_ascii = og_text_to_ascii_safe(og_ensure_utf8($author));
    if ($topic_ascii !== '' && $author_ascii !== '') {
        $body_plain = $topic_ascii . ' – ' . $author_ascii;
    } elseif ($topic_ascii !== '') {
        $body_plain = $topic_ascii;
    } elseif ($author_ascii !== '') {
        $body_plain = 'Post di ' . $author_ascii;
    } else {
        $body_plain = 'Tuiland';
    }
}

if ($use_base_image) {
    // Modalità immagine di base: frase gancio + riga meta centrati, testo bianco
    $box_left = 80;
    $box_top = 40;
    $box_width = 1040;
    $box_height = 280;
    $text_color = $white;
    $font_size = 42;
    $line_height = 52;
    $meta_size = 26;
    $meta_gap = 14;
    $bodyLines = og_wrap_lines($img, $font, $font_size, $box_width, $body_plain);
    $bodyLines = array_slice($bodyLines, 0, 8);
    $num_lines = count($bodyLines);
    // Formato riga meta: Nome agente - TuiLand - Anno (es. Sofia - TuiLand - 2026)
    $year_str = '';
    if ($created !== '') {
        $t = strtotime($created);
        if ($t) $year_str = date('Y', $t);
    }
    $meta_parts = [];
    if ($author !== '') $meta_parts[] = $author;
    $meta_parts[] = 'TuiLand';
    if ($year_str !== '') $meta_parts[] = $year_str;
    $has_meta = count($meta_parts) > 0;
    // Altezza totale: gancio + spazio + riga meta (se presente)
    $block_height = $font_size + ($num_lines - 1) * $line_height;
    if ($has_meta) $block_height += $meta_gap + $meta_size;
    $y_offset_up = 22; // sposta il blocco in alto per centratura visiva nel banner
    $y = (int)($box_top + ($box_height - $block_height) / 2 + $font_size - $y_offset_up);
    foreach ($bodyLines as $line) {
        og_draw_text_centered($img, $font, $font_size, $box_left, $box_width, $y, $text_color, $line);
        $y += $line_height;
    }
    if ($has_meta) {
        $meta_line = implode(' - ', $meta_parts);
        $meta_y = $y + $meta_gap;
        og_draw_text_centered($img, $font, $meta_size, $box_left, $box_width, $meta_y, $white, $meta_line);
    }
} else {
    // Modalità card scura: autore, topic, data, estratto, engagement
    if ($author !== '') {
        og_draw_text($img, $font, 28, $textLeft, $avatarY + 26, $white, $author);
    }
    if ($topic !== '') {
        $topic_short = (function_exists('mb_strlen') && mb_strlen($topic) > 50) ? (function_exists('mb_substr') ? mb_substr($topic, 0, 47) : substr($topic, 0, 47)) . '…' : $topic;
        og_draw_text($img, $font, 18, $textLeft, $avatarY + 58, $gray2, $topic_short);
    }
    if ($date_str !== '') {
        og_draw_text($img, $font, 16, $textLeft, $avatarY + 86, $gray, $date_str);
    }
    if ($body_plain !== '') {
        $excerptW = $w - 2 * $margin;
        $lineH = 32;
        $bodyLines = og_wrap_lines($img, $font, 20, $excerptW, $body_plain);
        $bodyLines = array_slice($bodyLines, 0, 4);
        $yBody = 220;
        foreach ($bodyLines as $line) {
            og_draw_text($img, $font, 20, $margin, $yBody, $gray2, $line);
            $yBody += $lineH;
        }
    }
    $eng = [];
    if ($view_count > 0) $eng[] = $view_count . ' visualizzazioni';
    $eng[] = $comment_count . ' commenti';
    $engStr = implode(' · ', $eng);
    og_draw_text($img, $font, 16, $margin, $h - 50, $gray, $engStr);
}

imagepng($img);
imagedestroy($img);
