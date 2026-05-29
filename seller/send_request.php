<?php
session_start();
require_once '../config/database.php';

date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
}

$dealer_id = $_POST['dealer_id'] ?? $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

// Fetch weight and coordinates from session
$v_weight = $_SESSION['v_weight'] ?? '0'; 
$v_lat = $_SESSION['u_lat'] ?? null; // Seller's pinned latitude
$v_lng = $_SESSION['u_lng'] ?? null; // Seller's pinned longitude

if (!$dealer_id) { 
    header("Location: find_dealers.php"); 
    exit(); 
}

$stmt = $pdo->prepare("SELECT * FROM stakeholders WHERE id = ?");
$stmt->execute([$dealer_id]);
$dealer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dealer) { 
    die("Dealer details not found!"); 
}

$current_time = date("H:i:s");
$closing_time = $dealer['closing_time'] ?? '18:00:00'; 
$is_closed = ($current_time > $closing_time);

// Database se working days nikalna (e.g., "Mon,Tue,Wed")[cite: 3]
$allowed_days = explode(',', $dealer['working_days'] ?: 'Mon,Tue,Wed,Thu,Fri,Sat');

// Shuruat aaj ki date se ya kal ki date se (agar dukan band ho chuki hai)
$current_time = date("H:i:s");
$closing_time = $dealer['closing_time'] ?? '18:00:00';
$check_date = ($current_time > $closing_time) ? strtotime("+1 day") : strtotime("today");

// Loop chalao jab tak koi valid working day na mil jaye 🔄
while (!in_array(date('D', $check_date), $allowed_days)) {
    $check_date = strtotime("+1 day", $check_date);
}

$min_date = date("Y-m-d", $check_date);



// --- REQUEST LIMIT CHECK (PER DEALER BASIS) ---
$can_send = true;
$wait_hours = 0;

// Ab hum dealer_id ko bhi check kar rahe hain taki sirf ek dealer block ho
$check_per_dealer = $pdo->prepare("SELECT created_at FROM scrap_requests 
                                   WHERE user_id = ? AND dealer_id = ? AND status = 'PENDING' 
                                   AND created_at > (NOW() - INTERVAL 1 DAY)
                                   ORDER BY created_at DESC LIMIT 1");
$check_per_dealer->execute([$user_id, $dealer_id]);
$existing_request = $check_per_dealer->fetch(PDO::FETCH_ASSOC);

if ($existing_request) {
    $request_time = strtotime($existing_request['created_at']);
    $unlock_time = $request_time + (24 * 3600);
    if (time() < $unlock_time) {
        $can_send = false;
        $wait_hours = round(($unlock_time - time()) / 3600, 1);
    }
}

// --- FORM SUBMISSION ---
// --- FORM SUBMISSION ---
if (isset($_POST['submit_request']) && $can_send) {
    // REMOVED 'phone_number' from the empty check
    if (empty($_FILES['scrap_photo']['name']) || empty($_POST['scrap_items'])) {
        die("<script>alert('Please fill all required fields!'); window.history.back();</script>");
    }

    $pickup_date = $_POST['pickup_date'];
    $weight = $_POST['weight']; 
    $map_address = $_POST['map_location']; 
    $house_info = $_POST['house_info'];
    
    // We get coordinates from hidden fields or session
    $final_lat = $_POST['s_lat'] ?? $v_lat;
    $final_lng = $_POST['s_lng'] ?? $v_lng;
    
    // Updated Address string without needing a separate phone field from POST
    $full_address = "Area: " . $map_address . " | House: " . $house_info;
    $items = implode(', ', $_POST['scrap_items']);

    // Photo upload logic
    $photo_name = time() . "_" . $_FILES['scrap_photo']['name'];
    move_uploaded_file($_FILES['scrap_photo']['tmp_name'], "../uploads/" . $photo_name);

    // STEP 1: Fetch current prices to lock them
    $stmt_p = $pdo->prepare("SELECT plastic_price, paper_price, iron_price, metal_price, copper_price, ewaste_price FROM stakeholders WHERE id = ?");
    $stmt_p->execute([$dealer_id]);
    $cp = $stmt_p->fetch(PDO::FETCH_ASSOC);

    // STEP 2: INSERT QUERY with locked prices
    $sql = "INSERT INTO scrap_requests (
                user_id, dealer_id, scrap_type, estimated_weight, scrap_image, 
                pickup_date, address, latitude, longitude, status, created_at,
                applied_plastic_price, applied_paper_price, applied_iron_price, 
                applied_metal_price, applied_copper_price, applied_ewaste_price
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW(), ?, ?, ?, ?, ?, ?)";
    
    $insert_stmt = $pdo->prepare($sql);
    
    // STEP 3: Execute
    $execution_result = $insert_stmt->execute([
        $user_id, $dealer_id, $items, $weight, $photo_name, 
        $pickup_date, $full_address, $final_lat, $final_lng,
        $cp['plastic_price'] ?? 0, 
        $cp['paper_price'] ?? 0, 
        $cp['iron_price'] ?? 0, 
        $cp['metal_price'] ?? 0,
        $cp['copper_price'] ?? 0,
        $cp['ewaste_price'] ?? 0
    ]);

    if ($execution_result) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <body style='font-family: sans-serif;'>
        <script>
            setTimeout(function() {
                Swal.fire({
                    title: 'Request Sent!',
                    text: 'Prices have been locked for this request.',
                    icon: 'success',
                    confirmButtonColor: '#9f2089'
                }).then(() => {
                    window.location.href = 'my_requests.php';
                });
            }, 100);
        </script>
        </body>";
        exit();
    }
}

$allowed_types = !empty($dealer['scrap_types']) ? explode(',', $dealer['scrap_types']) : [];

// Ye map har item ki price ko uske naam se jod dega
$price_map = [
    'Plastic' => $dealer['plastic_price'],
    'Paper'   => $dealer['paper_price'],
    'Iron'    => $dealer['iron_price'],
    'Metal'   => $dealer['metal_price'],
    'Copper'  => $dealer['copper_price'],
    'E-waste' => $dealer['ewaste_price']
];
?>
<!-- HTML Part (Keep same as original, just add hidden lat/lng inside form) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... (Original Styles) ... -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Pickup Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .header-nav { background: white; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .back-btn { color: #1e293b; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .back-btn:hover { color: #9f2089; }
        .card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 30px; }
        .section-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 10px; display: block; }
        .section-title i { color: #9f2089; margin-right: 8px; }
        .btn-submit { background: #9f2089; color: white; padding: 12px; border-radius: 50px; width: 100%; font-weight: 700; border: none; }
        .scrap-chip { display: none; }
        .scrap-label { display: block; padding: 10px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; text-align: center; background: #fff; font-size: 0.85rem; font-weight: 600; }
        .scrap-chip:checked + .scrap-label { border-color: #9f2089; background: #fdf2fb; color: #9f2089; }
        .location-badge { background: #f0f9ff; border: 1px solid #bae6fd; padding: 12px; border-radius: 12px; color: #0369a1; font-size: 0.9rem; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="header-nav">
    <div class="container">
        <a href="find_dealers.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dealers</a>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="text-center mb-4">
                    <h2 class="fw-bold" style="color: #9f2089;"><?= htmlspecialchars($dealer['business_name']) ?></h2>
                    <p class="text-muted small"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($dealer['city']) ?></p>
                </div>

                <?php if (!$can_send): ?>
                    <!-- ... (Limit Reached UI) ... -->
                    <div class="alert alert-warning text-center p-4">
                        <i class="fa-solid fa-lock fa-3x mb-3" style="color: #9f2089;"></i>
                        <h5 class="fw-bold">Limit Reached</h5>
                        <p>You have a pending request. Please wait <b>24 hours</b> before sending another.</p>
                        <a href="my_requests.php" class="btn btn-dark rounded-pill">View My Requests</a>
                    </div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" id="pickupForm">
                        <input type="hidden" name="dealer_id" value="<?= $dealer_id ?>">
                        
                        <!-- HIDDEN LOCATION FIELDS[cite: 3] -->
                        <input type="hidden" name="s_lat" value="<?= $v_lat ?>">
                        <input type="hidden" name="s_lng" value="<?= $v_lng ?>">

                        <!-- LOCATION & CONTACT SECTION -->
                        <div class="mb-4">
                            <label class="section-title"><i class="fa-solid fa-map-marked-alt"></i> Pickup Location & Contact</label>
                            <div class="location-badge">
                                <i class="fa-solid fa-check-circle me-2"></i>
                                <strong>Selected Area:</strong> <?= $_SESSION['u_addr'] ?? 'Location not found' ?>
                            </div>
                            <input type="hidden" name="map_location" value="<?= htmlspecialchars($_SESSION['u_addr'] ?? '') ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="small fw-bold text-muted mb-1">Additional Address Details</label>
                                    <textarea name="house_info" class="form-control" rows="2" placeholder="House No, Landmark..." required></textarea>
                                </div>
                                
                            </div>
                        </div>

                        <!-- ... (Rest of original Scrap Type and Photo UI) ... -->
                        <!-- Scrap Type loop wala hissa -->
<div class="row g-2">
    <?php foreach($allowed_types as $type): 
        $t_clean = trim($type);
        // Map se price nikalna, agar nahi hai to 0 dikhana
        $price = $price_map[$t_clean] ?? 0; 
    ?>
        <div class="col-4">
            <input type="checkbox" name="scrap_items[]" value="<?= $t_clean ?>" id="<?= $t_clean ?>" class="scrap-chip">
            <label for="<?= $t_clean ?>" class="scrap-label">
                <?= $t_clean ?> <br>
                <small class="text-muted">(₹<?= $price ?>/kg)</small>
            </label>
        </div>
    <?php endforeach; ?>
</div>

                        <div class="mb-4">
                            <label class="section-title"><i class="fa-solid fa-weight-scale"></i> Approx Weight</label>
                            <div class="location-badge" style="background: #fdf2fb; border: 1px solid #f5d0ed; color: #9f2089;">
                                <i class="fa-solid fa-scale-balanced me-2"></i>
                                <strong>Your Estimate:</strong> <?= htmlspecialchars($v_weight) ?> kg
                            </div>
                            <input type="hidden" name="weight" value="<?= htmlspecialchars($v_weight) ?>">
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="section-title"><i class="fa-solid fa-calendar"></i> Date</label>
                                <input type="date" name="pickup_date" class="form-control rounded-pill" min="<?= $min_date ?>" value="<?= $min_date ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="section-title"><i class="fa-solid fa-image"></i> Photo</label>
                                <input type="file" name="scrap_photo" class="form-control" accept="image/*" required>
                            </div>
                        </div>

                        <button type="submit" name="submit_request" class="btn-submit mt-3">
                            Confirm and Send Request <i class="fa-solid fa-circle-check ms-1"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- ... Scripts ... -->
</body>
</html>