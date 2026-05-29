<?php
// Path: stakeholder/process_request.php
session_start();
require_once '../config/database.php';

// Timezone setup for accurate time capture 🌍
date_default_timezone_set('Asia/Kolkata');

// ==========================================================
// ACTION 1: VENDOR SIDE (Initial Request Submission)
// ==========================================================
if (isset($_POST['submit_request'])) {
    $dealer_id   = $_POST['dealer_id'];
    $user_id     = $_SESSION['user_id']; 
    $pickup_date = $_POST['pickup_date'];
    $weight      = $_POST['weight'];
    $items       = isset($_POST['scrap_items']) ? implode(',', $_POST['scrap_items']) : '';
    
    $address = $_POST['house_info'] . ", " . $_POST['street_info'];
    if(!empty($_POST['landmark'])) { 
        $address .= " (Landmark: " . $_POST['landmark'] . ")"; 
    }

    $image_name = null;
    if(isset($_FILES['scrap_image']) && $_FILES['scrap_image']['error'] == 0){
        $ext = pathinfo($_FILES['scrap_image']['name'], PATHINFO_EXTENSION);
        $image_name = "SCRAP_" . time() . "." . $ext;
        move_uploaded_file($_FILES['scrap_image']['tmp_name'], "../uploads/" . $image_name);
    }

    try {
        $sql = "INSERT INTO scrap_requests (user_id, dealer_id, scrap_type, estimated_weight, address, pickup_date, status, scrap_image, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'PENDING', ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$user_id, $dealer_id, $items, $weight, $address, $pickup_date, $image_name]);

        if ($result) {
            header("Location: ../vendor/dashboard.php?status=success");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: ../vendor/dashboard.php?status=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
} 

// ==========================================================
// ACTION 2: DEALER SIDE (Accept & Assign Agent) ✅
// ==========================================================
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && $_POST['status'] == 'ACCEPTED') {

    $request_id     = $_POST['id'];
    // Dealer accept kare toh ASSIGNED set karo — agent dashboard par jaayega
    // Jab agent khud 'Accept' button dabayega tab ACCEPTED hoga
    $new_status     = 'ASSIGNED'; 
    $dealer_id      = $_SESSION['stakeholder_id'];
    $confirmed_date = $_POST['confirmed_date'];
    $dealer_note    = $_POST['dealer_note'] ?? ''; 
    
    // NEW logic: Capture agent_id from the dropdown
    $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : 0; 

    // Time Inputs capture from modal
    $h = isset($_POST['h']) ? trim($_POST['h']) : ''; 
    $m = isset($_POST['m']) ? trim($_POST['m']) : '00'; 
    $p = isset($_POST['p']) ? trim($_POST['p']) : ''; 
    
    $confirmed_time = "00:00:00"; 

    if (!empty($h) && !empty($p)) {
        $time_input = str_pad($h, 2, "0", STR_PAD_LEFT) . ":" . str_pad($m, 2, "0", STR_PAD_LEFT) . " " . strtoupper($p);
        $timestamp = strtotime($time_input);
        if ($timestamp) {
            $confirmed_time = date("H:i:s", $timestamp);
        }
    }

    $otp = rand(1000, 9999); 

    try {
        // Update includes agent_id so it shows up on the Agent Dashboard
        $sql = "UPDATE scrap_requests 
                SET status = ?, 
                    otp = ?, 
                    pickup_date = ?, 
                    pickup_time = ?, 
                    dealer_note = ?,
                    agent_id = ? 
                WHERE id = ? AND dealer_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([
            $new_status, 
            $otp, 
            $confirmed_date, 
            $confirmed_time, 
            $dealer_note,
            $agent_id,
            $request_id,
            $dealer_id
        ]);

        if ($res) {
            // REDIRECT: Go to the Monitoring/Assigned tasks page
            header("Location: assigned_tasks.php?status=assigned");
            exit();
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}

// ==========================================================
// ACTION 2.1: DEALER SIDE (Reject Request)
// ==========================================================
else if (isset($_GET['id']) && isset($_GET['status']) && $_GET['status'] == 'REJECTED') {
    $request_id = $_GET['id'];
    $dealer_id  = $_SESSION['stakeholder_id'];

    try {
        $sql = "UPDATE scrap_requests SET status = 'REJECTED' WHERE id = ? AND dealer_id = ?";
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([$request_id, $dealer_id]);

        if ($res) {
            header("Location: dealer_request.php?status=rejected");
            exit();
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}

// ==========================================================
// ACTION 3: AGENT/DEALER SIDE (Final Pickup Completion)
// ==========================================================
else if (isset($_POST['complete_pickup_by_dealer'])) {
    $request_id     = $_POST['request_id'];
    $input_otp      = $_POST['verify_otp'];
    $final_weight   = $_POST['final_weight'];
    $amount_paid    = $_POST['received_amount'];
    
    // Check if Dealer or Agent is completing it
    if(isset($_SESSION['stakeholder_id'])) {
        $user_check_id = $_SESSION['stakeholder_id'];
        $column = "dealer_id";
    } else {
        $user_check_id = $_SESSION['agent_id'];
        $column = "agent_id";
    }

    try {
        // Verify OTP from database
        $check_sql = "SELECT otp FROM scrap_requests WHERE id = ? AND $column = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$request_id, $user_check_id]);
        $stored_otp = $check_stmt->fetchColumn();

        if ($stored_otp !== false && $stored_otp == $input_otp) {
            $sql = "UPDATE scrap_requests 
                    SET status = 'COMPLETED', 
                        final_weight = ?, 
                        received_amount = ?, 
                        completed_at = NOW(),
                        otp = NULL 
                    WHERE id = ? AND $column = ?";
            
            $update_stmt = $pdo->prepare($sql);
            $res = $update_stmt->execute([$final_weight, $amount_paid, $request_id, $user_check_id]);

            if ($res) {
                // If agent completed it, stay in agent folder, else dealer folder
                $redirect = isset($_SESSION['agent_id']) ? "../agent/dashboard.php" : "assigned_tasks.php";
                header("Location: $redirect?status=completed");
                exit();
            }
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?status=wrong_otp");
            exit();
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}

// ==========================================================
// ACTION 4: VENDOR SIDE (Feedback Form)
// ==========================================================
else if (isset($_POST['vendor_submit_feedback'])) {
    $request_id      = $_POST['request_id'];
    $rating          = $_POST['rating'];
    $feedback        = $_POST['feedback'];

    try {
        $sql = "UPDATE scrap_requests SET rating = ?, feedback_text = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([$rating, $feedback, $request_id]);

        if ($res) {
            header("Location: ../vendor/my_requests.php?status=feedback_completed");
            exit();
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}

else {
    header("Location: dashboard.php");
    exit();
} 
?>