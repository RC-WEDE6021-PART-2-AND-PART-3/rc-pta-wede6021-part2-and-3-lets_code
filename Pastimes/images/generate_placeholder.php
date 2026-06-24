<?php
/**
 * generate_placeholder.php
 * Generates a placeholder image for items without photos
 * Run once then delete
 */

// Create placeholder image using GD
if (function_exists('imagecreatetruecolor')) {
    $img = imagecreatetruecolor(400, 400);
    $bg  = imagecolorallocate($img, 241, 243, 245);
    $fg  = imagecolorallocate($img, 173, 181, 189);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 5, 155, 190, 'No Image', $fg);
    imagejpeg($img, __DIR__ . '/images/placeholder.jpg', 80);
    imagedestroy($img);
    echo 'Placeholder created.';
} else {
    // Fallback: copy a default SVG placeholder
    file_put_contents(__DIR__ . '/images/placeholder.jpg', '');
    echo 'GD not available. Empty placeholder created.';
}
?>
