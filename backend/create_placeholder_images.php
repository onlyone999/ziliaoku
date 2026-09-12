<?php
/**
 * 生成占位图片
 * 3张轮播图 (750x360) + 1张默认封面 (400x300)
 * 需要GD库扩展
 */

$baseDir = __DIR__;

// Ensure directories exist
$bannerDir = $baseDir . '/uploads/banners';
$coverDir  = $baseDir . '/uploads/covers';
if (!is_dir($bannerDir)) { mkdir($bannerDir, 0777, true); echo "Created: $bannerDir\n"; }
if (!is_dir($coverDir))  { mkdir($coverDir, 0777, true);  echo "Created: $coverDir\n"; }

// Helper: create gradient image with text
function createBanner($path, $w, $h, $color1, $color2, $text) {
    $img = imagecreatetruecolor($w, $h);
    // Draw vertical gradient
    for ($y = 0; $y < $h; $y++) {
        $ratio = $y / $h;
        $r = (int)($color1[0] + ($color2[0] - $color1[0]) * $ratio);
        $g = (int)($color1[1] + ($color2[1] - $color1[1]) * $ratio);
        $b = (int)($color1[2] + ($color2[2] - $color1[2]) * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w - 1, $y, $color);
    }
    // White text centered
    $white = imagecolorallocate($img, 255, 255, 255);
    // Use built-in font (5 is the largest built-in)
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);
    // For multibyte, estimate width
    $textWidth = 20 * mb_strlen($text);
    $x = (int)(($w - $textWidth) / 2);
    if ($x < 10) $x = 10;
    $y = (int)(($h - imagefontheight($font)) / 2);
    imagestring($img, $font, $x, $y, $text, $white);
    imagepng($img, $path);
    imagedestroy($img);
    echo "Created: $path\n";
}

function createCover($path, $w, $h, $text) {
    $img = imagecreatetruecolor($w, $h);
    // Grey background
    $bg = imagecolorallocate($img, 200, 200, 200);
    imagefill($img, 0, 0, $bg);
    // Darker border
    $border = imagecolorallocate($img, 170, 170, 170);
    imagerectangle($img, 0, 0, $w - 1, $h - 1, $border);
    // Dark text centered
    $dark = imagecolorallocate($img, 100, 100, 100);
    $font = 5;
    $textWidth = 20 * mb_strlen($text);
    $x = (int)(($w - $textWidth) / 2);
    if ($x < 10) $x = 10;
    $y = (int)(($h - imagefontheight($font)) / 2);
    imagestring($img, $font, $x, $y, $text, $dark);
    imagepng($img, $path);
    imagedestroy($img);
    echo "Created: $path\n";
}

// 3 Banner images (750x360)
createBanner($bannerDir . '/banner1.png', 750, 360, [41, 98, 255],  [0, 200, 255],  'Banner 1 - Resources');
createBanner($bannerDir . '/banner2.png', 750, 360, [0, 180, 100],  [100, 255, 150], 'Banner 2 - Learning');
createBanner($bannerDir . '/banner3.png', 750, 360, [130, 50, 220], [200, 100, 255], 'Banner 3 - Design');

// 1 Default cover (400x300)
createCover($coverDir . '/default.jpg', 400, 300, 'ZiYuan');

echo "\nDone! All placeholder images created.\n";
