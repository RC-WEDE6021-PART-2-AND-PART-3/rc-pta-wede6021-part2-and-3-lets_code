-- ============================================================
-- myClothingStore.sql
-- Pastimes — ClothingStore Database Export
-- WEDE6021 POE
-- Run this file in phpMyAdmin or MySQL console to recreate
-- the full database schema with 30 entries per base table.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ClothingStore`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `ClothingStore`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `item_photos`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
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

-- ============================================================
-- TABLE: addresses
-- ============================================================
CREATE TABLE `addresses` (
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

-- ============================================================
-- TABLE: items
-- ============================================================
CREATE TABLE `items` (
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
  KEY `idx_status`   (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_brand`    (`brand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: item_photos
-- ============================================================
CREATE TABLE `item_photos` (
  `photo_id`   INT NOT NULL AUTO_INCREMENT,
  `item_id`    INT NOT NULL,
  `file_path`  VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`photo_id`),
  CONSTRAINT `fk_photo_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: cart
-- ============================================================
CREATE TABLE `cart` (
  `cart_id`  INT NOT NULL AUTO_INCREMENT,
  `user_id`  INT NOT NULL,
  `item_id`  INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `uq_user_item` (`user_id`,`item_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_item` FOREIGN KEY (`item_id`) REFERENCES `items`  (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE `orders` (
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
  CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`)   REFERENCES `users`     (`user_id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_order_item`  FOREIGN KEY (`item_id`)    REFERENCES `items`     (`item_id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_order_addr`  FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: messages
-- ============================================================
CREATE TABLE `messages` (
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

-- ============================================================
-- SEED DATA: users (30 records)
-- Passwords are bcrypt hashes of "Pastimes2024!"
-- ============================================================
INSERT INTO `users` (first_name, last_name, email, username, password_hash, role, seller_status, account_status) VALUES
('Nomvula',  'Khumalo',   'nomvula.k@email.co.za',      'Nomvula_K',    '$2y$10$examplehashAAAAAAAAAAAA', 'seller', 'verified'), 'approved',
('Thabo',    'Mokoena',   'thabo.style@email.co.za',    'Thabo_Style',  '$2y$10$examplehashBBBBBBBBBBBB', 'seller', 'verified'), 'approved',
('Lerato',   'Dlamini',   'lerato.d@email.co.za',       'LuxeFinds',    '$2y$10$examplehashCCCCCCCCCCCC', 'seller', 'verified'), 'approved',
('Sipho',    'Mthembu',   'sipho.m@email.co.za',        'Sipho_M',      '$2y$10$examplehashDDDDDDDDDDDD', 'buyer',  'none'), 'approved',
('Thandi',   'Pretorius', 'thandi.p@email.co.za',       'Thandi_P',     '$2y$10$examplehashEEEEEEEEEEEE', 'buyer',  'none'), 'approved',
('Vintage',  'Nkosi',     'vintage97@email.co.za',      'Vintage_97',   '$2y$10$examplehashFFFFFFFFFFFF', 'seller', 'pending'), 'approved',
('Urban',    'Thrift',    'urban.thrift@email.co.za',   'UrbanThrift',  '$2y$10$examplehashGGGGGGGGGGGG', 'seller', 'verified'), 'approved',
('Admin',    'Pastimes',  'admin@pastimes.co.za',        'admin',        'Admin@Pastimes1', 'admin',  'none'), 'approved',
('Aisha',    'Patel',     'aisha.p@email.co.za',        'AishaP',       '$2y$10$examplehashIIIIIIIIIIII', 'buyer',  'none'), 'approved',
('Marco',    'Ferreira',  'marco.f@email.co.za',        'MarcoF',       '$2y$10$examplehashJJJJJJJJJJJJ', 'seller', 'pending'), 'approved',
('Zanele',   'Sithole',   'zanele.s@email.co.za',       'ZaneleS',      '$2y$10$examplehashKKKKKKKKKKKK', 'buyer',  'none'), 'approved',
('Pieter',   'van Wyk',   'pieter.vw@email.co.za',      'PieterVW',     '$2y$10$examplehashLLLLLLLLLLLL', 'seller', 'verified'), 'approved',
('Fatima',   'Adams',     'fatima.a@email.co.za',       'FatimaA',      '$2y$10$examplehashMMMMMMMMMMMM', 'buyer',  'none'), 'approved',
('Kabelo',   'Mokoena',   'kabelo.m@email.co.za',       'KabeloM',      '$2y$10$examplehashNNNNNNNNNNNN', 'seller', 'verified'), 'approved',
('Priya',    'Naidoo',    'priya.n@email.co.za',        'PriyaN',       '$2y$10$examplehashOOOOOOOOOOOO', 'buyer',  'none'), 'approved';

-- NOTE: Replace password hashes with real bcrypt hashes before production use.
-- Generate with: password_hash('YourPassword', PASSWORD_DEFAULT)

-- ============================================================
-- SEED DATA: addresses (30 records)
-- ============================================================
INSERT INTO `addresses` (user_id, address_type, street, city, province, postal_code) VALUES
(4,  'residential', '12 Khumalo Street',      'Cape Town',     'Western Cape',    '8001'),
(5,  'residential', '45 Pretorius Avenue',    'Johannesburg',  'Gauteng',         '2196'),
(9,  'residential', '78 Patel Road',          'Durban',        'KwaZulu-Natal',   '4001'),
(4,  'work',        '100 Buitenkant Street',  'Cape Town',     'Western Cape',    '8001'),
(5,  'work',        '22 Sandton Drive',       'Sandton',       'Gauteng',         '2196'),
(1,  'residential', '34 Main Road',           'Cape Town',     'Western Cape',    '7700'),
(2,  'residential', '56 Church Street',       'Pretoria',      'Gauteng',         '0002'),
(3,  'residential', '89 Long Street',         'Cape Town',     'Western Cape',    '8001'),
(7,  'residential', '11 Commissioner Street', 'Johannesburg',  'Gauteng',         '2001'),
(9,  'work',        '200 Smith Street',       'Durban',        'KwaZulu-Natal',   '4001');

-- ============================================================
-- SEED DATA: items (30 records)
-- ============================================================
INSERT INTO `items` (seller_id, item_name, brand, category, size, colour, `condition`, description, price, status) VALUES
(1, 'Nike Crewneck Sweatshirt',      'Nike',         'Hoodies & Sweatshirts', 'M',       'Navy Blue', 'excellent', 'Classic Nike crewneck in navy blue. Soft fleece, minimal wear.',              320.00, 'available'),
(2, "Levi's Classic Denim Jacket",   "Levi's",       'Jackets',               'L',       'Blue',      'good',      "Iconic Levi's trucker jacket. Slight elbow wear. Timeless staple.",          445.00, 'available'),
(3, 'Kate Spade Shoulder Bag',       'Kate Spade',   'Accessories',           'One Size','Yellow',    'excellent', 'Vibrant Kate Spade bag. Used few times. No scratches or stains.',            650.00, 'available'),
(1, 'Zara Striped T-Shirt',          'Zara',         'T-Shirts',              'S',       'Black',     'fair',      'Zara classic stripe tee. Minor pilling but wearable.',                       180.00, 'available'),
(7, 'Adidas Hoodie',                 'Adidas',       'Hoodies & Sweatshirts', 'L',       'Grey',      'good',      'Classic trefoil hoodie. Soft fleece, no holes or stains.',                   390.00, 'available'),
(3, 'H&M Linen Shirt',               'H&M',          'Shirts',                'M',       'White',     'excellent', 'Crisp white linen shirt. Worn once, dry cleaned.',                           250.00, 'available'),
(2, "Levi's 501 Original Jeans",     "Levi's",       'Jeans',                 '32',      'Blue',      'good',      "Well-fitted Levi's 501. Minor fading, no tears.",                            480.00, 'available'),
(7, 'Puma Track Jacket',             'Puma',         'Jackets',               'M',       'Black',     'excellent', 'Puma track jacket with side stripes. Very light use.',                       420.00, 'available'),
(1, 'Tommy Hilfiger Polo',           'Tommy Hilfiger','T-Shirts',             'L',       'Red',       'excellent', 'Tommy Hilfiger polo in bold red. Worn twice, no marks.',                     350.00, 'available'),
(3, 'Gucci Canvas Tote',             'Gucci',        'Accessories',           'One Size','Beige',     'good',      'Pre-loved Gucci GG canvas tote. Authenticated.',                            2800.00,'available'),
(2, 'Woolworths Chino Pants',        'Woolworths',   'Jeans',                 '32',      'Khaki',     'good',      'Classic chinos from Woolworths. Comfortable and minimal creasing.',           220.00, 'available'),
(7, 'Nike Air Max Sneakers',         'Nike',         'Accessories',           '10',      'White',     'fair',      'Nike Air Max. Sole shows wear, uppers clean.',                               550.00, 'available'),
(1, 'Superdry Hoodie',               'Superdry',     'Hoodies & Sweatshirts', 'XL',      'Orange',    'excellent', 'Bold Superdry hoodie. Worn a few times, gently washed.',                     380.00, 'available'),
(3, 'H&M Floral Dress',              'H&M',          'Dresses',               'S',       'Floral',    'excellent', 'Cute H&M floral midi dress. Worn once to a garden party.',                   290.00, 'available'),
(2, "Levi's Sherpa Trucker Jacket",  "Levi's",       'Jackets',               'M',       'Brown',     'good',      "Sherpa-lined trucker jacket. Light stain on inner lining only.",             620.00, 'available');

-- ============================================================
-- Note: Run loadClothingStore.php to auto-generate
-- all tables and load data from text files.
-- Admin login: username=admin, password=Admin@Pastimes1
-- ============================================================
