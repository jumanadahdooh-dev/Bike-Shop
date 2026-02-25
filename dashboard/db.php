<?php
// معلومات الاتصال بقاعدة البيانات
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'bike_shop'; // اسم قاعدة البيانات التي سنعمل عليها

// الاتصال بقاعدة البيانات
$conn = mysqli_connect($host, $user, $pass);

// التحقق من الاتصال
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// إنشاء قاعدة البيانات إذا لم تكن موجودة
$createDB = "CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!mysqli_query($conn, $createDB)) {
    die("Error creating database: " . mysqli_error($conn));
}

// اختيار قاعدة البيانات
mysqli_select_db($conn, $db);

// تغيير الترميز إلى utf8mb4
mysqli_query($conn, "SET NAMES 'utf8mb4'");

// دالة لإنشاء الجداول
if (!function_exists('createTable')) {
    function createTable($conn, $sql, $tableName) {
        if (!mysqli_query($conn, $sql)) {
            error_log("Error creating table '$tableName': " . mysqli_error($conn)); // تخزين الأخطاء في ملف السجلات
        }
    }
}

// إنشاء الجداول هنا
$createUsersTable = "CREATE TABLE IF NOT EXISTS users (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(30) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(500) NOT NULL,
    phoneNumber VARCHAR(30),
    role ENUM('admin','user') NOT NULL,
    status ENUM('active','pending') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
createTable($conn, $createUsersTable, "users");


$createCategoriesTable = "CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
createTable($conn, $createCategoriesTable, "categories");


$createProductsTable = "CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT UNSIGNED,
    product_id INT UNSIGNED,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
createTable($conn, $createProductsTable, "products");

$createOrdersTable = "CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','shipped','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
createTable($conn, $createOrdersTable, "orders");

$createOrderItemsTable = "CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
createTable($conn, $createOrderItemsTable, "order_items");

$createCartTable = "CREATE TABLE IF NOT EXISTS cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
createTable($conn, $createCartTable, "cart");

$createOfferTable = "CREATE TABLE IF NOT EXISTS offers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    image VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 0,  -- الكمية المتاحة
    end_time DATETIME DEFAULT NULL,  -- وقت انتهاء العرض
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
createTable($conn, $createOfferTable, "offers");

$createContactMessagesTable = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
createTable($conn, $createContactMessagesTable, "contact_messages");

// إغلاق الاتصال بعد كل العمليات
// mysqli_close($conn);  // في حالة لم تكن تستخدمه لاحقًا في الكود يمكنك إلغاء التعليق
?>
