<?php
$img = imagecreatetruecolor(32, 32);
$bg = imagecolorallocate($img, 74, 144, 217);
$fg = imagecolorallocate($img, 255, 255, 255);
imagefill($img, 0, 0, $bg);
imagestring($img, 5, 10, 10, 'Z', $fg);
header('Content-Type: image/x-icon');
imageico($img);
imagedestroy($img);
