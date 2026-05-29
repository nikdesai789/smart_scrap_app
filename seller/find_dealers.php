<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
}

if (isset($_POST['lat']) && isset($_POST['lng'])) {
    $_SESSION['u_lat'] = (float)$_POST['lat'];
    $_SESSION['u_lng'] = (float)$_POST['lng'];
    $_SESSION['u_addr'] = $_POST['address'] ?? '';
}

$v_lat = (float)($_SESSION['u_lat'] ?? 15.8497);
$v_lng = (float)($_SESSION['u_lng'] ?? 74.4977);


$v_type = $_POST['scrap_type'] ?? ($_SESSION['v_type'] ?? ''); 
$v_weight = $_POST['weight'] ?? ($_SESSION['v_weight'] ?? 'N/A');

if (isset($_POST['weight'])) { $_SESSION['v_weight'] = $_POST['weight']; }
if (isset($_POST['scrap_type'])) { $_SESSION['v_type'] = $_POST['scrap_type']; }

function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// 2. Query updated to filter by scrap_type[cite: 2, 4]
$sql = "SELECT s.*, 
        AVG(r.rating) as avg_rating, 
        COUNT(r.id) as total_deals 
        FROM stakeholders s 
        LEFT JOIN scrap_requests r ON s.id = r.dealer_id AND r.status = 'COMPLETED'
        WHERE s.profile_completion = 100";

// Filter only if scrap type is provided
if (!empty($v_type)) {
    $sql .= " AND s.scrap_types LIKE :stype";
}

$sql .= " GROUP BY s.id";

$stmt = $pdo->prepare($sql);
if (!empty($v_type)) {
    $stmt->bindValue(':stype', '%' . $v_type . '%');
}
$stmt->execute();
$all = $stmt->fetchAll();

$results = [];
foreach ($all as $d) {
    if(empty($d['latitude']) || empty($d['longitude'])) continue;
    $dist = getDistance($v_lat, $v_lng, (float)$d['latitude'], (float)$d['longitude']);
    $limit = (float)($d['service_radius'] ?? 15); 
    if ($dist <= $limit) {
        $d['km'] = $dist;
        $results[] = $d;
    }
}
usort($results, fn($a, $b) => $a['km'] <=> $b['km']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nearest Dealers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f7f8f9; font-family: 'Segoe UI', sans-serif; }
        .m-header { background: #9f2089; color: white; padding: 15px; position: sticky; top: 0; z-index: 100; }
        .dealer-card { background: white; border-radius: 8px; border: 1px solid #eee; overflow: hidden; margin-bottom: 15px; cursor: pointer; transition: 0.2s; height: 100%; display: flex; flex-direction: column; }
        .dealer-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .shop-img-wrapper { width: 100%; height: 220px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .shop-img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .card-details { padding: 12px; flex-grow: 1; }
        .biz-name { font-size: 14px; font-weight: 600; color: #666; display: block; margin-bottom: 2px; text-transform: uppercase; }
        .distance { font-size: 18px; font-weight: 800; color: #333; }
        .rating-box { background: #23bb75; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .btn-view { background: #9f2089; color: white; border: none; width: 100%; padding: 10px; font-weight: bold; }
        .modal-img { width: 100%; height: 250px; object-fit: cover; background: #f8f9fa; border-radius: 10px; }
        .meesho-label { color: #888; font-size: 11px; font-weight: bold; margin-top: 12px; display: block; text-transform: uppercase; }
        .meesho-value { color: #333; font-size: 14px; font-weight: 600; display: block; }
        .price-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f1f1; font-weight: 600; }
        .price-val { color: #23bb75; font-weight: bold; }
    </style>
</head>
<body>

<div class="m-header shadow-sm">
    <div class="container fw-bold d-flex align-items-center">
        <a href="dashboard.php" style="color:white; text-decoration:none;"><i class="fas fa-arrow-left me-3"></i></a> 
        <?= htmlspecialchars($v_type ?: 'Available') ?> Dealers (<?= count($results) ?> Found)
    </div>
</div>

<div class="container mt-4">
    <!-- Active Filter Info -->
    <div class="mb-3 px-2">
        <span class="badge bg-white text-dark border shadow-sm p-2">
            <i class="fas fa-info-circle me-1 text-primary"></i> 
            Showing dealers for <strong><?= htmlspecialchars($v_type ?: 'All Scrap') ?></strong> | Est. Weight: <strong><?= htmlspecialchars($v_weight) ?> Kg</strong>
        </span>
    </div>

    <div class="row g-3">
        <?php foreach($results as $d): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="dealer-card" data-bs-toggle="modal" data-bs-target="#m<?= $d['id'] ?>">
                    <?php 
                        $photoName = trim($d['shop_image'] ?? '');
                        $finalSrc = 'https://via.placeholder.com/400x300?text=No+Photo';
                        $targetPath = "../uploads/shop_images/" . $photoName;
                        if (!empty($photoName) && file_exists($targetPath)) { $finalSrc = $targetPath; }
                        elseif(!empty($photoName) && file_exists("../uploads/" . $photoName)) { $finalSrc = "../uploads/" . $photoName; }
                    ?>
                    <div class="shop-img-wrapper">
                        <img src="<?= $finalSrc ?>" class="shop-img" onerror="this.src='https://via.placeholder.com/400x300?text=Image+Missing'">
                    </div>
                    <div class="card-details">
                        <span class="biz-name text-truncate"><?= htmlspecialchars($d['business_name'] ?? 'New Dealer') ?></span>
                        <div class="distance"><?= round($d['km'], 1) ?> <small style="font-size:12px">km away</small></div>
                        
                        <div class="mt-2 text-primary small fw-bold">Verified Dealer</div>
                        <div class="mt-2">
                            <span class="rating-box">
                                <?= $d['avg_rating'] ? number_format($d['avg_rating'], 1) : '4.3' ?> 
                                <i class="fas fa-star" style="font-size:10px"></i>
                            </span>
                            <small class="text-muted ms-1" style="font-size: 10px;">(<?= $d['total_deals'] ?> Deals)</small>
                        </div>
                    </div>
                    <button class="btn-view">VIEW DETAILS</button>
                </div>
            </div>

            <div class="modal fade" id="m<?= $d['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:15px; border:none;">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bold m-0"><?= htmlspecialchars($d['business_name'] ?? 'Shop Details') ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body pt-2">
                            <img src="<?= $finalSrc ?>" class="modal-img mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <span class="meesho-label">Working Days</span>
                                    <span class="meesho-value">
                                        <?php 
                                            $days = $d['working_days'] ?? '';
                                            if (!empty($days)) {
                                                $dayArray = explode(',', $days);
                                                if (count($dayArray) >= 7) { echo "All Days"; } 
                                                elseif (count($dayArray) == 6) { echo "Mon-Sat"; } 
                                                else { echo htmlspecialchars($days); }
                                            } else { echo "Mon-Sat"; }
                                        ?>
                                    </span>
                                </div>
                                <div class="row mt-2">
    <div class="col-6">
        <span class="meesho-label"><i class="far fa-clock me-1"></i> Opens At</span>
        <span class="meesho-value">
            <?= !empty($d['opening_time']) ? date("g:i A", strtotime($d['opening_time'])) : '09:00 AM' ?>
        </span>
    </div>
    <div class="col-6">
        <span class="meesho-label"><i class="fas fa-door-closed me-1"></i> Closes At</span>
        <span class="meesho-value">
            <?= !empty($d['closing_time']) ? date("g:i A", strtotime($d['closing_time'])) : '07:00 PM' ?>
        </span>
    </div>
</div>
                                <div class="col-6">
                                    <span class="meesho-label">Service Radius</span>
                                    <span class="meesho-value" style="color: #0d6efd;"><?= htmlspecialchars($d['service_radius'] ?? '15') ?> KM</span>
                                </div>
                            </div>

                            <span class="meesho-label">Full Address</span>
                            <span class="meesho-value" style="font-size:12px; font-weight:normal;"><?= htmlspecialchars($d['address'] ?? 'Not Provided') ?></span>
                            
                            <h6 class="mt-4 fw-bold" style="color:#9f2089; font-size:13px;">SCRAP PRICES (Per Kg)</h6>
                            <div class="price-list">
                                <?php 
                                    $selected = explode(',', $d['scrap_types'] ?? '');
                                    $priceMap = ['Plastic'=>'plastic_price','Paper'=>'paper_price','Iron'=>'iron_price','Metal'=>'metal_price','Copper'=>'copper_price','E-Waste'=>'ewaste_price'];
                                    if(!empty($d['scrap_types'])):
                                        foreach($selected as $type): 
                                            $type = trim($type); $col = $priceMap[$type] ?? null;
                                            if($col && isset($d[$col])): ?>
                                                <div class="price-row"><span><?= $type ?></span><span class="price-val">₹<?= $d[$col] ?></span></div>
                                            <?php endif; 
                                        endforeach; 
                                    endif;
                                ?>
                            </div>
                            <hr>
                            <h6 class="mt-4 fw-bold" style="color:#9f2089; font-size:13px; border-bottom: 2px solid #f1f1f1; padding-bottom: 5px;">
                                <i class="fas fa-comment-dots me-2"></i>CUSTOMER REVIEWS
                            </h6>

                            <div class="reviews-list" style="max-height: 200px; overflow-y: auto; padding-right: 5px;">
                                <?php 
                                    $review_sql = "SELECT r.feedback_text, r.rating, u.name, r.created_at 
                                                   FROM scrap_requests r 
                                                   JOIN users u ON r.user_id = u.id 
                                                   WHERE r.dealer_id = ? AND r.status = 'COMPLETED' AND r.rating IS NOT NULL 
                                                   ORDER BY r.id DESC LIMIT 3";
                                    $review_stmt = $pdo->prepare($review_sql);
                                    $review_stmt->execute([$d['id']]);
                                    $reviews = $review_stmt->fetchAll();

                                    if ($reviews): 
                                        foreach ($reviews as $rev): 
                                ?>
                                    <div class="review-item mb-3 p-2 rounded shadow-sm" style="background: #fdfdfd; border: 1px solid #eee;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark" style="font-size: 12px;"><?= htmlspecialchars($rev['name']) ?></span>
                                            <span class="text-warning" style="font-size: 10px;">
                                                <?php for($i=1; $i<=5; $i++) echo ($i <= $rev['rating']) ? '★' : '☆'; ?>
                                            </span>
                                        </div>
                                        <p class="mb-1 text-muted italic" style="font-size: 11px; line-height: 1.4;">"<?= htmlspecialchars($rev['feedback_text'] ?? 'Great service!') ?>"</p>
                                        <small class="text-uppercase border-top d-block pt-1 mt-1" style="font-size: 9px; color: #bbb;">
                                            <i class="far fa-clock me-1"></i><?= date('M Y', strtotime($rev['created_at'])) ?>
                                        </small>
                                    </div>
                                <?php endforeach; else: ?>
                                    <div class="text-center py-3">
                                        <p class="text-muted small mb-0 italic">No reviews yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <hr>
                            <!-- Submit Form: Yahan badlav kiya hai direct send karne ke liye[cite: 4] -->
                            <form action="send_request.php" method="POST">
                                <input type="hidden" name="dealer_id" value="<?= $d['id'] ?>">
                                <input type="hidden" name="weight" value="<?= htmlspecialchars($v_weight) ?>">
                                <input type="hidden" name="scrap_type" value="<?= htmlspecialchars($v_type) ?>">
                                <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-pill">Send Request</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>