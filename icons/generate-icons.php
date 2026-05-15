<?php
/**
 * Icon Generator – Run once to create all PWA icon PNGs.
 * Usage: php icons/generate-icons.php
 * Or visit: https://yourdomain.com/icons/generate-icons.php (then delete it)
 */

if (!extension_loaded('gd')) {
    die("GD extension is required. Enable it in php.ini.\n");
}

$sizes  = [72, 96, 128, 144, 152, 192, 384, 512];
$outDir = __DIR__;

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imageantialias($img, true);

    // Rounded background gradient (dark blue → indigo)
    $bg = imagecolorallocate($img, 15, 23, 42);        // #0f172a
    imagefill($img, 0, 0, $bg);

    // Draw gradient circle as background
    $cx = $cy = $size / 2;
    $r  = $size / 2;
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $dx   = $x - $cx;
            $dy   = $y - $cy;
            $dist = sqrt($dx * $dx + $dy * $dy);
            if ($dist <= $r) {
                $t   = ($x / $size);
                $red = (int)(59  + (99  - 59)  * $t); // 3b82f6 → 6366f1
                $grn = (int)(130 + (102 - 130) * $t);
                $blu = (int)(246 + (241 - 246) * $t);
                $c   = imagecolorallocatealpha($img, $red, $grn, $blu, 0);
                imagesetpixel($img, $x, $y, $c);
            }
        }
    }

    // Draw message bubble icon (simplified)
    $white = imagecolorallocate($img, 255, 255, 255);
    $p     = $size * 0.18; // padding
    $iw    = $size - $p * 2;
    $ih    = $iw * 0.72;
    $ix    = $p;
    $iy    = $p + ($size - $p * 2 - $ih) / 2 - $size * 0.05;

    // Bubble body (rounded rect approximation)
    $radius = $iw * 0.15;
    imagefilledrectangle($img, (int)($ix + $radius), (int)$iy, (int)($ix + $iw - $radius), (int)($iy + $ih), $white);
    imagefilledrectangle($img, (int)$ix, (int)($iy + $radius), (int)($ix + $iw), (int)($iy + $ih - $radius), $white);
    imagefilledellipse($img, (int)($ix + $radius), (int)($iy + $radius), (int)($radius * 2), (int)($radius * 2), $white);
    imagefilledellipse($img, (int)($ix + $iw - $radius), (int)($iy + $radius), (int)($radius * 2), (int)($radius * 2), $white);
    imagefilledellipse($img, (int)($ix + $radius), (int)($iy + $ih - $radius), (int)($radius * 2), (int)($radius * 2), $white);
    imagefilledellipse($img, (int)($ix + $iw - $radius), (int)($iy + $ih - $radius), (int)($radius * 2), (int)($radius * 2), $white);

    // Tail
    $tx = $size * 0.28;
    $ty = $iy + $ih;
    imagefilledpolygon($img, [
        (int)$tx, (int)$ty,
        (int)($tx + $size * 0.15), (int)$ty,
        (int)$tx, (int)($ty + $size * 0.14),
    ], $white);

    // Dots inside bubble
    $dot = imagecolorallocate($img, 59, 130, 246);
    $dc  = (int)($size * 0.15);
    $dy2 = (int)($iy + $ih * 0.5);
    foreach ([-1, 0, 1] as $di) {
        $dx = (int)($cx + $di * $dc);
        imagefilledellipse($img, $dx, $dy2, (int)($size * 0.07), (int)($size * 0.07), $dot);
    }

    // Save
    $file = "{$outDir}/icon-{$size}.png";
    imagepng($img, $file, 9);
    imagedestroy($img);
    echo "Created: icon-{$size}.png\n";
}
echo "Done! All icons generated.\n";
echo "IMPORTANT: Delete this file after generation.\n";
