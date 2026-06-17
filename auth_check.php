<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user session exists
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/**
 * Helper function to restrict pages to specific roles
 * @param array $allowed_roles e.g., ['admin']
 */
function restrict_to_roles($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // Force redirect unauthorized roles back to dashboard safely
        header("Location: index.php?error=unauthorized");
        exit;
    }
}