<?php
/**
 * ScrapSmart Database Configuration
 * Configured for Docker Container Architecture
 */

// In Docker, the host is the name of the container service, which we will call 'db'
$host = 'db'; 
$dbname = 'smart_scrap_db'; 
$username = 'root'; 
// We must give root a secure password because Docker MariaDB/MySQL containers require one
$password = 'root_secure_password'; 
$port = "3306";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (isset($enable_die) && $enable_die) {
        die("Connection failed: " . $e->getMessage());
    }
    $pdo_error = $e->getMessage();
}
?>