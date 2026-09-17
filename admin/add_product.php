<?php
/**
 * Add Product Page
 * Aligned with schema: id, name, price, description, image, created_at
 */
include '../includes/db.php';
session_start();

// Security: Optional - check if user is admin if needed
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }

$message = "";
$message_type = "";

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    
    // Simple validation
    if (!empty($name) && !empty($price) && !empty($image_name)) {
        // Prepare target path
        $target_dir = "../images/";
        
        // Ensure directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . basename($image_name);

        if (move_uploaded_file($image_tmp, $target_file)) {
            try {
                // Insert product details (matches schema provided: name, price, description, image)
                $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $price, $description, $image_name])) {
                    $message = "Success! Product has been added to the catalog.";
                    $message_type = "success";
                }
            } catch (PDOException $e) {
                $message = "Database Error: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = "Failed to upload image. Please check folder permissions.";
            $message_type = "error";
        }
    } else {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | Lumina Admin</title>
    <!-- Google Fonts: Outfit -->
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
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--light);
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.1) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            width: 100%;
            max-width: 600px;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: var(--shadow);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 2rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        h2 i { color: var(--primary); }

        form { display: flex; flex-direction: column; }

        .form-group { margin-bottom: 1.5rem; }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #475569;
        }

        input, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.5);
            font-family: inherit;
            transition: all 0.3s;
            font-size: 1rem;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        textarea { resize: vertical; min-height: 100px; }

        .file-input-wrapper {
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .file-input-wrapper:hover { border-color: var(--primary); }

        button {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }

        button:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 10px 15px rgba(79, 70, 229, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 500;
            text-align: center;
        }

        .alert-success { background: #dcfce7; color: #16a34a; }
        .alert-error { background: #fee2e2; color: #ef4444; }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link a:hover { color: var(--primary); }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa-solid fa-plus-circle"></i> Add New Product</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class="fa-solid <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-circle-exclamation' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" name="name" id="name" placeholder="e.g. Sumsung S24 Ultra" required>
            </div>

            <div class="form-group">
                <label for="price">Price (₹)</label>
                <input type="number" step="0.01" name="price" id="price" placeholder="99999.00" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" placeholder="Enter product details..." required></textarea>
            </div>

            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" name="image" id="image" accept="image/*" required>
            </div>

            <button type="submit" name="add_product">
                Save Product <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>

        <div class="back-link">
            <a href="manage_products.php">
                <i class="fa-solid fa-arrow-left"></i> Back to Manage Products
            </a>
        </div>
    </div>
</body>
</html>