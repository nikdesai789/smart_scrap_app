<?php
session_start();
require_once '../config/database.php';

// Logout handler
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['agent_id'])) {
    header("Location: login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];
$agent_name = $_SESSION['agent_name'] ?? "Field Agent";

// Handle Status Updates
if (isset($_POST['update_status'])) {
    $req_id = $_POST['request_id'];
    $new_status = $_POST['new_status'];
    $stmt_update = $pdo->prepare("UPDATE scrap_requests SET status = ? WHERE id = ? AND agent_id = ?");
    $stmt_update->execute([$new_status, $req_id, $agent_id]);
}

// Agent: Completed card ko apne dashboard se hide karo (session mein store)
if (isset($_POST['agent_dismiss_completed'])) {
    $req_id = (int)$_POST['request_id'];
    // Session mein dismissed IDs store karo — DB column ki zaroorat nahi
    if (!isset($_SESSION['agent_dismissed_ids'])) $_SESSION['agent_dismissed_ids'] = [];
    if (!in_array($req_id, $_SESSION['agent_dismissed_ids'])) {
        $_SESSION['agent_dismissed_ids'][] = $req_id;
    }
    header("Location: dashboard.php");
    exit();
}

// ============================================================
// AGENT: Complete Pickup Handler
// Ye tab chalega jab agent "Confirm Transaction" button dabayega
// ============================================================
if (isset($_POST['complete_pickup_by_dealer'])) {
    $req_id    = $_POST['request_id'];
    $input_otp = $_POST['otp'];
    $pickup_fee = floatval($_POST['pickup_fee'] ?? 0);
    $weights   = $_POST['weights'] ?? [];

    // OTP Verify karo
    $otp_stmt = $pdo->prepare("SELECT otp FROM scrap_requests WHERE id = ? AND agent_id = ?");
    $otp_stmt->execute([$req_id, $agent_id]);
    $stored_otp = $otp_stmt->fetchColumn();

    if ($stored_otp !== false && $stored_otp == $input_otp) {
        $total_weight = 0;
        $total_amount = 0;
        $pdo->beginTransaction();
        // Har scrap type ka weight save karo
        $scrap_types = ['paper', 'plastic', 'iron', 'metal', 'copper', 'ewaste'];
        foreach ($scrap_types as $type) {
            $w = floatval($weights[$type] ?? 0);
            if ($w > 0) {
                $total_weight += $w;
                // Price already applied_X_price column mein hai, wahi use karo
                $price_stmt = $pdo->prepare("SELECT applied_{$type}_price FROM scrap_requests WHERE id = ?");
                $price_stmt->execute([$req_id]);
                $price = floatval($price_stmt->fetchColumn() ?? 0);
                $total_amount += $w * $price;
                // Weight column update karo
                $w_stmt = $pdo->prepare("UPDATE scrap_requests SET {$type}_weight = ? WHERE id = ?");
                $w_stmt->execute([$w, $req_id]);
            }
        }
        $final_payable = max(0, $total_amount - $pickup_fee);
        // Status COMPLETED karo, amounts save karo
        $done_stmt = $pdo->prepare("UPDATE scrap_requests 
            SET status = 'COMPLETED', 
                final_weight = ?, 
                received_amount = ?, 
                pickup_fee = ?, 
                completed_at = NOW(), 
                otp = NULL 
            WHERE id = ? AND agent_id = ?");
        $done_stmt->execute([$total_weight, $final_payable, $pickup_fee, $req_id, $agent_id]);
        $pdo->commit();
        header("Location: dashboard.php?status=completed");
        exit();
    } else {
        header("Location: dashboard.php?otp_error=" . $req_id);
        exit();
    }
}

try {
    // Distance Calculation Logic
    $stmt_location = $pdo->prepare("SELECT s.latitude, s.longitude FROM stakeholders s JOIN agents a ON s.id = a.dealer_id WHERE a.id = ?");
    $stmt_location->execute([$agent_id]);
    $dealer_coords = $stmt_location->fetch(PDO::FETCH_ASSOC);
    $base_lat = $dealer_coords['latitude'] ?? 0;
    $base_lng = $dealer_coords['longitude'] ?? 0;

    function getDistance($lat1, $lon1, $lat2, $lon2) {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return "N/A";
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos(min(max($dist, -1.0), 1.0));
        return round(rad2deg($dist) * 60 * 1.1515 * 1.609344, 1);
    }

    // Active pickups: ASSIGNED/ACCEPTED/ON THE WAY/AT LOCATION
    $query = "SELECT r.*, u.name as user_name, u.phone as seller_phone 
              FROM scrap_requests r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.agent_id = ? AND r.status NOT IN ('PENDING', 'COMPLETED', 'CANCELLED') 
              ORDER BY r.pickup_date ASC, r.pickup_time ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$agent_id]);
    $assigned_pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Completed pickups: agent ke completed cards (delete karne ke liye)
    $query_done = "SELECT r.*, u.name as user_name 
                   FROM scrap_requests r 
                   JOIN users u ON r.user_id = u.id 
                   WHERE r.agent_id = ? AND r.status = 'COMPLETED' 
                   ORDER BY r.completed_at DESC";
    $stmt_done = $pdo->prepare($query_done);
    $stmt_done->execute([$agent_id]);
    $all_completed = $stmt_done->fetchAll(PDO::FETCH_ASSOC);
    // Session mein dismissed IDs filter karo
    $dismissed = $_SESSION['agent_dismissed_ids'] ?? [];
    $completed_pickups = array_filter($all_completed, fn($r) => !in_array((int)$r['id'], $dismissed));
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Duty | SmartScrap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .top-navbar { background: #0f172a; color: white; padding: 20px; border-radius: 0 0 25px 25px; }
        .dashboard-card { border: none; border-radius: 15px; background: white; margin-bottom: 20px; border-left: 5px solid #f59e0b; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .weight-badge { background: #f0f7ff; border: 1px dashed #3b82f6; border-radius: 12px; padding: 12px; }
        
        /* Tracking Style Buttons */
        .tracking-line { display: flex; align-items: center; justify-content: start; gap: 8px; margin-top: 15px; }
        .tracking-btn { font-size: 0.7rem; font-weight: 700; border-radius: 6px; padding: 8px 10px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; text-transform: uppercase; transition: 0.3s; }
        .arrow-separator { color: #cbd5e1; font-weight: bold; }
        
        /* Active Status Colors */
        .active-accepted { background: #3b82f6 !important; color: white !important; border-color: #3b82f6; }
        .active-shipped { background: #f97316 !important; color: white !important; border-color: #f97316; }
        .active-arrived { background: #10b981 !important; color: white !important; border-color: #10b981; }

        /* Accept button locked state */
        .btn-accepted-locked { background: #3b82f6 !important; color: white !important; border-color: #3b82f6; opacity: 0.75; cursor: not-allowed; }

        /* Completed section */
        .completed-card { border: none; border-radius: 15px; background: white; margin-bottom: 15px; border-left: 5px solid #10b981; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .section-divider { font-size: 0.7rem; font-weight: 800; letter-spacing: 2px; color: #94a3b8; text-transform: uppercase; margin: 25px 0 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
    </style>
</head>
<body>

<div class="top-navbar shadow mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div><h5 class="mb-0 fw-bold">Agent Portal</h5><small class="text-warning">Duty: <?= htmlspecialchars($agent_name) ?></small></div>
        <a href="?logout=1" class="text-white"><i class="fas fa-sign-out-alt fa-lg"></i></a>
    </div>
</div>

<div class="container">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'completed'): ?>
        <div class="alert alert-success rounded-4 shadow-sm mt-2 mb-3" id="completedAlert">
            <i class="fas fa-check-circle me-2"></i> <strong>Pickup Completed!</strong> Transaction successfully saved.
        </div>
        <script>
            // URL clean karo taaki page refresh pe dobara na dikhaye
            if (history.replaceState) history.replaceState(null, '', 'dashboard.php');
            // 4 second mein auto hide
            setTimeout(() => { const a = document.getElementById('completedAlert'); if(a) a.style.display='none'; }, 4000);
        </script>
    <?php endif; ?>
    <?php if(isset($_GET['otp_error'])): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mt-2 mb-3">
            <i class="fas fa-times-circle me-2"></i> <strong>Wrong OTP!</strong> Please re-enter the correct seller OTP.
        </div>
    <?php endif; ?>
    <?php if(empty($assigned_pickups)): ?>
        <div class="text-center py-5 mt-4">
            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No active pickups assigned to you.</h5>
        </div>
    <?php endif; ?>
    <?php foreach($assigned_pickups as $row): 
        $km = getDistance($base_lat, $base_lng, $row['latitude'], $row['longitude']);
        $display_address = preg_replace('/phone:\s*\d+/i', '', $row['address']);
    ?>
        <div class="card dashboard-card p-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($row['user_name']) ?></h5>
                    <p class="small text-muted mb-2"><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= htmlspecialchars(trim($display_address)) ?></p>

                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($row['address']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill mb-1">
                        <i class="fa-solid fa-location-arrow me-1"></i> Open Map
                    </a>

                 <form method="POST" class="tracking-line" id="form_<?= $row['id'] ?>">
    <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
    <input type="hidden" name="new_status" id="st_input_<?= $row['id'] ?>" value="<?= $row['status'] ?>">
    
    <?php 
        $is_accepted  = in_array($row['status'], ['ACCEPTED', 'ON THE WAY', 'AT LOCATION']);
        $is_on_way    = in_array($row['status'], ['ON THE WAY', 'AT LOCATION']);
        $is_at_loc    = $row['status'] === 'AT LOCATION';
    ?>
   <button type="submit" name="update_status" 
        onclick="<?= $is_accepted ? 'return false;' : 'updateBtn('.$row['id'].', \'ACCEPTED\')' ?>" 
        class="tracking-btn <?= $is_accepted ? 'btn-accepted-locked' : '' ?> <?= $row['status'] == 'ACCEPTED' ? 'active-accepted' : '' ?>" 
        id="btn_accept_<?= $row['id'] ?>"
        <?= $is_accepted ? 'disabled title="Already accepted"' : '' ?>>
    <i class="fas fa-check-circle me-1"></i> <?= $is_accepted ? 'Accepted ✓' : 'Accept' ?>
</button>
    
    <span class="arrow-separator">→</span>
    
    <button type="submit" name="update_status" 
            onclick="<?= $is_on_way ? 'return false;' : 'updateBtn('.$row['id'].', \'ON THE WAY\')' ?>"
            class="tracking-btn <?= $is_on_way ? 'btn-accepted-locked' : '' ?> <?= $row['status'] == 'ON THE WAY' ? 'active-shipped' : '' ?>" 
            id="btn_onway_<?= $row['id'] ?>"
            <?= $is_on_way ? 'disabled title="Already on the way"' : '' ?>>
        <i class="fas fa-truck me-1"></i> <?= $is_on_way ? 'On Way ✓' : 'On Way' ?>
    </button>
    
    <span class="arrow-separator">→</span>
    
    <button type="submit" name="update_status" 
            onclick="<?= $is_at_loc ? 'return false;' : 'updateBtn('.$row['id'].', \'AT LOCATION\')' ?>"
            class="tracking-btn <?= $is_at_loc ? 'active-arrived' : '' ?>" 
            id="btn_arrived_<?= $row['id'] ?>"
            <?= $is_at_loc ? 'disabled title="Already arrived"' : '' ?>>
        <i class="fas fa-map-marker-alt me-1"></i> <?= $is_at_loc ? 'Arrived ✓' : 'Arrived' ?>
    </button>
</form>
                </div>

                <div class="col-md-5 text-center">
                    <div class="weight-badge mb-3">
                        <small class="text-muted fw-bold d-block">ESTIMATED WEIGHT</small>
                        <span class="fs-4 fw-bold text-primary"><?= $row['estimated_weight'] ?> kg</span>
                    </div>
                    <div class="d-flex justify-content-around fw-bold small">
                        <span><i class="far fa-calendar-alt text-success me-1"></i> <?= date('d M', strtotime($row['pickup_date'])) ?></span>
                        <span><i class="far fa-clock text-warning me-1"></i> <?= date('h:i A', strtotime($row['pickup_time'])) ?></span>
                        <span class="text-info"><i class="fas fa-road me-1"></i> <?= ($km !== "N/A") ? "$km km" : "N/A" ?></span>
                    </div>
                </div>

                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-dark w-100 mb-2 rounded-pill fw-bold" onclick="this.innerHTML='<i class=\'fas fa-phone-alt me-2\'></i> <?= $row['seller_phone'] ?>'; window.location.href='tel:<?= $row['seller_phone'] ?>'">
                        <i class="fas fa-phone-alt me-2"></i> Call Seller
                    </button>
                    <button type="button" class="btn btn-warning text-white w-100 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#completeModal<?= $row['id'] ?>">
                        Mark Completed
                    </button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="completeModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form action="dashboard.php" method="POST" class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold">Verification & Billing</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                        
                        <div class="row mb-4">
                            <div class="col-6 border-end text-center">
                                <label class="form-label fw-bold">Seller OTP</label>
                                <input type="text" id="otp_input_<?= $row['id'] ?>" name="otp" class="form-control text-center fw-bold border-warning mx-auto" style="max-width: 150px;" required maxlength="4">
                                <div id="otp_error_<?= $row['id'] ?>" class="text-danger small mt-2 d-none">❌ Incorrect OTP</div>
                            </div>
                            <div class="col-6 text-center">
                                <label class="form-label fw-bold text-danger">Enter Pickup Fee</label>
                                <input type="number" step="0.01" name="pickup_fee" class="form-control text-center fw-bold border-danger pickup-fee-input mx-auto" style="max-width: 150px;" placeholder="0.00" required>
                            </div>
                        </div>
                        
                        <table class="table">
                            <thead class="table-light">
                                <tr><th>Type</th><th>Rate</th><th>Weight</th><th class="text-end">Total</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $items = ['paper' => 'Paper', 'plastic' => 'Plastic', 'iron' => 'Iron', 'metal' => 'Metal', 'copper' => 'Copper', 'ewaste' => 'E-Waste'];
                                foreach($items as $k => $l): 
                                    $p = $row["applied_".$k."_price"] ?? 0;
                                    if($p > 0): ?>
                                        <tr class="item-row">
                                            <td><strong><?= $l ?></strong></td>
                                            <td>₹<span class="unit-price"><?= $p ?></span></td>
                                            <td><input type="number" step="0.01" name="weights[<?= $k ?>]" class="form-control weight-input" placeholder="0"></td>
                                            <td class="text-end fw-bold">₹<span class="row-total">0.00</span></td>
                                        </tr>
                                <?php endif; endforeach; ?>
                            </tbody>
                        </table>

                        <div class="bg-light p-3 rounded-3 mt-3">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Subtotal:</span>
                                <span>₹<span class="sub-total">0.00</span></span>
                            </div>
                            <div class="d-flex justify-content-between text-danger small mb-2 border-bottom pb-2">
                                <span>Pickup Fee (Minus):</span>
                                <span>- ₹<span class="fee-display">0.00</span></span>
                            </div>
                            <div class="d-flex justify-content-between text-success fs-5">
                                <strong>Final Payable:</strong>
                                <strong>₹<span class="final-amount">0.00</span></strong>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="complete_pickup_by_dealer" id="submit_btn_<?= $row['id'] ?>" class="btn btn-warning w-100 py-3 rounded-pill fw-bold" disabled>Confirm Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if(!empty($completed_pickups)): ?>
    <div class="section-divider">✔ Completed Pickups</div>

    <?php foreach($completed_pickups as $done): 
        $scrap_label_map = ['paper'=>'Paper','plastic'=>'Plastic','iron'=>'Iron','metal'=>'Metal','copper'=>'Copper','ewaste'=>'E-Waste'];
        $done_gross = 0;
        foreach($scrap_label_map as $k => $l) {
            $w = floatval($done[$k.'_weight'] ?? 0);
            $p = floatval($done['applied_'.$k.'_price'] ?? 0);
            $done_gross += $w * $p;
        }
    ?>
    <div class="card completed-card p-3">
        <div class="row align-items-center">
            <div class="col-md-5">
                <span class="badge bg-success mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">COMPLETED</span>
                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($done['user_name']) ?></h6>
                <small class="text-muted"><i class="far fa-calendar-check me-1 text-success"></i>
                    <?= !empty($done['completed_at']) ? date('d M Y, h:i A', strtotime($done['completed_at'])) : 'N/A' ?>
                </small>
            </div>
            <div class="col-md-4 text-center">
                <small class="text-muted d-block" style="font-size: 0.65rem; letter-spacing: 1px;">FINAL WEIGHT</small>
                <span class="fw-bold text-primary"><?= number_format($done['final_weight'] ?? 0, 2) ?> kg</span>
                <br>
                <small class="text-muted d-block mt-1" style="font-size: 0.65rem; letter-spacing: 1px;">AMOUNT PAID</small>
                <span class="fw-bold text-success fs-6">₹<?= number_format($done['received_amount'] ?? 0, 2) ?></span>
            </div>
            <div class="col-md-3 text-end">
                <form method="POST" onsubmit="return confirm('Remove this card from your view?');">
                    <input type="hidden" name="request_id" value="<?= $done['id'] ?>">
                    <button type="submit" name="agent_dismiss_completed" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                        <i class="fas fa-trash-alt me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function updateBtn(id, status) {
    document.getElementById('st_input_' + id).value = status;
    
    // Reset all buttons in this row first
    const row = document.getElementById('form_' + id);
    row.querySelectorAll('.tracking-btn').forEach(btn => {
        btn.classList.remove('active-accepted', 'active-shipped', 'active-arrived');
    });

    // Apply the active color immediately
    const target = event.currentTarget;
    if (status === 'ACCEPTED') target.classList.add('active-accepted');
    if (status === 'ON THE WAY') target.classList.add('active-shipped');
    if (status === 'AT LOCATION') target.classList.add('active-arrived');
}
document.querySelectorAll('.modal').forEach(m => {
    const weights = m.querySelectorAll('.weight-input');
    const feeInput = m.querySelector('.pickup-fee-input');
    const feeDisplay = m.querySelector('.fee-display');

    function calc() {
        let subtotal = 0;
        const currentFee = parseFloat(feeInput.value) || 0;
        feeDisplay.innerText = currentFee.toFixed(2);

        m.querySelectorAll('.item-row').forEach(r => {
            const p = parseFloat(r.querySelector('.unit-price').innerText) || 0;
            const w = parseFloat(r.querySelector('.weight-input').value) || 0;
            const s = p * w;
            r.querySelector('.row-total').innerText = s.toFixed(2);
            subtotal += s;
        });

        m.querySelector('.sub-total').innerText = subtotal.toFixed(2);
        const final = Math.max(0, subtotal - currentFee);
        m.querySelector('.final-amount').innerText = final.toFixed(2);
    }
    weights.forEach(i => i.addEventListener('input', calc));
    feeInput.addEventListener('input', calc);
});

document.querySelectorAll('[id^="otp_input_"]').forEach(i => {
    i.addEventListener('input', function() {
        const id = this.id.split('_').pop();
        if(this.value.length === 4) {
            const fd = new FormData(); fd.append('id', id); fd.append('otp', this.value);
            fetch('../stakeholder/verify_otp_ajax.php', { method: 'POST', body: fd })
            .then(r => r.text()).then(s => {
                const btn = document.getElementById(`submit_btn_${id}`);
                const err = document.getElementById(`otp_error_${id}`);
                if(s.trim() === 'success') { err.classList.add('d-none'); btn.disabled = false; }
                else { err.classList.remove('d-none'); btn.disabled = true; }
            });
        }
    });
});
</script>
</body>
</html>