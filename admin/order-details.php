<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];

    // استعلام تفاصيل المنتجات
    $order_details_sql = "SELECT order_items.id, products.name, products.price, order_items.quantity, 
                                 (products.price * order_items.quantity) AS total_price, products.image 
                          FROM order_items 
                          JOIN products ON order_items.product_id = products.id 
                          WHERE order_items.order_id = ?";
    $stmt = $conn->prepare($order_details_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_details_result = $stmt->get_result();

    // استعلام تفاصيل الطلب العامة
    $order_sql = "SELECT orders.total, orders.status, users.fullName, users.email, orders.created_at 
                  FROM orders 
                  JOIN users ON orders.user_id = users.id 
                  WHERE orders.id = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result()->fetch_assoc();
} else {
    header("Location: manage-orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $order_id; ?></title>
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

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Cairo', sans-serif; background-color: var(--bg-black); color: var(--text-white); display: flex; min-height: 100vh; }

        /* --- Sidebar الموحد --- */
        .sidebar {
            width: var(--sidebar-width); min-width: var(--sidebar-width);
            background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color);
            padding: 30px 15px; position: fixed; height: 100vh; left: 0; top: 0;
            display: flex; flex-direction: column; z-index: 1000;
        }
        .sidebar h2 { color: var(--main-red); font-weight: 900; text-align: center; margin-bottom: 40px; font-size: 22px; }
        
        .menu-links { flex-grow: 1; display: flex; flex-direction: column; gap: 5px; }
        
        .sidebar a {
            display: flex; align-items: center; color: var(--text-gray); text-decoration: none;
            padding: 12px 15px; border-radius: 10px; font-weight: 700; transition: 0.3s;
        }
        .sidebar a i { margin-right: 12px; width: 25px; text-align: center; font-size: 18px; }
        .sidebar a:hover, .sidebar a.active { background: rgba(204, 51, 31, 0.1); color: var(--main-red); }
        .sidebar a.active { background: var(--main-red); color: #fff; }

        .btn-logout-nav { 
            margin-top: auto; margin-bottom: 20px;
            border: 1px solid #333 !important; color: var(--text-gray) !important; 
            justify-content: center !important; display: flex; align-items: center;
            padding: 12px; border-radius: 10px; text-decoration: none; font-weight: 700;
        }
        .btn-logout-nav:hover { border-color: var(--main-red) !important; color: var(--main-red) !important; }

        /* --- Main Content --- */
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; width: calc(100% - var(--sidebar-width)); }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .order-id-tag { background: var(--main-red); padding: 5px 15px; border-radius: 8px; font-weight: 900; font-size: 20px; }

        /* Customer Info Card */
        .info-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px; }
        .customer-card { background: var(--card-bg); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color); }
        .customer-card h2 { font-size: 18px; margin-bottom: 15px; color: var(--main-red); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; font-weight: 900; }
        .info-row { display: flex; margin-bottom: 12px; justify-content: space-between; font-size: 14px; }
        .info-label { color: var(--text-gray); }
        
        /* Items List */
        .items-container { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; }
        .item-row { display: flex; align-items: center; padding: 20px; border-bottom: 1px solid var(--border-color); transition: 0.3s; }
        .item-row:last-child { border-bottom: none; }
        .item-row:hover { background: rgba(255,255,255,0.02); }
        
        .item-img { width: 70px; height: 70px; object-fit: cover; border-radius: 12px; margin-right: 20px; border: 1px solid var(--border-color); }
        .item-info { flex: 2; }
        .item-info h3 { font-size: 16px; margin-bottom: 5px; font-weight: 700; }
        .item-info p { color: var(--text-gray); font-size: 13px; }
        
        .item-qty { flex: 1; text-align: center; color: var(--text-gray); font-size: 14px; }
        .item-total { flex: 1; text-align: right; font-weight: 900; color: var(--main-red); font-size: 18px; }

        /* Back Button */
        .btn-back { display: inline-flex; align-items: center; gap: 10px; color: var(--text-gray); text-decoration: none; margin-bottom: 25px; font-weight: 700; transition: 0.3s; font-size: 14px; }
        .btn-back:hover { color: var(--main-red); transform: translateX(-5px); }

        .status-badge { padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 900; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--main-red); letter-spacing: 1px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>BIKE SHOP</h2>
        <nav class="menu-links">
            <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
            <a href="manage-products.php"><i class="fas fa-bicycle"></i> <span>Products</span></a>
            <a href="manage-users.php"><i class="fas fa-users"></i> <span>Users</span></a>
            <a href="manage-orders.php" class="active"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a>
            <a href="manage-offers.php"><i class="fas fa-percent"></i> <span>Offers</span></a>
            <a href="manage-categories.php"><i class="fas fa-list"></i> <span>Categories</span></a>
        </nav>
        <a href="logout.php" class="btn-logout-nav">
            <i class="fas fa-sign-out-alt"></i> <span style="margin-left:10px">Logout</span>
        </a>
    </aside>

    <main class="main-content">
        <a href="manage-orders.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        
        <div class="header-section">
            <h1>Order Details <span class="order-id-tag">#<?php echo $order_id; ?></span></h1>
            <span class="status-badge"><?php echo strtoupper($order_result['status']); ?></span>
        </div>

        <div class="info-grid">
            <div class="customer-card">
                <h2>Customer Information</h2>
                <div class="info-row"><span class="info-label">Full Name:</span> <strong><?php echo htmlspecialchars($order_result['fullName']); ?></strong></div>
                <div class="info-row"><span class="info-label">Email:</span> <span><?php echo htmlspecialchars($order_result['email']); ?></span></div>
                <div class="info-row"><span class="info-label">Order Date:</span> <span><?php echo date('F d, Y - h:i A', strtotime($order_result['created_at'])); ?></span></div>
            </div>
            
            <div class="customer-card" style="text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; border-color: var(--main-red);">
                <h2 style="border:none; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; color: var(--text-gray);">Total Payment</h2>
                <div style="font-size: 36px; font-weight: 900; color: var(--main-red); line-height: 1;"><?php echo number_format($order_result['total'], 2); ?> <small style="font-size: 14px;">SR</small></div>
            </div>
        </div>

        <h2 style="margin-bottom: 20px; font-size: 20px; font-weight: 900;">Products in this Order</h2>
        <div class="items-container">
            <?php if ($order_details_result->num_rows > 0): ?>
                <?php while ($row = $order_details_result->fetch_assoc()) { ?>
                    <div class="item-row">
                        <img src="../uploads/<?php echo basename($row['image']); ?>" class="item-img" alt="product">
                        <div class="item-info">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p>Unit Price: <?php echo $row['price']; ?> SR</p>
                        </div>
                        <div class="item-qty">
                            Quantity: <strong>x<?php echo $row['quantity']; ?></strong>
                        </div>
                        <div class="item-total">
                            <?php echo number_format($row['total_price'], 2); ?> <span style="font-size: 12px;">SR</span>
                        </div>
                    </div>
                <?php } ?>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: var(--text-gray);">No items found in this order.</div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>