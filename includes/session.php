<?php
// SABSE PEHLE YE LINE HONI CHAHIYE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['stakeholder_id']);
}

function isVendor() {
    return isset($_SESSION['user_id']);
}

function isStakeholder() {
    return isset($_SESSION['stakeholder_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}

function redirectIfNotVendor() {
    if (!isVendor()) {
        header("Location: ../index.php");
        exit();
    }
}

function redirectIfNotStakeholder() {
    // Agar session set nahi hai toh ye function trigger hoga
    if (!isStakeholder()) {
        header("Location: ../index.php");
        exit();
    }
}
?>