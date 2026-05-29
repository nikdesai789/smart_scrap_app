<?php
// verify_otp_ajax.php
require_once '../config/database.php';

// Check if the required data is sent via POST
if (isset($_POST['id']) && isset($_POST['otp'])) {
    $req_id = $_POST['id'];
    $entered_otp = $_POST['otp'];

    try {
        // Fetch the stored OTP for this specific request
        $stmt = $pdo->prepare("SELECT otp FROM scrap_requests WHERE id = ?");
        $stmt->execute([$req_id]);
        $stored_otp = $stmt->fetchColumn();

        // Compare the entered OTP with the database value
        if ($stored_otp !== false && $entered_otp === $stored_otp) {
            // Send 'success' back to the JavaScript to enable the button
            echo 'success';
        } else {
            // Send 'wrong' if they don't match
            echo 'wrong';
        }
    } catch (PDOException $e) {
        echo 'error';
    }
} else {
    echo 'missing_data';
}
?>