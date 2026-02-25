<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// استعلام العروض
$offers_sql = "SELECT offers.id, offers.name, offers.description, offers.price, offers.created_at, offers.updated_at, offers.image, offers.end_time, categories.name AS category_name, products.name AS product_name, products.stock
               FROM offers 
               JOIN categories ON offers.category_id = categories.id
               JOIN products ON offers.product_id = products.id";
$offers_result = $conn->query($offers_sql);

if (!$offers_result) { die("Error in SQL query: " . $conn->error); }

$total_offers_sql = "SELECT COUNT(*) AS total_count FROM offers";
$total_offers_result = $conn->query($total_offers_sql);
$total_offers = $total_offers_result->fetch_assoc()['total_count'];

// منطق إضافة العرض
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_offer'])) {
    $offer_name = $_POST['name'];
    $offer_description = $_POST['description'];
    $offer_price = $_POST['price'];
    $product_id = $_POST['product'];
    $category_id = $_POST['category'];
    $offer_end_time = $_POST['end_time'];

    $product_sql = "SELECT categories.name AS category_name, products.image AS product_image FROM products 
                    JOIN categories ON products.category_id = categories.id 
                    WHERE products.id = ?";
    $stmt = $conn->prepare($product_sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_result = $stmt->get_result();

    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        $product_image = $product['product_image'];
    }

    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_dir = __DIR__ . '/../uploads/';
    
    if (empty($image_name)) {
        $db_image_name = basename($product_image);
    } else {
        $db_image_name = time() . '_' . basename($image_name);
        move_uploaded_file($image_tmp, $image_dir . $db_image_name);
    }

    $sql = "INSERT INTO offers (name, description, price, category_id, product_id, image, end_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdisss", $offer_name, $offer_description, $offer_price, $category_id, $product_id, $db_image_name, $offer_end_time);

    if ($stmt->execute()) {
        header("Location: manage-offers.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Offers | Bike Shop</title>
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
        body { font-family: 'Cairo', sans-serif; background-color: var(--bg-black); color: var(--text-white); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* --- Sidebar الموحد مع زر اللوقوت الثابت --- */
        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding: 30px 15px;
            position: fixed;
            height: 100vh;
            left: 0; top: 0;
            display: flex;
            flex-direction: column; /* لترتيب العناصر عمودياً */
            z-index: 1000;
        }

        .sidebar h2 { color: var(--main-red); font-weight: 900; text-align: center; margin-bottom: 40px; font-size: 22px; }
        
        .menu-links { flex-grow: 1; display: flex; flex-direction: column; gap: 5px; } 
        
        .sidebar a {
            display: flex; align-items: center; color: var(--text-gray); text-decoration: none;
            padding: 12px 15px; border-radius: 10px; font-weight: 700; transition: 0.3s;
        }
        
        .sidebar a i { margin-right: 12px; font-size: 18px; width: 25px; text-align: center; }
        .sidebar a:hover, .sidebar a.active { background: rgba(204, 51, 31, 0.1); color: var(--main-red); }
        .sidebar a.active { background: var(--main-red); color: #fff; }

        /* زر اللوقوت المصلح */
        .btn-logout-nav { 
            margin-top: auto; 
            margin-bottom: 20px;
            border: 1px solid #333 !important; 
            color: var(--text-gray) !important; 
            justify-content: center !important; 
            background: transparent !important;
        }
        .btn-logout-nav:hover { border-color: var(--main-red) !important; color: var(--main-red) !important; }

        /* --- Main Content --- */
        .main-content { 
            margin-left: var(--sidebar-width); 
            flex: 1; 
            padding: 30px 40px; 
            width: calc(100% - var(--sidebar-width)); 
        }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .stats-badge { background: var(--card-bg); padding: 10px 20px; border-radius: 10px; border: 1px solid var(--border-color); color: var(--text-gray); font-weight: 700; }
        .stats-badge span { color: var(--main-red); font-weight: 900; margin-left: 5px; }

        /* Form Styling */
        #add-offer-form { 
            background: var(--card-bg); padding: 30px; border-radius: 20px; border: 1px solid var(--border-color);
            margin-bottom: 40px; display: none; animation: slideDown 0.4s ease-out;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-gray); font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; background: #000; border: 1px solid var(--border-color); color: #fff;
            padding: 12px; border-radius: 8px; font-family: 'Cairo'; outline: none;
        }
        .form-group textarea { height: 100px; grid-column: span 2; }

        /* Offer Cards */
        .offers-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
        .offer-card { 
            background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border-color); 
            overflow: hidden; transition: 0.3s;
        }
        .offer-card:hover { transform: translateY(-10px); border-color: var(--main-red); }
        
        .offer-image-box { height: 200px; width: 100%; overflow: hidden; }
        .offer-image-box img { width: 100%; height: 100%; object-fit: cover; }
        
        .offer-content { padding: 20px; }
        .offer-name { font-weight: 900; font-size: 18px; margin-bottom: 10px; }
        .offer-desc { color: var(--text-gray); font-size: 13px; line-height: 1.6; margin-bottom: 15px; height: 40px; overflow: hidden; }
        
        .price-tag { background: #000; padding: 10px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #222; }
        .old-p { text-decoration: line-through; color: #666; font-size: 12px; }
        .new-p { color: var(--main-red); font-weight: 900; font-size: 18px; }

        .btn-main { background: var(--main-red); color: #fff; padding: 12px 25px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-main:hover { opacity: 0.9; transform: scale(1.02); }
        
        .action-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .btn-edit { background: #222; color: #fff; text-align: center; padding: 10px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.3s; }
        .btn-edit:hover { background: #333; }
        .btn-del { background: rgba(231, 76, 60, 0.05); color: #e74c3c; text-align: center; padding: 10px; border-radius: 8px; text-decoration: none; font-size: 13px; border: 1px solid #e74c3c; font-weight: 700; transition: 0.3s; }
        .btn-del:hover { background: #e74c3c; color: #fff; }

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
        <div class="header-flex">
            <h1>Offer Management</h1>
            <div class="stats-badge">Total Offers: <span><?php echo $total_offers; ?></span></div>
        </div>

        <button class="btn-main" onclick="toggleAddOfferForm()" style="margin-bottom: 30px;">
            <i class="fas fa-plus"></i> Create New Offer
        </button>

        <div id="add-offer-form">
            <h2 style="margin-bottom: 20px; color: var(--main-red);">Add New Special Offer</h2>
            <form action="manage-offers.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group"><label>Offer Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Discounted Price (SR)</label><input type="number" name="price" required></div>
                    <div class="form-group">
                        <label>Target Product</label>
                        <select name="product" required>
                            <?php 
                            $p_res = $conn->query("SELECT id, name FROM products");
                            while($p = $p_res->fetch_assoc()) echo "<option value='{$p['id']}'>{$p['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <?php 
                            $c_res = $conn->query("SELECT id, name FROM categories");
                            while($c = $c_res->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>End Duration</label>
                        <select name="end_time" required>
                            <option value="24">24 Hours</option>
                            <option value="48">48 Hours</option>
                            <option value="0">Until Stock Ends</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Offer Image (Optional)</label><input type="file" name="image"></div>
                    <div class="form-group" style="grid-column: span 2;"><label>Description</label><textarea name="description" required></textarea></div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="add_offer" class="btn-main">Publish Offer</button>
                    <button type="button" class="btn-edit" onclick="toggleAddOfferForm()" style="background:#333; cursor:pointer;">Cancel</button>
                </div>
            </form>
        </div>

        <div class="offers-container">
            <?php if ($offers_result->num_rows > 0) {
                while ($offer = $offers_result->fetch_assoc()) { ?>
                    <div class="offer-card">
                        <div class="offer-image-box">
                            <img src="../uploads/<?php echo basename($offer['image']); ?>" alt="Offer">
                        </div>
                        <div class="offer-content">
                            <div class="offer-name"><?php echo htmlspecialchars($offer['name']); ?></div>
                            <div class="offer-desc"><?php echo htmlspecialchars(substr($offer['description'], 0, 80)) . '...'; ?></div>
                            
                            <div class="price-tag">
                                <span class="old-p">Was: <?php echo $offer['price']; ?> SR</span><br>
                                <span class="new-p">Now: <?php echo number_format($offer['price'] * 0.8, 2); ?> SR</span>
                            </div>

                            <div class="action-btns">
                                <a href="edit-offer.php?id=<?php echo $offer['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="delete-offer.php?id=<?php echo $offer['id']; ?>" class="btn-del" onclick="return confirm('Delete this offer?')"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </div>
                    </div>
                <?php }
            } else { echo "<p style='color:var(--text-gray)'>No offers available yet.</p>"; } ?>
        </div>
    </main>

    <script>
        function toggleAddOfferForm() {
            const form = document.getElementById('add-offer-form');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }
    </script>
</body>
</html>