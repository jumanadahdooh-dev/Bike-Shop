<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// استعلام للحصول على جميع التصنيفات
$categories_sql = "SELECT * FROM categories ORDER BY id DESC";
$categories_result = $conn->query($categories_sql);

// استعلام للحصول على عدد التصنيفات
$count_categories_sql = "SELECT COUNT(*) AS category_count FROM categories";
$count_categories_result = $conn->query($count_categories_sql);
$category_count = $count_categories_result->fetch_assoc()['category_count'];

// إضافة تصنيف جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];
    $category_description = $_POST['category_description'];
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $category_name, $category_description);
    $stmt->execute();
    header("Location: manage-categories.php");
    exit();
}

// تعديل تصنيف موجود
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_category'])) {
    $category_id = $_POST['category_id'];
    $category_name = $_POST['category_name'];
    $category_description = $_POST['category_description'];
    $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $category_name, $category_description, $category_id);
    $stmt->execute();
    header("Location: manage-categories.php");
    exit();
}

// حذف تصنيف
if (isset($_GET['delete_id'])) {
    $category_id = $_GET['delete_id'];
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    header("Location: manage-categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories | Bike Shop</title>
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
            margin-top: auto; margin-bottom: 20px; border: 1px solid #333; color: var(--text-gray) !important; 
            justify-content: center; display: flex; align-items: center; padding: 12px; border-radius: 10px; text-decoration: none; font-weight: 700;
        }
        .btn-logout-nav:hover { border-color: var(--main-red); color: var(--main-red) !important; }

        /* --- Main Content --- */
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; width: calc(100% - var(--sidebar-width)); }
        
        .header-section { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; }
        h1 { font-weight: 900; font-size: 32px; letter-spacing: -1px; }

        .stats-brief { color: var(--text-gray); font-weight: 700; font-size: 14px; margin-top: 5px; }
        .stats-brief span { color: var(--main-red); font-size: 18px; margin: 0 5px; }

        /* Table Style */
        .table-container { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #161616; padding: 20px; color: var(--text-gray); font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
        td { padding: 20px; border-bottom: 1px solid var(--border-color); color: #efefef; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.02); }

        /* Action Buttons */
        .btn-primary { background: var(--main-red); color: white; border: none; padding: 12px 25px; border-radius: 12px; cursor: pointer; font-weight: 900; transition: 0.3s; text-decoration: none; font-family: 'Cairo'; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(204, 51, 31, 0.4); }
        
        .action-links { display: flex; gap: 15px; }
        .btn-edit-link { color: #4dadff; text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 5px; background: none; border: none; cursor: pointer; font-family: 'Cairo'; font-size: 15px; }
        .btn-delete-link { color: #ff4d4d; text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 5px; }
        .btn-edit-link:hover, .btn-delete-link:hover { opacity: 0.8; }

        /* Forms Styling (Overlays) */
        .form-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(5px); display: none; justify-content: center; align-items: center; z-index: 2000; padding: 20px; }
        .form-container { background: var(--card-bg); padding: 40px; border-radius: 25px; border: 1px solid var(--border-color); width: 100%; max-width: 500px; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .form-container h2 { margin-bottom: 25px; color: var(--main-red); font-weight: 900; }
        label { display: block; margin-bottom: 10px; color: var(--text-gray); font-weight: 700; font-size: 14px; }
        input, textarea { width: 100%; padding: 14px; background: #000; border: 1px solid var(--border-color); border-radius: 12px; color: white; margin-bottom: 20px; font-family: 'Cairo'; transition: 0.3s; }
        input:focus, textarea:focus { border-color: var(--main-red); outline: none; }
        
        .form-actions { display: flex; gap: 15px; margin-top: 10px; }
        .btn-cancel { background: #222; color: white; border: none; padding: 14px; border-radius: 12px; cursor: pointer; flex: 1; font-weight: 700; font-family: 'Cairo'; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>BIKE SHOP</h2>
        <nav class="menu-links">
            <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
            <a href="manage-products.php"><i class="fas fa-bicycle"></i> <span>Products</span></a>
            <a href="manage-users.php"><i class="fas fa-users"></i> <span>Users</span></a>
            <a href="manage-orders.php"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a>
            <a href="manage-offers.php"><i class="fas fa-percent"></i> <span>Offers</span></a>
            <a href="manage-categories.php" class="active"><i class="fas fa-list"></i> <span>Categories</span></a>
        </nav>
        <a href="logout.php" class="btn-logout-nav"><i class="fas fa-sign-out-alt"></i> <span style="margin-left:10px">Logout</span></a>
    </aside>

    <main class="main-content">
        <div class="header-section">
            <div>
                <h1>Categories</h1>
                <div class="stats-brief">You have total<span><?php echo $category_count; ?></span>active categories</div>
            </div>
            <button class="btn-primary" onclick="toggleForm('add-category-overlay')">
                <i class="fas fa-plus-circle"></i> Add New Category
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Name</th>
                        <th>Description</th>
                        <th style="width: 200px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($category = $categories_result->fetch_assoc()) { ?>
                        <tr>
                            <td><strong style="color: var(--main-red); font-size: 17px;"><?php echo htmlspecialchars($category['name']); ?></strong></td>
                            <td style="color: var(--text-gray); line-height: 1.6;"><?php echo htmlspecialchars($category['description']); ?></td>
                            <td>
                                <div class="action-links">
                                    <button class="btn-edit-link" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', '<?php echo addslashes($category['description']); ?>')">
                                        <i class="fas fa-pen"></i> Edit
                                    </button>
                                    <a href="manage-categories.php?delete_id=<?php echo $category['id']; ?>" class="btn-delete-link" onclick="return confirm('Deleting this category might affect linked products. Continue?')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="add-category-overlay" class="form-overlay">
        <div class="form-container">
            <h2><i class="fas fa-folder-plus"></i> New Category</h2>
            <form action="manage-categories.php" method="POST">
                <label>Category Name</label>
                <input type="text" name="category_name" placeholder="e.g. Mountain Bikes" required>
                <label>Description</label>
                <textarea name="category_description" rows="4" placeholder="Briefly describe this category..." required></textarea>
                <div class="form-actions">
                    <button type="submit" name="add_category" class="btn-primary" style="flex:2">Create Category</button>
                    <button type="button" class="btn-cancel" onclick="toggleForm('add-category-overlay')">Discard</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-category-overlay" class="form-overlay">
        <div class="form-container">
            <h2><i class="fas fa-edit"></i> Edit Category</h2>
            <form action="manage-categories.php" method="POST">
                <input type="hidden" name="category_id" id="edit_category_id">
                <label>Category Name</label>
                <input type="text" name="category_name" id="edit_category_name" required>
                <label>Description</label>
                <textarea name="category_description" id="edit_category_description" rows="4" required></textarea>
                <div class="form-actions">
                    <button type="submit" name="edit_category" class="btn-primary" style="flex:2">Update Changes</button>
                    <button type="button" class="btn-cancel" onclick="toggleForm('edit-category-overlay')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleForm(id) {
            const el = document.getElementById(id);
            if (el.style.display === 'flex') {
                el.style.display = 'none';
            } else {
                el.style.display = 'flex';
            }
        }

        function editCategory(id, name, description) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_category_description').value = description;
            toggleForm('edit-category-overlay');
        }

        // إغلاق النافذة عند الضغط خارجها
        window.onclick = function(event) {
            if (event.target.className === 'form-overlay') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>