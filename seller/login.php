<?php
session_start();
require_once '../config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'vendor') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password']; 
    
    // Query the 'users' table for Sellers
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Verify password using password_hash/verify
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = 'vendor'; 
        $_SESSION['username'] = $user['name'];
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Login - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            /* White theme: Light Gradient Overlay + Background Image */
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('https://img.freepik.com/premium-photo/industrial-warehouse-filled-with-scrap-material_836950-2817.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: #1e293b;
        }

        /* Removed the dark body::before overlay since we are using a white linear-gradient in the background property */

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            /* Light Glassmorphism */
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        }

        .header-section { text-align: center; margin-bottom: 35px; }

        .icon-box {
            width: 65px; height: 65px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            color: white; font-size: 26px;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        h2 { font-family: 'Poppins', sans-serif; color: #1e293b; font-weight: 700; margin-bottom: 5px; }
        p.subtitle { color: #64748b; font-size: 14px; }

        .form-label { color: #475569; font-weight: 500; font-size: 13px; margin-left: 2px; }

        .input-group-custom {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            padding: 0 15px;
            transition: 0.3s;
            margin-bottom: 20px;
            position: relative;
        }

        .input-group-custom:focus-within {
            border-color: #10b981;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-control {
            background: transparent !important;
            border: none !important;
            color: #1e293b !important;
            padding: 12px 10px;
            box-shadow: none !important;
            font-size: 15px;
            width: 100%;
        }

        /* Placeholder color for light theme */
        .form-control::placeholder { color: #94a3b8; }

        input::-ms-reveal, input::-ms-clear { display: none !important; }

        .toggle-password {
            cursor: pointer;
            color: #94a3b8 !important;
            position: absolute;
            right: 15px;
        }

        .btn-login {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            color: white;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
            text-transform: uppercase;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(16, 185, 129, 0.3);
        }

        .footer-links { text-align: center; margin-top: 25px; }
        .footer-links a { color: #10b981; text-decoration: none; font-weight: 600; }
        
        .alert-success-custom {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="header-section">
            <div class="icon-box"><i class="fas fa-user-lock"></i></div>
            <h2>Seller Login</h2>
            <p class="subtitle">Secure access to your account</p>
        </div>

        <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
            <div class="alert-success-custom">
                <i class="fas fa-check-circle me-2"></i> Password updated! Please login.
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger py-2 text-center" style="font-size: 13px; background: rgba(220, 38, 38, 0.2); color: #fca5a5; border: none; border-radius: 10px;">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-1">
                <label class="form-label">Email Address</label>
                <div class="input-group-custom">
                    <i class="fas fa-envelope text-secondary"></i>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
            </div>

            <div class="mb-1">
                <div class="d-flex justify-content-between">
                    <label class="form-label">Password</label>
                    <a href="forgot-password.php" style="color: #3b82f6; font-size: 12px; text-decoration: none;">Forgot Password?</a>
                </div>
                <div class="input-group-custom">
                    <i class="fas fa-lock text-secondary"></i>
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required>
                    <i class="fas fa-eye-slash toggle-password" id="togglePasswordIcon"></i>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="footer-links">
            <p class="text-secondary small">Don't have an account? <a href="register.php">Create New Account</a></p>
            <div class="mt-3">
                <a href="../choose-role.php" style="color: #121314; font-size: 12px; font-weight: 400;">
                    <i class="fas fa-arrow-left me-1"></i> Back to Role Selection
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    const toggleBtn = document.querySelector('#togglePasswordIcon');
    const passwordInput = document.querySelector('#passwordInput');

    toggleBtn.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>