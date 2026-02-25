<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// استعلامات جلب البيانات
$products_result = $conn->query("SELECT * FROM products");
$product_count = $conn->query("SELECT COUNT(*) AS product_count FROM products")->fetch_assoc()['product_count'];
$categories_result = $conn->query("SELECT * FROM categories");

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category_id = $_POST['category'];
    $quantity = $_POST['quantity'];

    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_dir = __DIR__ . '/../uploads/'; 
    $image_path = $image_dir . basename($image_name);

    if (!is_dir($image_dir)) { mkdir($image_dir, 0777, true); }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
    
    if (in_array($image_extension, $allowed_extensions)) {
        if (move_uploaded_file($image_tmp, $image_path)) {
            $sql = "INSERT INTO products (name, price, description, category_id, stock, image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdssis", $product_name, $price, $description, $category_id, $quantity, $image_path);
            $stmt->execute();
            header("Location: manage-products.php");
            exit();
        } else { $error_message = "Error uploading image."; }
    } else { $error_message = "Unsupported image format."; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | Bike Shop</title>
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
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        h1 { font-weight: 900; font-size: 28px; }

        /* Stats & Actions */
        .top-info { background: var(--card-bg); padding: 10px 20px; border-radius: 12px; border: 1px solid var(--border-color); font-weight: 700; margin-top: 10px; display: inline-block; }
        .top-info span { color: var(--main-red); font-size: 20px; }
        .btn-primary { background: var(--main-red); color: white; border: none; padding: 12px 25px; border-radius: 10px; cursor: pointer; font-weight: 700; transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(204, 51, 31, 0.4); }

        /* Product Grid */
        .product-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; margin-top: 20px; }
        .product-card { 
            background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border-color); 
            overflow: hidden; transition: 0.4s; position: relative;
        }
        .product-card:hover { border-color: var(--main-red); transform: translateY(-10px); }
        
        .product-image { width: 100%; height: 220px; background: #000; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .product-details { padding: 20px; }
        .product-name { font-size: 18px; font-weight: 900; margin-bottom: 8px; color: #fff; }
        .product-price { color: var(--main-red); font-weight: 900; font-size: 20px; margin-bottom: 10px; }
        .product-description { font-size: 14px; color: var(--text-gray); line-height: 1.5; margin-bottom: 20px; min-height: 42px; }
        
        .card-actions { display: flex; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 15px; }
        .btn-edit { flex: 1; text-align: center; color: #fff; text-decoration: none; background: #222; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700; }
        .btn-edit:hover { background: #333; }
        .btn-delete { width: 45px; height: 40px; display: flex; align-items: center; justify-content: center; color: var(--main-red); text-decoration: none; border: 1px solid var(--border-color); padding: 8px; border-radius: 8px; transition: 0.3s; }
        .btn-delete:hover { background: var(--main-red); color: #fff; border-color: var(--main-red); }

        /* Overlay Form */
        .form-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: none; justify-content: center; align-items: center; z-index: 2000; overflow-y: auto; padding: 20px; }
        .form-container { background: var(--card-bg); padding: 35px; border-radius: 25px; border: 1px solid var(--border-color); width: 100%; max-width: 600px; }
        .form-container h2 { margin-bottom: 25px; color: var(--main-red); font-weight: 900; text-align: center; }
        
        input, textarea, select { width: 100%; padding: 12px; background: #000; border: 1px solid var(--border-color); border-radius: 10px; color: white; margin-bottom: 15px; font-family: 'Cairo'; outline: none; }
        input:focus, select:focus { border-color: var(--main-red); }
        label { display: block; margin-bottom: 8px; color: var(--text-gray); font-size: 14px; }
        .error { background: rgba(204, 51, 31, 0.1); color: var(--main-red); padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid var(--main-red); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>BIKE SHOP</h2>
        <nav class="menu-links">
            <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
            <a href="manage-products.php" class="active"><i class="fas fa-bicycle"></i> <span>Products</span></a>
            <a href="manage-users.php"><i class="fas fa-users"></i> <span>Users</span></a>
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
            <div>
                <h1>Products Inventory</h1>
                <div class="top-info">Total Units: <span><?php echo $product_count; ?></span></div>
            </div>
            <button class="btn-primary" onclick="toggleOverlay()"><i class="fas fa-plus"></i> Add New Product</button>
        </div>

        <?php if (isset($error_message)) { echo "<div class='error'>$error_message</div>"; } ?>

        <div class="product-list">
            <?php while ($product = $products_result->fetch_assoc()) { ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo '/Bike-Shop/uploads/' . basename($product['image']); ?>" alt="product">
                    </div>
                    <div class="product-details">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-price"><?php echo $product['price']; ?> SR</p>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 70)) . '...'; ?></p>
                        <div class="card-actions">
                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit Details</a>
                            <a href="delete-product.php?id=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('Delete this bike?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </main>

    <div id="add-product-overlay" class="form-overlay">
        <div class="form-container">
            <h2>Add New Bike</h2>
            <form action="manage-products.php" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label>Product Name</label>
                        <input type="text" name="name" placeholder="e.g. Mountain X-2" required>
                    </div>
                    <div>
                        <label>Price (SR)</label>
                        <input type="number" name="price" placeholder="0.00" required>
                    </div>
                </div>

                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Enter product details..." required></textarea>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label>Category</label>
                        <select name="category" required>
                            <?php 
                            $categories_result->data_seek(0);
                            while ($cat = $categories_result->fetch_assoc()) { 
                                echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                            } ?>
                        </select>
                    </div>
                    <div>
                        <label>Stock Quantity</label>
                        <input type="number" name="quantity" value="1" required>
                    </div>
                </div>

                <label>Product Image</label>
                <input type="file" name="image" accept="image/*" required>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="add_product" class="btn-primary" style="flex: 2; justify-content: center;">Upload Product</button>
                    <button type="button" class="btn-primary" onclick="toggleOverlay()" style="flex: 1; background: #222; justify-content: center;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleOverlay() {
            const overlay = document.getElementById('add-product-overlay');
            overlay.style.display = (overlay.style.display === 'flex') ? 'none' : 'flex';
        }

        // إغلاق الفورم عند الضغط خارج المحتوى
        window.onclick = function(event) {
            const overlay = document.getElementById('add-product-overlay');
            if (event.target == overlay) {
                overlay.style.display = "none";
            }
        }
    </script>
</body>
</html>