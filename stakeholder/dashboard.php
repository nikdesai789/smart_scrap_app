<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotStakeholder();

$stakeholder_id = $_SESSION['stakeholder_id'];

// 1. Dealer Details Fetch
$stmt = $pdo->prepare("SELECT * FROM stakeholders WHERE id = ?");
$stmt->execute([$stakeholder_id]);
$dealer = $stmt->fetch();

// --- DATA CALCULATIONS (UPDATED) ---
// Total Revenue
$r_stmt = $pdo->prepare("SELECT SUM(received_amount) FROM scrap_requests WHERE dealer_id = ? AND status = 'COMPLETED'");
$r_stmt->execute([$stakeholder_id]);
$total_revenue = $r_stmt->fetchColumn() ?? 0;

// Stat Counts 
$t_stmt = $pdo->prepare("SELECT COUNT(*) FROM scrap_requests WHERE dealer_id = ? AND status != 'CANCELLED'");
$t_stmt->execute([$stakeholder_id]);
$total_requests = $t_stmt->fetchColumn() ?? 0;

$p_stmt = $pdo->prepare("SELECT COUNT(*) FROM scrap_requests WHERE dealer_id = ? AND status = 'PENDING'");
$p_stmt->execute([$stakeholder_id]);
$pending = $p_stmt->fetchColumn() ?? 0;

$a_stmt = $pdo->prepare("SELECT COUNT(*) FROM scrap_requests WHERE dealer_id = ? AND status = 'ACCEPTED'");
$a_stmt->execute([$stakeholder_id]);
$accepted = $a_stmt->fetchColumn() ?? 0;

$c_stmt = $pdo->prepare("SELECT COUNT(*) FROM scrap_requests WHERE dealer_id = ? AND status = 'COMPLETED'");
$c_stmt->execute([$stakeholder_id]);
$completed_count = $c_stmt->fetchColumn() ?? 0;

$can_stmt = $pdo->prepare("SELECT COUNT(*) FROM scrap_requests WHERE dealer_id = ? AND TRIM(UPPER(status)) = 'CANCELLED'");
$can_stmt->execute([$stakeholder_id]);
$cancelled_count = $can_stmt->fetchColumn() ?? 0;

// >>> ADDED: AGENT COUNT LOGIC <<<
$ag_stmt = $pdo->prepare("SELECT COUNT(*) FROM agents WHERE dealer_id = ?");
$ag_stmt->execute([$stakeholder_id]);
$total_agents = $ag_stmt->fetchColumn() ?? 0;

// Chart Data (Last 7 Days)
$chart_stmt = $pdo->prepare("SELECT DATE(created_at) as date, SUM(received_amount) as amount 
                            FROM scrap_requests 
                            WHERE dealer_id = ? AND status = 'COMPLETED' 
                            GROUP BY DATE(created_at) ORDER BY date ASC LIMIT 7");
$chart_stmt->execute([$stakeholder_id]);
$chart_rows = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
$labels = []; $values = [];
foreach($chart_rows as $row) {
    $labels[] = date('d M', strtotime($row['date']));
    $values[] = $row['amount'];
}

// Profile completion logic
$required_fields = ['business_name', 'address', 'city', 'state', 'pincode', 'latitude', 'longitude', 'scrap_types', 'opening_time', 'closing_time'];
$completed_fields = 0;
foreach($required_fields as $field) { 
    if(!empty($dealer[$field])) $completed_fields++; 
}
$completion = round(($completed_fields / count($required_fields)) * 100);

$up_sql = "UPDATE stakeholders SET profile_completion = ? WHERE id = ?";
$pdo->prepare($up_sql)->execute([$completion, $stakeholder_id]);

$profile_complete = ($completion == 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dealer Dashboard - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; overflow-x: hidden; }
        .sidebar { position: fixed; left: 0; top: 0; height: 100%; width: 260px; background: #0f172a; color: white; z-index: 1000; transition: all 0.3s; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h3 { font-size: 1.4rem; font-weight: 700; color: #10b981; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu .menu-item { padding: 12px 25px; display: flex; align-items: center; gap: 15px; color: #94a3b8; text-decoration: none; transition: all 0.3s; font-size: 0.95rem; }
        .sidebar-menu .menu-item:hover, .sidebar-menu .menu-item.active { background: rgba(16, 185, 129, 0.1); color: #10b981; border-right: 4px solid #10b981; }
        .main-content { margin-left: 260px; min-height: 100vh; transition: all 0.3s; }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; position: relative; overflow: hidden; height: 100%; }
        .stat-number { font-size: 1.5rem; font-weight: 700; color: #1e293b; display: block; }
        .stat-label { color: #64748b; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .progress-container { height: 8px; background: #f1f5f9; border-radius: 10px; margin-top: 10px; }
        .progress-fill { height: 100%; background: #10b981; border-radius: 10px; width: <?php echo $completion; ?>%; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar-header h3 span, .links_name { display: none; }
            .main-content { margin-left: 80px; }
            .sidebar-menu .menu-item { justify-content: center; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-recycle"></i><span> SmartScrap</span></h3>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item active"><i class="fas fa-table-cells-large"></i><span class="links_name">Dashboard</span></a>
        <a href="dealer_request.php" class="menu-item"><i class="fas fa-clipboard-list"></i><span class="links_name">New Leads</span></a>
        <a href="assigned_tasks.php" class="menu-item"><i class="fas fa-truck"></i><span class="links_name">Assigned Tasks</span></a>

        
        <a href="complete_pickup.php" class="menu-item"><i class="fas fa-check-double"></i><span class="links_name">History</span></a>
        <a href="reviews.php" class="menu-item"><i class="fas fa-star"></i><span class="links_name">Reviews</span></a>
        <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span class="links_name">Reports</span></a>
        <a href="my_profile.php" class="menu-item"><i class="fas fa-user-gear"></i><span class="links_name">Settings</span></a>
        <a href="manage_agents.php" class="menu-item"><i class="fas fa-users-gear text-warning"></i><span class="links_name">Manage Agents</span></a>
        <a href="logout.php" class="menu-item mt-5 text-danger"><i class="fas fa-power-off"></i><span class="links_name">Logout</span></a>
    </div>
</div>

<div class="main-content">
    <div class="top-navbar">
        <div>
            <h5 class="fw-bold mb-0">Overview</h5>
            <small class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['owner_name'] ?? 'Partner'); ?></small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block">
                <p class="mb-0 small fw-bold"><?= htmlspecialchars($dealer['business_name'] ?? 'Business Name'); ?></p>
                <p class="mb-0 x-small text-success" style="font-size: 11px;"><i class="fas fa-circle me-1"></i>Active Now</p>
            </div>
            <div class="bg-light p-2 rounded-3 border"><i class="fas fa-store text-primary"></i></div>
        </div>
    </div>

    <div class="container-fluid p-4">
        
        <?php if(!$profile_complete): ?>
        <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #f59e0b !important;">
            <div class="card-body d-flex align-items-center py-3">
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3"><i class="fas fa-id-card text-warning"></i></div>
                <div class="flex-grow-1">
                    <h6 class="mb-1 fw-bold">Complete your Profile (<?php echo $completion; ?>%)</h6>
                    <div class="progress-container"><div class="progress-fill"></div></div>
                </div>
                <a href="my_profile.php" class="btn btn-dark btn-sm ms-3 px-4 rounded-pill">Update</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="stat-card">
                    <span class="stat-label">Total Active</span>
                    <span class="stat-number"><?= $total_requests ?></span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <span class="stat-label">New Leads</span>
                    <span class="stat-number text-warning"><?= $pending ?></span>
                </div>
            </div>

            <div class="col-md-2">
                <div class="stat-card" style="border-bottom: 3px solid #f59e0b;">
                    <span class="stat-label">My Agents</span>
                    <span class="stat-number"><?= $total_agents ?></span>
                    <i class="fas fa-user-tie position-absolute opacity-10" style="right: 15px; bottom: 15px; font-size: 25px;"></i>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <span class="stat-label">Completed</span>
                    <span class="stat-number text-success"><?= $completed_count ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: #fff1f2; border-color: #fecdd3;">
                    <span class="stat-label text-danger">Cancelled Leads</span>
                    <span class="stat-number text-danger"><?= $cancelled_count ?></span>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold m-0">Revenue Trend (Weekly)</h6>
                        <span class="badge bg-success bg-opacity-10 text-success px-3">Total: ₹<?= number_format($total_revenue) ?></span>
                    </div>
                    <canvas id="revenueChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Quick Control</h5>
                        <p class="small opacity-75 mb-4">Manage your leads and shop settings easily.</p>
                        <div class="d-grid gap-2">
                            <a href="dealer_request.php" class="btn btn-success border-0 py-2 fw-bold">Process New Leads</a>
                            <a href="manage_agents.php" class="btn btn-warning border-0 py-2 fw-bold text-dark">Manage Agents</a>
                            <a href="my_profile.php" class="btn btn-outline-light py-2">Edit Scrap Prices</a>
                            <hr class="my-2 opacity-25">
                            <a href="complete_pickup.php" class="btn btn-light btn-sm rounded-pill fw-bold text-dark">View History</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Earnings (₹)',
                data: <?php echo json_encode($values); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
        }
    });
</script>
</body>
</html>