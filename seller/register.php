<?php
require_once '../config/database.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    // Backend Logic Update: Receiving full phone from hidden field
    $phone = trim($_POST['full_phone']); 
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if(empty($name) || strlen($name) < 3) $errors[] = "Valid name required";
    // Phone validation updated to allow international lengths
    if(empty($phone) || strlen($phone) < 10) $errors[] = "Valid phone number required";
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required";
    if(empty($address)) $errors[] = "Address required";
    
    // Strong Password Validation
    $password_regex = "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/";
    if(empty($password) || !preg_match($password_regex, $password)) {
        $errors[] = "Password must be at least 8 characters and include 1 uppercase, 1 lowercase, 1 number, and 1 special character.";
    }
    
    if($password != $confirm_password) $errors[] = "Passwords do not match";
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $phone]);
        if($stmt->fetch()) {
            $errors[] = "Email or phone already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
            
            if($stmt->execute([$name, $email, $hashed_password, $phone, $address])) {
                $_SESSION['temp_vendor_id'] = $pdo->lastInsertId();
                $_SESSION['temp_vendor_name'] = $name;
                header("Location: login.php?success=Account created successfully!");
                exit();
            } else {
                $errors[] = "Registration failed";
            }
        }
    }
    
    if(!empty($errors)) $error = implode("<br>", $errors);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Vendor - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- International Telephone Input CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            /* White Theme: Light Gradient Overlay + Background Image */
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
            padding: 40px 20px;
            position: relative;
            color: #1e293b;
        }

        /* Removed the dark body::before overlay */

        .register-container {
            width: 100%;
            max-width: 550px;
            position: relative;
            z-index: 1;
        }

        .register-card {
            /* Light Glassmorphism */
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            padding: 35px 30px 20px;
            text-align: center;
        }

        .icon-box {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            color: white; font-size: 24px;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        .card-header h2 { 
            color: #1e293b; font-weight: 700; font-family: 'Poppins', sans-serif; 
            margin-bottom: 5px;
        }
        .card-header p { color: #030303; font-size: 14px; }

        .card-body { padding: 20px 40px 40px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; margin-bottom: 8px; font-weight: 500; 
            color: #0a0a0b; font-size: 13px; margin-left: 5px;
        }

        .input-group-custom {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 14px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            padding: 0 15px;
            transition: 0.3s;
        }

        .input-group-custom:focus-within {
            border-color: #10b981;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .input-group-custom i { color: #94a3b8; font-size: 16px; min-width: 25px; }
        
        .form-control-custom {
            width: 100%;
            background: transparent !important;
            border: none !important;
            color: #1e293b !important;
            padding: 12px 10px;
            font-size: 15px;
            box-shadow: none !important;
        }

        input::-ms-reveal,
        input::-ms-clear {
            display: none;
        }

        .password-toggle {
            cursor: pointer;
            color: #94a3b8;
            transition: 0.2s;
            padding: 0 5px;
            z-index: 10;
        }
        .password-toggle:hover {
            color: #10b981;
        }

        /* International Phone Plugin Light Theme Styling */
        .iti { width: 100%; }
        .iti__country-list { 
            background-color: #ffffff !important; 
            color: #1e293b !important; 
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .iti__country:hover { background-color: #f1f5f9 !important; }

        textarea.form-control-custom { min-height: 80px; }
        .form-control-custom::placeholder { color: #cbd5e1; }

        .btn-register {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none; border-radius: 14px; color: white;
            font-weight: 700; font-size: 16px; transition: 0.3s;
            text-transform: uppercase; letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .btn-register:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 12px 20px rgba(16, 185, 129, 0.3); 
        }

        .login-link { 
            text-align: center; margin-top: 25px; 
            padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.05); 
        }
        .login-link a { color: #10b981; text-decoration: none; font-weight: 600; font-size: 15px; }
        .login-link a:hover { text-decoration: underline; }

        .alert-custom { 
            background: rgba(220, 38, 38, 0.1); 
            border: 1px solid rgba(220, 38, 38, 0.1); 
            color: #b91c1c;
            padding: 12px 16px; border-radius: 12px; 
            margin-bottom: 25px; font-size: 13px; 
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="register-card">
            <div class="card-header">
                <div class="icon-box"><i class="fas fa-leaf"></i></div>
                <h2>Register as Seller</h2>
                <p>Start your journey toward a cleaner future</p>
            </div>

            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registrationForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Seller Name</label>
                                <div class="input-group-custom">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="name" class="form-control-custom" required placeholder="Enter full name">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <div class="input-group-custom">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" id="phone" name="phone" class="form-control-custom" required placeholder="Mobile number">
                                    <input type="hidden" name="full_phone" id="full_phone">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email Address</label>
                                <div class="input-group-custom">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" class="form-control-custom" required placeholder="name@email.com">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <div class="input-group-custom" style="align-items: flex-start; padding-top: 8px;">
                            <i class="fas fa-map-marker-alt" style="margin-top: 5px;"></i>
                            <textarea name="address" class="form-control-custom" rows="2" required placeholder="Enter complete address"></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password</label>
                                <div class="input-group-custom">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" id="reg_password" class="form-control-custom" required placeholder="Strong password">
                                    <i class="fas fa-eye password-toggle" onclick="togglePass('reg_password', this)"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <div class="input-group-custom">
                                    <i class="fas fa-check-double"></i>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom" required placeholder="Re-type password">
                                    <i class="fas fa-eye password-toggle" onclick="togglePass('confirm_password', this)"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus me-2"></i>Create Seller Account
                    </button>
                </form>

                <div class="login-link">
                    <a href="login.php">Already have an account? Login here</a>
                     <div class="mt-3">
                <a href="../choose-role.php" style="color: #121314; font-size: 12px; font-weight: 400;">
                    <i class="fas fa-arrow-left me-1"></i> Back to Role Selection
                </a>
            </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
        function togglePass(inputId, icon) {
            const field = document.getElementById(inputId);
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        const phoneInputField = document.querySelector("#phone");
        const phoneInput = window.intlTelInput(phoneInputField, {
            initialCountry: "in",
            preferredCountries: ["in", "ae", "us", "gb"],
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        });

        const form = document.querySelector("#registrationForm");
        form.addEventListener("submit", (e) => {
            const fullNumber = phoneInput.getNumber();
            document.querySelector("#full_phone").value = fullNumber;
        });
    </script>
</body>
</html>