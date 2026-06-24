<?php
/**
 * createTable.php
 * Creates/Recreates the users table and loads userData.txt
 * WEDE6021 POE — Part 2 Requirement
 *
 * This script:
 * 1. Includes DBConn.php for the database connection
 * 2. Checks if users table exists; if so, drops it
 * 3. Recreates the users table
 * 4. Loads data from userData.txt into the table
 */

// ============================================================
// INCLUDE DATABASE CONNECTION
// ============================================================
// First connect without DB to ensure it exists
$conn_temp = new mysqli('localhost', 'root', '');
if (!$conn_temp->connect_error) {
    $conn_temp->query("CREATE DATABASE IF NOT EXISTS `ClothingStore` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn_temp->close();
}

// Now include the DBConn.php which connects to ClothingStore
require_once __DIR__ . '/includes/DBConn.php';

echo "<h2 style='font-family:sans-serif;'>🔄 Pastimes — createTable.php</h2>";
echo "<pre style='font-family:monospace; background:#f5f5f5; padding:16px; border-radius:8px;'>";

// ============================================================
// CHECK IF TABLE EXISTS — DROP IF IT DOES
// ============================================================
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    // Disable FK checks to allow drop
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Drop dependent tables first to avoid FK constraint errors
    $dependents = ['orders', 'messages', 'cart', 'item_photos', 'items', 'addresses', 'users'];
    foreach ($dependents as $tbl) {
        $conn->query("DROP TABLE IF EXISTS `$tbl`");
        echo "🗑  Dropped table: $tbl\n";
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
} else {
    echo "ℹ️  Table 'users' did not exist — creating fresh.\n";
}

// ============================================================
// RECREATE USERS TABLE
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
    echo "✅ Created table: users\n\n";
} else {
    echo "❌ Failed to create users table: " . $conn->error . "\n";
    echo "</pre>";
    exit;
}

// ============================================================
// ALSO RECREATE OTHER REQUIRED TABLES
// ============================================================
$otherTables = [
    'addresses' => "
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'items' => "
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
        CONSTRAINT `fk_item_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'item_photos' => "
    CREATE TABLE IF NOT EXISTS `item_photos` (
        `photo_id`   INT NOT NULL AUTO_INCREMENT,
        `item_id`    INT NOT NULL,
        `file_path`  VARCHAR(255) NOT NULL,
        `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`photo_id`),
        CONSTRAINT `fk_photo_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'cart' => "
    CREATE TABLE IF NOT EXISTS `cart` (
        `cart_id`  INT NOT NULL AUTO_INCREMENT,
        `user_id`  INT NOT NULL,
        `item_id`  INT NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`cart_id`),
        UNIQUE KEY `uq_user_item` (`user_id`,`item_id`),
        CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_cart_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'orders' => "
    CREATE TABLE IF NOT EXISTS `orders` (
        `order_id`     INT NOT NULL AUTO_INCREMENT,
        `buyer_id`     INT NOT NULL,
        `item_id`      INT NOT NULL,
        `address_id`   INT NOT NULL,
        `quantity`     INT NOT NULL DEFAULT 1,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `order_ref`    VARCHAR(20) NOT NULL,
        `order_date`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status`       ENUM('pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
        PRIMARY KEY (`order_id`),
        UNIQUE KEY `uq_order_ref` (`order_ref`),
        CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_order_item`  FOREIGN KEY (`item_id`)  REFERENCES `items` (`item_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_order_addr`  FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'messages' => "
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($otherTables as $name => $ddl) {
    if ($conn->query($ddl)) {
        echo "✅ Created table: $name\n";
    } else {
        echo "❌ Error creating $name: " . $conn->error . "\n";
    }
}

echo "\n--- Loading userData.txt ---\n";

// ============================================================
// LOAD DATA FROM userData.txt
// ============================================================
$userData_file = __DIR__ . '/database/userData.txt';

if (!file_exists($userData_file)) {
    echo "⚠️  userData.txt not found at: $userData_file\n";
} else {
    $lines = file($userData_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $inserted = 0;
    $skipped  = 0;

    foreach ($lines as $line) {
        // Skip comment lines starting with #
        if (str_starts_with(trim($line), '#')) continue;

        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 7) {
            $skipped++;
            continue;
        }

        list($first_name, $last_name, $email, $username, $password, $role, $seller_status) = $parts;

        // Hash the password from the text file
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Use prepared statement to safely insert
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO users (first_name, last_name, email, username, password_hash, role, seller_status, account_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')"
        );

        if (!$stmt) {
            echo "❌ Prepare error: " . $conn->error . "\n";
            continue;
        }

        $stmt->bind_param('sssssss', $first_name, $last_name, $email, $username, $hash, $role, $seller_status);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $inserted++;
                echo "  ➕ Inserted: $first_name $last_name ($username)\n";
            } else {
                $skipped++;
                echo "  ⚠️  Skipped (duplicate): $username\n";
            }
        } else {
            echo "  ❌ Error inserting $username: " . $stmt->error . "\n";
        }
        $stmt->close();
    }

    echo "\n✅ Loaded: $inserted users | Skipped: $skipped\n";
}

// ============================================================
// VERIFY — FETCH USING ASSOCIATIVE ARRAY
// ============================================================
echo "\n--- Verification: Fetching users table (associative) ---\n";
$result = $conn->query("SELECT user_id, first_name, last_name, email, username, role, seller_status, created_at FROM users");
if ($result && $result->num_rows > 0) {
    // Display using associative array column name access (POE requirement)
    while ($row = $result->fetch_assoc()) {
        echo sprintf(
            "  [%d] %s %s | @%s | %s | seller:%s | joined:%s\n",
            $row['user_id'],
            $row['first_name'],
            $row['last_name'],
            $row['username'],
            $row['role'],
            $row['seller_status'],
            $row['created_at']
        );
    }
} else {
    echo "  No users found.\n";
}

echo "\n✅ createTable.php completed successfully!\n";
echo "</pre>";
echo "<p style='font-family:sans-serif;'>";
echo "<a href='index.php' style='color:#0D8B8B;font-weight:600;'>→ Go to Pastimes Home</a> &nbsp;|&nbsp; ";
echo "<a href='loadClothingStore.php' style='color:#082B59;font-weight:600;'>Run Full DB Setup</a>";
echo "</p>";

$conn->close();
?>
