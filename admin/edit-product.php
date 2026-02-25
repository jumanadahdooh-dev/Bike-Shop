<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
} else {
    header("Location: manage-products.php");
    exit();
}

$categories_result = $conn->query("SELECT * FROM categories");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $product_name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category_id = $_POST['category'];
    $quantity = $_POST['quantity'];

    $db_image_path = $product['image']; 

    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_dir = __DIR__ . '/../uploads/'; 
        $image_path = $image_dir . basename($image_name);
        
        if (!is_dir($image_dir)) { mkdir($image_dir, 0777, true); }

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        
        if (in_array($image_extension, $allowed_extensions)) {
            if (move_uploaded_file($image_tmp, $image_path)) {
                $db_image_path = $image_name; 
            }
        }
    }

    $sql = "UPDATE products SET name = ?, price = ?, description = ?, category_id = ?, stock = ?, image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdssisi", $product_name, $price, $description, $category_id, $quantity, $db_image_path, $product_id);
    
    if ($stmt->execute()) {
        header("Location: manage-products.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | Bike Shop</title>
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
        
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: var(--bg-black); 
            color: var(--text-white); 
            display: flex; 
            min-height: 100vh;
        }

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
            flex-grow: 1;
            margin-left: var(--sidebar-width); 
            padding: 40px; 
            width: calc(100% - var(--sidebar-width));
        }

        .edit-container {
            background: var(--card-bg);
            width: 100%;
            max-width: 850px;
            padding: 40px;
            border-radius: 25px;
            border: 1px solid var(--border-color);
            margin: 0 auto;
        }

        h1 { font-weight: 900; font-size: 28px; margin-bottom: 35px; color: var(--main-red); display: flex; align-items: center; gap: 15px; }

        .edit-form { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full-width { grid-column: span 2; }

        label { display: block; margin-bottom: 10px; color: var(--text-gray); font-weight: 700; font-size: 14px; }
        
        input, textarea, select {
            width: 100%; padding: 14px; background: #000; border: 1px solid var(--border-color);
            border-radius: 12px; color: white; font-family: 'Cairo'; transition: 0.3s;
        }
        
        input:focus, textarea:focus, select:focus { border-color: var(--main-red); outline: none; }

        /* Current Image Display */
        .image-preview-box {
            display: flex; align-items: center; gap: 20px; background: #080808;
            padding: 20px; border-radius: 15px; border: 1px dashed #333;
        }
        
        .current-img { 
            width: 100px; height: 100px; object-fit: cover; 
            border-radius: 12px; border: 2px solid var(--main-red); 
        }

        .form-actions {
            margin-top: 40px; display: flex; gap: 20px; grid-column: span 2;
        }

        .btn-update {
            flex: 2; background: var(--main-red); color: white; border: none;
            padding: 16px; border-radius: 12px; cursor: pointer; font-weight: 900; 
            transition: 0.3s; font-size: 16px;
        }
        
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(204, 51, 31, 0.4); }

        .btn-cancel {
            flex: 1; background: #1a1a1a; color: white; text-decoration: none;
            padding: 16px; border-radius: 12px; text-align: center; font-weight: 700; 
            transition: 0.3s; border: 1px solid #333;
        }
        .btn-cancel:hover { background: #252525; }

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
        <div class="edit-container">
            <h1><i class="fas fa-pen-square"></i> Edit Bike Details</h1>
            
            <form class="edit-form" action="edit-product.php?id=<?php echo $product['id']; ?>" method="POST" enctype="multipart/form-data">
                
                <div>
                    <label>Product Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div>
                    <label>Price (SR)</label>
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>

                <div class="full-width">
                    <label>Description</label>
                    <textarea name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div>
                    <label>Category</label>
                    <select name="category" required>
                        <?php 
                        $categories_result->data_seek(0);
                        while ($category = $categories_result->fetch_assoc()) { ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label>Stock Quantity</label>
                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                </div>

                <div class="full-width">
                    <label>Product Image</label>
                    <div class="image-preview-box">
                        <?php 
                            $img_src = !empty($product['image']) ? '../uploads/' . basename($product['image']) : '../assets/no-image.png';
                        ?>
                        <img src="<?php echo $img_src; ?>" class="current-img" alt="Product">
                        <div style="flex: 1;">
                            <span style="font-size: 13px; color: var(--text-gray); display: block; margin-bottom: 10px;">
                                <i class="fas fa-info-circle"></i> Current file: <?php echo basename($product['image']); ?>
                            </span>
                            <input type="file" name="image" accept="image/*" style="border:none; padding:0; background:transparent;">
                            <p style="font-size: 12px; color: #666; margin-top: 5px;">Leave empty to keep current image.</p>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-update" name="edit_product">Save Changes</button>
                    <a href="manage-products.php" class="btn-cancel">Back to Inventory</a>
                </div>

            </form>
        </div>
    </main>

</body>
</html>