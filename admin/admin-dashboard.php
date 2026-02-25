<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user'];
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// الإحصائيات
$categories_result = $conn->query("SELECT * FROM categories");
$orders_result = $conn->query("SELECT * FROM orders");
$users_result = $conn->query("SELECT * FROM users");
$products_result = $conn->query("SELECT * FROM products");
$offers_result = $conn->query("SELECT * FROM offers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Bike Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-red: #cc331f;
            --bg-black: #0a0a0a;
            --card-bg: #111111;
            --sidebar-bg: #000000;
            --text-white: #ffffff;
            --text-gray: #aaaaaa;
            --border-color: #222222;
            --sidebar-width: 260px;
        }

        /* تصفير الإعدادات لضمان ثبات الأبعاد */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-black);
            color: var(--text-white);
            display: flex; /* لتقسيم الصفحة لجانبين */
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* --- Sidebar: العرض ثابت ومستقل --- */
        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            padding: 30px 15px;
            z-index: 1000;
        }

        .sidebar h2 {
            color: var(--main-red);
            font-weight: 900;
            text-align: center;
            margin-bottom: 40px;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .menu-links {
            flex-grow: 1; /* تأخذ المساحة المتاحة وتدفع الزر للأسفل */
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: var(--text-gray);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 10px;
            font-weight: 700;
            transition: 0.3s;
        }

        .sidebar a i {
            margin-right: 12px;
            font-size: 18px;
            width: 25px;
            text-align: center;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(204, 51, 31, 0.1);
            color: var(--main-red);
        }

        .sidebar a.active {
            background: var(--main-red);
            color: #fff;
        }

        /* زر الخروج أسفل السايدبار */
        .btn-logout-nav {
            margin-top: auto;
            border: 1px solid #333 !important;
            justify-content: center !important;
            background: transparent !important;
        }

        .btn-logout-nav:hover {
            border-color: var(--main-red) !important;
            color: var(--main-red) !important;
        }

        /* --- المحتوى الرئيسي: يأخذ باقي مساحة الشاشة --- */
        .main-content {
            flex-grow: 1;
            margin-left: var(--sidebar-width); /* إزاحة تساوي عرض السايدبار */
            padding: 30px 40px;
            width: calc(100% - var(--sidebar-width));
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-bg);
            padding: 20px 30px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        /* --- شبكة البطاقات: توزيع احترافي --- */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            transition: 0.3s;
        }

        .card:hover {
            border-color: var(--main-red);
            transform: translateY(-5px);
        }

        .card i {
            font-size: 35px;
            color: var(--main-red);
            margin-bottom: 15px;
            display: block;
        }

        .card h3 { font-size: 15px; color: var(--text-gray); margin-bottom: 10px; }
        .card p { font-size: 38px; font-weight: 900; margin-bottom: 20px; color: #fff; }

        .btn-action {
            display: inline-block;
            color: var(--main-red);
            border: 1px solid var(--main-red);
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-action:hover {
            background: var(--main-red);
            color: white;
        }

        /* لإصلاح الأبعاد في الشاشات الصغيرة */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; min-width: 80px; padding: 20px 10px; }
            .sidebar h2, .sidebar a span { display: none; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>BIKE SHOP</h2>
        <nav class="menu-links">
            <a href="admin-dashboard.php" class="active"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
            <a href="manage-products.php"><i class="fas fa-bicycle"></i> <span>Products</span></a>
            <a href="manage-users.php"><i class="fas fa-users"></i> <span>Users</span></a>
            <a href="manage-orders.php"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a>
            <a href="manage-offers.php"><i class="fas fa-percent"></i> <span>Offers</span></a>
            <a href="manage-categories.php"><i class="fas fa-list"></i> <span>Categories</span></a>
        </nav>
        
        <a href="logout.php" class="btn-logout-nav"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1>Dashboard Overview</h1>
            <div>Welcome, <span style="color:var(--main-red); font-weight:700;"><?php echo htmlspecialchars($user['fullName']); ?></span></div>
        </div>

        <div class="cards-grid">
            <div class="card">
                <i class="fas fa-box"></i>
                <h3>Total Orders</h3>
                <p><?php echo $orders_result->num_rows; ?></p>
                <a href="manage-orders.php" class="btn-action">View All</a>
            </div>

            <div class="card">
                <i class="fas fa-users"></i>
                <h3>Total Users</h3>
                <p><?php echo $users_result->num_rows; ?></p>
                <a href="manage-users.php" class="btn-action">View All</a>
            </div>

            <div class="card">
                <i class="fas fa-bicycle"></i>
                <h3>Total Products</h3>
                <p><?php echo $products_result->num_rows; ?></p>
                <a href="manage-products.php" class="btn-action">View All</a>
            </div>

            <div class="card">
                <i class="fas fa-tags"></i>
                <h3>Categories</h3>
                <p><?php echo $categories_result->num_rows; ?></p>
                <a href="manage-categories.php" class="btn-action">View All</a>
            </div>

            <div class="card">
                <i class="fas fa-fire"></i>
                <h3>Active Offers</h3>
                <p><?php echo $offers_result->num_rows; ?></p>
                <a href="manage-offers.php" class="btn-action">View All</a>
            </div>
        </div>
    </main>

</body>
</html>