<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotVendor();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary: #10b981; --dark: #1e293b; --meesho: #9f2089; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fa; margin: 0; display: flex; }
        
        /* Sidebar - Same as Dashboard */
        .sidebar { 
            width: 250px; 
            background: var(--dark); 
            color: white; 
            height: 100vh; 
            position: fixed; 
            padding: 20px; 
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        
        .main-content { margin-left: 250px; width: calc(100% - 250px); padding: 30px; min-height: 100vh; }
        
        .nav-link { color: rgba(255,255,255,0.8); transition: 0.2s; border-radius: 8px; margin-bottom: 5px; padding: 10px 15px; text-decoration: none; display: block; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { background: var(--primary); color: white; }
        
        .logout-container { margin-top: auto; padding-bottom: 10px; }

        /* Profile Card Styling */
        .profile-card { background: white; border-radius: 15px; padding: 35px; max-width: 700px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: none; }
        .info-row { display: flex; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .info-label { width: 150px; font-weight: 700; color: var(--dark); font-size: 0.9rem; text-transform: uppercase; }
        .info-value { flex: 1; color: #475569; }
        
        .avatar-circle {
            width: 80px; height: 80px; 
            background: var(--meesho); 
            border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            margin: 0 auto 15px;
            box-shadow: 0 4px 10px rgba(159, 32, 137, 0.2);
        }
        
        .btn-edit { background: var(--meesho); color: white; border: none; padding: 10px 25px; border-radius: 8px; font-weight: 600; }
        .btn-edit:hover { background: #821a70; color: white; }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-4">
        <h4 class="text-success fw-bold"><i class="fas fa-recycle me-2"></i>SmartScrap</h4>
        <hr class="bg-light">
    </div>
    
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="my_requests.php" class="nav-link"><i class="fas fa-clock-rotate-left me-2"></i> My Pickups</a>
        <a href="profile.php" class="nav-link active"><i class="fas fa-user me-2"></i> My Profile</a>
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
        <h3 class="fw-bold">My <span style="color:var(--meesho)">Profile</span></h3>
        <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">Verified Vendor</span>
    </div>

    <div class="profile-card">
        <div class="text-center mb-4">
            <div class="avatar-circle">
                <i class="fas fa-user-tie" style="font-size: 2.5rem; color: white;"></i>
            </div>
            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
            <p class="text-muted small">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
        </div>

        <div class="info-row">
            <div class="info-label">Full Name</div>
            <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Phone</div>
            <div class="info-value">+91 <?php echo htmlspecialchars($user['phone']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Email</div>
            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Address</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($user['address'])); ?></div>
        </div>

        <div class="mt-5 d-flex gap-2 justify-content-center">
            <a href="edit_profile.php" class="btn btn-edit">
                <i class="fas fa-user-edit me-2"></i>Edit Profile
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary px-4 fw-bold">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>