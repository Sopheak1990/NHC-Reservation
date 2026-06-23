<?php
// 1. SAFELY start the session (only if it hasn't been started yet)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Check if the user is NOT logged in.
if (!isset($_SESSION['user_id'])) {
    // Crucial: Make sure we aren't ALREADY on the login page before redirecting!
    if (basename($_SERVER['PHP_SELF']) != 'login.php') {
        header("Location: login.php");
        exit;
    }
}

// 3. Your role restriction function
function restrict_to_roles($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // If they don't have permission, send them to an error page or back to index
        header("Location: index.php?error=access_denied");
        exit;
    }
}
?>