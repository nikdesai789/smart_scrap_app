<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../find_dealers.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$display_name = !empty($user['username']) ? $user['username'] : (!empty($user['name']) ? $user['name'] : 'Vendor');
$my_items = ['Paper', 'Plastic', 'Metal', 'E-Waste', 'Copper', 'Iron'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { --primary: #10b981; --dark: #1e293b; --meesho: #9f2089; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fa; margin: 0; display: flex; }
        .sidebar { width: 250px; background: var(--dark); color: white; height: 100vh; position: fixed; padding: 20px; display: flex; flex-direction: column; z-index: 1000; }
        .main { margin-left: 250px; width: calc(100% - 250px); padding: 30px; min-height: 100vh; }
        .glass-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: none; }
        #map { height: 350px; width: 100%; border-radius: 12px; border: 1px solid #ddd; }
        .scrap-checkbox { display: none; }
        .scrap-label { display: flex; align-items: center; padding: 12px 15px; background: #fff; border: 2px solid #f1f5f9; border-radius: 10px; cursor: pointer; margin-bottom: 8px; font-weight: 600; transition: 0.2s; height: 100%; }
        .scrap-checkbox:checked + .scrap-label { background: #ecfdf5; border-color: var(--primary); color: #065f46; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.1); }
        .address-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; margin-top: 15px; }
        .btn-search { background: var(--meesho); color: white; border: none; padding: 14px; border-radius: 10px; font-weight: 700; width: 100%; margin-top: 15px; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
        .btn-search:hover { background: #821a70; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(159, 32, 137, 0.3); }
        .nav-link { color: rgba(255,255,255,0.8); transition: 0.2s; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: block; padding: 10px; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { background: var(--primary); color: white; }
        .logout-container { margin-top: auto; }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-4"><h4 class="text-success fw-bold"><i class="fas fa-recycle me-2"></i>SmartScrap</h4><hr class="bg-light"></div>
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="my_requests.php" class="nav-link"><i class="fas fa-clock-rotate-left me-2"></i> My Pickups</a>
        <a href="requests_send.php" class="nav-link"><i class="fas fa-paper-plane me-2"></i> Sent History</a>
        <a href="profile.php" class="nav-link"><i class="fas fa-user me-2"></i> My Profile</a>
    </nav>
    <div class="logout-container"><hr class="bg-light"><a href="logout.php" class="nav-link text-danger fw-bold"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></div>
</div>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Welcome, <span style="color:var(--meesho)"><?php echo htmlspecialchars($display_name); ?></span>!</h3>
        <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">Seller Mode</span>
    </div>

    <div class="glass-card mb-4">
        <h5 class="fw-bold mb-4" style="color: var(--dark)"><i class="fas fa-calendar-check me-2"></i>Schedule a New Pickup</h5>
        
        <form action="find_dealers.php" method="POST" id="searchForm">
            <div class="row">
                <div class="col-md-5 border-end">
                    <div class="mb-4">
                        <p class="fw-bold text-muted small mb-3 text-uppercase"><i class="fas fa-list me-1"></i> 1. Select Scrap Types <span class="text-danger">*</span></p>
                        <div class="row g-2">
                            <?php foreach($my_items as $i => $item): ?>
                            <div class="col-6">
                                <input type="checkbox" name="types[]" value="<?php echo $item; ?>" id="it<?php echo $i; ?>" class="scrap-checkbox">
                                <label for="it<?php echo $i; ?>" class="scrap-label mb-0">
                                    <i class="fas fa-box me-2 text-success small"></i> <?php echo $item; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                        <p class="fw-bold text-muted small mb-3 text-uppercase"><i class="fas fa-weight-hanging me-1"></i> 2. Estimated Weight <span class="text-danger">*</span></p>
                        <div class="input-group">
                            <input type="number" name="weight" class="form-control border-success" placeholder="Enter total weight" min="1" required>
                            <span class="input-group-text bg-success text-white">kg</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-7 ps-md-4">
                    <p class="fw-bold text-muted small mb-3 text-uppercase"><i class="fas fa-map-marker-alt me-1"></i> 3. Pin Your Pickup Location</p>
                <div class="input-group mb-3 shadow-sm">
    <input type="text" id="addr_search" class="form-control" placeholder="Search for your street, area, or landmark...">
    <button class="btn btn-outline-secondary" type="button" onclick="getLocation()" title="Get Current Location">
        <i class="fas fa-crosshairs"></i>
    </button>
    <button class="btn btn-primary" type="button" onclick="searchAddress()"><i class="fas fa-search me-1"></i> Locate</button>
</div>
                    <div id="map" class="shadow-sm"></div>

                    <div class="address-box">
                        <div class="d-flex">
                            <i class="fas fa-location-arrow text-danger me-2 mt-1"></i>
                            <div>
                                <span id="addr_text" class="small fw-bold">Detecting your location...</span>
                                <input type="hidden" name="address" id="addr_input">
                                <input type="hidden" name="lat" id="lat_input" value="15.8497">
                                <input type="hidden" name="lng" id="lng_input" value="74.4977">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-search shadow">Find Dealers Near Me <i class="fas fa-chevron-right ms-2"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var map, marker;
    const defaultLat = 15.8497; 
    const defaultLng = 74.4977;

    function initMap(lat, lng) {
        if(!map){
            map = L.map('map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            marker = L.marker([lat, lng], {draggable: true}).addTo(map);
            
            marker.on('dragend', () => {
                let position = marker.getLatLng();
                updateAddress(position.lat, position.lng);
            });

            map.on('click', (e) => { 
                marker.setLatLng(e.latlng); 
                updateAddress(e.latlng.lat, e.latlng.lng); 
            });
        } else {
            map.setView([lat, lng], 15);
            marker.setLatLng([lat, lng]);
        }
        updateAddress(lat, lng);
    }

    // Function to manually trigger geolocation without visual loading popups
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    initMap(position.coords.latitude, position.coords.longitude);
                },
                (error) => {
                    handleLocationError(error);
                },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        } else {
            Swal.fire('Error', 'Geolocation is not supported by this browser.', 'error');
            initMap(defaultLat, defaultLng);
        }
    }

    function handleLocationError(error) {
        let msg = "Could not get your location.";
        if(error.code === 1) msg = "Location access denied. Please enable permissions in your browser settings.";
        else if(error.code === 3) msg = "Location request timed out. Try again or search manually.";
        
        Swal.fire('Location Error', msg, 'warning');
        initMap(defaultLat, defaultLng); // Fallback
    }

    function searchAddress() {
        let query = document.getElementById('addr_search').value;
        if(query.length < 3) {
            Swal.fire('Error', 'Please enter a valid address to search.', 'warning');
            return;
        }

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if(data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    initMap(lat, lon);
                    document.getElementById('addr_text').innerText = data[0].display_name;
                    document.getElementById('addr_input').value = data[0].display_name;
                } else {
                    Swal.fire('Not Found', 'Could not find that location.', 'info');
                }
            });
    }

    function updateAddress(lat, lng) {
        document.getElementById('lat_input').value = lat;
        document.getElementById('lng_input').value = lng;
        document.getElementById('addr_text').innerText = "Updating address...";

        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
            .then(res => res.json()).then(data => {
                let addr = data.display_name || "Custom Pin Location";
                document.getElementById('addr_text').innerText = addr;
                document.getElementById('addr_input').value = addr;
            }).catch(() => {
                document.getElementById('addr_text').innerText = "Location pinned";
            });
    }

    // Auto-detect on load
    window.onload = () => { 
        getLocation();
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>