<?php
session_start();

// --- 1. SETUP & DB CONNECTION ---
$db_connected = false;
$selected_address = "";
$cart_items = [];
$total_cost = 0;

// Check Login
if (!isset($_SESSION['user_id'])) {
    $user_id = 1; // Fallback for demo
} else {
    $user_id = $_SESSION['user_id'];
}

// Check Address Selection
if (!isset($_SESSION['selected_address'])) {
    $selected_address_id = 0;
} else {
    $selected_address_id = $_SESSION['selected_address'];
}

// Try to include DB
if (file_exists('../includes/db.php')) {
    include '../includes/db.php';
    if (isset($conn)) $db_connected = true;
}

// --- 2. FETCH DATA ---
if ($db_connected) {
    // Fetch Address
    $stmt = $conn->prepare("SELECT address FROM addresses WHERE id = ?");
    $stmt->execute([$selected_address_id]);
    $selected_address = $stmt->fetchColumn();
    
    // Fallback if no specific address ID was found in session to avoid empty values
    if (!$selected_address) {
        $stmt = $conn->prepare("SELECT address FROM addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $selected_address = $stmt->fetchColumn();
    }

    // Fetch Cart
    $stmt = $conn->prepare("SELECT cart.product_id, cart.quantity, products.name, products.price 
                            FROM cart INNER JOIN products ON cart.product_id = products.id 
                            WHERE cart.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Mock Data
    $selected_address = "12B, Cyber Heights, Tech Park Road, Silicon Valley, CA, 94000";
    $cart_items = [
        ['product_id' => 1, 'name' => 'Neon Cyber Headset', 'price' => 199.99, 'quantity' => 1],
        ['product_id' => 2, 'name' => 'Minimalist Watch', 'price' => 249.50, 'quantity' => 2]
    ];
}

// Calculate Total
foreach ($cart_items as $item) {
    $total_cost += $item['price'] * $item['quantity'];
}

// Apply GST factor consistently across insertions 
$grand_total = $total_cost * 1.08;

// --- 3. HANDLE ORDER PLACEMENT & REDIRECTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if ($db_connected && !empty($cart_items)) {
        if (empty($selected_address)) {
            $error = "Error: Please select a shipping address before checking out.";
        } else {
            try {
                $conn->beginTransaction();

                // Insert Order - Matches your altered database layout cleanly
                $stmt = $conn->prepare("INSERT INTO orders (user_id, total_cost, address, status) VALUES (?, ?, ?, 'Pending')");
                $stmt->execute([$user_id, $grand_total, $selected_address]);
                $order_id = $conn->lastInsertId();

                // Insert Items
                foreach ($cart_items as $item) {
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                }

                // Clear Cart matching your specific schema user bindings
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);

                $conn->commit();
                
                // Store accurate summary total for display on confirmation page
                $_SESSION['last_order'] = [
                    'order_id' => $order_id,
                    'total' => $grand_total
                ];
                
                unset($_SESSION['selected_address']);

                // Redirection path
                header("Location: orderplaced.php");
                exit();

            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        // Mock Success Logic Fallback
        $_SESSION['last_order'] = [
            'order_id' => rand(1000, 9999), 
            'total' => $grand_total
        ];
        
        unset($_SESSION['selected_address']);

        header("Location: orderplaced.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Lumina</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --accent: #ec4899;
            --dark: #0f172a;
            --light: #f8fafc;
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
            background-image: radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.1) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
        }

        .container { width: 100%; max-width: 900px; animation: fadeInUp 0.6s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .checkout-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; }
        .header-row { grid-column: 1 / -1; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header-row h2 { font-size: 2.2rem; font-weight: 800; }

        .glass-card {
            background: var(--glass-strong);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow);
            height: fit-content;
        }

        .section-header {
            font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem;
            color: var(--primary); display: flex; align-items: center; gap: 0.5rem;
            border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;
        }

        .address-box { background: #f1f5f9; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .order-item { display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #f1f5f9; }
        .order-item:last-child { border-bottom: none; }

        .total-row {
            display: flex; justify-content: space-between; margin-top: 1.5rem;
            padding-top: 1.5rem; border-top: 2px solid #e2e8f0;
            font-size: 1.4rem; font-weight: 800; color: var(--dark);
        }

        .btn-confirm {
            width: 100%; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white; border: none; padding: 1.2rem; border-radius: 14px;
            font-weight: 700; font-size: 1.1rem; margin-top: 2rem;
            cursor: pointer; transition: 0.3s; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            display: flex; justify-content: center; align-items: center; gap: 0.75rem;
        }

        .btn-confirm:hover { transform: translateY(-3px); box-shadow: 0 12px 20px rgba(79, 70, 229, 0.4); }

        .error-message {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        @media (max-width: 800px) { .checkout-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-row">
            <h2>Final Review</h2>
            <div style="font-weight: 600; color: #64748b; background: white; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.85rem;">Step 3 of 3</div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <div class="glass-card">
                <div class="section-header">
                    <i class="fa-solid fa-truck-fast"></i> Shipping To
                </div>
                <div class="address-box">
                    <p style="color: #475569; line-height: 1.6;">
                        <?= !empty($selected_address) ? htmlspecialchars($selected_address) : "No address available. Please add an address to your profile."; ?>
                    </p>
                </div>

                <div class="section-header" style="margin-top: 2rem;">
                    <i class="fa-solid fa-basket-shopping"></i> Review Items
                </div>
                <?php if (!empty($cart_items)): ?>
                    <?php foreach ($cart_items as $item) : ?>
                        <div class="order-item">
                            <div>
                                <p style="font-weight: 700;"><?= htmlspecialchars($item['name']) ?></p>
                                <p style="font-size: 0.85rem; color: #64748b;">Qty: <?= $item['quantity'] ?></p>
                            </div>
                            <p style="font-weight: 700;">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748b;">Your checkout cart is empty.</p>
                <?php endif; ?>
            </div>

            <div class="glass-card">
                <div class="section-header">
                    <i class="fa-solid fa-receipt"></i> Order Summary
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: #64748b;">
                    <span>Subtotal</span>
                    <span>₹<?= number_format($total_cost, 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: #64748b;">
                    <span>GST (Est. 8%)</span>
                    <span>₹<?= number_format($total_cost * 0.08, 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: #16a34a; font-weight: 600;">
                    <span>Shipping</span>
                    <span>FREE</span>
                </div>

                <div class="total-row">
                    <span>Total</span>
                    <span>₹<?= number_format($grand_total, 2); ?></span>
                </div>

                <form method="POST">
                    <button type="submit" name="place_order" class="btn-confirm" <?= empty($cart_items) || empty($selected_address) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : '' ?>>
                        Place Order <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
                <p style="text-align: center; font-size: 0.8rem; color: #94a3b8; margin-top: 1.5rem;">
                    <i class="fa-solid fa-lock"></i> SSL Secured Checkout
                </p>
            </div>
        </div>
    </div>

</body>
</html>