<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['stakeholder_id'])) {
    header("Location: ../login.php"); 
    exit();
}
$dealer_id = $_SESSION['stakeholder_id'];

// Distance Calculation
function getDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return "0.0";
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos(min(max($dist, -1.0), 1.0));
    return round(rad2deg($dist) * 60 * 1.1515 * 1.609344, 1);
}

try {
    $stmt_d = $pdo->prepare("SELECT latitude, longitude FROM stakeholders WHERE id = ?");
    $stmt_d->execute([$dealer_id]);
    $dealer = $stmt_d->fetch();

    // FULL ORIGINAL QUERY: Restored with PENDING sorting logic
    $query = "SELECT r.*, u.name as user_name, u.phone as seller_phone, 
                 a.name as agent_name, a.phone as agent_phone 
          FROM scrap_requests r 
          JOIN users u ON r.user_id = u.id 
          LEFT JOIN agents a ON r.agent_id = a.id
          WHERE r.dealer_id = ? AND r.status NOT IN ('CANCELLED', 'REJECTED') 
          ORDER BY CASE WHEN r.status = 'PENDING' THEN 0 ELSE 1 END ASC, r.pickup_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$dealer_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assigned Tasks | ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        .task-card { border: none; border-radius: 15px; background: white; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 6px solid #0d6efd; }
        .status-badge { font-weight: 800; font-size: 0.7rem; padding: 6px 14px; border-radius: 50px; text-transform: uppercase; letter-spacing: 0.5px; }
        .st-pending { background: #fef3c7; color: #92400e; }
        .st-accepted { background: #eff6ff; color: #1d4ed8; }
        .st-ontheway { background: #fff7ed; color: #c2410c; }
        .st-arrived { background: #f0fdf4; color: #15803d; }
        .st-completed { background: #f1f5f9; color: #475569; } 
        .st-waiting { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .price-chip { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; padding: 2px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; margin-right: 5px; }
        .btn-payment { background: #10b981; color: white; border: none; border-radius: 12px; padding: 12px; font-weight: 700; width: 100%; transition: 0.3s; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
        .weight-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 10px; text-align: center; }
        .call-btn-style { cursor: pointer; transition: 0.2s; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h3 class="fw-bold text-dark mb-0">Assigned Task Monitoring</h3>
    </div>

<?php foreach($tasks as $row): 
    $db_status = $row['status'];
    
    // Agar status ASSIGNED hai, matlab agent ne abhi tak Accept nahi dabaya
    if($db_status == 'ASSIGNED') {
        $status_label = "PENDING"; 
        $status_class = "pending";
    } else {
        $status_label = $db_status;
        $status_class = strtolower(str_replace(' ', '', $db_status));
    }
    // ... baaki logic same ...

    $dist = getDistance($dealer['latitude'], $dealer['longitude'], $row['latitude'], $row['longitude']);
    // ... baaki ka code ...

    // ... baki ka price_map logic ...
        
        $price_map = [
            'Plastic' => $row['applied_plastic_price'], 
            'Paper'   => $row['applied_paper_price'], 
            'Iron'    => $row['applied_iron_price'],
            'Copper'  => $row['applied_copper_price'],
            'Metal'   => $row['applied_metal_price'],
            'E-Waste' => $row['applied_ewaste_price']
        ];
    ?>
    <div class="card task-card p-4">
        <div class="row align-items-center">
            <div class="col-md-4 border-end">
                <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($row['user_name']) ?></h5>
                <div class="mb-3">
                    <?php 
                        $items = explode(',', $row['scrap_type']);
                        foreach($items as $item): 
                            $name = trim($item); 
                            $p = (!empty($price_map[$name]) && $price_map[$name] > 0) ? $price_map[$name] : 'N/A';
                    ?>
                        <span class="price-chip"><?= htmlspecialchars($name) ?> (₹<?= $p ?>)</span>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="revealPhone(this, '<?= $row['seller_phone'] ?>')" class="btn btn-sm btn-outline-dark rounded-pill px-3 call-btn-style">
                        <i class="fas fa-phone-alt me-1"></i> <span class="btn-text">Call Seller</span>
                    </button>
                    <?php if($row['agent_phone']): ?>
                    <button onclick="revealPhone(this, '<?= $row['agent_phone'] ?>')" class="btn btn-sm btn-primary rounded-pill px-3 call-btn-style">
                        <i class="fas fa-user-check me-1"></i> <span class="btn-text">Call Agent</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4 text-center">
                <div class="mb-2">
                    <span class="status-badge st-<?= $status_class ?>">
                        <?= htmlspecialchars($status_label) ?>
                    </span>
                </div>
                <div class="small fw-bold text-muted">
                    <i class="far fa-calendar-check text-primary me-1"></i> <?= date('d M', strtotime($row['pickup_date'])) ?> | 
                    <i class="far fa-clock text-primary me-1"></i> <?= date('h:i A', strtotime($row['pickup_time'])) ?>
                </div>
                <div class="mt-2 text-primary fw-bold small">
                    <i class="fas fa-map-marker-alt me-1"></i> <?= $dist ?> KM Away
                </div>
            </div>

            <div class="col-md-4">
                <?php if($row['status'] === 'COMPLETED'): ?>
                    <button class="btn-payment" data-bs-toggle="modal" data-bs-target="#paymentModal<?= $row['id'] ?>">
                        <i class="fas fa-wallet me-2"></i> PAYMENT
                    </button>
                <?php else: ?>
                    <div class="weight-box">
                        <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.6rem;">
                            Status: <?= htmlspecialchars($row['status']) ?>
                        </small>
                        <div class="mt-1">
                            <small class="text-muted d-block small">Est. Weight</small>
                            <span class="fs-3 fw-bold text-primary"><?= $row['estimated_weight'] ?> KG</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if($row['status'] == 'COMPLETED'): ?>
    <div class="modal fade" id="paymentModal<?= $row['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0 bg-success text-white" style="border-radius: 20px 20px 0 0;">
                    <h5 class="fw-bold"><i class="fas fa-receipt me-2"></i>Payment Summary</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Final Amount Banner -->
                    <div class="p-3 bg-light rounded-4 mb-4 text-center">
                        <small class="text-muted fw-bold d-block" style="font-size: 0.7rem; letter-spacing: 1px;">FINAL AMOUNT COLLECTED</small>
                        <h2 class="fw-bold text-success mb-0">₹<?= number_format($row['received_amount'] ?? 0, 2) ?></h2>
                    </div>

                    <!-- Agent & Weight Info -->
                    <div class="row text-center mb-4 border-bottom pb-3">
                        <div class="col-6 border-end">
                            <small class="text-muted d-block" style="font-size: 0.7rem; letter-spacing: 1px;">ACTUAL WEIGHT</small>
                            <span class="fw-bold fs-5"><?= number_format($row['final_weight'] ?? 0, 2) ?> KG</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block" style="font-size: 0.7rem; letter-spacing: 1px;">COMPLETED BY</small>
                            <span class="fw-bold"><?= htmlspecialchars($row['agent_name'] ?? 'Dealer') ?></span>
                        </div>
                    </div>

                    <!-- Per-item Breakdown Table -->
                    <?php
                    $scrap_label_map = [
                        'plastic' => 'Plastic', 'paper' => 'Paper', 'iron' => 'Iron',
                        'metal' => 'Metal', 'copper' => 'Copper', 'ewaste' => 'E-Waste'
                    ];
                    $gross = 0;
                    $has_items = false;
                    foreach ($scrap_label_map as $key => $label) {
                        $w = floatval($row[$key.'_weight'] ?? 0);
                        $p = floatval($row['applied_'.$key.'_price'] ?? 0);
                        if ($w > 0 && $p > 0) { $has_items = true; break; }
                    }
                    if ($has_items): ?>
                    <table class="table table-sm mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Scrap Type</th>
                                <th class="text-center">Rate (₹/kg)</th>
                                <th class="text-center">Weight (kg)</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($scrap_label_map as $key => $label):
                            $w = floatval($row[$key.'_weight'] ?? 0);
                            $p = floatval($row['applied_'.$key.'_price'] ?? 0);
                            if ($w > 0 && $p > 0):
                                $line = $w * $p;
                                $gross += $line;
                        ?>
                            <tr>
                                <td><strong><?= $label ?></strong></td>
                                <td class="text-center">₹<?= number_format($p, 2) ?></td>
                                <td class="text-center"><?= number_format($w, 2) ?></td>
                                <td class="text-end fw-bold">₹<?= number_format($line, 2) ?></td>
                            </tr>
                        <?php endif; endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="fw-bold text-end">Subtotal</td>
                                <td class="text-end fw-bold">₹<?= number_format($gross, 2) ?></td>
                            </tr>
                            <?php if (!empty($row['pickup_fee']) && $row['pickup_fee'] > 0): ?>
                            <tr class="text-danger">
                                <td colspan="3" class="fw-bold text-end">Pickup Fee (Deducted)</td>
                                <td class="text-end fw-bold">- ₹<?= number_format($row['pickup_fee'], 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-success">
                                <td colspan="3" class="fw-bold text-end text-success">Final Payable</td>
                                <td class="text-end fw-bold text-success fs-6">₹<?= number_format($row['received_amount'] ?? 0, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php else: ?>
                        <p class="text-muted text-center small">Per-item weight details not available for this pickup.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-dark w-100 rounded-pill py-2 fw-bold" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endforeach; ?>
</div>

<script>
function revealPhone(btn, phone) {
    const textSpan = btn.querySelector('.btn-text');
    textSpan.innerText = phone;
    btn.onclick = function() { window.location.href = 'tel:' + phone; };
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>