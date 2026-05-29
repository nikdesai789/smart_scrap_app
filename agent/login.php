<?php
session_start();
require_once '../config/database.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // DEBUG 1: Is the database even finding the row?
    $stmt = $pdo->prepare("SELECT * FROM agents WHERE email = ?");
    $stmt->execute([$email]);
    $agent = $stmt->fetch();

    if (!$agent) {
        $error = "DEBUG: Email '$email' not found in database at all.";
    } else {
        // DEBUG 2: Is the status blocking it?
        if ($agent['status'] !== 'ACTIVE') {
            $error = "DEBUG: Agent found, but status is '" . $agent['status'] . "' instead of 'ACTIVE'.";
        } 
        // DEBUG 3: Does the password match exactly?
        // Replace your old "if ($password === $agent['password'])" with this:
if ($agent && password_verify($password, $agent['password'])) {
    // Login Success!
    $_SESSION['agent_id'] = $agent['id'];
    $_SESSION['agent_name'] = $agent['name'];
    header("Location: dashboard.php");
    exit();
} else {
    $error = "Invalid email or password.";
}
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Login - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --agent-primary: #f59e0b; --agent-dark: #d97706; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border: 1px solid #e2e8f0; }
        .brand-logo { width: 70px; height: 70px; background: linear-gradient(135deg, var(--agent-primary), var(--agent-dark)); color: white; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px; box-shadow: 0 8px 16px rgba(245, 158, 11, 0.2); }
        h2 { font-weight: 700; color: #1e293b; text-align: center; margin-bottom: 8px; }
        p.subtitle { text-align: center; color: #64748b; font-size: 0.9rem; margin-bottom: 30px; }
        .form-label { font-weight: 600; color: #475569; font-size: 0.85rem; }
        .form-control { padding: 12px 16px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f1f5f9; }
        .form-control:focus { border-color: var(--agent-primary); box-shadow: none; background: white; }
        .btn-login { background: linear-gradient(135deg, var(--agent-primary), var(--agent-dark)); border: none; color: white; padding: 14px; border-radius: 12px; width: 100%; font-weight: 600; margin-top: 10px; transition: 0.3s; cursor: pointer; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(217, 119, 6, 0.3); }
        .alert { font-size: 0.85rem; border-radius: 12px; }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="login-card mx-auto">
        <div class="brand-logo">
            <i class="fas fa-truck-pickup"></i>
        </div>
        <h2>Agent Portal</h2>
        <p class="subtitle">Secure login for field agents</p>

        <?php if($error): ?>
            <div class="alert alert-danger border-0 mb-4">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST"> 
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group border rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-0 text-muted"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-0" placeholder="agent@scrapsmart.com" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Access Password</label>
                <div class="input-group border rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-0 text-muted"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control border-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login">Login to Dashboard</button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <p class="text-muted small mb-1">Assigned by a dealer?</p>
            <a href="../index.php" class="text-decoration-none text-warning fw-bold small">
                <i class="fas fa-chevron-left me-1"></i> Back to Home
            </a>
        </div>
    </div>
</div>

</body>
</html>