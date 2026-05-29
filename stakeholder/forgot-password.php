<?php
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM stakeholders WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed_password = md5($new_pass);
            $update = $pdo->prepare("UPDATE stakeholders SET password = ? WHERE email = ?");
            
            if ($update->execute([$hashed_password, $email])) {
                header("Location: login.php?reset=success");
                exit();
            } else {
                $error = "Database error. Please try again.";
            }
        } else {
            $error = "No account found with that business email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dealer Recovery - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            font-family: 'Inter', sans-serif; 
            /* White Theme Background */
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('https://images.pexels.com/photos/4450337/pexels-photo-4450337.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
        }

        .card-custom { 
            background: #ffffff;
            border-radius: 24px; 
            border: 1px solid #e2e8f0; 
            padding: 40px; 
            width: 100%; 
            max-width: 420px; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .icon-header {
            width: 60px; height: 60px;
            background: #1e40af;
            border-radius: 16px; 
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; color: white; font-size: 24px;
        }

        /* Black Text for Headings and Labels */
        .card-custom h2 { color: #000000; font-weight: 700; margin-bottom: 8px; }
        .card-custom p { color: #475569; margin-bottom: 25px; }

        .form-label {
            display: block;
            color: #000000 !important; /* Label Black */
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            text-align: left;
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
            border-color: #1e40af;
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.1);
        }

        .form-control-custom { 
            width: 100%; background: transparent; border: none; color: #000000; 
            padding: 12px 10px; outline: none; font-size: 15px;
        }

        .btn-reset { 
            width: 100%; padding: 14px; 
            background: #1e40af; 
            border: none; border-radius: 12px; color: white; 
            font-weight: 700; text-transform: uppercase; cursor: pointer; 
            transition: 0.3s; margin-top: 10px;
        }

        .btn-reset:hover { background: #1e3a8a; transform: translateY(-1px); }
        
        .password-toggle { cursor: pointer; color: #64748b; }
        
        .alert-danger-custom {
            background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2;
            padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="card-custom text-center">
        <div class="icon-header"><i class="fas fa-key"></i></div>
        <h2>Reset Password</h2>
        <p>Update your dealer account credentials</p>

        <?php if($error): ?>
            <div class="alert-danger-custom">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="text-start">
                <label class="form-label">Business Email</label>
                <div class="input-group-custom">
                    <i class="fas fa-envelope text-secondary"></i>
                    <input type="email" name="email" class="form-control-custom" placeholder="email@business.com" required>
                </div>
                
                <label class="form-label">New Password</label>
                <div class="input-group-custom">
                    <i class="fas fa-lock text-secondary"></i>
                    <input type="password" name="new_password" id="p1" class="form-control-custom" placeholder="••••••••" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePass('p1', this)"></i>
                </div>

                <label class="form-label">Confirm New Password</label>
                <div class="input-group-custom">
                    <i class="fas fa-check-circle text-secondary"></i>
                    <input type="password" name="confirm_password" id="p2" class="form-control-custom" placeholder="••••••••" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePass('p2', this)"></i>
                </div>
            </div>

            <button type="submit" class="btn-reset">Update & Login</button>
        </form>
        
        <div class="mt-4 pt-3 border-top">
            <a href="login.php" class="text-decoration-none small fw-bold" style="color: #1e40af;">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>

    <script>
        function togglePass(id, icon) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>
</body>
</html>