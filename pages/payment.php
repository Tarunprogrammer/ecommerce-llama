<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];

if (!isset($_SESSION['selected_address'])) {
    header("Location: address.php");
    exit();
}

// Fetch selected address
$stmt = $conn->prepare("SELECT address FROM addresses WHERE id = ?");
$stmt->execute([$_SESSION['selected_address']]);
$selected_address = $stmt->fetchColumn();

// Fetch cart items
$stmt = $conn->prepare("SELECT cart.product_id, cart.quantity, products.name, products.price 
                        FROM cart INNER JOIN products ON cart.product_id = products.id 
                        WHERE cart.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = 0;

foreach ($cart_items as $item) {
    $total_cost += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';

    if (!empty($cart_items)) {
        $conn->beginTransaction();
        try {
            // Insert order into orders table
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_cost, address, status, payment_method) 
                                    VALUES (?, ?, ?, 'Pending', ?)");
            $stmt->execute([$user_id, $total_cost, $selected_address, $payment_method]);
            $order_id = $conn->lastInsertId();

            // Insert each cart item into order_items table
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }

            // Clear cart after order is placed
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $conn->commit();

            // Unset session variable and redirect to orderplaced.php
            unset($_SESSION['selected_address']);
            header("Location: orderplaced.php");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            echo "Error processing order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <style>
        /* General Page Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: transparent;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        /* Main Container */
        .container {
            width: 50%;
            margin: auto;
            background:rgba(26, 103, 186, 0.48);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }

        /* Headings */
        h2, h3 {
            color: #333;
        }

        /* Order Summary */
        .order-summary {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            background: #e9e9e9;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            text-decoration: none;
            background: linear-gradient(135deg, rgb(156, 40, 167), rgb(68, 70, 199));
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background: linear-gradient(135deg, rgb(68, 70, 199), rgb(156, 40, 167));
        }

        /* Payment Method */
        .payment-method {
            margin-top: 20px;
        }

        .payment-method label {
            display: block;
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }

        .payment-method input {
            margin-right: 10px;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .container {
                width: 80%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Payment</h2>

            <?php if (empty($cart_items)) : ?>
                <p>Your cart is empty.</p>
            <?php else : ?>
                <ul>
                    <?php foreach ($cart_items as $item) : ?>
                        <li><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>) -  ₹<?= number_format($item['price'] * $item['quantity'], 2) ?></li>
                    <?php endforeach; ?>
                </ul>
                <h3>Total: ₹<?= number_format($total_cost, 2) ?></h3>
            <?php endif; ?>
        </div>

        <h3>Select Payment Method</h3>
        <form method="POST">
            <div class="payment-method">
                <label><input type="radio" name="payment_method" value="Cash on Delivery" checked> Cash on Delivery</label>
                <label><input type="radio" name="payment_method" value="PayPal">  Google Pay</label>
            </div>
            
            <a href="orderplaced.php" class="btn">Confirm Payment</a>
        </form>
    </div>
</body>
</html>
