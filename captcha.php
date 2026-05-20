<?php
session_start();

// Log errors instead of displaying them
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/captcha_errors.log');

if (!extension_loaded('gd')) {
    http_response_code(500);
    exit('GD library not available');
}

header("Content-Type: image/png");

ob_clean();

$code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);
$_SESSION['captcha'] = $code;

// Bigger image
$width = 220;
$height = 80;

$image = imagecreate($width, $height);

if (!$image) {
    http_response_code(500);
    exit('Failed to create image');
}

// Colors
$background = imagecolorallocate($image, 255, 255, 255);
$textColor = imagecolorallocate($image, 0, 0, 0);
$lineColor = imagecolorallocate($image, 140, 140, 140);

// Random lines
for ($i = 0; $i < 6; $i++) {
    imageline(
        $image,
        rand(0, $width),
        rand(0, $height),
        rand(0, $width),
        rand(0, $height),
        $lineColor
    );
}

// Random dots
for ($i = 0; $i < 200; $i++) {
    imagesetpixel(
        $image,
        rand(0, $width),
        rand(0, $height),
        $lineColor
    );
}

// Font path
$font = __DIR__ . '/arial.ttf';

// BIGGER text
if (file_exists($font)) {

    $fontSize = 40;

    // Get text size
    $bbox = imagettfbbox($fontSize, 0, $font, $code);

    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];

    // Center text perfectly
    $x = ($width - $textWidth) / 2;
    $y = ($height + $textHeight) / 2;

    imagettftext(
        $image,
        $fontSize,
        rand(-2, 2),
        $x,
        $y,
        $textColor,
        $font,
        $code
    );

} else {

    // fallback
    imagestring($image, 5, 70, 30, $code, $textColor);

}

// Output image
imagepng($image);
imagedestroy($image);
exit;
?>