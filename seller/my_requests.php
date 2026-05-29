<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$types = ['plastic', 'paper', 'iron', 'metal', 'copper', 'ewaste'];
$feedback_success = false;
$feedback_error = "";

// --- 1. CANCEL REQUEST LOGIC ---
if (isset($_POST['cancel_request'])) {
    $req_id = $_POST['request_id'];
    $cancel_sql = "UPDATE scrap_requests SET status = 'CANCELLED' WHERE id = ? AND user_id = ? AND status = 'PENDING'";
    $cancel_stmt = $pdo->prepare($cancel_sql);
    if ($cancel_stmt->execute([$req_id, $user_id])) {
        echo "<script>alert('Request cancelled successfully.'); window.location.href='my_requests.php';</script>";
        exit();
    }
}

// --- 2. FEEDBACK SUBMISSION LOGIC (IMPROVED) ---
if (isset($_POST['submit_feedback'])) {
    try {
        $req_id = intval($_POST['request_id']);
        $rating = intval($_POST['rating']);
        $feedback = trim($_POST['feedback'] ?? '');
        
        // Validation
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Rating must be between 1 and 5");
        }
        
        // IMPORTANT: Use SET with named columns, NOT string interpolation
        $update_sql = "UPDATE scrap_requests 
                      SET rating = ?, 
                          feedback_text = ?
                      WHERE id = ? AND user_id = ? AND status = 'COMPLETED'";
        
        $update_stmt = $pdo->prepare($update_sql);
        $result = $update_stmt->execute([$rating, $feedback, $req_id, $user_id]);
        
        if ($result && $update_stmt->rowCount() > 0) {
            $feedback_success = true;
            // Show success message via JavaScript
            echo "<script>
                setTimeout(function() {
                    alert('Thank you! Your feedback has been submitted successfully.');
                    window.location.href='my_requests.php';
                }, 500);
            </script>";
            exit();
        } else {
            $feedback_error = "Could not update request. Please try again.";
        }
    } catch (Exception $e) {
        $feedback_error = "Error: " . $e->getMessage();
    }
}

// --- 3. FETCH ALL REQUESTS (JOINED WITH AGENTS) ---
$query = "SELECT r.*, s.business_name, s.phone as dealer_phone, 
                 a.name as agent_name, a.phone as agent_phone
          FROM scrap_requests r 
          LEFT JOIN stakeholders s ON r.dealer_id = s.id 
          LEFT JOIN agents a ON r.agent_id = a.id 
          WHERE r.user_id = ? 
          ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Scrap Requests | Smart Scrap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        .m-header { background: #10b981; color: white; padding: 15px; position: sticky; top: 0; z-index: 100; }
        .request-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; border: none; max-width: 800px; margin-left: auto; margin-right: auto; }
        .status-completed { border-left: 6px solid #3b82f6; }
        .status-accepted { border-left: 6px solid #10b981; }
        .status-pending { border-left: 6px solid #f59e0b; }
        .status-cancelled { border-left: 6px solid #ef4444; opacity: 0.8; }
        
        .badge-completed { background: #dbeafe; color: #1e40af; }
        .badge-accepted { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        .star-rating { display: flex; flex-direction: row-reverse; font-size: 2rem; justify-content: center; }
        .star-rating input { display: none; }
        .star-rating label { color: #ddd; cursor: pointer; transition: 0.2s; padding: 0 5px; }
        .star-rating input:checked ~ label { color: #f59e0b; }
        .star-rating label:hover, .star-rating label:hover ~ label { color: #fbbf24; }
        
        .dealer-msg-box { background: #fff; border: 1px dashed #10b981; border-radius: 10px; padding: 10px; margin-top: 10px; text-align: left; }
        .agent-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; margin-bottom: 15px; }
        .feedback-submitted { background: #f0fdf4; border: 2px solid #10b981; padding: 15px; border-radius: 12px; }
    </style>
</head>
<body>

<div class="m-header shadow-sm text-center">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="dashboard.php" class="text-white"><i class="fas fa-chevron-left fa-lg"></i></a> 
        <h5 class="m-0 fw-bold">My Scrap Requests</h5>
        <div style="width: 20px;"></div>
    </div>
</div>

<div class="container mt-4 pb-5">
    <?php if(!empty($feedback_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mx-auto" style="max-width: 800px;">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($feedback_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(empty($my_requests)): ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
            <p class="text-muted">No requests found.</p>
        </div>
    <?php endif; ?>

    <?php foreach($my_requests as $req): 
        // Seller ke liye friendly status label aur badge class
        $status_display_map = [
            'ASSIGNED'    => ['label' => 'CONFIRMED',   'class' => 'accepted'],
            'ACCEPTED'    => ['label' => 'CONFIRMED',   'class' => 'accepted'],
            'ON THE WAY'  => ['label' => 'ON THE WAY',  'class' => 'accepted'],
            'AT LOCATION' => ['label' => 'AGENT ARRIVED','class' => 'accepted'],
            'PENDING'     => ['label' => 'PENDING',      'class' => 'pending'],
            'COMPLETED'   => ['label' => 'COMPLETED',    'class' => 'completed'],
            'CANCELLED'   => ['label' => 'CANCELLED',    'class' => 'cancelled'],
            'REJECTED'    => ['label' => 'REJECTED',     'class' => 'cancelled'],
        ];
        $sd = $status_display_map[$req['status']] ?? ['label' => strtoupper($req['status']), 'class' => 'pending'];
    ?>
        <div class="request-card status-<?= $sd['class'] ?> shadow-sm">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($req['business_name'] ?? 'Dealer') ?></h6>
                    <small class="text-muted">
                        <i class="far fa-calendar-alt me-1"></i> 
                        <?= !empty($req['pickup_date']) ? date('d M, Y', strtotime($req['pickup_date'])) : date('d M, Y', strtotime($req['created_at'])) ?>
                    </small>
                </div>
                <span class="badge rounded-pill badge-<?= $sd['class'] ?> px-3 py-2"><?= $sd['label'] ?></span>
            </div>

            <hr class="my-3 opacity-25">

            <?php if(in_array($req['status'], ['ASSIGNED', 'ACCEPTED', 'ON THE WAY', 'AT LOCATION'])): ?>
                <div class="p-3 rounded-4 text-center" style="background: #f0fdf4;">

                    <!-- Pickup Date & Time -->
                    <div class="row g-0 mb-3 border-bottom pb-2">
                        <div class="col-6 border-end">
                            <small class="text-muted d-block" style="font-size: 10px;">PICKUP DATE</small>
                            <span class="fw-bold text-dark"><?= !empty($req['pickup_date']) ? date('d M', strtotime($req['pickup_date'])) : "N/A" ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block" style="font-size: 10px;">PICKUP TIME</small>
                            <span class="fw-bold text-dark"><?= !empty($req['pickup_time']) ? date("g:i A", strtotime($req['pickup_time'])) : "Not Set" ?></span>
                        </div>
                    </div>

                    <!-- Dealer Message -->
                    <?php if(!empty($req['dealer_note'])): ?>
                        <div class="dealer-msg-box shadow-sm">
                            <small class="text-success fw-bold d-block" style="font-size: 9px; letter-spacing: 1px; margin-bottom: 5px;">DEALER MESSAGE:</small>
                            <p class="mb-0 small text-dark">"<?= htmlspecialchars($req['dealer_note']) ?>"</p>
                        </div>
                    <?php endif; ?>

                    <!-- Agent Info -->
                    <?php if(!empty($req['agent_name'])): ?>
                        <div class="agent-box mt-3 text-start">
                            <small class="text-muted d-block fw-bold" style="font-size: 9px;">AGENT ASSIGNED</small>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($req['agent_name']) ?></span>
                                <a href="javascript:void(0);" 
                                   id="agent-btn-<?= $req['id'] ?>"
                                   onclick="handleSmartCall('agent-btn-<?= $req['id'] ?>', '<?= htmlspecialchars($req['agent_phone']) ?>')"
                                   class="btn btn-sm btn-outline-success rounded-pill px-3">
                                    <i class="fas fa-phone-alt me-1"></i> Call
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Location -->
                    <div class="mt-3 pt-2 border-top text-start">
                        <small class="text-muted fw-bold d-block">LOCATION</small>
                        <small class="text-dark"><?= htmlspecialchars($req['delivery_location'] ?? 'Your location') ?></small>
                        <?php if($req['is_self_pickup'] ?? 0): ?>
                            <small class="text-muted fst-italic" style="font-size: 11px;">Self Pickup</small>
                        <?php endif; ?>
                    </div>

                    <!-- OTP -->
                    <p class="small text-muted mb-1 fw-bold mt-3">PICKUP OTP</p>
                    <div class="bg-white border-2 border-dashed border-success text-success p-2 rounded-3 fw-bold fs-3 mb-3" style="letter-spacing: 4px;">
                        <?= $req['otp'] ?? '----' ?>
                    </div>

                    <!-- Call Dealer Button -->
                    <a href="javascript:void(0);"
                       id="dealer-btn-<?= $req['id'] ?>"
                       onclick="handleSmartCall('dealer-btn-<?= $req['id'] ?>', '<?= htmlspecialchars($req['dealer_phone']) ?>')"
                       class="btn btn-success w-100 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-phone-alt me-2"></i> <span id="dealer-btn-text-<?= $req['id'] ?>">CALL DEALER</span>
                    </a>
                </div>

            <?php elseif($req['status'] == 'REJECTED'): ?>
                <div class="p-3 rounded-4" style="background: #fef2f2; border: 1px solid #fee2e2;">
                    <div class="text-center mb-2">
                        <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
                        <h6 class="fw-bold text-danger mb-0">Request Rejected</h6>
                    </div>
                    <?php if(!empty($req['dealer_note'])): ?>
                        <div class="dealer-msg-box shadow-sm" style="background: white; border-color: #ef4444;">
                            <small class="text-danger fw-bold d-block" style="font-size: 9px; letter-spacing: 1px;">REASON FROM DEALER:</small>
                            <p class="mb-0 small italic text-dark">"<?= htmlspecialchars($req['dealer_note']) ?>"</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif($req['status'] == 'COMPLETED'): ?>
                <div class="p-3 rounded-4 mb-3" style="background: #f0f9ff; border: 1px solid #bae6fd;">
                    <h6 class="fw-bold text-primary mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Pickup Summary
                    </h6>
                    <table class="table table-sm table-borderless mb-2" style="font-size: 0.85rem;">
                        <tbody>
                            <?php 
                            $gross_total = 0; 
                            foreach ($types as $t): 
                                $p_col = "applied_" . $t . "_price"; 
                                $w_col = $t . "_weight"; 
                                if (isset($req[$p_col]) && $req[$p_col] > 0 && isset($req[$w_col]) && $req[$w_col] > 0): 
                                    $item_total = $req[$p_col] * $req[$w_col];
                                    $gross_total += $item_total;
                            ?>
                                <tr>
                                    <td class="text-muted"><?= ucfirst($t) ?> <span class="badge bg-light text-dark fw-normal ms-1"><?= number_format($req[$w_col], 2) ?> kg</span></td>
                                    <td class="text-end fw-bold">₹<?= number_format($item_total, 2) ?></td>
                                </tr>
                            <?php endif; endforeach; ?>

                            <tr class="border-top">
                                <td class="text-muted pt-2">Subtotal</td>
                                <td class="text-end fw-bold pt-2">₹<?= number_format($gross_total, 2) ?></td>
                            </tr>

                            <?php if(isset($req['pickup_fee']) && $req['pickup_fee'] > 0): ?>
                            <tr>
                                <td class="text-danger">Pickup Fee</td>
                                <td class="text-end fw-bold text-danger">- ₹<?= number_format($req['pickup_fee'], 2) ?></td>
                            </tr>
                            <?php endif; ?>

                            <tr>
                                <td class="fw-bold text-success">Total Received</td>
                                <td class="text-end fw-bold text-success" style="font-size: 1.1rem;">
                                    ₹<?= number_format($req['received_amount'] > 0 ? $req['received_amount'] : max(0, $gross_total - ($req['pickup_fee'] ?? 0)), 2) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- FEEDBACK SECTION -->
                <?php if(empty($req['rating'])): ?>
                    <div class="feedback-box mt-2 border-top pt-3">
                        <form method="POST" onsubmit="return validateFeedback(this)">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <p class="small fw-bold text-muted text-center mb-2">🌟 RATE YOUR EXPERIENCE</p>
                            <div class="star-rating mb-3">
                                <input type="radio" name="rating" value="5" id="star5-<?= $req['id'] ?>" required><label for="star5-<?= $req['id'] ?>">★</label>
                                <input type="radio" name="rating" value="4" id="star4-<?= $req['id'] ?>"><label for="star4-<?= $req['id'] ?>">★</label>
                                <input type="radio" name="rating" value="3" id="star3-<?= $req['id'] ?>"><label for="star3-<?= $req['id'] ?>">★</label>
                                <input type="radio" name="rating" value="2" id="star2-<?= $req['id'] ?>"><label for="star2-<?= $req['id'] ?>">★</label>
                                <input type="radio" name="rating" value="1" id="star1-<?= $req['id'] ?>"><label for="star1-<?= $req['id'] ?>">★</label>
                            </div>
                            <textarea name="feedback" class="form-control mb-3 rounded-3" placeholder="Share your experience (Optional)" rows="2" maxlength="500"></textarea>
                            <button type="submit" name="submit_feedback" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i> SUBMIT FEEDBACK
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="feedback-submitted text-center">
                        <div class="text-warning mb-2 fs-5">
                            <?php for($i=1; $i<=5; $i++) echo ($i <= $req['rating']) ? '★' : '☆'; ?>
                        </div>
                        <p class="small text-dark mb-0">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            <strong>Thank you for your feedback!</strong>
                        </p>
                        <?php if(!empty($req['feedback_text'])): ?>
                            <p class="small text-dark mt-2 mb-0 fst-italic">
                                "<?= htmlspecialchars($req['feedback_text']) ?>"
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif($req['status'] == 'PENDING'): ?>
                <div class="text-center py-2">
                    <p class="text-muted small mb-3">Waiting for dealer's confirmation...</p>
                    <form method="POST" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <button type="submit" name="cancel_request" class="btn btn-outline-danger btn-sm rounded-pill px-4">Cancel Request</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function validateFeedback(form) {
    const rating = document.querySelector('input[name="rating"]:checked');
    if (!rating) {
        alert('Please select a rating');
        return false;
    }
    return true;
}

function handleSmartCall(elementId, phone) {
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    const target = document.getElementById(elementId);
    if (!target) return;

    if (isMobile) {
        window.location.href = "tel:" + phone;
    } else {
        const spanChild = target.querySelector('span');
        if (spanChild) {
            spanChild.innerText = phone;
        } else {
            target.innerHTML = '<i class="fas fa-phone-alt me-2"></i> ' + phone;
        }
        target.style.color = "#059669";
        target.style.fontWeight = "bold";
        target.onclick = function() { window.location.href = "tel:" + phone; };
    }
}
</script>

</body>
</html>