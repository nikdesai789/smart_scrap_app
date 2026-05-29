<?php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['stakeholder_id']) && !isset($_SESSION['temp_dealer_id'])) {
    header('Location: register.php');
    exit();
}
$dealer_id = $_SESSION['stakeholder_id'] ?? $_SESSION['temp_dealer_id'];

$stmt = $pdo->prepare("SELECT * FROM stakeholders WHERE id = ?");
$stmt->execute([$dealer_id]);
$dealer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dealer) {
    $dealer = [
        'business_name' => '', 'phone' => '', 'alternate_phone' => '', 'address' => '', 
        'city' => '', 'state' => '', 'pincode' => '', 'latitude' => '15.8497', 
        'longitude' => '74.4977', 'id_type' => 'GST', 'id_number' => '',
        'scrap_types' => '', 'working_days' => '', 'opening_time' => '09:00',
        'closing_time' => '19:00', 'service_radius' => '10', 
        'shop_image' => '',
        'plastic_price' => 0, 'paper_price' => 0, 'iron_price' => 0, 
        'metal_price' => 0, 'copper_price' => 0, 'ewaste_price' => 0
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_type = $_POST['id_type'];
    $id_number = strtoupper(str_replace(' ', '', $_POST['id_number']));
    $db_error = null;

    if (!$db_error) {
        $scrap_types = isset($_POST['scrap_types']) ? implode(',', $_POST['scrap_types']) : '';
        $working_days = isset($_POST['working_days']) ? implode(',', $_POST['working_days']) : '';

        $shop_image = $dealer['shop_image'];
        if (isset($_FILES['shop_image']) && $_FILES['shop_image']['error'] === 0) {
            $folder = '../uploads/shop_images/';
            if (!is_dir($folder)) mkdir($folder, 0777, true);
            $filename = "SHOP_" . time() . "." . pathinfo($_FILES['shop_image']['name'], PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES['shop_image']['tmp_name'], $folder . $filename)) {
                $shop_image = $filename;
            }
        }

        try {
            // FIX: Removed the extra comma before scrap_types[cite: 1]
            $update = $pdo->prepare("UPDATE stakeholders SET
                business_name = ?, shop_image = ?, alternate_phone = ?, address = ?, city = ?, 
                state = ?, pincode = ?, latitude = ?, longitude = ?, 
                opening_time = ?, closing_time = ?, working_days = ?, service_radius = ?, 
                scrap_types = ?, id_type = ?, id_number = ?, plastic_price = ?, 
                paper_price = ?, iron_price = ?, metal_price = ?, copper_price = ?, 
                ewaste_price = ?, profile_completion = 100, status = 'active', verified = 1 
                WHERE id = ?");

            $update->execute([
                $_POST['business_name'], $shop_image, $_POST['alternate_phone'], $_POST['address'], 
                $_POST['city'], $_POST['state'], $_POST['pincode'], $_POST['latitude'], 
                $_POST['longitude'], $_POST['opening_time'], $_POST['closing_time'], 
                $working_days, $_POST['service_radius'], 
                $scrap_types, $id_type, $id_number, $_POST['plastic_price'] ?? 0, 
                $_POST['paper_price'] ?? 0, $_POST['iron_price'] ?? 0, $_POST['metal_price'] ?? 0, 
                $_POST['copper_price'] ?? 0, $_POST['ewaste_price'] ?? 0, $dealer_id
            ]);

            header('Location: my_profile.php');
            exit();
        } catch (PDOException $e) {
            $db_error = "Database Error: " . $e->getMessage();
        }
    }
}

$selected_types = !empty($dealer['scrap_types']) ? explode(',', $dealer['scrap_types']) : [];
$selected_days = !empty($dealer['working_days']) ? explode(',', $dealer['working_days']) : ['Mon','Tue','Wed','Thu','Fri','Sat'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Business Profile | Smart Scrap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --primary: #10b981; --dark: #065f46; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .form-card { background: #fff; border-radius: 20px; padding: 30px; margin-bottom: 25px; border: 1px solid #eef2f6; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .section-title { color: var(--dark); font-weight: 700; border-bottom: 2px solid #f0fdf4; padding-bottom: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        #map { height: 300px; border-radius: 15px; border: 1px solid #ddd; }
        .btn-submit { background: var(--primary); color: #fff; padding: 16px; border-radius: 50px; font-weight: 700; width: 100%; border: none; transition: 0.3s; cursor: pointer; }
        .day-badge { cursor: pointer; padding: 10px 18px; border: 1px solid #e2e8f0; border-radius: 50px; display: inline-block; margin: 4px; background: #fff; font-weight: 600; font-size: 13px; }
        .day-check:checked + .day-badge { background: var(--primary); color: #fff; border-color: var(--primary); }
        .day-check { display: none; }
        .img-preview-box { width: 180px; height: 120px; border: 2px dashed var(--primary); border-radius: 15px; margin: 0 auto 15px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f0fdf4; }
        .img-preview-box img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 950px;">
    <div class="text-center mb-4">
        <h2 class="fw-bold" style="color: var(--dark);">Business Profile Setup</h2>
        <p class="text-muted">Fill in your shop details to get verified and start business.</p>
    </div>

    <?php if(isset($db_error)): ?> <div class="alert alert-danger shadow-sm"><?= $db_error ?></div> <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        
        <div class="form-card">
            <h5 class="section-title"><i class="fa-solid fa-store text-success"></i> Shop Identity</h5>
            <div class="text-center mb-4">
                <div class="img-preview-box">
                    <?php $img = !empty($dealer['shop_image']) ? '../uploads/shop_images/'.$dealer['shop_image'] : 'https://via.placeholder.com/180x120?text=Shop+Image'; ?>
                    <img id="preview" src="<?= $img ?>">
                </div>
                <label class="btn btn-outline-success btn-sm rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-camera me-1"></i> Change Shop Photo 
                    <input type="file" name="shop_image" class="d-none" onchange="previewImg(this)">
                </label>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="small fw-bold mb-1">Official Business Name</label>
                    <input type="text" name="business_name" class="form-control rounded-pill px-3" value="<?= htmlspecialchars($dealer['business_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold mb-1">Primary Phone (Registered)</label>
                    <input type="text" class="form-control rounded-pill px-3 bg-light" value="<?= htmlspecialchars($dealer['phone'] ?? '') ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold mb-1">Secondary Phone Number</label>
                    <input type="text" name="alternate_phone" class="form-control rounded-pill px-3" value="<?= htmlspecialchars($dealer['alternate_phone'] ?? '') ?>" required maxlength="10">
                </div>
            </div>
        </div>

        <div class="form-card">
            <h5 class="section-title"><i class="fa-solid fa-location-dot text-success"></i> Store Location</h5>
            <div id="map" class="mb-3 shadow-sm"></div>
            <label class="small fw-bold mb-1">Full Shop Address</label>
            <textarea name="address" id="address" class="form-control mb-3" rows="2" required><?= htmlspecialchars($dealer['address']) ?></textarea>
            <div class="row g-2 mb-3">
                <div class="col-md-4"><input type="text" name="city" id="city" value="<?= htmlspecialchars($dealer['city']) ?>" placeholder="City" class="form-control rounded-pill"></div>
                <div class="col-md-4"><input type="text" name="state" id="state" value="<?= htmlspecialchars($dealer['state']) ?>" placeholder="State" class="form-control rounded-pill"></div>
                <div class="col-md-4"><input type="text" name="pincode" id="pincode" value="<?= htmlspecialchars($dealer['pincode']) ?>" placeholder="Pincode" class="form-control rounded-pill"></div>
            </div>
            <input type="hidden" name="latitude" id="lat" value="<?= $dealer['latitude'] ?>">
            <input type="hidden" name="longitude" id="lng" value="<?= $dealer['longitude'] ?>">
            
            <div class="p-3 bg-light rounded-4 mb-3">
                <label class="fw-bold small d-flex justify-content-between">
                    <span>Service Radius Coverage</span>
                    <span class="text-success"><span id="radiusVal"><?= $dealer['service_radius'] ?></span> KM</span>
                </label>
                <input type="range" name="service_radius" class="form-range" min="1" max="50" value="<?= $dealer['service_radius'] ?>" oninput="document.getElementById('radiusVal').innerText = this.value">
            </div>
        </div>

        <div class="form-card">
            <h5 class="section-title"><i class="fa-solid fa-clock text-success"></i> Working Hours & Days</h5>
            <div class="mb-4">
                <label class="small fw-bold d-block mb-2">Available Days</label>
                <div class="d-flex flex-wrap">
                    <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): 
                        $s = in_array($day, $selected_days) ? 'checked' : ''; ?>
                    <label>
                        <input type="checkbox" name="working_days[]" value="<?= $day ?>" class="day-check" <?= $s ?>>
                        <span class="day-badge shadow-sm"><?= $day ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <label class="small fw-bold">Shop Opens At</label>
                    <select name="opening_time" class="form-select rounded-pill">
    <?php for($i=6; $i<22; $i++){ 
        $t = sprintf('%02d:00', $i); 
        // Database se aane wale time ka sirf HH:MM part check karein[cite: 3]
        $db_time = substr($dealer['opening_time'], 0, 5); 
        $sel = ($db_time == $t) ? 'selected' : ''; 
        echo "<option value='$t' $sel>".date("g:i A", strtotime($t))."</option>"; 
    } ?>
</select>
                </div>
                <div class="col-6">
                    <label class="small fw-bold">Shop Closes At</label>
                    <select name="closing_time" class="form-select rounded-pill">
    <?php for($i=6; $i<23; $i++){ 
        $t = sprintf('%02d:00', $i); 
     // Yahan bhi substr() ka use karein taaki format match ho[cite: 3]
        $db_time = substr($dealer['closing_time'], 0, 5); 
        $sel = ($db_time == $t) ? 'selected' : ''; 
        echo "<option value='$t' $sel>".date("g:i A", strtotime($t))."</option>"; 
    } ?>
</select>
                </div>
            </div>
        </div>

        <div class="form-card">
            <h5 class="section-title"><i class="fa-solid fa-money-bill-trend-up text-success"></i> Buying Rates (₹/KG)</h5>
            <div class="row g-3">
                <?php 
                $scraps = ['Plastic'=>'plastic_price', 'Paper'=>'paper_price', 'Iron'=>'iron_price', 'Metal'=>'metal_price', 'Copper'=>'copper_price', 'E-Waste'=>'ewaste_price'];
                foreach($scraps as $label => $field_name): 
                    $is_checked = in_array($label, $selected_types) ? 'checked' : ''; ?>
                <div class="col-md-4">
                    <div class="p-3 border rounded-4 bg-light text-center">
                        <div class="form-check form-switch d-inline-block mb-1">
                            <input class="form-check-input" type="checkbox" name="scrap_types[]" value="<?= $label ?>" id="c_<?= $field_name ?>" <?= $is_checked ?> onchange="document.getElementById('i_<?= $field_name ?>').disabled = !this.checked">
                            <label class="form-check-label fw-bold small" for="c_<?= $field_name ?>"><?= $label ?></label>
                        </div>
                        <input type="number" step="0.5" name="<?= $field_name ?>" id="i_<?= $field_name ?>" class="form-control border-0 bg-transparent text-center fw-bold text-success" value="<?= $dealer[$field_name] ?>" <?= $is_checked ? '' : 'disabled' ?> placeholder="0.00">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-card">
            <h5 class="section-title"><i class="fa-solid fa-user-shield text-success"></i> Official Verification</h5>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="small fw-bold mb-1">ID Document Type</label>
                    <select name="id_type" id="id_type_select" class="form-select rounded-pill">
                        <option value="GST" <?= ($dealer['id_type']=='GST')?'selected':'' ?>>GSTIN Number</option>
                        <option value="PAN" <?= ($dealer['id_type']=='PAN')?'selected':'' ?>>PAN Card</option>
                        <option value="Aadhar" <?= ($dealer['id_type']=='Aadhar')?'selected':'' ?>>Aadhar Card (12 Digits)</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="small fw-bold mb-1">Identification Number</label>
                    <input type="text" name="id_number" id="id_number_input" class="form-control rounded-pill px-3" value="<?= htmlspecialchars($dealer['id_number']) ?>" required placeholder="Enter ID Number">
                    <small id="id_error" class="text-danger mt-1 d-block" style="font-size: 11px;"></small>
                </div>
            </div>
        </div>

        <div class="action-btns text-center">
            <button type="submit" class="btn-submit mb-3 shadow">SAVE & ACTIVATE PROFILE</button>
            <a href="my_profile.php" class="btn btn-link text-muted"><i class="fa-solid fa-arrow-left me-2"></i>Back to Profile</a>
        </div>
    </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ID Validation Logic
const idSelect = document.getElementById('id_type_select');
const idInput = document.getElementById('id_number_input');
const idError = document.getElementById('id_error');

function validateID() {
    const type = idSelect.value;
    const val = idInput.value.toUpperCase().replace(/\s/g, '');
    idInput.value = val;
    let isValid = true, msg = "";

    if(type === "GST") {
        if(!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(val)) { isValid = false; msg = "Invalid GST format"; }
    } else if(type === "Aadhar") {
        if(!/^\d{12}$/.test(val)) { isValid = false; msg = "Enter exactly 12 digits"; }
    } else if(type === "PAN") {
        if(!/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(val)) { isValid = false; msg = "Invalid PAN format"; }
    }
    
    idError.innerText = msg;
    idInput.setCustomValidity(isValid ? "" : msg);
}
idSelect.addEventListener('change', validateID);
idInput.addEventListener('input', validateID);

// Map Engine
let startLat = document.getElementById('lat').value || 15.8497;
let startLng = document.getElementById('lng').value || 74.4977;
const map = L.map('map').setView([startLat, startLng], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
let marker = L.marker([startLat, startLng], {draggable: true}).addTo(map);

function updateLocation(lat, lng) {
    document.getElementById('lat').value = lat; document.getElementById('lng').value = lng;
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
    .then(r => r.json()).then(data => {
        if(data.address) {
            document.getElementById('address').value = data.display_name;
            document.getElementById('city').value = data.address.city || data.address.town || "";
            document.getElementById('state').value = data.address.state || "";
            document.getElementById('pincode').value = data.address.postcode || "";
        }
    });
}
marker.on('dragend', () => updateLocation(marker.getLatLng().lat, marker.getLatLng().lng));
map.on('click', (e) => { marker.setLatLng(e.latlng); updateLocation(e.latlng.lat, e.latlng.lng); });

function previewImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = e => document.getElementById('preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// --- ID Validation Logic ---
const idSelect = document.getElementById('id_type_select');
const idInput = document.getElementById('id_number_input');
const idError = document.getElementById('id_error');

function validateID() {
    const type = idSelect.value;
    const val = idInput.value.toUpperCase().replace(/\s/g, '');
    idInput.value = val;
    let isValid = true, msg = "";

    if(type === "GST") {
        if(!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(val)) { isValid = false; msg = "Invalid GST format"; }
    } else if(type === "Aadhar") {
        if(!/^\d{12}$/.test(val)) { isValid = false; msg = "Enter exactly 12 digits"; }
    } else if(type === "PAN") {
        if(!/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(val)) { isValid = false; msg = "Invalid PAN format"; }
    }
    
    idError.innerText = msg;
    idInput.setCustomValidity(isValid ? "" : msg);
}
idSelect.addEventListener('change', validateID);
idInput.addEventListener('input', validateID);

// --- Map & Geolocation Engine ---
let startLat = document.getElementById('lat').value || 15.8497;
let startLng = document.getElementById('lng').value || 74.4977;

const map = L.map('map').setView([startLat, startLng], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
let marker = L.marker([startLat, startLng], {draggable: true}).addTo(map);

// Function to update hidden inputs and fetch address details
function updateLocation(lat, lng) {
    document.getElementById('lat').value = lat; 
    document.getElementById('lng').value = lng;
    
    // UI feedback ki address load ho raha hai
    document.getElementById('address').placeholder = "Fetching address...";

    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
    .then(r => r.json()).then(data => {
        if(data.address) {
            document.getElementById('address').value = data.display_name;
            document.getElementById('city').value = data.address.city || data.address.town || data.address.village || "";
            document.getElementById('state').value = data.address.state || "";
            document.getElementById('pincode').value = data.address.postcode || "";
        }
    }).catch(err => console.log("Error fetching address:", err));
}

// Marker drag and Map click events
marker.on('dragend', () => updateLocation(marker.getLatLng().lat, marker.getLatLng().lng));
map.on('click', (e) => { 
    marker.setLatLng(e.latlng); 
    updateLocation(e.latlng.lat, e.latlng.lng); 
});

// --- Automatic Current Location Logic ---
window.onload = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Map move karein aur marker place karein
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                
                // Address details fill karein
                updateLocation(lat, lng);
            },
            (error) => {
                console.warn("Location access denied or not found.");
                // Agar user mana kar de, toh default lat/lng (Belagavi) par rehne de
            },
            { enableHighAccuracy: true }
        );
    }
};

// Image Preview
function previewImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = e => document.getElementById('preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>