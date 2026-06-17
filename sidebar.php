<?php
// 1. MUST start the session before any other code
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Find out which file we are currently looking at
$current_page = basename($_SERVER['PHP_SELF']);

// Set dynamic titles for the top bar
$page_title = "Dashboard"; 
if ($current_page == 'all_bookings.php') $page_title = "All Bookings";
if ($current_page == 'add_booking.php') $page_title = "Reservation";
if ($current_page == 'manage_users.php') $page_title = "Control";
if ($current_page == 'change_password.php') $page_title = "Account Security";

// Safely handle the user's name and role for the top bar
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin Staff';
$user_role = isset($_SESSION['role']) ? ucfirst(str_replace('_', ' ', $_SESSION['role'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NHC Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; overflow-x: hidden; }
        .sidebar { height: 100vh; background-color: #343a40; padding-top: 20px; color: white; position: fixed; width: 250px; display: flex; flex-direction: column; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; font-size: 1.1rem; }
        .sidebar a:hover, .sidebar a.active { background-color: #007bff; color: white; border-radius: 5px; margin: 0 10px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .navbar { margin-left: 250px; background-color: white; border-bottom: 1px solid #dee2e6; }
        
        /* Logo sizing */
        .sidebar-logo { max-width: 120px; max-height: 120px; object-fit: contain; }

        /* Logout styles */
        .logout-link { 
            margin-top: auto; 
            margin-bottom: 20px; 
            border-top: 1px solid #4f5962; 
            padding-top: 20px !important; 
            transition: all 0.3s ease !important;
            color: #dc3545 !important;
        }
        .sidebar .logout-link:hover { 
            background-color: #dc3545 !important; 
            color: #ffffff !important; 
            padding-left: 30px !important; 
        }
        .sidebar .logout-link:hover i {
            transform: translateX(5px) !important;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="text-center mb-2">
            <img src="./images/nhc-logo.png" alt="NHC Logo" class="sidebar-logo img-fluid rounded">
        </div>

        <h4 class="text-center mb-2">New Hope Training Restaurant</h4>
        <hr class="text-white-50 mx-3 my-2">
        <h5 class="text-center mb-2 text-info">Reservation System</h5>
        <hr class="text-white-50 mx-3 my-2 mb-4">
        
        <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="all_bookings.php" class="<?= $current_page == 'all_bookings.php' ? 'active' : '' ?>">
            <i class="fas fa-table me-2"></i> All Bookings
        </a>

        <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'super_admin' || $_SESSION['role'] == 'manager')): ?>
        <a href="add_booking.php" class="<?= $current_page == 'add_booking.php' ? 'active' : '' ?>">
            <i class="fas fa-plus-circle me-2"></i> Add Booking
        </a>
        <?php endif; ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'super_admin'): ?>
        <a href="manage_users.php" class="<?= $current_page == 'manage_users.php' ? 'active' : '' ?>">
            <i class="fas fa-user-shield me-2"></i> Manage Users
        </a>
        <?php endif; ?>

        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt me-2"></i> Log Out
        </a>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light py-3 px-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1 text-uppercase"><?= $page_title ?></span>
            
            <div class="dropdown">
                <div class="d-flex align-items-center dropdown-toggle" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                    <div class="text-end me-3">
                        <div class="fw-bold"><?= htmlspecialchars($user_name) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($user_role) ?></div>
                    </div>
                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                </div>
                
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
                    <li><h6 class="dropdown-header">Account</h6></li>
                    <li><a class="dropdown-item" href="change_password.php">
                        <i class="fas fa-key me-2"></i> Change Password
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Log Out
                    </a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">