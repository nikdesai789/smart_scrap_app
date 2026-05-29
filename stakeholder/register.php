<?php
require_once '../config/database.php';

// Variables ko initialize karein taaki "Undefined variable" error na aaye
$error = '';
$success = '';
$name = $phone = $email = ''; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['full_phone']); 
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if(empty($name)) {
        $errors[] = "Name is required";
    } elseif(strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters";
    }
    
    if(empty($phone)) {
        $errors[] = "Phone number with country code is required";
    }
    
    if(empty($email)) {
        $errors[] = "Email is required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter valid email address";
    }
    
    $password_regex = "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/";
    if(empty($password)) {
        $errors[] = "Password is required";
    } elseif(!preg_match($password_regex, $password)) {
        $errors[] = "Password must be at least 8 characters and include 1 Uppercase, 1 Lowercase, 1 Number, and 1 Special Character.";
    }
    
    if($password != $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM stakeholders WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->fetch()) {
            $errors[] = "Email already registered!";
        }
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM stakeholders WHERE phone = ?");
        $stmt->execute([$phone]);
        if($stmt->fetch()) {
            $errors[] = "Phone number already registered!";
        }
    }
    
    if(empty($errors)) {
        $hashed_password = md5($password);
        $stmt = $pdo->prepare("INSERT INTO stakeholders (owner_name, phone, email, password, business_name, verified) VALUES (?, ?, ?, ?, ?, 1)");
        
        if($stmt->execute([$name, $phone, $email, $hashed_password, $name])) {
            $success = "Registration successful! Please login.";
            $name = $phone = $email = '';
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
    
    if(!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scrap Dealer Registration - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            /* White Theme: Pure white overlay with background image */
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('https://images.pexels.com/photos/4450337/pexels-photo-4450337.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .register-container {
            width: 100%;
            max-width: 550px;
            position: relative;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            padding: 35px 30px 20px;
            text-align: center;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }

        .icon-box {
            width: 60px; height: 60px;
            background: #1e40af;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            color: white; font-size: 24px;
        }

        .card-header h2 { color: #000000; font-weight: 700; font-family: 'Poppins', sans-serif; }
        .card-header p { color: #475569; font-size: 14px; }

        .card-body { padding: 30px 35px 40px; }

        .form-group { margin-bottom: 20px; }
        
        /* Labels ko black karne ke liye */
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #000000 !important; 
            font-size: 13.5px;
            margin-left: 2px;
        }

        .input-group-custom {
            background: #ffffff;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            padding: 0 15px;
            transition: all 0.3s;
        }

        .input-group-custom:focus-within {
            border-color: #1e40af;
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.1);
        }

        .input-group-custom i { color: #64748b; font-size: 16px; }

        .form-control-custom {
            width: 100%;
            background: transparent !important;
            border: none !important;
            color: #000000 !important;
            padding: 12px 10px;
            font-size: 15px;
            box-shadow: none !important;
        }

        .form-control-custom::placeholder { color: #94a3b8; }

        .btn-register {
            width: 100%; padding: 14px;
            background: #1e40af;
            border: none; border-radius: 12px; color: white;
            font-weight: 700; font-size: 16px; transition: 0.3s;
            text-transform: uppercase; margin-top: 10px;
        }

        .btn-register:hover { background: #1e3a8a; transform: translateY(-1px); }

        .login-link { 
            text-align: center; margin-top: 25px; 
            padding-top: 20px; border-top: 1px solid #f1f5f9; 
        }
        .login-link a { color: #1e40af; text-decoration: none; font-weight: 600; }

        .alert-danger-custom { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-success-custom { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; padding: 12px; border-radius: 10px; margin-bottom: 20px; }

        .required { color: #dc2626; }
        
        /* International Tel Input Light Fix */
        .iti__country-list { background-color: white !important; color: black !important; }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="register-card">
            <div class="card-header">
                <div class="icon-box"><i class="fas fa-building"></i></div>
                <h2>Dealer Registration</h2>
                <p>Create your shop account</p>
            </div>
            
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert-danger-custom">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert-success-custom">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        <br><a href="login.php" style="color: #166534; font-weight: 700; text-decoration: underline;">Login Now</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="registrationForm">
                    <div class="form-group">
                        <label>Business / Owner Name <span class="required">*</span></label>
                        <div class="input-group-custom">
                            <i class="fas fa-user-tie"></i>
                            <input type="text" name="name" class="form-control-custom" required placeholder="Full Name" value="<?php echo htmlspecialchars($name); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone <span class="required">*</span></label>
                                <div class="input-group-custom">
                                    <input type="tel" id="phone" name="phone" class="form-control-custom" required value="<?php echo htmlspecialchars($phone); ?>">
                                    <input type="hidden" name="full_phone" id="full_phone">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email <span class="required">*</span></label>
                                <div class="input-group-custom">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" class="form-control-custom" required placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password <span class="required">*</span></label>
                                <div class="input-group-custom">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" id="regPassword" class="form-control-custom" required placeholder="••••••••">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Confirm Password <span class="required">*</span></label>
                                <div class="input-group-custom">
                                    <i class="fas fa-shield-alt"></i>
                                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control-custom" required placeholder="••••••••">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus me-2"></i>Register Shop
                    </button>
                </form>
                
                <div class="login-link">
                    <span style="color: #64748b;">Already have an account?</span> 
                    <a href="login.php">Login Here</a>
                       <div class="mt-3">
                <a href="../choose-role.php" style="color: #121314; font-size: 12px; font-weight: 400;">
                    <i class="fas fa-arrow-left me-1"></i> Back to Role Selection
                </a>
            </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
        const phoneInputField = document.querySelector("#phone");
        const phoneInput = window.intlTelInput(phoneInputField, {
            initialCountry: "in",
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        });

        const form = document.querySelector("#registrationForm");
        form.onsubmit = function() {
            document.querySelector("#full_phone").value = phoneInput.getNumber();
        };
    </script>
</body>
</html>