<?php
// Path: stakeholder/reviews.php
require_once '../config/database.php';
session_start();

// Security: Check if dealer is logged in
if (!isset($_SESSION['stakeholder_id'])) {
    header('Location: login.php');
    exit();
}

$dealer_id = $_SESSION['stakeholder_id'];

try {
    // 1. Fetch Summary Stats
    $stat_sql = "SELECT 
                    AVG(CAST(rating AS DECIMAL(3,2))) as avg_rating, 
                    COUNT(id) as total_count,
                    SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_count,
                    SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative_count
                 FROM scrap_requests 
                 WHERE dealer_id = ? AND rating IS NOT NULL AND rating > 0";
    $stat_stmt = $pdo->prepare($stat_sql);
    $stat_stmt->execute([$dealer_id]);
    $stats = $stat_stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Fetch Detailed Reviews List - IMPORTANT: Include all necessary fields
    $list_sql = "SELECT 
                    r.id,
                    r.rating, 
                    r.feedback_text, 
                    r.completed_at,
                    r.final_weight,
                    u.name as vendor_name,
                    u.phone as vendor_phone
                 FROM scrap_requests r 
                 JOIN users u ON r.user_id = u.id 
                 WHERE r.dealer_id = ? AND r.rating IS NOT NULL AND r.rating > 0
                 ORDER BY r.completed_at DESC";
    $list_stmt = $pdo->prepare($list_sql);
    $list_stmt->execute([$dealer_id]);
    $reviews = $list_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviews - ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        
        .summary-card { 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            text-align: center; 
            border: none; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
        }
        
        .rating-num { 
            font-size: 3.5rem; 
            font-weight: 800; 
            color: #1e293b; 
        }
        
        .stars-gold { 
            color: #ffc107; 
            font-size: 1.2rem; 
        }
        
        .review-item { 
            background: white; 
            border-radius: 12px; 
            padding: 20px; 
            margin-bottom: 15px; 
            border-left: 5px solid #28a745; 
            transition: 0.3s; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .review-item:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        
        .review-item.negative {
            border-left-color: #dc3545;
        }
        
        .review-item.neutral {
            border-left-color: #ffc107;
        }
        
        .review-item.positive {
            border-left-color: #28a745;
        }
        
        .vendor-name { 
            font-weight: 700; 
            color: #333; 
            font-size: 1rem;
        }
        
        .vendor-phone {
            font-size: 0.85rem;
            color: #666;
        }
        
        .time-ago { 
            font-size: 0.8rem; 
            color: #999; 
        }
        
        .feedback-text {
            color: #555;
            font-style: italic;
            line-height: 1.5;
            margin-top: 12px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <!-- Header Section -->
    <div class="header-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-star-half-alt me-2"></i>Ratings & Reviews
                </h2>
                <p class="mb-0 opacity-90">Feedback from your sellers</p>
            </div>
            <a href="dashboard.php" class="btn btn-light rounded-pill px-4 fw-bold">
                <i class="fas fa-arrow-left me-2"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="summary-card">
                <p class="text-uppercase text-muted fw-bold small">Overall Rating</p>
                <div class="rating-num">
                    <?= $stats['total_count'] > 0 ? number_format($stats['avg_rating'] ?? 0, 1) : '0.0' ?>
                </div>
                <div class="stars-gold mb-2">
                    <?php 
                    $avg = $stats['total_count'] > 0 ? round($stats['avg_rating'] ?? 0) : 0;
                    for($i=1; $i<=5; $i++) echo ($i <= $avg) ? '★' : '☆';
                    ?>
                </div>
                <p class="text-muted small mb-0">
                    Based on <strong><?= $stats['total_count'] ?></strong> verified reviews
                </p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="stat-box">
                        <div class="stat-number text-success">
                            <i class="fas fa-thumbs-up me-2"></i><?= $stats['positive_count'] ?? 0 ?>
                        </div>
                        <div class="stat-label">Positive Reviews (4-5 ★)</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="stat-box">
                        <div class="stat-number text-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?= $stats['negative_count'] ?? 0 ?>
                        </div>
                        <div class="stat-label">Needs Improvement (1-2 ★)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="review-list">
        <h4 class="fw-bold mb-4 text-dark">
            <i class="fas fa-comments me-2 text-primary"></i>Recent Reviews
        </h4>

        <?php if(empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-comments fa-4x text-light mb-3"></i>
                <p class="text-muted mb-0">No reviews yet. Your sellers will leave feedback after their first transaction!</p>
                <p class="text-muted small mt-2">Start accepting requests to collect reviews.</p>
            </div>
        <?php else: ?>
            <?php foreach($reviews as $r): 
                // Determine review sentiment for styling
                $sentiment = $r['rating'] >= 4 ? 'positive' : ($r['rating'] >= 3 ? 'neutral' : 'negative');
            ?>
                <div class="review-item <?= $sentiment ?> shadow-sm">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="vendor-name">
                                <i class="fas fa-user-circle me-2 text-primary"></i>
                                <?= htmlspecialchars($r['vendor_name']) ?>
                            </div>
                            <div class="vendor-phone">
                                <i class="fas fa-phone-alt me-1"></i>
                                <?= htmlspecialchars($r['vendor_phone']) ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="stars-gold mb-1">
                                <?php for($i=1; $i<=5; $i++) echo ($i <= $r['rating']) ? '★' : '☆'; ?>
                            </div>
                            <span class="time-ago">
                                <i class="far fa-calendar me-1"></i>
                                <?= date('d M Y', strtotime($r['completed_at'] ?? 'now')) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Pickup Details -->
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-weight me-1"></i>
                        Final Weight: <strong><?= number_format($r['final_weight'], 2) ?> kg</strong>
                    </small>

                    <!-- Feedback Text -->
                    <?php if(!empty($r['feedback_text'])): ?>
                        <div class="feedback-text">
                            <i class="fas fa-quote-left text-muted me-2"></i>
                            <?= htmlspecialchars($r['feedback_text']) ?>
                        </div>
                    <?php else: ?>
                        <div class="feedback-text text-center text-muted">
                            <em>No comment provided</em>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>