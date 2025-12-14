<?php
/**
 * Petty Cash System - Main Entry Point
 * 
 * This is the main entry point for the Petty Cash System application.
 * All requests are routed through this file or directly to their respective pages.
 */

session_start();

// Check if user is logged in
if (isset($_SESSION['user'])) {
    // Redirect to appropriate dashboard based on role
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: employee/dashboard.php");
    }
    exit();
} else {
    // Redirect to login page
    header("Location: auth/login.php");
    exit();
}
?>
