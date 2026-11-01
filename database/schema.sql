-- Smart Shop schema aligned with T090_database_tables.docx
-- WARNING: Importing this file drops and recreates ONLY categories and products.
-- Existing data in these two tables will be deleted.
-- Select the intended local database in phpMyAdmin before importing.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `categories` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `woo_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `brand` VARCHAR(100) NOT NULL,
    `category_id` INT NOT NULL,
    `base_price` INT NOT NULL,
    `discount_percent` TINYINT NOT NULL DEFAULT 0,
    `in_stock` TINYINT NOT NULL DEFAULT 1,
    `description` TEXT NULL,
    `features` TEXT NULL,
    `image` VARCHAR(500) NULL,
    `product_url` VARCHAR(500) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_woo_id` (`woo_id`),
    KEY `idx_products_category_id` (`category_id`),
    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quick verification:
-- SHOW TABLES;
-- DESCRIBE categories;
-- DESCRIBE products;
