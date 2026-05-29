<?php
session_start();
require_once '../config/database.php';

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $request_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Update status to CANCELLED only if it was PENDING
    $stmt = $pdo->prepare("UPDATE scrap_requests SET status = 'CANCELLED' WHERE id = ? AND user_id = ? AND status = 'PENDING'");
    $stmt->execute([$request_id, $user_id]);
    
    header("Location: my_requests.php");
    exit();
}
?>