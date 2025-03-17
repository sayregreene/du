<?php
// /var/www/html/du/create-category-tables.php
// Include the configuration file
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop existing tables if they exist (in the correct order)
    $pdo->exec("DROP TABLE IF EXISTS `category_mappings`");
    $pdo->exec("DROP TABLE IF EXISTS `akeneo_categories`");
    $pdo->exec("DROP TABLE IF EXISTS `pivotree_categories`");
    
    // Create pivotree_categories table
    $pdo->exec("
        CREATE TABLE `pivotree_categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `category_name` varchar(255) NOT NULL,
          `source` varchar(50) DEFAULT 'terminal_node',
          `product_count` int(11) DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_category_name` (`category_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    
    // Create akeneo_categories table
    $pdo->exec("
        CREATE TABLE `akeneo_categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `category` varchar(255) NOT NULL,
          `category_name` varchar(255) NOT NULL,
          `parent_category_id` int(11) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_category_code` (`category`),
          KEY `idx_parent_category` (`parent_category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    
    // Create category_mappings table
    $pdo->exec("
        CREATE TABLE `category_mappings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `pivotree_category_id` int(11) NOT NULL,
          `akeneo_category_id` int(11) NOT NULL,
          `created_by` varchar(50) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_mapping_unique` (`pivotree_category_id`, `akeneo_category_id`),
          KEY `idx_pivotree_category` (`pivotree_category_id`),
          KEY `idx_akeneo_category` (`akeneo_category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    
    // Add foreign key constraint to akeneo_categories table
    try {
        $pdo->exec("
            ALTER TABLE `akeneo_categories`
            ADD CONSTRAINT `fk_parent_category` 
            FOREIGN KEY (`parent_category_id`) 
            REFERENCES `akeneo_categories` (`id`) 
            ON DELETE SET NULL
        ");
        echo "Added parent_category constraint\n";
    } catch (PDOException $e) {
        echo "Failed to add parent_category constraint: " . $e->getMessage() . "\n";
        // Continue even if this fails
    }
    
    // Add foreign key constraints to category_mappings table
    try {
        $pdo->exec("
            ALTER TABLE `category_mappings`
            ADD CONSTRAINT `fk_pivotree_category` 
            FOREIGN KEY (`pivotree_category_id`) 
            REFERENCES `pivotree_categories` (`id`) 
            ON DELETE CASCADE
        ");
        echo "Added pivotree_category constraint\n";
    } catch (PDOException $e) {
        echo "Failed to add pivotree_category constraint: " . $e->getMessage() . "\n";
        // Continue even if this fails
    }
    
    try {
        $pdo->exec("
            ALTER TABLE `category_mappings`
            ADD CONSTRAINT `fk_akeneo_category` 
            FOREIGN KEY (`akeneo_category_id`) 
            REFERENCES `akeneo_categories` (`id`) 
            ON DELETE CASCADE
        ");
        echo "Added akeneo_category constraint\n";
    } catch (PDOException $e) {
        echo "Failed to add akeneo_category constraint: " . $e->getMessage() . "\n";
        // Continue even if this fails
    }
    
    echo "Tables created successfully!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit;
}