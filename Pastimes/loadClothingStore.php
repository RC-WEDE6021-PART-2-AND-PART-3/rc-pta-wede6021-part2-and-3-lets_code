<?php
/**
 * loadClothingStore.php
 * Database Setup Script — Pastimes ClothingStore
 * WEDE6021 POE
 *
 * This script:
 * 1. Creates the ClothingStore database if it does not exist
 * 2. Drops all existing tables (clean reset)
 * 3. Creates all tables with proper PKs, FKs, ENUMs
 * 4. Loads seed data from text files in /database folder
 *
 * Run this script once to initialise or reset the database.
 * Access: http://localhost/Pastimes/loadClothingStore.php
 */

// ============================================================
// CONNECT WITHOUT SELECTING A DATABASE (to create it if needed)
// ============================================================
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ClothingStore';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("<b>Connection failed:</b> " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// ============================================================
// CREATE DATABASE IF IT DOESN'T EXIST
// ============================================================
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sql)) {
    die("<b>Failed to create database:</b> " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

echo "<h2 style='font-family:sans-serif;'>🚀 Pastimes — Database Initialisation</h2>";
echo "<pre style='font-family:monospace; background:#f0f0f0; padding:16px; border-radius:8px;'>";

// ============================================================
// DISABLE FOREIGN KEY CHECKS (to allow DROP in any order)
// ============================================================
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
echo "✅ Foreign key checks disabled\n";

// ============================================================
// DROP EXISTING TABLES
// ============================================================
$tables = ['orders', 'messages', 'cart', 'item_photos', 'items', 'addresses', 'users'];
foreach ($tables as $table) {
    if ($conn->query("DROP TABLE IF EXISTS `$table`")) {
        echo "🗑  Dropped table: $table\n";
    }
}

// ============================================================
// RE-ENABLE FOREIGN KEY CHECKS
// ============================================================
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "✅ Foreign key checks re-enabled\n\n";

// ============================================================
// CREATE TABLE: users
// ============================================================
$sql = "
CREATE TABLE IF NOT EXISTS `users` (
    `user_id`       INT NOT NULL AUTO_INCREMENT,
    `first_name`    VARCHAR(50) NOT NULL,
    `last_name`     VARCHAR(50) NOT NULL,
    `email`         VARCHAR(100) NOT NULL,
    `username`      VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer',
    `seller_status` ENUM('none','pending','verified') NOT NULL DEFAULT 'none',
    `account_status` ENUM('pending','approved') NOT NULL DEFAULT 'pending',
    `profile_pic`   VARCHAR(255) DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_email` (`email`),
    UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($conn->query($sql)) {
    echo "✅ Created table: users\n";
} else {
    echo "❌ Error creating users: " . $conn->error . "\n";
}

// ============================================================
// CREATE TABLE: addresses
// ============================================================
$sql = "
CREATE TABLE IF NOT EXISTS `addresses` (
    `address_id`   INT NOT NULL AUTO_INCREMENT,
    `user_id`      INT NOT NULL,
    `address_type` ENUM('residential','work') NOT NULL DEFAULT 'residential',
    `street`       VARCHAR(150) NOT NULL,
    `city`         VARCHAR(80) NOT NULL,
    `province`     VARCHAR(80) NOT NULL,
    `postal_code`  VARCHAR(10) NOT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`address_id`),
    CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($conn->query($sql)) {
    echo "✅ Created table: addresses\n";
} else {
    echo "❌ Error creating addresses: " . $conn->error . "\n";
}

// ============================================================
// CREATE TABLE: items
// ============================================================
$sql = "
CREATE TABLE IF NOT EXISTS `items` (
    `item_id`     INT NOT NULL AUTO_INCREMENT,
    `seller_id`   INT NOT NULL,
    `item_name`   VARCHAR(150) NOT NULL,
    `brand`       VARCHAR(80) NOT NULL,
    `category`    VARCHAR(80) NOT NULL,
    `size`        VARCHAR(20) NOT NULL,
    `colour`      VARCHAR(50) DEFAULT NULL,
    `condition`   ENUM('excellent','good','fair') NOT NULL DEFAULT 'good',
    `description` TEXT NOT NULL,
    `price`       DECIMAL(10,2) NOT NULL,
    `status`      ENUM('available','sold','pending') NOT NULL DEFAULT 'available',
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`item_id`),
    CONSTRAINT `fk_item_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    KEY `idx_status` (`status`),
    KEY `idx_category` (`category`),
    KEY `idx_brand` (`brand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($conn->query($sql)) {
    echo "✅ Created table: items\n";
} else {
    echo "❌ Error creating items: " . $conn->error . "\n";
}

// ============================================================
// CREATE TABLE: item_photos
// ============================================================
$sql = "
CREATE TABLE IF NOT EXISTS `item_photos` (
    `photo_id`   INT NOT NULL AUTO_INCREMENT,
    `item_id`    INT NOT NULL,
    `file_path`  VARCHAR(255) NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`photo_id`),
    CONSTRAINT `fk_photo_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($conn->query($sql)) {
    echo "✅ Created table: item_photos\n";
} else {
    echo "❌ Error creating item_photos: " . $conn->error . "\n";
}

// ============================================================
// CREATE TABLE: cart
// ============================================================
$sql = "
CREATE TABLE IF NOT EXISTS `cart` (
    `cart_id`    INT NOT NULL AUTO_INCREMENT,
    `user_id`    INT NOT NULL,
    `item_id`    INT NOT NULL,
    `quantity`   INT NOT NULL DEFAULT 1,
    `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`cart_id`),
    UNIQUE KEY `uq_user_item` (`user_id`, `item_id`),
    CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cart_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($conn->query($sql)) {
    echo "✅ Created table: cart\n";
} else {
    echo "❌ Error creating cart: " . $conn->error . "\n";
}

// ============================================================
// CREATE TABLE: orders
// ============================================================
$sql = "
CREATE TABLE IF NOT EXISTS `orders` (
    `order_id`      INT NOT NULL AUTO_INCREMENT,
    `buyer_id`      INT NOT NULL,
    `item_id`       INT NOT NULL,
    `address_id`    INT NOT NULL,
    `quantity`      INT NOT NULL DEFAULT 1,
    `total_amount`  DECIMAL(10,2) NOT NULL,
    `order_ref`     VARCHAR(20) NOT NULL,
    `order_date`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`        ENUM('pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    PRIMARY KEY (`order_id`),
    UNIQUE KEY `uq_order_ref` (`order_ref`),
    CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_item`  FOREIGN KEY (`item_id`)  REFERENCES `items` (`item_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_addr`  FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($conn->query($sql)) {
    echo "✅ Created table: orders\n";
} else {
    echo "❌ Error creating orders: " . $conn->error . "\n";
}

// ============================================================
// CREATE TABLE: messages
// ============================================================
$sql = "
CREATE TABLE IF NOT EXISTS `messages` (
    `message_id`   INT NOT NULL AUTO_INCREMENT,
    `item_id`      INT DEFAULT NULL,
    `sender_id`    INT NOT NULL,
    `receiver_id`  INT NOT NULL,
    `message_text` TEXT NOT NULL,
    `is_read`      TINYINT(1) NOT NULL DEFAULT 0,
    `sent_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`message_id`),
    CONSTRAINT `fk_msg_item`     FOREIGN KEY (`item_id`)     REFERENCES `items` (`item_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_msg_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($conn->query($sql)) {
    echo "✅ Created table: messages\n\n";
} else {
    echo "❌ Error creating messages: " . $conn->error . "\n\n";
}

// ============================================================
// LOAD SEED DATA FROM TEXT FILES
// ============================================================
echo "--- Loading seed data from text files ---\n\n";

/**
 * Helper: load and insert data from tab/pipe separated text file
 *
 * @param mysqli $conn
 * @param string $filepath
 * @param string $table
 * @param array  $columns
 * @param string $types MySQLi bind param type string
 */
function loadFromFile($conn, $filepath, $table, $columns, $types) {
    if (!file_exists($filepath)) {
        echo "⚠️  File not found: $filepath\n";
        return;
    }
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $count = 0;
    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $cols = implode(',', array_map(fn($c) => "`$c`", $columns));
    $stmt = $conn->prepare("INSERT IGNORE INTO `$table` ($cols) VALUES ($placeholders)");
    if (!$stmt) {
        echo "❌ Prepare failed for $table: " . $conn->error . "\n";
        return;
    }
    foreach ($lines as $line) {
        // Skip comment lines
        if (str_starts_with(trim($line), '#')) continue;
        $parts = explode('|', $line);
        if (count($parts) < count($columns)) continue;
        // Trim each part
        $parts = array_map('trim', $parts);
        $stmt->bind_param($types, ...$parts);
        if ($stmt->execute()) $count++;
    }
    $stmt->close();
    echo "✅ Loaded $count records into: $table\n";
}

// ---- Load Users (with proper password hashing) ----
$userFile = __DIR__ . '/database/userData.txt';
if (!file_exists($userFile)) {
    echo "⚠️  File not found: $userFile\n";
} else {
    $userLines = file($userFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $userCount = 0;
    foreach ($userLines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        $p = array_map('trim', explode('|', $line));
        if (count($p) < 7) continue;
        // $p[4] is the plain-text password — hash it before storing
        $hashedPassword = password_hash($p[4], PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO users (first_name, last_name, email, username, password_hash, role, seller_status, account_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')"
        );
        if ($stmt) {
            $stmt->bind_param('sssssss', $p[0], $p[1], $p[2], $p[3], $hashedPassword, $p[5], $p[6]);
            if ($stmt->execute()) $userCount++;
            $stmt->close();
        }
    }
    echo "✅ Loaded $userCount users with hashed passwords\n";
}

// ---- Load Items ----
// 10 columns, 10 bind vars — all 's' is safe; MySQL casts int/decimal automatically
$itemLines = @file(__DIR__ . '/database/itemsData.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$itemCount = 0;
if ($itemLines) {
    foreach ($itemLines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        $p = array_map('trim', explode('|', $line));
        if (count($p) < 10) continue;
        $stmt = $conn->prepare(
            "INSERT INTO items (seller_id, item_name, brand, category, size, colour, `condition`, description, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt) {
            $stmt->bind_param('ssssssssss', $p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9]);
            if ($stmt->execute()) $itemCount++;
            $stmt->close();
        }
    }
    echo "✅ Loaded $itemCount records into: items\n";
}

// ---- Load Item Photos ----
loadFromFile(
    $conn,
    __DIR__ . '/database/photosData.txt',
    'item_photos',
    ['item_id','file_path','is_primary'],
    'isi'
);

// ---- Load Orders ----
$lines = @file(__DIR__ . '/database/ordersData.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines) {
    $inserted = 0;
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        $p = array_map('trim', explode('|', $line));
        if (count($p) < 7) continue;
        $stmt = $conn->prepare("INSERT IGNORE INTO orders (buyer_id,item_id,address_id,quantity,total_amount,order_ref,status) VALUES (?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param('iiiidss', $p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6]);
            if ($stmt->execute()) $inserted++;
            $stmt->close();
        }
    }
    echo "✅ Loaded $inserted records into: orders\n";
}

// ---- Load Messages ----
loadFromFile(
    $conn,
    __DIR__ . '/database/messagesData.txt',
    'messages',
    ['item_id','sender_id','receiver_id','message_text','is_read'],
    'iiisi'
);

// ---- Load Addresses ----
loadFromFile(
    $conn,
    __DIR__ . '/database/addressesData.txt',
    'addresses',
    ['user_id','address_type','street','city','province','postal_code'],
    'isssss'
);

echo "\n✅ Database initialisation complete!\n";
echo "</pre>";
echo "<p style='font-family:sans-serif;'><a href='index.php' style='color:#0D8B8B;font-weight:600;'>→ Go to Pastimes Home</a></p>";

$conn->close();
?>
