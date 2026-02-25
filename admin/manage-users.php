<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// استعلام للحصول على جميع المستخدمين
$users_sql = "SELECT * FROM users";
$users_result = $conn->query($users_sql);

// استعلام للحصول على عدد المستخدمين
$count_users_sql = "SELECT COUNT(*) AS user_count FROM users";
$count_users_result = $conn->query($count_users_sql);
$user_count = $count_users_result->fetch_assoc()['user_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Bike Shop</title>
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
            background: transparent !important;
        }
        .btn-logout-nav:hover { border-color: var(--main-red) !important; color: var(--main-red) !important; }

        /* --- Main Content --- */
        .main-content { 
            margin-left: var(--sidebar-width); flex: 1; padding: 40px; 
            width: calc(100% - var(--sidebar-width)); 
        }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h1 { font-weight: 900; font-size: 28px; }

        /* Summary Card */
        .user-stats { background: var(--card-bg); padding: 15px 25px; border-radius: 12px; border: 1px solid var(--border-color); display: inline-block; margin-bottom: 20px; }
        .user-stats span { color: var(--main-red); font-weight: 900; font-size: 20px; margin-left: 5px; }

        /* Table Design */
        .table-container { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: rgba(255,255,255,0.03); padding: 20px; color: var(--text-gray); font-weight: 700; border-bottom: 1px solid var(--border-color); font-size: 13px; text-transform: uppercase; }
        td { padding: 20px; border-bottom: 1px solid var(--border-color); color: #ddd; font-size: 14px; }
        tr:hover { background: rgba(255,255,255,0.02); }

        /* Status Badges */
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-active { background: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid #28a745; }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid #ffc107; }
        
        .role-badge { color: var(--main-red); font-weight: 900; font-size: 12px; letter-spacing: 1px; }

    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>BIKE SHOP</h2>
        <nav class="menu-links">
            <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
            <a href="manage-products.php"><i class="fas fa-bicycle"></i> <span>Products</span></a>
            <a href="manage-users.php" class="active"><i class="fas fa-users"></i> <span>Users</span></a>
            <a href="manage-orders.php"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a>
            <a href="manage-offers.php"><i class="fas fa-percent"></i> <span>Offers</span></a>
            <a href="manage-categories.php"><i class="fas fa-list"></i> <span>Categories</span></a>
        </nav>
        <a href="logout.php" class="btn-logout-nav">
            <i class="fas fa-sign-out-alt"></i> <span style="margin-left:10px">Logout</span>
        </a>
    </aside>

    <main class="main-content">
        <div class="header-section">
            <h1>User Management</h1>
        </div>

        <div class="user-stats">
            Total Registered Users: <span><?php echo $user_count; ?></span>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Phone Number</th>
                        <th>Role</th>
                        <th>Account Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result->num_rows > 0): ?>
                        <?php while ($user = $users_result->fetch_assoc()) { ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['fullName']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phoneNumber']); ?></td>
                                <td class="role-badge"><?php echo strtoupper($user['role']); ?></td>
                                <td>
                                    <?php if($user['status'] == 'active'): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-gray);">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>