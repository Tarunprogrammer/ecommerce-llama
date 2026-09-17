<?php
session_start();

// --- OLLAMA API HANDLER ---
// This handles the AJAX request from the frontend to talk to Llama 3
if (isset($_GET['chat_query'])) {
    header('Content-Type: application/json');
    $userPrompt = $_GET['chat_query'];
    
    // Prepare the payload for Ollama
    $data = [
        "model" => "llama3",
        "prompt" => "You are Lumina's AI Assistant. You help customers with tech gadgets. Keep answers short and professional. User says: " . $userPrompt,
        "stream" => false
    ];

    $ch = curl_init("http://localhost:11434/api/generate");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        echo json_encode(['success' => true, 'reply' => $result['response']]);
    } else {
        echo json_encode(['success' => false, 'reply' => "I'm offline. Is Ollama running?"]);
    }
    exit();
}

// --- DATABASE CONNECTION & DATA FETCHING ---
$db_connected = false;
$products = [];
$user_details = null;

try {
    if (file_exists('includes/db.php')) {
        include 'includes/db.php';
        if (isset($conn)) {
            $stmt = $conn->query("SELECT * FROM products");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $db_connected = true;

            if (isset($_SESSION['user_id'])) {
                $uStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $uStmt->execute([$_SESSION['user_id']]);
                $user_details = $uStmt->fetch(PDO::FETCH_ASSOC);

                if ($user_details) {
                    $addrStmt = $conn->prepare("SELECT address FROM addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                    $addrStmt->execute([$_SESSION['user_id']]);
                    $latest_address = $addrStmt->fetchColumn();
                    $user_details['current_address'] = $latest_address ?: "No address added yet";
                }
            }
        }
    }
} catch (Exception $e) {}

if (empty($products)) {
    $products = [
        ['id' => 13, 'name' => 'Samsung S24 Ultra', 'price' => 98499.00, 'description' => 'The ultimate flagship for premium users.', 'image' => 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=800&q=80', 'category' => 'Mobile'],
        ['id' => 22, 'name' => 'iPhone 16 Pro Max', 'price' => 144900.00, 'description' => 'Titanium build for a lightweight yet durable design.', 'image' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800&q=80', 'category' => 'Mobile']
    ];
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumina | Premium Store</title>
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
            --glass: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow-float: 0 25px 50px -12px rgba(79, 70, 229, 0.25);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            background-image: radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.1) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        header {
            position: sticky; top: 0; z-index: 1000;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
        }

        .header-container {
            max-width: 1400px; margin: 0 auto; padding: 0 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }

        .brand { font-size: 1.8rem; font-weight: 700; color: var(--dark); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .brand i { color: var(--primary); }

        nav { display: flex; gap: 1.5rem; align-items: center; }
        .nav-link { text-decoration: none; color: #64748b; font-weight: 600; transition: 0.3s; cursor: pointer; }
        .nav-link:hover { color: var(--primary); }

        .user-profile-trigger {
            background: rgba(79, 70, 229, 0.1); padding: 0.5rem 1.2rem;
            border-radius: 50px; color: var(--primary);
            display: flex; align-items: center; gap: 0.6rem;
            border: 1px solid rgba(79, 70, 229, 0.2); transition: 0.3s;
        }

        .hero { padding: 4rem 2rem; text-align: center; max-width: 1000px; margin: 0 auto; }
        .hero h2 { font-size: 3.5rem; font-weight: 800; margin-bottom: 1rem; background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .main-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem 4rem 2rem; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2.5rem; }

        .card {
            background: var(--white); border-radius: 20px; padding: 1rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255,255,255,0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            display: flex; flex-direction: column; overflow: hidden;
            text-decoration: none; color: inherit;
        }
        .card:hover { transform: translateY(-12px); box-shadow: var(--shadow-float); }
        .card-image-wrapper { position: relative; border-radius: 15px; overflow: hidden; height: 250px; margin-bottom: 1rem; }
        .product-image { width: 100%; height: 100%; object-fit: cover; }
        .price { font-size: 1.5rem; font-weight: 800; color: var(--dark); }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 12px;
            font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: 0.3s;
        }

        /* --- AI CHAT WIDGET STYLES --- */
        #ai-widget {
            position: fixed; bottom: 30px; right: 30px; z-index: 5000;
        }
        #ai-button {
            width: 60px; height: 60px; border-radius: 50%; background: var(--primary);
            color: white; border: none; cursor: pointer; font-size: 1.5rem;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4); transition: 0.3s;
        }
        #ai-chat-window {
            position: absolute; bottom: 80px; right: 0; width: 350px; height: 450px;
            background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            display: none; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0;
        }
        .ai-header { background: var(--primary); color: white; padding: 1rem; font-weight: 700; display: flex; justify-content: space-between; }
        #ai-messages { flex: 1; padding: 1rem; overflow-y: auto; background: #f8fafc; font-size: 0.9rem; }
        .message { margin-bottom: 1rem; padding: 0.8rem; border-radius: 12px; max-width: 85%; }
        .bot { background: white; border: 1px solid #e2e8f0; align-self: flex-start; }
        .user { background: var(--primary); color: white; align-self: flex-end; margin-left: auto; }
        .ai-input-area { padding: 1rem; border-top: 1px solid #e2e8f0; display: flex; gap: 0.5rem; }
        .ai-input-area input { flex: 1; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.5rem; outline: none; }

        /* MODAL */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
            display: none; justify-content: center; align-items: center; z-index: 2000;
        }
        .modal-content {
            background: var(--white); padding: 2.5rem; border-radius: 24px;
            width: 100%; max-width: 450px; position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); text-align: center;
        }
        .profile-avatar {
            width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; margin: 0 auto 1.5rem auto; font-weight: 700;
        }
        .detail-row { display: flex; flex-direction: column; padding: 1rem 0; border-bottom: 1px solid #f1f5f9; text-align: left; }
        .detail-label { color: #64748b; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.25rem; }
        .detail-value { color: var(--dark); font-weight: 700; line-height: 1.4; }

        footer { background: var(--white); padding: 3rem 0; text-align: center; border-top: 1px solid var(--glass-border); margin-top: auto; }
    </style>
</head>
<body>

    <header>
        <div class="header-container">
            <a href="index.php" class="brand">
                <i class="fa-solid fa-bolt"></i> Lumina
            </a>
            
            <nav>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-profile-trigger nav-link" onclick="toggleModal()">
                        <i class="fa-solid fa-circle-user"></i> 
                        <span><?= htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                <?php else: ?>
                    <a href="pages/login.php" class="nav-link">Login</a>
                    <a href="pages/register.php" class="nav-link">Register</a>
                <?php endif; ?>

                <a href="pages/cart.php" class="nav-link" title="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                </a>

                <?php if(isset($_SESSION['user_id'])): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="nav-link" style="background:none; border:none; color:#ef4444; font-size: 1.2rem; cursor:pointer;">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </form>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Floating AI Chat Widget -->
    <div id="ai-widget">
        <div id="ai-chat-window">
            <div class="ai-header">
                <span>Lumina AI (Llama 3)</span>
                <i class="fa-solid fa-times" onclick="toggleChat()" style="cursor:pointer"></i>
            </div>
            <div id="ai-messages">
                <div class="message bot">Hello! I'm your local Llama 3 assistant. Ask me anything about our gadgets!</div>
            </div>
            <div class="ai-input-area">
                <input type="text" id="ai-input" placeholder="Type a message..." onkeypress="handleChatKey(event)">
                <button onclick="sendChatMessage()" class="btn-primary" style="padding: 0.5rem 1rem;"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
        <button id="ai-button" onclick="toggleChat()">
            <i class="fa-solid fa-robot"></i>
        </button>
    </div>

    <!-- User Profile Modal -->
    <div class="modal-overlay" id="profileModal" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="close-modal" onclick="toggleModal()">&times;</span>
            <div class="profile-avatar">
                <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
            </div>
            <h2 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($user_details['username'] ?? 'User Profile'); ?></h2>
            <p style="color: #64748b; margin-bottom: 1.5rem;">Member since <?= date('M Y', strtotime($user_details['created_at'] ?? 'now')); ?></p>

            <div class="detail-row">
                <span class="detail-label">Email Address</span>
                <span class="detail-value"><?= htmlspecialchars($user_details['email'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone Number</span>
                <span class="detail-value"><?= htmlspecialchars($user_details['phone_no'] ?? 'Not added'); ?></span>
            </div>
            <div class="detail-row" style="border-bottom: none;">
                <span class="detail-label">Current Address</span>
                <span class="detail-value"><?= htmlspecialchars($user_details['current_address'] ?? 'No address saved yet'); ?></span>
            </div>
        </div>
    </div>

    <section class="hero">
        <h2>Curated Technology <br>For The Future.</h2>
        <p>Experience the next generation of gadgets. Premium quality, sustainable materials, and cutting-edge performance.</p>
    </section>

    <div class="main-container">
        <h3 style="font-size: 2rem; margin-bottom: 2rem;">Trending Now</h3>
        <div class="product-grid">
            <?php foreach ($products as $product) : ?>
                <article class="card">
                    <a href="pages/show.php?id=<?= $product['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                        <div class="card-image-wrapper">
                            <?php 
                                $imgSrc = $product['image'];
                                if (!preg_match("~^(?:f|ht)tps?://~i", $imgSrc)) {
                                    $imgSrc = "images/" . htmlspecialchars($imgSrc);
                                }
                            ?>
                            <img src="<?= $imgSrc; ?>" alt="<?= htmlspecialchars($product['name']); ?>" class="product-image">
                        </div>
                        <div class="card-content">
                            <span style="font-size: 0.75rem; color: var(--primary); font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem; display:block;">
                                <?= $product['category'] ?? 'Premium Gadget'; ?>
                            </span>
                            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;"><?= htmlspecialchars($product['name']); ?></h3>
                            <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">
                                <?= substr(htmlspecialchars($product['description']), 0, 75); ?>...
                            </p>
                        </div>
                    </a>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 1rem;">
                        <div class="price">₹<?= number_format($product['price'], 2); ?></div>
                        <form method="POST" action="pages/cart.php">
                            <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                            <button type="submit" name="add_to_cart" class="btn-primary">
                                Add <i class="fa-solid fa-plus" style="font-size: 0.8em;"></i>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y'); ?> Lumina Store. Design by Expert.</p>
    </footer>

    <script>
        // Profile Modal Logic
        function toggleModal() {
            const modal = document.getElementById('profileModal');
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }

        function closeModal(e) {
            if (e.target.classList.contains('modal-overlay')) toggleModal();
        }

        // AI Chat Widget Logic
        function toggleChat() {
            const chat = document.getElementById('ai-chat-window');
            chat.style.display = (chat.style.display === 'flex') ? 'none' : 'flex';
        }

        function handleChatKey(e) {
            if (e.key === 'Enter') sendChatMessage();
        }

        async function sendChatMessage() {
            const input = document.getElementById('ai-input');
            const box = document.getElementById('ai-messages');
            const text = input.value.trim();
            if (!text) return;

            // Append User Message
            box.innerHTML += `<div class="message user">${text}</div>`;
            input.value = '';
            box.scrollTop = box.scrollHeight;

            // Show Thinking
            const thinkingId = 'thinking-' + Date.now();
            box.innerHTML += `<div class="message bot" id="${thinkingId}">...</div>`;
            
            try {
                const response = await fetch(`index.php?chat_query=${encodeURIComponent(text)}`);
                const data = await response.json();
                document.getElementById(thinkingId).innerText = data.reply;
            } catch (err) {
                document.getElementById(thinkingId).innerText = "Error connecting to Llama 3 API.";
            }
            box.scrollTop = box.scrollHeight;
        }
    </script>
</body>
</html>