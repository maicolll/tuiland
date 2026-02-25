<?php
/**
 * Compone un'immagine base OG 1200×630: sfondo viola, fumetto bianco, personaggio da avatars_200.
 * Uso: php _install/build_og_base_from_avatar.php Alex
 * Crea images/avatars_base_og_image/Alex.png
 */
if (php_sapi_name() !== 'cli') {
    die('Solo da riga di comando.');
}

$name = $argv[1] ?? '';
if ($name === '') {
    fwrite(STDERR, "Uso: php build_og_base_from_avatar.php <NomeAgente>\n");
    exit(1);
}

$base = dirname(__DIR__);
$w = 1200;
$h = 630;

// Sfondo viola (armonico con Sofia/Brenda)
$bg_r = 127;
$bg_g = 106;
$bg_b = 160;

$avatar_path = $base . '/images/avatars_200/' . strtolower($name) . '.png';
$out_path = $base . '/images/avatars_base_og_image/' . $name . '.png';

if (!is_file($avatar_path)) {
    fwrite(STDERR, "Avatar non trovato: $avatar_path\n");
    exit(1);
}

$img = imagecreatetruecolor($w, $h);
if (!$img) {
    fwrite(STDERR, "Impossibile creare immagine.\n");
    exit(1);
}

$purple = imagecolorallocate($img, $bg_r, $bg_g, $bg_b);
$white = imagecolorallocate($img, 255, 255, 255);
imagefill($img, 0, 0, $purple);

// Riquadro bianco (fumetto): stesse coordinate di og-image.php
$box_left = 80;
$box_top = 40;
$box_width = 1040;
$box_height = 280;
$radius = 40;

// Rettangolo con angoli arrotondati: 4 cerchi + 5 rettangoli
imagefilledellipse($img, $box_left + $radius, $box_top + $radius, $radius * 2, $radius * 2, $white);
imagefilledellipse($img, $box_left + $box_width - $radius, $box_top + $radius, $radius * 2, $radius * 2, $white);
imagefilledellipse($img, $box_left + $box_width - $radius, $box_top + $box_height - $radius, $radius * 2, $radius * 2, $white);
imagefilledellipse($img, $box_left + $radius, $box_top + $box_height - $radius, $radius * 2, $radius * 2, $white);
imagefilledrectangle($img, $box_left + $radius, $box_top, $box_left + $box_width - $radius, $box_top + $box_height, $white);
imagefilledrectangle($img, $box_left, $box_top + $radius, $box_left + $radius, $box_top + $box_height - $radius, $white);
imagefilledrectangle($img, $box_left + $box_width - $radius, $box_top + $radius, $box_left + $box_width, $box_top + $box_height - $radius, $white);
imagefilledrectangle($img, $box_left + $radius, $box_top + $box_height - $radius, $box_left + $box_width - $radius, $box_top + $box_height, $white);

// Punta del fumetto (triangolo verso il basso)
$cx = $box_left + (int)($box_width / 2);
$tip_y = $box_top + $box_height;
$tip_h = 24;
$tip_w = 40;
$tri = [$cx, $tip_y + $tip_h, $cx - (int)($tip_w/2), $tip_y, $cx + (int)($tip_w/2), $tip_y];
imagefilledpolygon($img, $tri, 3, $white);

// Sovrascrivi il bordo inferiore del box per unire con la punta (piccolo rettangolo)
imagefilledrectangle($img, $cx - (int)($tip_w/2), $tip_y, $cx + (int)($tip_w/2), $tip_y + $tip_h, $white);

// Carica avatar da avatars_200
$avatar = @imagecreatefrompng($avatar_path);
if (!$avatar) {
    fwrite(STDERR, "Impossibile caricare PNG: $avatar_path\n");
    imagedestroy($img);
    exit(1);
}

$aw = imagesx($avatar);
$ah = imagesy($avatar);
$avatar_size = 280;
$ax = (int)(($w - $avatar_size) / 2);
$ay = $h - $avatar_size - 50;

imagealphablending($img, true);
imagesavealpha($img, true);
imagecopyresampled($img, $avatar, $ax, $ay, 0, 0, $avatar_size, $avatar_size, $aw, $ah);
imagedestroy($avatar);

if (!is_dir(dirname($out_path))) {
    mkdir(dirname($out_path), 0755, true);
}
imagepng($img, $out_path);
imagedestroy($img);

echo "Creato: $out_path\n";
