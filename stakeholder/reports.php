<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotStakeholder();

$stakeholder_id = $_SESSION['stakeholder_id'];

// 1. URL se Month aur Year fetch karna (Default: Current Month/Year)
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// 2. Summary Logic
$summary_sql = "SELECT 
                COUNT(*) as total_deals, 
                SUM(received_amount) as rev, 
                AVG(rating) as rate 
              FROM scrap_requests 
              WHERE dealer_id = ? AND status = 'COMPLETED' 
              AND MONTH(created_at) = ? 
              AND YEAR(created_at) = ?";
$s_stmt = $pdo->prepare($summary_sql);
$s_stmt->execute([$stakeholder_id, $selected_month, $selected_year]);
$summary = $s_stmt->fetch();

// 3. Category Breakdown
$item_sql = "SELECT scrap_type, SUM(received_amount) as total_amt, COUNT(*) as count 
             FROM scrap_requests 
             WHERE dealer_id = ? AND status = 'COMPLETED' 
             AND MONTH(created_at) = ? AND YEAR(created_at) = ?
             GROUP BY scrap_type 
             ORDER BY total_amt DESC";
$i_stmt = $pdo->prepare($item_sql);
$i_stmt->execute([$stakeholder_id, $selected_month, $selected_year]);
$item_reports = $i_stmt->fetchAll();

// 4. CSV Export Logic
if (isset($_GET['export'])) {
    $filename = "Report_" . $selected_month . "_" . $selected_year . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Customer', 'Category', 'Weight', 'Amount', 'Date']);
    
    $csv_sql = "SELECT r.id, u.name, r.scrap_type, r.estimated_weight, r.received_amount, r.created_at 
                FROM scrap_requests r JOIN users u ON r.user_id = u.id 
                WHERE r.dealer_id = ? AND r.status = 'COMPLETED' AND MONTH(r.created_at) = ? AND YEAR(r.created_at) = ?";
    $csv_stmt = $pdo->prepare($csv_sql);
    $csv_stmt->execute([$stakeholder_id, $selected_month, $selected_year]);
    while ($row = $csv_stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['id'], $row['name'], $row['scrap_type'], $row['estimated_weight'], $row['received_amount'], date('d-m-Y', strtotime($row['created_at']))]);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">

    <style>
        :root { --primary: #10b981; --dark: #0f172a; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; margin: 0; }
        
        /* SIDEBAR (Dashboard Style) */
        .sidebar { position: fixed; left: 0; top: 0; height: 100vh; width: 260px; background: var(--dark); color: white; z-index: 1000; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h3 { font-size: 1.4rem; font-weight: 700; color: var(--primary); margin: 0; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item { padding: 12px 25px; display: flex; align-items: center; gap: 15px; color: #94a3b8; text-decoration: none; transition: 0.3s; font-size: 0.95rem; }
        .menu-item:hover, .menu-item.active { background: rgba(16, 185, 129, 0.1); color: var(--primary); border-right: 4px solid var(--primary); }
        
        .main-content { margin-left: 260px; padding: 40px; }
        .filter-card { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stat-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; text-align: center; border-start: 5px solid var(--primary); transition: 0.3s; }
        
        /* Calendar Input Styling */
        .calendar-box { background: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; cursor: pointer; font-weight: 600; text-align: center; }
        .input-group-text { background: var(--primary); color: white; border: none; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-recycle"></i><span> ScrapSmart</span></h3>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item active">
            <i class="fas fa-chart-line"></i><span class="links_name">Dashboard</span>
        </a>
        
        <a href="dealer_request.php" class="menu-item">
            <i class="fas fa-list-check"></i><span class="links_name">New Leads</span>
        </a>
        
        <a href="accepted_requests.php" class="menu-item">
            <i class="fas fa-truck"></i><span class="links_name">Active Pickups</span>
        </a>
        
        <a href="complete_pickup.php" class="menu-item">
            <i class="fas fa-history"></i><span class="links_name">History</span>
        </a>
        
        <a href="reviews.php" class="menu-item">
            <i class="fas fa-star"></i><span class="links_name">Reviews</span>
        </a>
        
        <a href="reports.php" class="menu-item">
            <i class="fas fa-file-invoice-dollar"></i><span class="links_name">Reports</span>
        </a>
        
        <a href="my_profile.php" class="menu-item">
            <i class="fas fa-user-gear"></i><span class="links_name">Settings</span>
        </a>
        
        <a href="logout.php" class="menu-item mt-5 text-danger">
            <i class="fas fa-sign-out-alt"></i><span class="links_name">Logout</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">Business Analytics</h4>
        <a href="?export=1&month=<?= $selected_month ?>&year=<?= $selected_year ?>" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm">
            <i class="fas fa-file-csv me-2"></i>Export CSV
        </a>
    </div>

    <div class="filter-card">
        <form method="GET" action="reports.php" id="filterForm" class="row g-3 align-items-end justify-content-center">
            <div class="col-md-6">
                <label class="small fw-bold text-muted mb-2">Select Month & Year from Calendar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    <input type="hidden" name="month" id="hiddenMonth" value="<?= $selected_month ?>">
                    <input type="hidden" name="year" id="hiddenYear" value="<?= $selected_year ?>">
                    
                    <input type="text" id="monthYearPicker" class="form-control calendar-box py-2" placeholder="Click to select..." readonly>
                    
                    <button type="submit" class="btn btn-dark fw-bold px-4">Generate Report</button>
                </div>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <small class="text-muted fw-bold">TOTAL REVENUE</small>
                <h2 class="fw-bold text-success mt-2">₹<?= number_format($summary['rev'] ?? 0) ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-color: #3b82f6;">
                <small class="text-muted fw-bold">DEALS DONE</small>
                <h2 class="fw-bold text-dark mt-2"><?= $summary['total_deals'] ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-color: #f59e0b;">
                <small class="text-muted fw-bold">AVG RATING</small>
                <h2 class="fw-bold text-warning mt-2"><?= round($summary['rate'] ?? 0, 1) ?> ★</h2>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-chart-pie me-2"></i>Category Breakdown</h6>
        <?php if(empty($item_reports)): ?>
            <p class="text-center py-5 text-muted">No transactions recorded for this period.</p>
        <?php else: ?>
            <?php foreach($item_reports as $item): ?>
            <div class="d-flex justify-content-between py-3 border-bottom">
                <span class="fw-bold text-dark"><?= htmlspecialchars($item['scrap_type']) ?> <small class="text-muted ms-2">(<?= $item['count'] ?>)</small></span>
                <span class="text-success fw-bold">₹<?= number_format($item['total_amt']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
    flatpickr("#monthYearPicker", {
        disableMobile: "true",
        plugins: [
            new monthSelectPlugin({
                shorthand: true, 
                dateFormat: "F Y", 
                altFormat: "F Y"
            })
        ],
        defaultDate: "<?= $selected_year ?>-<?= $selected_month ?>",
        onChange: function(selectedDates, dateStr, instance) {
            // Jab user calendar se select kare, toh hidden fields update karo
            const date = selectedDates[0];
            document.getElementById('hiddenMonth').value = date.getMonth() + 1;
            document.getElementById('hiddenYear').value = date.getFullYear();
        }
    });
</script>
</body>
</html>