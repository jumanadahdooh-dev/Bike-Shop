<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $offer_id = $_GET['id'];

    $offer_sql = "SELECT * FROM offers WHERE id = ?";
    $stmt = $conn->prepare($offer_sql);
    $stmt->bind_param("i", $offer_id);
    $stmt->execute();
    $offer = $stmt->get_result()->fetch_assoc();

    if (!$offer) { die("Offer not found."); }

    $categories_result = $conn->query("SELECT * FROM categories");
    $products_result = $conn->query("SELECT * FROM products");

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_offer'])) {
        $offer_name = $_POST['name'];
        $offer_description = $_POST['description'];
        $offer_price = $_POST['price'];
        $product_id = $_POST['product'];
        $category_id = $_POST['category'];
        $offer_end_time = $_POST['end_time'];

        $db_image_path = $offer['image']; 

        if (!empty($_FILES['image']['name'])) {
            $image_name = time() . '_' . $_FILES['image']['name'];
            $target_path = __DIR__ . '/../uploads/' . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $db_image_path = $image_name; 
            }
        }

        $update_sql = "UPDATE offers SET name = ?, description = ?, price = ?, category_id = ?, product_id = ?, image = ?, end_time = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssdisssi", $offer_name, $offer_description, $offer_price, $category_id, $product_id, $db_image_path, $offer_end_time, $offer_id);

        if ($stmt->execute()) {
            header("Location: manage-offers.php");
            exit();
        } else {
            $error_message = "Error updating offer.";
        }
    }
} else {
    header("Location: manage-offers.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Offer | Bike Shop</title>
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

        .back-link { 
            color: var(--text-gray); 
            text-decoration: none; 
            margin-bottom: 25px; 
            display: inline-flex; 
            align-items: center;
            gap: 10px;
            font-weight: 700;
            transition: 0.3s;
            font-size: 14px;
        }
        .back-link:hover { color: var(--main-red); transform: translateX(-5px); }

        /* Form Card */
        .edit-card {
            background: var(--card-bg); 
            padding: 40px; 
            border-radius: 25px; 
            border: 1px solid var(--border-color);
            max-width: 900px; 
            margin: 0 auto;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }

        .edit-card h1 { font-weight: 900; font-size: 26px; margin-bottom: 30px; color: var(--main-red); display: flex; align-items: center; gap: 10px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full-width { grid-column: span 2; }

        .form-group label { display: block; margin-bottom: 10px; color: var(--text-gray); font-size: 14px; font-weight: 700; }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; background: #000; border: 1px solid var(--border-color); color: #fff;
            padding: 14px; border-radius: 12px; font-family: 'Cairo'; outline: none;
            transition: 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--main-red); }

        .image-preview-wrapper {
            display: flex; align-items: center; gap: 20px; background: #080808;
            padding: 15px; border-radius: 15px; border: 1px dashed #333;
        }
        
        .current-img { width: 90px; height: 90px; object-fit: cover; border-radius: 12px; border: 2px solid var(--main-red); }

        .btn-submit {
            background: var(--main-red); color: white; border: none; padding: 18px;
            border-radius: 12px; font-weight: 900; cursor: pointer; transition: 0.3s; 
            width: 100%; margin-top: 20px; font-size: 16px; text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(204, 51, 31, 0.4); }

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
            <a href="manage-offers.php" class="active"><i class="fas fa-percent"></i> <span>Offers</span></a>
            <a href="manage-categories.php"><i class="fas fa-list"></i> <span>Categories</span></a>
        </nav>
        <a href="logout.php" class="btn-logout-nav">
            <i class="fas fa-sign-out-alt"></i> <span style="margin-left:10px">Logout</span>
        </a>
    </aside>

    <main class="main-content">
        <a href="manage-offers.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Offers</a>
        
        <div class="edit-card">
            <h1><i class="fas fa-edit"></i> Update Offer</h1>
            
            <form action="edit-offer.php?id=<?php echo $offer['id']; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Offer Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($offer['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Discounted Price (SR)</label>
                        <input type="number" name="price" value="<?php echo htmlspecialchars($offer['price']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Target Product</label>
                        <select name="product" required>
                            <?php 
                            $products_result->data_seek(0);
                            while ($product = $products_result->fetch_assoc()) { ?>
                                <option value="<?php echo $product['id']; ?>" <?php if ($product['id'] == $offer['product_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <?php 
                            $categories_result->data_seek(0);
                            while ($category = $categories_result->fetch_assoc()) { ?>
                                <option value="<?php echo $category['id']; ?>" <?php if ($category['id'] == $offer['category_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Duration</label>
                        <select name="end_time" required>
                            <option value="24" <?php if ($offer['end_time'] == '24') echo 'selected'; ?>>24 Hours</option>
                            <option value="2" <?php if ($offer['end_time'] == '2') echo 'selected'; ?>>2 Hours</option>
                            <option value="0" <?php if ($offer['end_time'] == '0') echo 'selected'; ?>>Until Sold Out</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Offer Image</label>
                        <div class="image-preview-wrapper">
                            <img src="../uploads/<?php echo basename($offer['image']); ?>" class="current-img" alt="current">
                            <div style="flex-grow: 1;">
                                <input type="file" name="image" style="border:none; padding:0; font-size: 13px;">
                                <p style="font-size: 11px; color: #555; margin-top: 5px;">Upload new to replace</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" rows="4" required><?php echo htmlspecialchars($offer['description']); ?></textarea>
                    </div>
                </div>

                <button type="submit" name="update_offer" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </main>

</body>
</html>