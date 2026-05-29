<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotVendor();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
    if($stmt->execute([$name, $phone, $address, $user_id])) {
        $success = "Profile updated successfully!";
        $_SESSION['user_name'] = $name;
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        $error = "Failed to update profile";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary: #10b981; --dark: #1e293b; --meesho: #9f2089; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fa; margin: 0; display: flex; }
        
        /* Sidebar - Same as Dashboard */
        .sidebar { 
            width: 250px; background: var(--dark); color: white; height: 100vh; 
            position: fixed; padding: 20px; display: flex; flex-direction: column; z-index: 1000;
        }
        
        .main-content { margin-left: 250px; width: calc(100% - 250px); padding: 30px; min-height: 100vh; }
        
        .nav-link { color: rgba(255,255,255,0.8); transition: 0.2s; border-radius: 8px; margin-bottom: 5px; padding: 10px 15px; text-decoration: none; display: block; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { background: var(--primary); color: white; }
        
        .logout-container { margin-top: auto; padding-bottom: 10px; }

        /* Form Card Styling */
        .edit-card { background: white; border-radius: 15px; padding: 30px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: none; }
        
        .form-label { font-weight: 600; color: var(--dark); font-size: 0.9rem; }
        .form-control { border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; transition: 0.3s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
        
        .btn-save { background: var(--meesho); color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 700; width: 100%; transition: 0.3s; }
        .btn-save:hover { background: #821a70; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(159, 32, 137, 0.3); }
        
        .alert { border-radius: 10px; border: none; font-weight: 500; }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-4">
        <h4 class="text-success fw-bold"><i class="fas fa-recycle me-2"></i>ScrapSmart</h4>
        <hr class="bg-light">
    </div>
    
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="my_requests.php" class="nav-link"><i class="fas fa-clock-rotate-left me-2"></i> My Pickups</a>
        <a href="profile.php" class="nav-link"><i class="fas fa-user me-2"></i> My Profile</a>
    </nav>

    <div class="logout-container">
        <hr class="bg-light">
        <a href="logout.php" class="nav-link text-danger fw-bold">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Edit <span style="color:var(--meesho)">Profile</span></h3>
        <a href="profile.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fas fa-arrow-left me-1"></i> Cancel
        </a>
    </div>

    <div class="edit-card">
        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="name" class="form-control border-start-0" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-phone text-muted"></i></span>
                    <input type="tel" name="phone" class="form-control border-start-0" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" class="form-control border-start-0 bg-light" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Email cannot be changed for security reasons.</small>
            </div>

            <div class="mb-4">
                <label class="form-label">Pickup Address</label>
                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>

            <button type="submit" class="btn-save shadow-sm">
                <i class="fas fa-save me-2"></i> Update Profile
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>