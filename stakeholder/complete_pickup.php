<?php
session_start();
require_once '../config/database.php';

// --- SECURITY CHECK: Dealer ID set karna zaroori hai ---
if (!isset($_SESSION['stakeholder_id'])) {
    header("Location: ../login.php");
    exit();
}
$dealer_id = $_SESSION['stakeholder_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $weights = $_POST['weights'] ?? []; 
    $prices = $_POST['prices'] ?? [];
    $pickup_fee = floatval($_POST['pickup_fee'] ?? 0);

    $total_weight = !empty($weights) ? array_sum($weights) : 0;
    $total_amount = 0;

    try {
        $pdo->beginTransaction();
        
        // Loop ke andar weights aur prices dono update karein
        foreach ($weights as $type => $w) {
            $w = floatval($w);
            $p = floatval($prices[$type] ?? 0); // Price form se aa raha hai
            $subtotal = $w * $p;
            $total_amount += $subtotal;

            $col_w = $type . "_weight";
            $col_p = "applied_" . $type . "_price";

            $stmt = $pdo->prepare("UPDATE scrap_requests SET $col_w = ?, $col_p = ? WHERE id = ?");
            $stmt->execute([$w, $p, $request_id]);
        }

        // Final update mein pickup_fee aur final amount save karein
        $final_payable = max(0, $total_amount - $pickup_fee);

        // Status update karne wala section
        $final_payable = max(0, $total_amount - $pickup_fee);

        // Query mein 'pickup_fee = ?' hona chahiye
        $stmt_update = $pdo->prepare("UPDATE scrap_requests SET 
            status = 'COMPLETED', 
            final_weight = ?, 
            received_amount = ?, 
            pickup_fee = ?, 
            completed_at = NOW() 
            WHERE id = ?");

        // Correct sequence: weight, payable, fee, id
        $stmt_update->execute([$total_weight, $final_payable, $pickup_fee, $request_id]);

        $pdo->commit();

        // Dealer ko isi page par redirect karein taaki wo history dekh sake
        header("Location: complete_pickup.php?msg=transaction_success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// --- Part 2: Fetching History Data (GET) ---
try {
    // Status 'COMPLETED' (Caps) check karein kyunki update mein humne caps use kiya hai
    // IMPORTANT: Include rating aur feedback_text fields
    $sql = "SELECT r.*, u.name as seller_name, u.phone as seller_phone 
            FROM scrap_requests r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.dealer_id = ? AND (r.status = 'COMPLETED' OR r.status = 'completed') 
            ORDER BY r.completed_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dealer_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .history-card { background: white; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); border: none; }
        .status-completed { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .stars { color: #facc15; font-size: 1rem; }
        .text-amount { color: #059669; font-weight: 700; font-size: 1.1rem; }
        .rating-badge { display: inline-block; background: #fff9e6; color: #92400e; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600; }
        .no-rating { background: #f0f0f0; color: #666; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">Transaction History</h3>
        
        <span class="badge bg-dark rounded-pill px-3 py-2">Total Records: <?= count($history) ?></span>
        <a href="dashboard.php" class="btn btn-outline-dark rounded-pill">Dashboard</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> Transaction completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="history-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted" style="font-size: 0.85rem;">
                    <tr>
                        <th class="ps-4">REQUEST ID</th>
                        <th>SELLER</th>
                        <th>DATE</th>
                        <th>SCRAP TYPE</th>
                        <th>FINAL WEIGHT</th>
                        <th>AMOUNT</th>
                        <th>STATUS</th>
                        <th>RATING</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($history)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No transactions found.</td></tr>
                    <?php else: ?>
                        <?php foreach($history as $row): 
                            $items = [];
                            $types = ['plastic', 'paper', 'iron', 'metal', 'copper', 'ewaste'];
                            foreach ($types as $t) {
                                $p = "applied_".$t."_price";
                                if (isset($row[$p]) && $row[$p] > 0) {
                                  $items[] = ucfirst($t) . ' <span class="text-muted" style="font-size: 0.75rem;">(₹' . number_format($row[$p], 0) . ')</span>';
                                }
                            }
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-secondary">#SR-<?= $row['id'] ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($row['seller_name']) ?></div>
                                <small class="text-muted"><?= $row['seller_phone'] ?></small>
                            </td>
                            <td><i class="far fa-calendar-alt text-primary me-1"></i> <?= date('d M, Y', strtotime($row['completed_at'])) ?></td>
                            <td><small><?= implode(", ", $items) ?></small></td>
                            <td><span class="fw-bold"><?= number_format($row['final_weight'], 2) ?></span> <small>kg</small></td>
                            <td class="text-amount">₹<?= number_format($row['received_amount'], 2) ?></td>
                            <td><span class="status-completed">Completed</span></td>
                            <td>
                                <?php if(empty($row['rating']) || $row['rating'] == 0): ?>
                                    <span class="no-rating">
                                        <i class="far fa-star me-1"></i> Pending
                                    </span>
                                <?php else: ?>
                                    <div>
                                        <div class="stars mb-1" title="<?= $row['rating'] ?> out of 5">
                                            <?php for($i=1; $i<=5; $i++) echo ($i <= $row['rating']) ? '★' : '☆'; ?>
                                        </div>
                                    
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>