<?php

define('CAPTCHA_COOKIE', 'imgcaptcha_');

mt_srand(time());

define('PATH_TTF', 'fonts/');
$fonts = array('liber-mono.ttf', 'liber-sans.ttf');

$par = array(
    'WIDTH' => 120,
    'HEIGHT' => 32,
    'FONT_SIZE' => 14,
    'CHARS_COUNT' => 5,
    'ALLOWED_CHARS' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23458',
    'BG_COLOR' => '#FFFFFF',
    'LINES_COUNT' => 3,
    'LINES_THICKNESS' => 2
);

define('CODE_CHAR_COLORS', '#880000,#008800,#000088,#888800,#880088,#008888,#000000');
define('CODE_LINE_COLORS', '#880000,#008800,#000088,#888800,#880088,#008888,#000000');

$char_colors = explode(',', CODE_CHAR_COLORS);
$line_colors = explode(',', CODE_LINE_COLORS);

$img = imagecreatetruecolor($par['WIDTH'], $par['HEIGHT']);
imagefilledrectangle($img, 0, 0, $par['WIDTH'] - 1, $par['HEIGHT'] - 1, gd_color($par['BG_COLOR']));

imagesetthickness($img, $par['LINES_THICKNESS']);

for ($i = 0; $i < $par['LINES_COUNT']; $i++) {
    imageline(
        $img,
        mt_rand(0, $par['WIDTH'] - 1),
        mt_rand(0, $par['HEIGHT'] - 1),
        mt_rand(0, $par['WIDTH'] - 1),
        mt_rand(0, $par['HEIGHT'] - 1),
        gd_color($line_colors[mt_rand(0, count($line_colors) - 1)])
    );
}

$code = '';
$y = ($par['HEIGHT'] / 2) + ($par['FONT_SIZE'] / 2);

for ($i = 0; $i < $par['CHARS_COUNT']; $i++) {
    $color = gd_color($char_colors[mt_rand(0, count($char_colors) - 1)]);
    $angle = mt_rand(-45, 45);
    $char = substr($par['ALLOWED_CHARS'], mt_rand(0, strlen($par['ALLOWED_CHARS']) - 1), 1);
    $font = PATH_TTF . $fonts[mt_rand(0, count($fonts) - 1)];
    $x = (intval(($par['WIDTH'] / $par['CHARS_COUNT']) * $i) + ($par['FONT_SIZE'] / 2));

    imagettftext($img, $par['FONT_SIZE'], $angle, $x, $y, $color, $font, $char);
    $code .= $char;
}

setcookie(CAPTCHA_COOKIE, md5($code));

header("Content-Type: image/png");
imagepng($img);
imagedestroy($img);

function gd_color($html_color)
{
    return preg_match('/^#?([\dA-F]{6})$/i', $html_color, $rgb)
        ? hexdec($rgb[1]) : false;
}
