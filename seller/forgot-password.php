<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            if ($update->execute([$new_hashed_password, $email])) {
                header("Location: login.php?reset=success");
                exit();
            } else {
                $error = "Failed to update database.";
            }
        } else {
            $error = "No account found with this email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            /* White Theme Background with subtle overlay */
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('https://images.pexels.com/photos/3100835/pexels-photo-3100835.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .icon-box {
            width: 60px; height: 60px;
            background: #10b981;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            color: white; font-size: 24px;
        }

        h2 { color: #000000; text-align: center; font-weight: 700; margin-bottom: 8px; }
        p.subtitle { color: #64748b; text-align: center; font-size: 14px; margin-bottom: 30px; }

        /* Labels Black */
        .form-label {
            display: block;
            color: #000000 !important;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .input-group-custom {
            background: #ffffff;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            padding: 0 15px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .input-group-custom:focus-within {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .input-group-custom i { color: #94a3b8; font-size: 16px; }

        .form-control-custom {
            width: 100%;
            background: transparent !important;
            border: none !important;
            color: #000000 !important;
            padding: 12px 10px;
            font-size: 15px;
            outline: none;
        }

        .btn-reset {
            background: #10b981;
            border: none;
            border-radius: 12px;
            padding: 14px;
            color: white;
            font-weight: 700;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-reset:hover { background: #059669; transform: translateY(-1px); }

        .footer-links { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
        
        .alert-danger-custom {
            background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2;
            padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="icon-box"><i class="fas fa-key"></i></div>
    <h2>Reset Password</h2>
    <p class="subtitle">Enter your email and choose a new password</p>

    <?php if ($error): ?>
        <div class="alert-danger-custom">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label class="form-label">Email Address</label>
        <div class="input-group-custom">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" class="form-control-custom" placeholder="name@email.com" required>
        </div>

        <label class="form-label">New Password</label>
        <div class="input-group-custom">
            <i class="fas fa-lock"></i>
            <input type="password" name="new_password" class="form-control-custom" placeholder="••••••••" required>
        </div>

        <label class="form-label">Confirm Password</label>
        <div class="input-group-custom">
            <i class="fas fa-check-circle"></i>
            <input type="password" name="confirm_password" class="form-control-custom" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-reset">Update Password</button>
    </form>

    <div class="footer-links">
        <a href="login.php" style="color: #10b981; text-decoration: none; font-size: 14px; font-weight: 600;">
            <i class="fas fa-arrow-left me-1"></i> Back to Login
        </a>
    </div>
</div>
</body>
</html>