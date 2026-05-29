<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['stakeholder_id'])) {
    header("Location: ../login.php");
    exit();
}

$dealer_id = $_SESSION['stakeholder_id'];

try {
    $stmt_dealer = $pdo->prepare("SELECT * FROM stakeholders WHERE id = ?");
    $stmt_dealer->execute([$dealer_id]);
    $dealer_data = $stmt_dealer->fetch(PDO::FETCH_ASSOC);
    $d_lat = $dealer_data['latitude'] ?? 0;
    $d_lng = $dealer_data['longitude'] ?? 0;

    function getDistance($lat1, $lon1, $lat2, $lon2) {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return "N/A";
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos(min(max($dist, -1.0), 1.0));
        return round(rad2deg($dist) * 60 * 1.1515 * 1.609344, 1);
    }

    $query = "SELECT r.*, u.name as user_name, u.phone as user_phone 
              FROM scrap_requests r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.dealer_id = ? AND r.status = 'ACCEPTED' 
              ORDER BY r.pickup_date DESC, r.pickup_time DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$dealer_id]);
    $accepted_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ongoing Pickups | Smart Scrap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .dashboard-card { border: none; border-radius: 15px; background: white; margin-bottom: 20px; border-left: 5px solid #10b981; transition: 0.3s; }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .weight-badge { background: #f0f7ff; border: 1px dashed #3b82f6; border-radius: 12px; padding: 12px; }
        .note-box { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 10px; margin-top: 10px; font-size: 0.85rem; color: #92400e; }
        .star-rating { direction: rtl; display: inline-block; padding: 20px; }
        .star-rating input { display: none; }
        .star-rating label { color: #ccc; cursor: pointer; font-size: 30px; transition: 0.3s; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #f59e0b; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Ongoing Pickups 🚛</h2>
            <p class="text-muted">Manage your active scrap collections</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <?php if(empty($accepted_requests)): ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
            <p class="fs-5 text-muted">No active pickups found at the moment.</p>
        </div>
    <?php else: ?>
       <!-- ... (Pichla PHP logic same rahega) ... -->

<?php foreach($accepted_requests as $row): 
    $km = getDistance($d_lat, $d_lng, $row['latitude'], $row['longitude']);
?>
    <div class="card dashboard-card shadow-sm p-4">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($row['user_name']) ?></h5>
                <p class="small text-muted mb-2">
                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                    <?= htmlspecialchars($row['address']) ?>
                </p>
                 <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($req['address']) ?>" target="_blank" class="btn btn-action btn-maps">
                                        <i class="fa-solid fa-location-arrow me-1"></i> Map View
                                    </a>
                <!-- 📝 Message/Note Section (Wapas Add Kiya) -->
                <?php if(!empty($row['dealer_note'])): ?>
                    <div class="note-box mb-3">
                        <strong><i class="fas fa-sticky-note me-1 text-warning"></i> Your Note:</strong><br>
                        "<?= htmlspecialchars($row['dealer_note']) ?>"
                    </div>
                <?php endif; ?>

                
            </div>

            <div class="col-md-5 text-center">
                <div class="weight-badge mb-3">
                    <small class="text-muted fw-bold d-block">ESTIMATED WEIGHT</small>
                    <span class="fs-4 fw-bold text-primary"><?= $row['estimated_weight'] ?> kg</span>
                </div>
                <div class="d-flex justify-content-around fw-bold small">
                    <span><i class="far fa-calendar-alt text-success me-1"></i> <?= date('d M Y', strtotime($row['pickup_date'])) ?></span>
                    <!-- 12-Hour Time Format -->
                    <span><i class="far fa-clock text-warning me-1"></i> <?= date('h:i A', strtotime($row['pickup_time'])) ?></span>
                    <span><i class="fas fa-road text-info me-1"></i> <?= $km ?> km</span>
                </div>
            </div>

            <div class="col-md-3 text-end">
                <a href="tel:<?= $row['user_phone'] ?>" class="btn btn-dark w-100 mb-2 rounded-pill fw-bold">
                    <i class="fas fa-phone-alt me-2"></i> Call Vendor
                </a>
                <button type="button" class="btn btn-success w-100 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#completeModal<?= $row['id'] ?>">
                    <i class="fas fa-check-circle me-2"></i> Mark Completed
                </button>
            </div>
        </div>
    </div>

            <!-- Modal logic remains consistent with previous fix -->
            <div class="modal fade" id="completeModal<?= $row['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form action="complete_pickup.php" method="POST" class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header bg-success text-white rounded-top-4">
                            <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Generate Final Bill</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                            <div class="mb-4 text-center">
                                <label class="form-label fw-bold">Seller Verification OTP 🔒</label>
                                <input type="text" id="otp_input_<?= $row['id'] ?>" name="otp" class="form-control form-control-lg text-center fw-bold border-primary mx-auto" style="max-width: 220px; letter-spacing: 5px;" required maxlength="4" placeholder="0000">
                                <div id="otp_error_<?= $row['id'] ?>" class="text-danger small mt-2 d-none">❌ OTP incorrect. Ask seller again.</div>
                            </div>
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr><th>Type</th><th>Rate</th><th style="width:120px;">Weight</th><th class="text-end">Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $items = ['paper' => 'Paper', 'plastic' => 'Plastic', 'iron' => 'Iron', 'metal' => 'Metal', 'copper' => 'Copper', 'ewaste' => 'E-Waste'];
                                    foreach($items as $k => $l): 
                                        $p = $row["applied_".$k."_price"] ?? 0;
                                        if($p > 0): ?>
                                     <!-- Har row ke andar unit-price ke pass yeh hidden input dalein -->
<tr class="item-row">
    <td><strong><?= $l ?></strong></td>
    <td>
        ₹<span class="unit-price"><?= $p ?></span>
        <input type="hidden" name="prices[<?= $k ?>]" value="<?= $p ?>"> <!-- Yeh line check karein -->
    </td>
    <td><input type="number" step="0.01" name="weights[<?= $k ?>]" class="form-control weight-input"></td>
    <td class="text-end fw-bold">₹<span class="row-total">0.00</span></td>
</tr>
                                    <?php endif; endforeach; ?>
                                </tbody>
                            </table>
                            <div class="bg-light p-3 rounded-3 mt-3 shadow-sm border">
                                <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><span class="fw-bold">₹<span class="items-total">0.00</span></span></div>
                                <div class="d-flex justify-content-between align-items-center mb-2"><span>Pickup Fee:</span><input type="number" name="pickup_fee" class="form-control pickup-fee-input" value="0" style="width: 80px;"></div>
                                <hr><div class="d-flex justify-content-between fs-5 text-success"><strong>Final Payable:</strong><strong>₹<span class="final-amount">0.00</span></strong></div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="submit" id="submit_btn_<?= $row['id'] ?>" class="btn btn-success w-100 py-3 rounded-pill fw-bold" disabled>Confirm Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Logic to ensure time and calculations are updated real-time
document.querySelectorAll('.modal').forEach(m => {
    const weights = m.querySelectorAll('.weight-input');
    const fee = m.querySelector('.pickup-fee-input');
    function calc() {
        let t = 0;
        m.querySelectorAll('.item-row').forEach(r => {
            const p = parseFloat(r.querySelector('.unit-price').innerText) || 0;
            const w = parseFloat(r.querySelector('.weight-input').value) || 0;
            const s = p * w;
            r.querySelector('.row-total').innerText = s.toFixed(2);
            t += s;
        });
        m.querySelector('.items-total').innerText = t.toFixed(2);
        const f = parseFloat(fee.value) || 0;
        m.querySelector('.final-amount').innerText = Math.max(0, t - f).toFixed(2);
    }
    weights.forEach(i => i.addEventListener('input', calc));
    fee.addEventListener('input', calc);
});

// OTP AJAX verification remains unchanged
document.querySelectorAll('[id^="otp_input_"]').forEach(i => {
    i.addEventListener('input', function() {
        const id = this.id.split('_').pop();
        const otp = this.value;
        const btn = document.getElementById(`submit_btn_${id}`);
        const err = document.getElementById(`otp_error_${id}`);
        if(otp.length === 4) {
            const fd = new FormData(); fd.append('id', id); fd.append('otp', otp);
            fetch('verify_otp_ajax.php', { method: 'POST', body: fd })
            .then(r => r.text()).then(s => {
                if(s.trim() === 'success') { err.classList.add('d-none'); btn.disabled = false; }
                else { err.classList.remove('d-none'); btn.disabled = true; }
            });
        }
    });
});
</script>
</body>
</html>