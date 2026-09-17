<?php
session_start();

// --- 1. SETUP & MOCK DATA HANDLING ---
$db_connected = false;
$addresses = [];
$error_message = '';

// Check Login (Simulated for demo if no DB)
if (!isset($_SESSION['user_id'])) {
    $user_id = 1; // Fallback demo user
} else {
    $user_id = $_SESSION['user_id'];
}

// Try to include DB
if (file_exists('../includes/db.php')) {
    include '../includes/db.php';
    if (isset($conn)) $db_connected = true;
}

// --- 2. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CASE A: Selected an existing address
    if (!empty($_POST['selected_address'])) {
        $_SESSION['selected_address'] = $_POST['selected_address'];
        header("Location: checkout.php");
        exit();
    } 
    // CASE B: Added a new address
    elseif (
        !empty($_POST['house_no']) && 
        !empty($_POST['street']) && 
        !empty($_POST['village_town']) && 
        !empty($_POST['pincode'])
    ) {
        $new_address_string = implode(", ", [
            trim($_POST['house_no']),
            trim($_POST['street']),
            trim($_POST['village_town']),
            trim($_POST['district'] ?? ''),
            trim($_POST['state'] ?? ''),
            trim($_POST['landmark'] ?? ''),
            trim($_POST['pincode'])
        ]);
        
        // Remove empty segments
        $new_address_string = str_replace(", ,", ",", $new_address_string);

        if ($db_connected) {
            $stmt = $conn->prepare("INSERT INTO addresses (user_id, address) VALUES (?, ?)");
            $stmt->execute([$user_id, $new_address_string]);
            $_SESSION['selected_address'] = $conn->lastInsertId();
        } else {
            // Mock success
            $_SESSION['selected_address'] = 'mock_new_id';
        }

        header("Location: checkout.php");
        exit();
    } else {
        $error_message = "Please select an address or fill in the required fields.";
    }
}

// --- 3. FETCH DATA ---
if ($db_connected) {
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Mock Data for Design Preview
    $addresses = [
        ['id' => 101, 'address' => '12B, Cyber Heights, Tech Park Road, Silicon Valley, CA, 94000'],
        ['id' => 102, 'address' => 'Flat 404, Green Villa, North Street, Downtown, NY, 10001']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Address | Lumina</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- CSS VARIABLES --- */
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --accent: #ec4899;
            --dark: #0f172a;
            --light: #f8fafc;
            --glass: rgba(255, 255, 255, 0.7);
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
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
        }

        /* --- LAYOUT --- */
        .container {
            width: 100%;
            max-width: 600px; /* Reduced width for single column */
            animation: fadeInUp 0.6s ease-out;
            position: relative;
            z-index: 1;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .page-header h2 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .error-banner {
            background: #fee2e2;
            color: #ef4444;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            border: 1px solid #fecaca;
        }

        /* --- SECTION: SAVED ADDRESSES --- */
        .section-card {
            background: var(--glass-strong);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
        }

        .address-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Custom Radio Card */
        .address-option {
            position: relative;
            cursor: pointer;
        }

        .address-option input[type="radio"] {
            display: none;
        }

        .address-card-visual {
            background: rgba(255,255,255,0.5);
            border: 2px solid transparent;
            padding: 1.2rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .address-option:hover .address-card-visual {
            background: rgba(255,255,255,0.8);
            transform: translateY(-2px);
        }

        .address-option input:checked + .address-card-visual {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.05);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }

        .radio-circle {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            flex-shrink: 0;
            position: relative;
            margin-top: 2px;
        }

        .address-option input:checked + .address-card-visual .radio-circle {
            border-color: var(--primary);
        }

        .address-option input:checked + .address-card-visual .radio-circle::after {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 10px; height: 10px;
            background: var(--primary);
            border-radius: 50%;
        }

        .address-text {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.5;
        }

        /* --- MODAL STYLES --- */
        .modal-overlay {
            display: none; /* Hidden by default */
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-card {
            background: #ffffff;
            padding: 2rem;
            border-radius: var(--radius);
            width: 100%;
            max-width: 600px;
            position: relative;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-overlay.active .modal-card {
            transform: translateY(0);
        }

        .close-modal {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-modal:hover { color: #ef4444; }

        /* --- FORM STYLES --- */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* --- BUTTONS --- */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            margin-top: 1.5rem;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.4);
        }

        .btn-secondary {
            background: transparent;
            border: 2px dashed #cbd5e1;
            color: #64748b;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(79, 70, 229, 0.05);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <div class="page-header">
            <h2>Shipping Details</h2>
            <p style="color: #64748b;">Select where you want your order delivered.</p>
        </div>

        <?php if (!empty($error_message)) : ?>
            <div class="error-banner">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- SAVED ADDRESSES SECTION -->
        <form method="POST" class="section-card">
            <div class="section-title">
                <i class="fa-solid fa-location-dot"></i> Saved Addresses
            </div>
            
            <div class="address-list">
                <?php if (!empty($addresses)) : ?>
                    <?php foreach ($addresses as $index => $address) : ?>
                        <label class="address-option">
                            <input type="radio" name="selected_address" value="<?= $address['id'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
                            <div class="address-card-visual">
                                <div class="radio-circle"></div>
                                <div class="address-text">
                                    <?= htmlspecialchars($address['address']) ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="btn-primary">
                        Deliver Here <i class="fa-solid fa-arrow-right"></i>
                    </button>

                <?php else : ?>
                    <div style="text-align: center; padding: 2rem; color: #94a3b8;">
                        <i class="fa-regular fa-folder-open" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>No saved addresses found.</p>
                    </div>
                <?php endif; ?>

                <!-- Button to Open Modal -->
                <button type="button" class="btn-secondary" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> Add New Address
                </button>
            </div>
        </form>

    </div>

    <!-- POPUP MODAL FOR ADDING ADDRESS -->
    <div id="addressModal" class="modal-overlay">
        <div class="modal-card">
            <button class="close-modal" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            
            <div class="section-title">
                <i class="fa-solid fa-house-chimney-medical"></i> Add New Address
            </div>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>House No / Flat</label>
                        <input type="text" name="house_no" placeholder="e.g. 12B" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Pincode</label>
                        <input type="text" name="pincode" placeholder="e.g. 500081" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Street / Area</label>
                        <input type="text" name="street" placeholder="e.g. Main Street, Tech Park" required>
                    </div>

                    <div class="form-group">
                        <label>Village / Town</label>
                        <input type="text" name="village_town" required>
                    </div>

                    <div class="form-group">
                        <label>District</label>
                        <input type="text" name="district">
                    </div>

                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state">
                    </div>

                    <div class="form-group">
                        <label>Landmark (Optional)</label>
                        <input type="text" name="landmark">
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%);">
                    Save & Deliver Here <i class="fa-solid fa-check"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('addressModal');

        function openModal() {
            modal.style.display = 'flex';
            // Slight delay to allow display:flex to apply before adding active class for transition
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }

        function closeModal() {
            modal.classList.remove('active');
            // Wait for transition to finish before hiding
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Close modal if clicked outside the card
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>

</body>
</html>