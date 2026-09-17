<?php
session_start();

// --- 1. DB CONNECTION & MOCK DATA HANDLING ---
$db_connected = false;
$cart_items = [];
$products = [];
$total_cost = 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Try to include DB
if (file_exists('../includes/db.php')) {
    include '../includes/db.php';
    if (isset($conn)) $db_connected = true;
}

// --- 2. HANDLE ACTIONS (Real Logic) ---
if ($db_connected && $user_id) {
    
    // Add/Update Cart
    if (isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            $new_quantity = $cart_item['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$new_quantity, $user_id, $product_id]);
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }
    }

    // Remove
    if (isset($_POST['remove_from_cart'])) {
        $product_id = $_POST['product_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    }

    // Update Quantity
    if (isset($_POST['update_quantity'])) {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
        }
    }

    // Fetch Data
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($cart_items)) {
        $product_ids = array_column($cart_items, 'product_id');
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 
// --- 3. MOCK DATA (For Preview/No DB) ---
else {
    // If no DB or User, simulate a cart for design preview
    $products = [
        ['id' => 1, 'name' => 'Neon Cyber Headset', 'price' => 199.99, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&q=80', 'category' => 'Audio'],
        ['id' => 2, 'name' => 'Minimalist Watch', 'price' => 249.50, 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&q=80', 'category' => 'Accessories']
    ];
    $cart_items = [
        ['product_id' => 1, 'quantity' => 1],
        ['product_id' => 2, 'quantity' => 2]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Lumina</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- CSS VARIABLES & RESET --- */
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --accent: #ec4899;
            --dark: #0f172a;
            --light: #f8fafc;
            --white: #ffffff;
            --glass: rgba(255, 255, 255, 0.85);
            --glass-strong: rgba(255, 255, 255, 0.95);
            --border: rgba(255, 255, 255, 0.6);
            --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
            --radius: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            /* Matching gradient from index.php */
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        /* --- ANIMATIONS --- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- LAYOUT --- */
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
        }

        .page-header a {
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .page-header a:hover { color: var(--primary); }

        /* Two Column Layout */
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            align-items: start;
        }

        /* --- CART ITEMS LIST --- */
        .cart-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .cart-item-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            display: flex;
            gap: 1.5rem;
            align-items: center;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .cart-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.2);
            z-index: 10;
        }

        /* Stagger animation */
        .cart-item-card:nth-child(1) { animation-delay: 0.1s; }
        .cart-item-card:nth-child(2) { animation-delay: 0.2s; }
        .cart-item-card:nth-child(3) { animation-delay: 0.3s; }

        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .item-details {
            flex: 1;
        }

        .item-category {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .item-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .item-price-unit {
            color: #64748b;
            font-size: 0.95rem;
        }

        .item-total-price {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.1rem;
        }

        /* Quantity Controls */
        .quantity-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.5);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 2px;
        }

        .qty-btn {
            background: none;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            color: var(--dark);
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover { background: rgba(0,0,0,0.05); }

        .qty-input {
            width: 40px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: 600;
            font-family: inherit;
            pointer-events: none; /* Just for display in this design */
        }

        /* Actions */
        .btn-remove {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-remove:hover {
            background: #ef4444;
            color: white;
            transform: rotate(90deg);
        }

        /* --- SUMMARY SIDEBAR --- */
        .order-summary {
            background: var(--glass-strong);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid white;
            position: sticky;
            top: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        .summary-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #64748b;
        }

        .summary-row.total {
            color: var(--dark);
            font-weight: 800;
            font-size: 1.5rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid #f1f5f9;
        }

        .btn-checkout {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 2rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: block;
            text-align: center;
            text-decoration: none;
        }

        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .empty-cart-msg {
            text-align: center;
            padding: 4rem;
            grid-column: 1 / -1;
            background: var(--glass);
            border-radius: var(--radius);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .cart-grid { grid-template-columns: 1fr; }
            .order-summary { position: static; }
        }

        @media (max-width: 600px) {
            .cart-item-card { flex-direction: column; text-align: center; }
            .item-image { width: 100%; height: 200px; }
            .quantity-wrapper { margin: 1rem auto; }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- Header -->
        <div class="page-header">
            <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
            <h1>Your Shopping Cart</h1>
        </div>

        <?php if (empty($cart_items) && empty($products)): ?>
            
            <div class="empty-cart-msg">
                <i class="fa-solid fa-basket-shopping" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                <h2>Your cart is empty</h2>
                <p style="color: #64748b; margin-top: 0.5rem;">Looks like you haven't added anything yet.</p>
                <a href="../index.php" class="btn-checkout" style="display: inline-block; width: auto; padding: 0.8rem 2rem; margin-top: 1.5rem;">Start Shopping</a>
            </div>

        <?php else: ?>

            <div class="cart-grid">
                
                <!-- Left Column: Items -->
                <div class="cart-list">
                    <?php 
                    // Calculate totals and render items
                    foreach ($products as $product):
                        $quantity = 0;
                        foreach ($cart_items as $ci) {
                            if ($ci['product_id'] == $product['id']) {
                                $quantity = $ci['quantity'];
                                break;
                            }
                        }
                        if ($quantity == 0) continue;

                        $line_total = $product['price'] * $quantity;
                        $total_cost += $line_total;

                        // Image handling (Mock vs Real)
                        $imgSrc = $product['image'];
                        if (!preg_match("~^(?:f|ht)tps?://~i", $imgSrc)) {
                            $imgSrc = "../images/" . htmlspecialchars($imgSrc);
                        }
                    ?>
                    
                    <div class="cart-item-card">
                        <img src="<?= $imgSrc; ?>" alt="<?= htmlspecialchars($product['name']); ?>" class="item-image">
                        
                        <div class="item-details">
                            <div class="item-category"><?= isset($product['category']) ? $product['category'] : 'Product'; ?></div>
                            <h3 class="item-name"><?= htmlspecialchars($product['name']); ?></h3>
                            <div class="item-price-unit">$<?= number_format($product['price'], 2); ?> each</div>
                        </div>

                        <!-- Modern Quantity Control with Forms -->
                        <form method="POST" style="display: flex; align-items: center; gap: 1rem;">
                            <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                            
                            <div class="quantity-wrapper">
                                <!-- Minus Button -->
                                <button type="submit" name="update_quantity" value="<?= $quantity - 1; ?>" class="qty-btn" onclick="this.form.querySelector('.qty-input-hidden').value=<?= $quantity - 1; ?>">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                
                                <!-- Display Number -->
                                <input type="text" value="<?= $quantity; ?>" class="qty-input" readonly>
                                <input type="hidden" name="quantity" class="qty-input-hidden" value="<?= $quantity; ?>">

                                <!-- Plus Button -->
                                <button type="submit" name="update_quantity" value="<?= $quantity + 1; ?>" class="qty-btn" onclick="this.form.querySelector('.qty-input-hidden').value=<?= $quantity + 1; ?>">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <button type="submit" name="remove_from_cart" class="btn-remove" title="Remove Item">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>

                        <div class="item-total-price">
                            ₹<?= number_format($line_total, 2); ?>
                        </div>
                    </div>

                    <?php endforeach; ?>
                </div>

                <!-- Right Column: Summary -->
                <div class="order-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?= number_format($total_cost, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping Estimate</span>
                        <span>₹5.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>₹<?= number_format($total_cost * 0.08, 2); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>₹<?= number_format($total_cost * 1.08 + 5, 2); ?></span>
                    </div>

                    <a href="address.php" class="btn-checkout">
                        Proceed to Checkout <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    
                    <div style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: #94a3b8;">
                        <i class="fa-solid fa-lock"></i> Secure Checkout
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

</body>
</html>