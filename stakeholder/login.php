<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$error = null;

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            // MD5 hash create karein login check ke liye (kyunki register mein MD5 hai)
            $login_hashed_password = md5($password);

            $stmt = $pdo->prepare("SELECT * FROM stakeholders WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Register page MD5 use kar raha hai, isliye yahan direct compare hoga
                if ($login_hashed_password === $user['password']) {
                    $_SESSION['stakeholder_id'] = $user['id'];
                    $_SESSION['role'] = 'DEALER';
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Incorrect password!";
                }
            } else {
                $error = "This email is not registered in our database.";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dealer Login - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('https://images.pexels.com/photos/4450337/pexels-photo-4450337.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover; background-position: center; background-attachment: fixed;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; color: #1e293b; 
        }
        .login-card { 
            background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(20px); 
            border-radius: 28px; border: 1px solid rgba(59, 130, 246, 0.2); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1); 
            width: 100%; max-width: 450px; padding: 40px; 
        }
        .icon-circle { 
            width: 70px; height: 70px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); 
            border-radius: 20px; display: flex; align-items: center; justify-content: center; 
            margin: 0 auto 15px; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); 
        }
        .icon-circle i { color: white; font-size: 30px; }
        .login-label { color: #1e293b !important; font-weight: 600; font-size: 13px; margin-bottom: 4px; display: block; }
        .input-group-custom { 
            background: rgba(255, 255, 255, 0.8); border-radius: 14px; border: 1px solid rgba(0, 0, 0, 0.1); 
            display: flex; align-items: center; padding: 0 15px; margin-bottom: 20px; transition: 0.3s; 
        }
        .form-control-custom { width: 100%; background: transparent !important; border: none !important; padding: 12px 10px; font-size: 15px; outline: none; }
        .btn-login { 
            width: 100%; padding: 14px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); 
            border: none; border-radius: 14px; color: white; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: 0.3s; 
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(37, 99, 235, 0.3); }
        .cursor-pointer { cursor: pointer; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="icon-circle"><i class="fas fa-building"></i></div>
        <h2 class="fw-bold mb-1" style="color: #1e293b;">Dealer Login</h2>
        <p class="text-secondary small">Access your business dashboard</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action=""> 
        <div class="mb-3">
            <label class="login-label">Business Email</label>
            <div class="input-group-custom">
                <i class="fas fa-envelope text-secondary"></i>
                <input type="email" name="email" class="form-control-custom" required placeholder="email@business.com">
            </div>
        </div>
        
        <div class="mb-2">
            <label class="login-label">Password</label>
            <div class="input-group-custom">
                <i class="fas fa-lock text-secondary"></i>
                <input type="password" name="password" id="loginPass" class="form-control-custom" required placeholder="••••••••">
                <i class="fas fa-eye text-secondary cursor-pointer" onclick="toggleLoginPass()"></i>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="forgot-password.php" class="text-decoration-none small" style="color: #2563eb;">Forgot password?</a>
        </div>
        
        <button type="submit" name="login" class="btn-login">Login to Dashboard</button>
    </form>

    <div class="text-center mt-4">
        <p class="small text-secondary">New Dealer? <a href="register.php" class="text-decoration-none fw-bold" style="color: #2563eb;">Register Your Shop</a></p>
           <div class="mt-3">
                <a href="../choose-role.php" style="color: #121314; font-size: 12px; font-weight: 400;">
                    <i class="fas fa-arrow-left me-1"></i> Back to Role Selection
                </a>
            </div>
    </div>
</div>

<script>
    function toggleLoginPass() {
        const passInput = document.getElementById('loginPass');
        passInput.type = passInput.type === 'password' ? 'text' : 'password';
    }
</script>
</body>
</html>