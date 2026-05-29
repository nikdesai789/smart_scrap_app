<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Data Fetching Query[cite: 7]
$query = "SELECT r.*, s.business_name 
          FROM scrap_requests r 
          LEFT JOIN stakeholders s ON r.dealer_id = s.id 
          WHERE r.user_id = ? 
          ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$sent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sent Requests History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7fa; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 30px; }
        .table-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .item-badge { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; margin: 2px; }
        .img-thumbnail-custom { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; cursor: pointer; border: 2px solid #fff; shadow: 0 2px 5px rgba(0,0,0,0.1); transition: 0.3s; }
        .img-thumbnail-custom:hover { transform: scale(1.1); }
        .modal-content { border-radius: 20px; border: none; overflow: hidden; }
    </style>
</head>
<body>

<div class="container-fluid main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-paper-plane text-success me-2"></i> Sent Requests Log</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <div class="table-card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Photo</th>
                        <th>Dealer & Date</th>
                        <th>Items & Fixed Rates</th>
                        <th>Weight</th>
                        <th>Location Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sent_requests as $req): ?>
                    <tr>
                        <td>
                            <!-- Image trigger for Modal -->
                            <img src="../uploads/<?= $req['scrap_image'] ?>" 
                                 class="img-thumbnail-custom shadow-sm" 
                                 data-bs-toggle="modal" 
                                 data-bs-target="#imgModal<?= $req['id'] ?>">
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($req['business_name'] ?? 'Direct Dealer') ?></div>
                            <!-- Sirf Date dikhayenge[cite: 7] -->
                            <small class="text-muted"><i class="far fa-calendar me-1"></i><?= date('d M Y', strtotime($req['created_at'])) ?></small>
                        </td>
                        <td>
                            <?php 
                            $items = explode(',', $req['scrap_type']);
                            foreach($items as $item): 
                                $t_name = trim($item);
                                // Price mapping base on column name in DB[cite: 7]
                                $col = "applied_" . strtolower(str_replace([' ', '-'], '', $t_name)) . "_price";
                                $price = $req[$col] ?? 0;
                            ?>
                                <span class="item-badge">
                                    <?= $t_name ?> <span class="text-success ms-1">₹<?= $price ?></span>
                                </span>
                            <?php endforeach; ?>
                        </td>
                        <td><span class="badge bg-dark rounded-pill px-3"><?= $req['estimated_weight'] ?> KG</span></td>
                        <td>
                            <div class="small text-muted" style="max-width: 250px;">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i> <?= htmlspecialchars($req['address']) ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal for Image Preview with Close Option -->
                    <div class="modal fade" id="imgModal<?= $req['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0 pb-0">
                                    <h6 class="modal-title fw-bold">Scrap Photo Preview</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-3 text-center">
                                    <img src="../uploads/<?= $req['scrap_image'] ?>" class="img-fluid rounded-3 shadow">
                                </div>
                                <div class="modal-footer border-0 pt-0 justify-content-center">
                                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>