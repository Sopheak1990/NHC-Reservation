<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

require_once 'db_connect.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';

// YOUR SMART DATE EXPRESSION
$date_expr = "COALESCE(STR_TO_DATE(BookingDate, '%c/%e/%Y'), STR_TO_DATE(BookingDate, '%Y-%m-%d'), BookingDate)";

$dateCondition = "";
$params = [];

// Apply Date Filters using your specific logic
if ($filter === 'today') {
    $dateCondition = "WHERE DATE($date_expr) = :today";
    $params = [':today' => date('Y-m-d')];
} elseif ($filter === 'week') {
    $dateCondition = "WHERE YEARWEEK($date_expr, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'month') {
    $dateCondition = "WHERE MONTH($date_expr) = :month AND YEAR($date_expr) = :year";
    $params = [':month' => date('n'), ':year' => date('Y')];
} elseif ($filter === 'year') {
    $dateCondition = "WHERE YEAR($date_expr) = :year";
    $params = [':year' => date('Y')];
}

try {
    // 1. Get Total Bookings
    $stmtTotal = $conn->prepare("SELECT COUNT(*) FROM tbl_booking $dateCondition");
    $stmtTotal->execute($params);
    $totalBookings = $stmtTotal->fetchColumn();

    // 2. Get Confirmed Bookings
    $confCond = $dateCondition ? $dateCondition . " AND Confirm = 'True'" : "WHERE Confirm = 'True'";
    $stmtConfirmed = $conn->prepare("SELECT COUNT(*) FROM tbl_booking $confCond");
    $stmtConfirmed->execute($params);
    $confirmedBookings = $stmtConfirmed->fetchColumn();

    // 3. Get Pending Bookings
    $pendCond = $dateCondition ? $dateCondition . " AND Confirm = 'False'" : "WHERE Confirm = 'False'";
    $stmtPending = $conn->prepare("SELECT COUNT(*) FROM tbl_booking $pendCond");
    $stmtPending->execute($params);
    $pendingBookings = $stmtPending->fetchColumn();

    // 4. Get Total Pax (Passengers)
    $stmtPax = $conn->prepare("SELECT SUM(Pax) FROM tbl_booking $dateCondition");
    $stmtPax->execute($params);
    $totalPax = $stmtPax->fetchColumn();

    // 5. Get Bookings FOR THE SELECTED TIMEFRAME
    // Notice we added $dateCondition here and changed to ASC for chronological schedule order!
    // I also removed "LIMIT 5" so if you have 8 tours today, you see all 8. (Add LIMIT 5 back if you want to restrict it).
    $stmtRecent = $conn->prepare("SELECT BookingID, TourCompany, BookingDate, Confirm, Pax, TourGuideName, TourGuideContact, Meal FROM tbl_booking $dateCondition ORDER BY $date_expr ASC");
    $stmtRecent->execute($params); // <-- Must pass $params here too!
    $recentBookings = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    // Send the package back to the mobile app
    echo json_encode([
        "status" => "success",
        "data" => [
            "total_bookings" => (int)$totalBookings,
            "confirmed" => (int)$confirmedBookings,
            "pending" => (int)$pendingBookings,
            "total_pax" => (int)$totalPax ?: 0, 
            "recent_bookings" => $recentBookings
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>