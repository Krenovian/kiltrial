<?php
/**
 * Database Setup Script
 * Creates the database and all required tables
 * Run this once to initialize the system
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS restaurant_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE restaurant_pos");

    // Categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT '🍽️',
        sort_order INT DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        name VARCHAR(200) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        description TEXT,
        emoji VARCHAR(10) DEFAULT '🍴',
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )");

    // Bills table
    $pdo->exec("CREATE TABLE IF NOT EXISTS bills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_number VARCHAR(20) NOT NULL UNIQUE,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        tax_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
        tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method ENUM('cash','card','upi') DEFAULT 'cash',
        status ENUM('completed','cancelled') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Bill items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS bill_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_id INT NOT NULL,
        item_id INT NOT NULL,
        item_name VARCHAR(200) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
    )");

    // Insert sample categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO categories (name, icon, sort_order) VALUES
            ('Starters', '🥗', 1),
            ('Main Course', '🍛', 2),
            ('Biryani', '🍚', 3),
            ('Bread', '🫓', 4),
            ('Beverages', '🥤', 5),
            ('Desserts', '🍰', 6)
        ");

        // Insert sample items
        $pdo->exec("INSERT INTO items (category_id, name, price, emoji) VALUES
            (1, 'Paneer Tikka', 220.00, '🧀'),
            (1, 'Chicken 65', 280.00, '🍗'),
            (1, 'Gobi Manchurian', 180.00, '🥦'),
            (1, 'Fish Fry', 320.00, '🐟'),
            (1, 'Mushroom Pepper Fry', 200.00, '🍄'),
            (1, 'Spring Roll', 160.00, '🌯'),
            (2, 'Butter Chicken', 320.00, '🍗'),
            (2, 'Paneer Butter Masala', 260.00, '🧀'),
            (2, 'Dal Makhani', 200.00, '🫘'),
            (2, 'Chicken Curry', 280.00, '🍛'),
            (2, 'Mutton Rogan Josh', 380.00, '🥩'),
            (2, 'Kadai Veg', 220.00, '🥘'),
            (3, 'Chicken Biryani', 300.00, '🍗'),
            (3, 'Mutton Biryani', 380.00, '🥩'),
            (3, 'Veg Biryani', 220.00, '🥕'),
            (3, 'Egg Biryani', 240.00, '🥚'),
            (3, 'Prawns Biryani', 360.00, '🦐'),
            (4, 'Butter Naan', 60.00, '🫓'),
            (4, 'Garlic Naan', 70.00, '🧄'),
            (4, 'Tandoori Roti', 40.00, '🫓'),
            (4, 'Rumali Roti', 50.00, '🫓'),
            (4, 'Paratha', 80.00, '🫓'),
            (5, 'Fresh Lime Soda', 80.00, '🍋'),
            (5, 'Mango Lassi', 100.00, '🥭'),
            (5, 'Masala Chai', 40.00, '☕'),
            (5, 'Cold Coffee', 120.00, '🧋'),
            (5, 'Buttermilk', 50.00, '🥛'),
            (6, 'Gulab Jamun', 80.00, '🍩'),
            (6, 'Rasmalai', 100.00, '🍮'),
            (6, 'Ice Cream', 90.00, '🍨'),
            (6, 'Brownie', 120.00, '🍫')
        ");
    }

    echo "<div style='font-family:Inter,sans-serif;max-width:600px;margin:80px auto;text-align:center;'>";
    echo "<div style='font-size:64px;margin-bottom:20px;'>✅</div>";
    echo "<h1 style='color:#10b981;'>Setup Complete!</h1>";
    echo "<p style='color:#666;font-size:18px;'>Database <strong>restaurant_pos</strong> has been created with sample data.</p>";
    echo "<div style='margin-top:30px;'>";
    echo "<a href='index.php' style='background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:14px 32px;border-radius:12px;text-decoration:none;font-weight:600;margin:8px;display:inline-block;'>Open POS →</a>";
    echo "<a href='admin.php' style='background:linear-gradient(135deg,#f093fb,#f5576c);color:white;padding:14px 32px;border-radius:12px;text-decoration:none;font-weight:600;margin:8px;display:inline-block;'>Admin Panel →</a>";
    echo "</div></div>";

} catch (PDOException $e) {
    echo "<div style='font-family:Inter,sans-serif;max-width:600px;margin:80px auto;text-align:center;'>";
    echo "<div style='font-size:64px;margin-bottom:20px;'>❌</div>";
    echo "<h1 style='color:#ef4444;'>Setup Failed</h1>";
    echo "<p style='color:#666;font-size:16px;'>" . $e->getMessage() . "</p>";
    echo "</div>";
}
