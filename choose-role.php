<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Role - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            color: #1e293b; /* Dark text for light background */
        }
        
        .role-card {
            /* Light Glassmorphism Effect */
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
        }
        
        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .role-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        
        .vendor-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .dealer-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
        }
        
        .role-icon i {
            font-size: 3rem;
            color: white;
        }

        
        .btn-custom {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-vendor {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
        }
        
        .btn-dealer {
            background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
            border: none;
            color: white;
        }
        
        .btn-vendor:hover, .btn-dealer:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            color: white;
        }
        
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 50px 20px;
        }
        
        h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: #1e293b; /* Dark title for white theme */
            text-align: center;
            margin-bottom: 50px;
        }
        
        @media (max-width: 768px) {
            .role-card {
                margin-bottom: 20px;
            }
        }
        
        .btn-outline-dark-custom {
            border: 2px solid #1e293b;
            background: transparent;
            color: #1e293b;
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-outline-dark-custom:hover {
            background: #1e293b;
            color: #ffffff;
        }
        /* Agent specific styles */
.agent-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); /* Orange/Amber Theme */
}

.btn-agent {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    color: white;
}

/* Adjusting columns for 3 cards */
@media (min-width: 768px) {
    .col-md-6 {
        flex: 0 0 auto;
        width: 33.333333%; /* Isse teeno cards ek line mein aa jayenge */
    }
}
    </style>
</head>
<body>
    <div class="container-custom">
        <h1>Choose Your Role</h1>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="role-card">
                    <div class="role-icon vendor-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 style="color: #10b981; margin-bottom: 15px;">I'm a Scrap Seller</h2>
                    <p style="color: #475569; margin-bottom: 25px;">
                        I want to <strong>sell scrap materials</strong> like paper, plastic, metal, and e-waste 
                        to verified scrap dealers and earn money.
                    </p>
                    <ul style="text-align: left; margin-bottom: 25px; color: #64748b; list-style: none; padding: 0;">
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #10b981;"></i> Get best market prices</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #10b981;"></i> Schedule convenient pickup</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #10b981;"></i> Track payments online</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #10b981;"></i> Cash or UPI payment at pickup</li>
                    </ul>
                    <a href="seller/login.php" class="btn btn-vendor btn-custom">
                        <i class="fas fa-tag me-2"></i>Sell Scrap Now
                    </a>
                    <p class="mt-3" style="font-size: 0.9rem; color: #64748b;">
                        New user? <a href="seller/register.php" style="color: #10b981; text-decoration: none;">Register here</a>
                    </p>
                </div>
            </div>
           
            <div class="col-md-6">
                <div class="role-card">
                    <div class="role-icon dealer-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h2 style="color: #3b82f6; margin-bottom: 15px;">I'm a Scrap Dealer</h2>
                    <p style="color: #475569; margin-bottom: 25px;">
                        I own a <strong>scrap business/recycling company</strong> and want to purchase scrap materials 
                        from scrap sellers to recycle and process.
                    </p>
                    <ul style="text-align: left; margin-bottom: 25px; color: #64748b; list-style: none; padding: 0;">
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #3b82f6;"></i> Find scrap sellers in your area</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #3b82f6;"></i> Set your own purchase prices</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #3b82f6;"></i> Manage pickup schedule</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #3b82f6;"></i> Track all purchases</li>
                    </ul>
                    <a href="stakeholder/login.php" class="btn btn-dealer btn-custom">
                        <i class="fas fa-shopping-cart me-2"></i>Purchase Scrap
                    </a>
                    <p class="mt-3" style="font-size: 0.9rem; color: #64748b;">
                        New dealer? <a href="stakeholder/register.php" style="color: #3b82f6; text-decoration: none;">Register here</a>
                    </p>
                </div>
            </div>
             <div class="col-md-4"> <div class="role-card">
        <div class="role-icon agent-icon">
            <i class="fas fa-truck-pickup"></i>
        </div>
        <h2 style="color: #f59e0b; margin-bottom: 15px;">I'm a Pickup Agent</h2>
        <p style="color: #475569; margin-bottom: 25px;">
            I am a <strong>field representative</strong> working for a dealer. I visit sellers to verify scrap, weigh items, and complete pickups.
        </p>
        <ul style="text-align: left; margin-bottom: 25px; color: #64748b; list-style: none; padding: 0;">
            <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #f59e0b;"></i> View assigned pickup tasks</li>
            <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #f59e0b;"></i> Use GPS for seller location</li>
            <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #f59e0b;"></i> Verify transaction via OTP</li>
            <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #f59e0b;"></i> Instant digital billing</li>
        </ul>
        <a href="agent/login.php" class="btn btn-agent btn-custom">
            <i class="fas fa-sign-in-alt me-2"></i>Agent Login
        </a>
    
    </div>
</div>
        </div>
        
        <div class="text-center mt-5">
            <a href="index.php" class="btn-outline-dark-custom">
                <i class="fas fa-arrow-left me-2"></i>Back to Home
            </a>
        </div>
    </div>
</body>
</html>