<?php
session_start();
include('../includes/db.php');

$error_message = "";
$success_message = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            $error_message = "Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, phone_no, password, role) VALUES (?, ?, ?, ?, 'user')");
            
            if ($stmt->execute([$username, $email, $phone, $hashed_password])) {
                $success_message = "Account created! You can now login.";
            } else {
                $error_message = "Registration failed. Please try again.";
            }
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Lumina Store</title>
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
            --glass: rgba(255, 255, 255, 0.8);
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--light);
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.15) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 3rem 2rem;
            border-radius: 24px;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        p.subtitle { color: #64748b; margin-bottom: 2rem; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            text-align: left;
        }

        .form-group { margin-bottom: 1.25rem; }
        .full-width { grid-column: 1 / -1; }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #475569;
        }

        input {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.5);
            font-family: inherit;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }

        .error-message { background: #fee2e2; color: #ef4444; padding: 0.75rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .success-message { background: #dcfce7; color: #16a34a; padding: 0.75rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.9rem; }

        .footer-link { margin-top: 1.5rem; font-size: 0.95rem; color: #64748b; }
        .footer-link a { color: var(--primary); text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>

    <div class="auth-card">
        <div style="font-size: 2.5rem; color: var(--accent); margin-bottom: 1rem;"><i class="fa-solid fa-user-plus"></i></div>
        <h2>Create Account</h2>
        <p class="subtitle">Join Lumina and start your tech journey.</p>

        <?php if ($error_message): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?= $success_message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Full Name</label>
                    <input type="text" name="username" placeholder="John Doe" required>
                </div>
                <div class="form-group full-width">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="john@example.com" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="91821...">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" name="register" class="btn-submit">Register Now</button>
        </form>

        <div class="footer-link">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>

</body>
</html>