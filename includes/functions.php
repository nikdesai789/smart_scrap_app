<?php
// Send SMS notification (integrate with SMS API)
function sendSMS($phone, $message) {
    // Implement SMS API integration here
    // Example: Twilio, MSG91, etc.
    return true;
}

// Send Email notification
function sendEmail($to, $subject, $message) {
    // Implement email sending using PHPMailer
    return true;
}

// Generate unique OTP
function generateOTP($length = 6) {
    return rand(pow(10, $length-1), pow(10, $length)-1);
}

// Validate Indian phone number
function validatePhoneNumber($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone);
}

// Calculate distance between two points
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Earth's radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Get request status badge
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'accepted' => '<span class="badge badge-success">Accepted</span>',
        'completed' => '<span class="badge badge-info">Completed</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>'
    ];
    return $badges[$status] ?? '<span class="badge">Unknown</span>';
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Upload image with validation
function uploadImage($file, $target_dir = "../assets/uploads/") {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $file_name];
    }
    
    return ['success' => false, 'error' => 'Upload failed'];
}

// Log activity
function logActivity($user_id, $user_type, $action, $details = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_type, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_type, $action, $details, $_SERVER['REMOTE_ADDR']]);
}