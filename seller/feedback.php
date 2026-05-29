<?php
session_start();
require_once '../config/database.php';

$request_id = $_GET['request_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'];
    $feedback = $_POST['feedback'];
    $amount = $_POST['amount'];
    
    // Update request to completed and save feedback
    $stmt = $pdo->prepare("UPDATE pickup_requests SET status = 'completed', amount_paid = ?, rating = ?, feedback = ? WHERE id = ?");
    $stmt->execute([$amount, $rating, $feedback, $request_id]);
    
    echo "<script>alert('Thank you for your feedback!'); window.location.href='my_requests.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback & Rating</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .star-rating { font-size: 30px; color: #ddd; cursor: pointer; }
        .star-rating .fas { color: #f1c40f; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow border-0 p-4" style="max-width: 500px; margin: auto; border-radius: 20px;">
        <h4 class="fw-bold text-center mb-4">Pickup Completed?</h4>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Amount Received (₹)</label>
                <input type="number" name="amount" class="form-control form-control-lg" placeholder="e.g. 500" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Rate the Dealer</label>
                <select name="rating" class="form-select" required>
                    <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                    <option value="4">⭐⭐⭐⭐ (Good)</option>
                    <option value="3">⭐⭐⭐ (Average)</option>
                    <option value="2">⭐⭐ (Poor)</option>
                    <option value="1">⭐ (Very Bad)</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Your Feedback</label>
                <textarea name="feedback" class="form-control" rows="3" placeholder="Tell us about the service..."></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill">SUBMIT FEEDBACK</button>
        </form>
    </div>
</div>

</body>
</html>