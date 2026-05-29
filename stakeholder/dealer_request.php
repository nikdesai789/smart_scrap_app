<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Dealer Login Check
if (!isset($_SESSION['stakeholder_id'])) {
    header('Location: login.php');
    exit();
}



// Get the logged-in dealer's ID from session
$dealer_id = $_SESSION['stakeholder_id'];

// Fetch ONLY agents that belong to THIS dealer
$agent_query = "SELECT id, name FROM agents WHERE dealer_id = ? AND status = 'ACTIVE'";
$stmt_agents = $pdo->prepare($agent_query);
$stmt_agents->execute([$dealer_id]);
$my_agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);

// Fetch Active Agents for Dropdown
try {
    $stmt_agents = $pdo->prepare("SELECT id, name, phone FROM agents WHERE dealer_id = ? AND status = 'ACTIVE' ORDER BY name ASC");
    $stmt_agents->execute([$dealer_id]);
    $dealer_agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dealer_agents = [];
}

// Fetch Dealer Coordinates
try {
    $stmt_dealer = $pdo->prepare("SELECT latitude, longitude FROM stakeholders WHERE id = ?");
    $stmt_dealer->execute([$dealer_id]);
    $dealer_data = $stmt_dealer->fetch(PDO::FETCH_ASSOC);
    $d_lat = $dealer_data['latitude'] ?? null;
    $d_lng = $dealer_data['longitude'] ?? null;
} catch (PDOException $e) {
    $d_lat = $d_lng = null;
}

// Distance Calculation
function getDistance($lat1, $lon1, $lat2, $lon2) {
    if (empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) return "N/A";
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos(min(max($dist, -1.0), 1.0));
    return round(rad2deg($dist) * 60 * 1.1515 * 1.609344, 1);
}

$requests = [];
try {
    $sql = "SELECT r.*, u.name as vendor_name, u.phone as vendor_phone 
            FROM scrap_requests r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.dealer_id = ? AND r.status = 'PENDING' 
            ORDER BY r.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dealer_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Scrap Leads | ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        :root { --primary: #10b981; --secondary: #0ea5e9; --bg: #f8fafc; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: #1e293b; }
        .main-container { max-width: 950px; margin: 40px auto; padding: 0 15px; }
        .page-header { background: white; padding: 25px 30px; border-radius: 24px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; border-left: 8px solid var(--primary); }
        .lead-card { background: white; border-radius: 28px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); overflow: hidden; transition: 0.3s; }
        .lead-card:hover { transform: translateY(-5px); }
        .loc-badge { background: #e0f2fe; color: #0369a1; padding: 18px 25px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: flex-start; border-bottom: 1px solid #bae6fd; }
        .loc-badge i { margin-top: 4px; flex-shrink: 0; }
        .loc-badge span { line-height: 1.5; word-break: break-word; flex: 1; }
        .card-body-content { padding: 25px; }
        .scrap-img-box { width: 110px; height: 110px; flex-shrink: 0; }
        .scrap-thumb { width: 100%; height: 100%; object-fit: cover; border-radius: 20px; cursor: pointer; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .info-row { display: flex; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; }
        .info-item { flex: 1; min-width: 100px; }
        .info-label { font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: 800; display: block; margin-bottom: 2px; }
        .info-value { font-size: 14px; font-weight: 700; color: #1e293b; }
        .btn-action { border-radius: 16px; font-weight: 700; padding: 12px; border: none; width: 100%; transition: 0.3s; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .btn-accept { background: var(--primary); color: white; }
        .btn-accept:hover { background: #059669; box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); }
        .btn-call { background: #f1f5f9; color: #334155; margin-bottom: 10px; border: 1px solid #e2e8f0; }
        .btn-maps { background: #0ea5e9; color: white; font-size: 12px; margin-top: 10px; }
        .btn-maps:hover { background: #0284c7; color: white; transform: scale(1.02); }
        .scrap-badge { background: #f8fafc; color: #475569; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 600; margin-right: 6px; margin-top: 6px; display: inline-block; border: 1px solid #e2e8f0; }
        .close-preview { position: absolute; top: -15px; right: -15px; background: #ef4444; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 1050; cursor: pointer; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<?php
// ... [Existing PHP logic remains exactly the same] ...
?>

<body>

<div class="container main-container">
    <div class="page-header">
        <div>
            <h4 class="fw-bold mb-0">Pickup Requests</h4>
            <span class="text-muted small"><i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i> <?= count($requests) ?> pending leads</span>
        </div>
        <a href="dashboard.php" class="btn btn-dark rounded-pill px-4 btn-sm fw-bold">Dashboard</a>
    </div>

    <?php if(empty($requests)): ?>
        <div class="text-center py-5 bg-white rounded-5 shadow-sm border">
            <i class="fa-solid fa-calendar-day fa-3x text-light mb-3"></i>
            <h5 class="text-muted">No requests found at the moment.</h5>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach($requests as $req): 
                $km = getDistance($d_lat, $d_lng, $req['latitude'], $req['longitude']);
                
                // MODIFIED: Completely remove phone numbers and the "[Phone Hidden]" text from the address
                $clean_address = preg_replace('/[0-9]{10}/', '', $req['address']);
                // Remove the " | Phone: " label if it was hardcoded in your database string
                $clean_address = str_replace([' | Phone:', 'Phone:', '| Phone'], '', $clean_address);

                $applied_prices = [
                    'Plastic' => $req['applied_plastic_price'] ?? 0, 'Paper' => $req['applied_paper_price'] ?? 0,
                    'Iron' => $req['applied_iron_price'] ?? 0, 'Metal' => $req['applied_metal_price'] ?? 0,
                    'Copper' => $req['applied_copper_price'] ?? 0, 'E-waste' => $req['applied_ewaste_price'] ?? 0
                ];
            ?>
                <div class="col-12">
                    <div class="lead-card">
                        <div class="loc-badge">
                            <i class="fa-solid fa-map-location-dot me-3"></i>
                            <span><?= htmlspecialchars(trim($clean_address, " |")) ?></span>
                        </div>

                        <div class="card-body-content">
                            <div class="row g-4 align-items-start">
                                <div class="col-auto text-center">
                                    <div class="scrap-img-box">
                                        <?php $img = !empty($req['scrap_image']) ? "../uploads/".$req['scrap_image'] : "https://via.placeholder.com/100"; ?>
                                        <img src="<?= $img ?>" class="scrap-thumb" onclick="openPreview('<?= $img ?>', '<?= htmlspecialchars($req['scrap_type']) ?>')">
                                    </div>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($req['address']) ?>" target="_blank" class="btn btn-action btn-maps">
                                        <i class="fa-solid fa-location-arrow me-1"></i> Map View
                                    </a>
                                </div>

                                <div class="col">
                                    <div class="mb-2">
                                        <div class="h5 fw-bold text-dark mb-0"><?= htmlspecialchars($req['vendor_name']) ?></div>
                                        <div class="text-muted small"><i class="fa-solid fa-user-shield me-1"></i> Verified Seller</div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <?php 
                                        $items = explode(',', $req['scrap_type']);
                                        foreach($items as $item) {
                                            $item_name = trim($item);
                                            $p = $applied_prices[$item_name] ?? 0;
                                            echo '<span class="scrap-badge">'.htmlspecialchars($item_name).' (₹'.$p.')</span>';
                                        }
                                        ?>
                                    </div>

                                    <div class="info-row">
                                        <div class="info-item">
                                            <span class="info-label">Weight</span>
                                            <span class="info-value text-success"><?= $req['estimated_weight'] ?> KG</span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Distance</span>
                                            <span class="info-value text-primary"><?= ($km !== "N/A") ? "$km KM Away" : "N/A" ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Pickup Date</span>
                                            <span class="info-value"><?= date('d M, Y', strtotime($req['pickup_date'])) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3">
    <div class="d-flex flex-column h-100 justify-content-center">
        <a href="javascript:void(0);" 
           id="call-btn-<?= $req['id'] ?>"
           onclick="handleCall(<?= $req['id'] ?>, '<?= preg_replace('/[^0-9]/', '', $req['vendor_phone']) ?>')" 
           class="btn btn-action btn-call shadow-sm">
            <i class="fa-solid fa-phone me-2 text-success"></i>
            <span id="text-<?= $req['id'] ?>">Call Seller</span>
        </a>
        
        <button class="btn btn-action btn-accept" onclick="openAcceptModal(<?= $req['id'] ?>, '<?= $req['pickup_date'] ?>')">
            <i class="fa-solid fa-calendar-check me-2"></i>Accept
        </button>
        
        <a href="process_request.php?id=<?= $req['id'] ?>&status=REJECTED" class="text-danger small fw-bold text-center mt-3 text-decoration-none" onclick="return confirm('Reject this lead?')">
            Reject Lead
        </a>
    </div>
</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<div class="modal fade" id="acceptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="process_request.php" method="POST" class="modal-content shadow-lg" style="border-radius: 25px;">
            <input type="hidden" name="id" id="modal_req_id">
            <input type="hidden" name="status" value="ACCEPTED">
            <div class="modal-header border-0 bg-light p-4">
                <h5 class="modal-title fw-bold">Confirm Pickup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
               <div class="mb-3">
    <label class="form-label fw-bold">Assign Field Agent</label>
    <select name="agent_id" class="form-select" required>
        <option value="0">Self Pickup (Dealer)</option>
        
        <?php if(!empty($my_agents)): ?>
            <?php foreach($my_agents as $agent): ?>
                <option value="<?= $agent['id'] ?>">
                    <?= htmlspecialchars($agent['name']) ?>
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <option disabled>No agents found. Please add agents first.</option>
        <?php endif; ?>
        
    </select>
    <small class="text-muted">Only your registered agents are shown here.</small>
</div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="info-label">Set Date</label>
                        <input type="date" name="confirmed_date" id="modal_date" class="form-control rounded-3" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="info-label">Set Time</label>
                        <div class="d-flex gap-1">
                            <input type="number" name="h" class="form-control px-2" placeholder="HH" min="1" max="12" required>
                            <input type="number" name="m" class="form-control px-2" placeholder="MM" min="0" max="59" required>
                            <select name="p" class="form-select px-1" required><option value="AM">AM</option><option value="PM">PM</option></select>
                        </div>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="info-label">Message (Optional)</label>
                    <textarea name="dealer_note" class="form-control rounded-3" rows="2" placeholder="Instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill">Notify Seller & Agent</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body text-center p-0 position-relative">
                <div class="close-preview" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></div>
                <img src="" id="modalImg" class="img-fluid rounded-4 shadow-lg border border-3 border-white">
                <p id="modalLabel" class="text-white mt-3 fw-bold"></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function handleCall(id, phone) {
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    const btnText = document.getElementById('text-' + id);
    const btnLink = document.getElementById('call-btn-' + id);

    if (isMobile) {
        // If Mobile: Direct Call
        window.location.href = "tel:" + phone;
    } else {
        // If Dashboard/Desktop: Show the number on the button
        btnText.innerText = phone;
        btnLink.classList.remove('btn-call');
        btnLink.style.background = "#fff";
        btnLink.style.border = "1px solid #10b981";
        btnLink.style.color = "#10b981";
    }
}
function openAcceptModal(id, pickupDate) {
    document.getElementById('modal_req_id').value = id;
    const now = new Date();
    const today = new Date(now - (now.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
    const dateInput = document.getElementById('modal_date');
    dateInput.min = today;
    dateInput.value = pickupDate;
    new bootstrap.Modal(document.getElementById('acceptModal')).show();
}

function openPreview(imgSrc, title) {
    document.getElementById('modalImg').src = imgSrc;
    document.getElementById('modalLabel').innerText = title;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}
</script>
</body>
</html>