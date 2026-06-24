<?php
/**
 * image_helper.php
 * Brand-aware image resolver — Pastimes
 * WEDE6021 POE
 *
 * Fixes the issue where all product cards show the same image
 * because photosData.txt referenced non-existent upload paths.
 *
 * Resolution order:
 *   1. If DB file_path exists on disk → use it
 *   2. Map brand name → matching local image in /images/
 *   3. Fallback to images/placeholder.jpg
 */

/**
 * Return the best available image src for a given item.
 *
 * @param string $db_path   Path stored in item_photos.file_path
 * @param string $brand     Brand name from items table
 * @return string           Safe HTML-encoded image src
 */
function getItemImage(string $db_path = '', string $brand = ''): string {

    // 1. If DB path looks valid and the file actually exists on disk, use it
    if (!empty($db_path) && $db_path !== 'images/placeholder.jpg') {
        // Build server-side path relative to the project root
        $server_file = __DIR__ . '/../' . ltrim($db_path, '/');
        if (file_exists($server_file)) {
            return htmlspecialchars($db_path, ENT_QUOTES, 'UTF-8');
        }
    }

    // 2. Brand → local image mapping
    //    Uses the actual image filenames present in /images/
    $brand_map = [
        'nike'           => 'images/NIKE.png',
        "levi's"         => "images/Levi's.png",
        'levis'          => "images/Levi's.png",
        'kate spade'     => 'images/Kate Spade.png',
        'zara'           => 'images/Zara.png',
        'adidas'         => 'images/Adidas Hoodie.png',
        'h&m'            => 'images/H&M Linen Shirt.png',
        'hm'             => 'images/H&M Linen Shirt.png',
        'puma'           => 'images/Puma Track Jacket.png',
        'tommy hilfiger' => 'images/Tommy Hilfiger Polo Shirt.png',
        'gucci'          => 'images/Gucci Canvas Tote Bag.png',
        'woolworths'     => 'images/Woolworths Chino Pants.png',
        'superdry'       => 'images/Superdry Hoodie.png',
    ];

    $key = strtolower(trim($brand));

    if (isset($brand_map[$key])) {
        return htmlspecialchars($brand_map[$key], ENT_QUOTES, 'UTF-8');
    }

    // 3. Default fallback
    return 'images/placeholder.jpg';
}
