<?php
session_start();
include '../includes/db.php';

// Get Product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($product_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Handle error
    }
}

// Redirect if product not found
if (!$product) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | Lumina</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --accent: #ec4899;
            --dark: #0f172a;
            --light: #f8fafc;
            --white: #ffffff;
            --glass: rgba(255, 255, 255, 0.95);
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--light);
            background-image: radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.08) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .show-container {
            max-width: 1100px;
            width: 100%;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .image-section {
            background: #ffffff;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #f1f5f9;
        }

        .product-large-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 20px;
        }

        .content-section {
            padding: 3.5rem;
            display: flex;
            flex-direction: column;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: color 0.2s;
        }
        .back-btn:hover { color: var(--primary); }

        .category-badge {
            display: inline-block;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .price-tag {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .description-box {
            color: #475569;
            line-height: 1.8;
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            flex-grow: 1;
        }

        .action-row {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .btn-add {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1.2rem;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.3);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .show-container { grid-template-columns: 1fr; }
            .image-section { border-right: none; border-bottom: 1px solid #f1f5f9; }
            .content-section { padding: 2rem; }
            h1 { font-size: 2.2rem; }
        }
    </style>
</head>
<body>

    <div class="show-container">
        <!-- Left: Image -->
        <div class="image-section">
            <?php 
                $imgSrc = $product['image'];
                if (!preg_match("~^(?:f|ht)tps?://~i", $imgSrc)) {
                    $imgSrc = "../images/" . htmlspecialchars($imgSrc);
                }
            ?>
            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-large-image">
        </div>

        <!-- Right: Content -->
        <div class="content-section">
            <a href="../index.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to Catalog
            </a>

            <div class="category-badge">
                <?= htmlspecialchars($product['category'] ?? 'Premium Gadget') ?>
            </div>

            <h1><?= htmlspecialchars($product['name']) ?></h1>

            <div class="price-tag">
                ₹<?= number_format($product['price'], 2) ?>
            </div>

            <div class="description-box">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>

            <div class="action-row">
                <form method="POST" action="cart.php" style="width: 100%;">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button type="submit" name="add_to_cart" class="btn-add">
                        Add to Shopping Cart <i class="fa-solid fa-cart-plus"></i>
                    </button>
                </form>
            </div>
            
            <p style="margin-top: 2rem; color: #94a3b8; font-size: 0.85rem;">
                <i class="fa-solid fa-shield-check"></i> 1 Year Warranty & Free Shipping Included
            </p>
        </div>
    </div>

</body>
</html>