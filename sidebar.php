<?php
// Find out which file we are currently looking at
$current_page = basename($_SERVER['PHP_SELF']);

// Set dynamic titles for the top bar
$page_title = "Dashboard"; 
if ($current_page == 'all_bookings.php') $page_title = "All Bookings";
if ($current_page == 'add_booking.php') $page_title = "Add New Booking";
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
        .sidebar { height: 100vh; background-color: #343a40; padding-top: 20px; color: white; position: fixed; width: 250px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; font-size: 1.1rem; }
        .sidebar a:hover, .sidebar a.active { background-color: #007bff; color: white; border-radius: 5px; margin: 0 10px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .navbar { margin-left: 250px; background-color: white; border-bottom: 1px solid #dee2e6; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4 class="text-center mb-4"><i class="fas fa-calendar"></i> NHC Reservation</h4>
        <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="all_bookings.php" class="<?= $current_page == 'all_bookings.php' ? 'active' : '' ?>">
            <i class="fas fa-table me-2"></i> All Bookings
        </a>
        <a href="add_booking.php" class="<?= $current_page == 'add_booking.php' ? 'active' : '' ?>">
            <i class="fas fa-plus-circle me-2"></i> Add Booking
        </a>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light py-3 px-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1 text-uppercase">
                <?= $page_title ?>
            </span>
            <div class="d-flex">
                <span class="navbar-text"><i class="fas fa-user-circle fa-lg me-2"></i> Admin Staff</span>
            </div>
        </div>
    </nav>

    <div class="main-content">