<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// استعلام للحصول على جميع الطلبات مع اسم المستخدم
$orders_sql = "SELECT orders.id, orders.user_id, orders.total, orders.status, orders.created_at, users.fullName 
               FROM orders 
               JOIN users ON orders.user_id = users.id 
               ORDER BY orders.created_at DESC";
$orders_result = $conn->query($orders_sql);

// جلب الإحصائيات
$stats = [
    'total' => $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'],
    'confirmed' => $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'confirmed'")->fetch_assoc()['c'],
    'shipped' => $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'shipped'")->fetch_assoc()['c'],
    'completed' => $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'completed'")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'")->fetch_assoc()['c'],
    'cancelled' => $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'cancelled'")->fetch_assoc()['c'],
];

// معالجة تغيير الحالة
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    $action = $_GET['action'];
    $new_status = '';

    switch ($action) {
        case 'confirm': $new_status = 'confirmed'; break;
        case 'ship': $new_status = 'shipped'; break;
        case 'complete': $new_status = 'completed'; break;
        case 'cancel': $new_status = 'cancelled'; break;
    }

    if ($new_status) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
    }
    header("Location: manage-orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | Bike Shop</title>
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
        .main-content { 
            margin-left: var(--sidebar-width); flex: 1; padding: 40px; 
            width: calc(100% - var(--sidebar-width)); 
        }
        h1 { font-weight: 900; font-size: 28px; margin-bottom: 30px; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 40px; }
        .stat-card { background: var(--card-bg); padding: 20px; border-radius: 15px; border: 1px solid var(--border-color); text-align: center; }
        .stat-card h3 { font-size: 11px; color: var(--text-gray); text-transform: uppercase; margin-bottom: 10px; }
        .stat-card p { font-size: 24px; font-weight: 900; color: var(--main-red); }

        /* Table Design */
        .table-container { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: rgba(255,255,255,0.03); padding: 18px; color: var(--text-gray); font-size: 13px; text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
        td { padding: 18px; border-bottom: 1px solid var(--border-color); color: #ddd; font-size: 14px; }
        tr:hover { background: rgba(255,255,255,0.02); }

        /* Status Styling */
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid #ffc107; }
        .status-confirmed { background: rgba(52, 152, 219, 0.1); color: #3498db; border: 1px solid #3498db; }
        .status-shipped { background: rgba(155, 89, 182, 0.1); color: #9b59b2; border: 1px solid #9b59b2; }
        .status-completed { background: rgba(46, 204, 113, 0.1); color: #2ecc71; border: 1px solid #2ecc71; }
        .status-cancelled { background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid #e74c3c; }

        /* Action Buttons */
        .btn-action { padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 700; transition: 0.3s; display: inline-block; margin: 2px; }
        .btn-confirm { background: var(--main-red); color: white; }
        .btn-detail { background: #222; color: #fff; }
        .btn-cancel { border: 1px solid #444; color: var(--text-gray); }
        .btn-action:hover { transform: translateY(-2px); opacity: 0.9; }

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
        <h1>Order Management</h1>

        <div class="stats-grid">
            <div class="stat-card"><h3>Total</h3><p><?php echo $stats['total']; ?></p></div>
            <div class="stat-card"><h3>Pending</h3><p><?php echo $stats['pending']; ?></p></div>
            <div class="stat-card"><h3>Confirmed</h3><p><?php echo $stats['confirmed']; ?></p></div>
            <div class="stat-card"><h3>Shipped</h3><p><?php echo $stats['shipped']; ?></p></div>
            <div class="stat-card"><h3>Completed</h3><p><?php echo $stats['completed']; ?></p></div>
            <div class="stat-card"><h3>Cancelled</h3><p><?php echo $stats['cancelled']; ?></p></div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result->num_rows > 0): ?>
                        <?php while ($order = $orders_result->fetch_assoc()) { ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['fullName']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td style="color: var(--main-red); font-weight: 700;"><?php echo $order['total']; ?> SR</td>
                                <td>
                                    <span class="status-pill status-<?php echo $order['status']; ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['status'] == 'pending') { ?>
                                        <a href="manage-orders.php?action=confirm&order_id=<?php echo $order['id']; ?>" class="btn-action btn-confirm">Confirm</a>
                                        <a href="manage-orders.php?action=cancel&order_id=<?php echo $order['id']; ?>" class="btn-action btn-cancel" onclick="return confirm('Cancel this order?')">Cancel</a>
                                    <?php } elseif ($order['status'] == 'confirmed') { ?>
                                        <a href="manage-orders.php?action=ship&order_id=<?php echo $order['id']; ?>" class="btn-action btn-confirm">Ship Order</a>
                                    <?php } elseif ($order['status'] == 'shipped') { ?>
                                        <a href="manage-orders.php?action=complete&order_id=<?php echo $order['id']; ?>" class="btn-action btn-confirm">Complete</a>
                                    <?php } ?>
                                    <a href="order-details.php?order_id=<?php echo $order['id']; ?>" class="btn-action btn-detail">Details</a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-gray);">No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>