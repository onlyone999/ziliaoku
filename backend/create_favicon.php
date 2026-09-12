<?php
/**
 * Generate a simple 32x32 favicon ICO file
 * Blue background (#4A90D9) with white "Z" letter
 */

$size = 32;

// Create image
$img = imagecreatetruecolor($size, $size);

// Colors
$bg = imagecolorallocate($img, 0x4A, 0x90, 0xD9);
$white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);

// Fill background with rounded corners effect
imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $bg);

// Draw a darker border for definition
$border = imagecolorallocate($img, 0x3A, 0x78, 0xC0);
imagerectangle($img, 0, 0, $size - 1, $size - 1, $border);

// Draw "Z" letter - using a bold stroke approach
// We draw the Z with thick lines
$zColor = $white;

// Top horizontal bar of Z
for ($i = 0; $i < 4; $i++) {
    imageline($img, 6, 6 + $i, 26, 6 + $i, $zColor);
}

// Diagonal bar of Z
for ($offset = -1; $offset <= 1; $offset++) {
    imageline($img, 6 + $offset, 8, 26 + $offset, 24, $zColor);
    imageline($img, 6, 8 + $offset, 26, 24 + $offset, $zColor);
}

// Bottom horizontal bar of Z
for ($i = 0; $i < 4; $i++) {
    imageline($img, 6, 25 + $i, 26, 25 + $i, $zColor);
}

// Build ICO file
// ICO header: reserved(2) + type(2) + count(2)
$icoHeader = pack('vvv', 0, 1, 1);

// ICO directory entry: width(1) + height(1) + colors(1) + reserved(1) + planes(2) + bpp(2) + size(4) + offset(4)
$imageData = '';

// Get PNG data from GD
ob_start();
imagepng($img);
$pngData = ob_get_clean();

// For ICO, we use a BMP with alpha (BITMAPINFOHEADER)
// Let's create a 32bpp BMP
$width = $size;
$height = $size;
$bitsPerPixel = 32;
$pixelDataSize = $width * $height * 4;
$bitmapSize = 40 + $pixelDataSize; // BITMAPINFOHEADER + pixels
$imageDataSize = $bitmapSize + ($width * $height / 8); // + AND mask

// BITMAPINFOHEADER
$bitmapHeader = pack('VvvVVvvVVvv',
    40,            // biSize
    $width,        // biWidth
    $height * 2,   // biHeight (doubled for ICO - includes AND mask)
    1,             // biPlanes
    $bitsPerPixel, // biBitCount
    0,             // biCompression
    $imageDataSize,// biSizeImage
    0,             // biXPelsPerMeter
    0,             // biYPelsPerMeter
    0,             // biClrUsed
    0              // biClrImportant
);

// Pixel data (BGRA, bottom-to-top)
$pixelData = '';
for ($y = $height - 1; $y >= 0; $y--) {
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $a = 127; // fully opaque in ICO alpha (127=opaque, 0=transparent in some impls)
        $pixelData .= pack('CCCC', $b, $g, $r, $a);
    }
}

// AND mask (all zeros = fully opaque)
$andMask = str_repeat("\x00", ($width * $height) / 8);

$imageData = $bitmapHeader . $pixelData . $andMask;

// ICO directory entry
$dirEntry = pack('CCCCvvVV',
    $size < 256 ? $size : 0,  // width (0 means 256)
    $size < 256 ? $size : 0,  // height
    0,                         // color count
    0,                         // reserved
    1,                         // planes
    32,                        // bits per pixel
    strlen($imageData),        // image data size
    22                         // offset (6 header + 16 dir entry)
);

$icoData = $icoHeader . $dirEntry . $imageData;

$outputPath = __DIR__ . '/favicon.ico';
file_put_contents($outputPath, $icoData);

imagedestroy($img);

echo "Favicon created: {$outputPath}\n";
echo "Size: " . strlen($icoData) . " bytes\n";
