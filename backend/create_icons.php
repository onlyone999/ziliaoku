<?php
/**
 * Generate tabbar icons for mini program
 * Creates simple 81x81 PNG icons using GD library
 */

$iconDir = __DIR__ . '/../miniprogram/static/icons/';
if (!is_dir($iconDir)) {
    mkdir($iconDir, 0755, true);
}

$size = 81;

function createIcon($size, $color, $type) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    // Parse hex color
    $r = hexdec(substr($color, 1, 2));
    $g = hexdec(substr($color, 3, 2));
    $b = hexdec(substr($color, 5, 2));
    $fg = imagecolorallocate($img, $r, $g, $b);

    switch ($type) {
        case 'home':
            // Triangle roof
            $points = [
                $size/2, 10,     // top center
                10, 40,          // bottom left
                $size-10, 40     // bottom right
            ];
            imagefilledpolygon($img, $points, 3, $fg);
            // Rectangle body
            imagefilledrectangle($img, 18, 38, $size-18, $size-12, $fg);
            // Door (cut out with transparent)
            imagefilledrectangle($img, 32, 50, $size-32, $size-12, $transparent);
            break;

        case 'category':
            // 2x2 grid of squares
            $gap = 6;
            $boxSize = ($size - 3 * $gap) / 2;
            $x1 = $gap;
            $y1 = $gap;
            $x2 = $gap * 2 + $boxSize;
            $y2 = $gap * 2 + $boxSize;
            $cornerRadius = 6;

            // Top-left square
            imagefilledrectangle($img, $x1 + $cornerRadius, $y1, $x1 + $boxSize - $cornerRadius, $y1 + $boxSize, $fg);
            imagefilledrectangle($img, $x1, $y1 + $cornerRadius, $x1 + $boxSize, $y1 + $boxSize - $cornerRadius, $fg);
            imagefilledellipse($img, $x1 + $cornerRadius, $y1 + $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x1 + $boxSize - $cornerRadius, $y1 + $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x1 + $cornerRadius, $y1 + $boxSize - $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x1 + $boxSize - $cornerRadius, $y1 + $boxSize - $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);

            // Top-right square
            imagefilledrectangle($img, $x2 + $cornerRadius, $y1, $x2 + $boxSize - $cornerRadius, $y1 + $boxSize, $fg);
            imagefilledrectangle($img, $x2, $y1 + $cornerRadius, $x2 + $boxSize, $y1 + $boxSize - $cornerRadius, $fg);
            imagefilledellipse($img, $x2 + $cornerRadius, $y1 + $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x2 + $boxSize - $cornerRadius, $y1 + $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x2 + $cornerRadius, $y1 + $boxSize - $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x2 + $boxSize - $cornerRadius, $y1 + $boxSize - $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);

            // Bottom-left square
            imagefilledrectangle($img, $x1 + $cornerRadius, $y2, $x1 + $boxSize - $cornerRadius, $y2 + $boxSize, $fg);
            imagefilledrectangle($img, $x1, $y2 + $cornerRadius, $x1 + $boxSize, $y2 + $boxSize - $cornerRadius, $fg);
            imagefilledellipse($img, $x1 + $cornerRadius, $y2 + $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x1 + $boxSize - $cornerRadius, $y2 + $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x1 + $cornerRadius, $y2 + $boxSize - $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x1 + $boxSize - $cornerRadius, $y2 + $boxSize - $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);

            // Bottom-right square
            imagefilledrectangle($img, $x2 + $cornerRadius, $y2, $x2 + $boxSize - $cornerRadius, $y2 + $boxSize, $fg);
            imagefilledrectangle($img, $x2, $y2 + $cornerRadius, $x2 + $boxSize, $y2 + $boxSize - $cornerRadius, $fg);
            imagefilledellipse($img, $x2 + $cornerRadius, $y2 + $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x2 + $boxSize - $cornerRadius, $y2 + $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x2 + $cornerRadius, $y2 + $boxSize - $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            imagefilledellipse($img, $x2 + $boxSize - $cornerRadius, $y2 + $boxSize - $cornerRadius, $cornerRadius*2, $cornerRadius*2, $fg);
            break;

        case 'user':
            // Circle head
            $headRadius = 12;
            $headCenterX = $size / 2;
            $headCenterY = 22;
            imagefilledellipse($img, $headCenterX, $headCenterY, $headRadius * 2, $headRadius * 2, $fg);

            // Body (rounded trapezoid shape - use ellipse for shoulders + rectangle)
            $bodyTop = 38;
            $bodyBottom = $size - 10;
            $bodyLeft = 16;
            $bodyRight = $size - 16;

            // Shoulders (wide ellipse top)
            imagefilledellipse($img, $size/2, $bodyTop + 8, $bodyRight - $bodyLeft, 30, $fg);
            // Body rectangle
            imagefilledrectangle($img, $bodyLeft, $bodyTop + 8, $bodyRight, $bodyBottom, $fg);
            // Bottom rounding
            imagefilledellipse($img, $size/2, $bodyBottom, $bodyRight - $bodyLeft, 16, $fg);
            break;
    }

    return $img;
}

$grey = '#999999';
$blue = '#4A90D9';

$icons = [
    ['name' => 'home',          'color' => $grey, 'type' => 'home'],
    ['name' => 'home-active',   'color' => $blue, 'type' => 'home'],
    ['name' => 'category',      'color' => $grey, 'type' => 'category'],
    ['name' => 'category-active','color' => $blue, 'type' => 'category'],
    ['name' => 'user',          'color' => $grey, 'type' => 'user'],
    ['name' => 'user-active',   'color' => $blue, 'type' => 'user'],
];

foreach ($icons as $icon) {
    $img = createIcon($size, $icon['color'], $icon['type']);
    $path = $iconDir . $icon['name'] . '.png';
    imagepng($img, $path);
    imagedestroy($img);
    echo "Created: {$icon['name']}.png\n";
}

echo "All 6 tabbar icons generated successfully!\n";
