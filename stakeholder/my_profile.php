<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Session Check
if (!isset($_SESSION['stakeholder_id'])) {
    header("Location: login.php");
    exit();
}

$dealer_id = $_SESSION['stakeholder_id'];

// 2. Fetch Dealer Data
$stmt = $pdo->prepare("SELECT * FROM stakeholders WHERE id = ?");
$stmt->execute([$dealer_id]);
$dealer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dealer) {
    header("Location: register.php");
    exit();
}

// 3. Data Preparation
$selectedTypes = !empty($dealer['scrap_types']) ? explode(',', $dealer['scrap_types']) : [];
$workingDays = !empty($dealer['working_days']) ? explode(',', $dealer['working_days']) : [];
$scrapPrices = [
    'Plastic' => $dealer['plastic_price'] ?? '0',
    'Paper'   => $dealer['paper_price'] ?? '0',
    'Iron'     => $dealer['iron_price'] ?? '0',
    'Metal'   => $dealer['metal_price'] ?? '0',
    'Copper'  => $dealer['copper_price'] ?? '0',
    'E-Waste' => $dealer['ewaste_price'] ?? '0'
];

// 4. Verification logic for "Verified Badge"
$is_verified = (!empty($dealer['id_number']) && !empty($dealer['shop_image']));

// 5. Service Radius logic
$service_radius = !empty($dealer['service_radius']) ? $dealer['service_radius'] : 'Not Specified';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Smart Scrap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary-green: #10b981;
            --dark-green: #065f46;
            --bg-light: #fafeff;
        }
        body { background: var(--bg-light); font-family: 'Inter', sans-serif; color: #1e293b; }
        .main-container { max-width: 900px; margin: 30px auto; padding: 15px; }
        .badge-verified {
            position: absolute; top: 20px; right: 20px; background: white;
            padding: 8px 15px; border-radius: 50px; font-weight: 800;
            font-size: 0.8rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            color: #0ea5e9; z-index: 5;
        }
        .shop-banner {
            width: 100%; background: #fff; border-radius: 20px;
            overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 25px; border: 1px solid #e2e8f0; position: relative;
        }
        .shop-banner img {
            width: 100%; height: auto; aspect-ratio: 16 / 9; 
            object-fit: cover; display: block; object-position: center; 
        }
        .no-image-placeholder {
            height: 200px; background: linear-gradient(135deg, var(--dark-green), var(--primary-green));
            width: 100%; display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.5rem; font-weight: bold;
        }
        .info-card {
            background: #fff; border-radius: 20px; padding: 25px;
            margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
        }
        .section-header {
            font-weight: 700; color: var(--dark-green); font-size: 1.1rem;
            margin-bottom: 20px; border-bottom: 2px solid #f0fdf4;
            padding-bottom: 8px; display: flex; align-items: center;
            justify-content: space-between;
        }
        .label-text { font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 2px; }
        .value-text { font-size: 1.05rem; font-weight: 600; color: #1e293b; }
        .day-badge {
            background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;
            padding: 6px 15px; border-radius: 50px; font-weight: 600;
            font-size: 0.85rem; margin: 4px; display: inline-block;
        }
        .price-tag {
            background: white; border: 1px solid #e2e8f0;
            border-left: 5px solid var(--primary-green); padding: 15px;
            border-radius: 15px; transition: all 0.3s ease;
        }
        .action-btns { text-align: center; margin-top: 40px; margin-bottom: 60px; }
        .btn-edit { background: var(--primary-green); color: white; padding: 12px 40px; border-radius: 50px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); transition: 0.3s; }
        .btn-back { background: #fff; color: #64748b; padding: 12px 40px; border-radius: 50px; font-weight: 700; text-decoration: none; border: 1px solid #e2e8f0; transition: 0.3s; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="shop-banner">
        <?php if($is_verified): ?>
            <div class="badge-verified"><i class="fa-solid fa-circle-check text-primary"></i> VERIFIED DEALER</div>
        <?php endif; ?>
        
        <?php 
        $img_path = !empty($dealer['shop_image']) ? "../uploads/shop_images/" . $dealer['shop_image'] : "";
        if(!empty($dealer['shop_image'])): ?>
            <img src="<?php echo $img_path; ?>" alt="Shop View">
        <?php else: ?>
            <div class="no-image-placeholder">
                <i class="fa-solid fa-shop me-2"></i> <?php echo htmlspecialchars($dealer['business_name']); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mb-4 text-center">
        <h1 class="fw-bold mb-1" style="color: var(--dark-green);"><?php echo htmlspecialchars($dealer['business_name']); ?></h1>
        <p class="text-muted fw-medium"><i class="fa-solid fa-location-dot me-1 text-success"></i> <?php echo htmlspecialchars($dealer['city'] . ', ' . $dealer['state']); ?></p>
    </div>

    <div class="row">
        <div class="col-md-7">
            <!-- Contact Details with both phone numbers -->
            <div class="info-card">
                <div class="section-header"><span><i class="fa-solid fa-store me-2"></i> Contact Details</span></div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="label-text">Primary Phone</div>
                        <div class="value-text"><?php echo htmlspecialchars($dealer['phone']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="label-text">Alternate Phone</div>
                        <div class="value-text"><?php echo htmlspecialchars($dealer['alternate_phone'] ?? 'Not Set'); ?></div>
                    </div>
                    <div class="col-12 border-top pt-3">
                        <div class="label-text">Email</div>
                        <div class="value-text" style="font-size: 0.9rem;"><?php echo htmlspecialchars($dealer['email']); ?></div>
                    </div>
                    <div class="col-12 border-top pt-3">
                        <div class="label-text">Business Address</div>
                        <div class="value-text"><?php echo htmlspecialchars($dealer['address']); ?></div>
                    </div>
                    <div class="col-12 border-top pt-3">
                        <div class="label-text">Service Radius</div>
                        <div class="value-text text-primary">
                            <i class="fa-solid fa-truck-fast me-2"></i>
                            <?php echo htmlspecialchars($service_radius); ?> 
                            <?php echo ($service_radius !== 'Not Specified') ? 'KM' : ''; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="section-header"><span><i class="fa-solid fa-clock me-2"></i> Working Hours</span></div>
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <div class="label-text">Opens At</div>
                        <div class="value-text text-success"><?php echo date('h:i A', strtotime($dealer['opening_time'])); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="label-text">Closes At</div>
                        <div class="value-text text-danger"><?php echo date('h:i A', strtotime($dealer['closing_time'])); ?></div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="label-text mb-2">Operation Days</div>
                    <?php foreach($workingDays as $day): ?>
                        <span class="day-badge"><?php echo htmlspecialchars($day); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="info-card">
                <div class="section-header">
                    <span><i class="fa-solid fa-bolt me-2 text-warning"></i> Current Rates</span>
                </div>
                <div class="row g-2">
                    <?php foreach($selectedTypes as $type): ?>
                    <div class="col-12">
                        <div class="price-tag d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-secondary"><?php echo htmlspecialchars($type); ?></span>
                            <span class="h5 mb-0 fw-bold text-success">₹<?php echo htmlspecialchars($scrapPrices[$type]); ?><small class="text-muted" style="font-size: 12px;">/kg</small></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="info-card <?php echo $is_verified ? 'bg-light' : 'bg-warning-subtle'; ?> border-0">
                <div class="section-header"><span><i class="fa-solid fa-shield-halved me-2"></i> KYC Status</span></div>
                <div class="d-flex align-items-center mb-2">
                    <div class="label-text me-2">Status:</div>
                    <?php if($is_verified): ?>
                        <span class="badge bg-success">Verified Dealer</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Pending Verification</span>
                    <?php endif; ?>
                </div>
                <div class="label-text">ID Type</div>
                <div class="value-text mb-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($dealer['id_type'] ?? 'Not Set'); ?></div>
                <div class="value-text text-muted" style="letter-spacing: 2px;">
                    <?php 
                    if(!empty($dealer['id_number'])) {
                        echo "XXXX-XXXX-" . substr($dealer['id_number'], -4); 
                    } else {
                        echo "No ID Provided";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="action-btns">
        <a href="dashboard.php" class="btn-back shadow-sm"><i class="fa-solid fa-arrow-left me-2"></i>Home</a>
        <a href="complete_profile.php" class="btn-edit"><i class="fa-solid fa-pen-to-square me-2"></i>Edit My Profile</a>
    </div>
</div>

</body>
</html>