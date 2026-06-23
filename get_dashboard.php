<?php
// Tell the mobile app to expect pure data, not a webpage
header('Content-Type: application/json');
require_once 'db_connect.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$sql_condition = "";
$params = [];

// SMART DATE EXPRESSION
$date_expr = "COALESCE(STR_TO_DATE(BookingDate, '%c/%e/%Y'), STR_TO_DATE(BookingDate, '%Y-%m-%d'), BookingDate)";

switch ($filter) {
    case 'month':
        $sql_condition = "MONTH($date_expr) = :month AND YEAR($date_expr) = :year";
        $params = [':month' => date('n'), ':year' => date('Y')];
        break;
    case 'year':
        $sql_condition = "YEAR($date_expr) = :year";
        $params = [':year' => date('Y')];
        break;
    case 'today':
    default:
        $sql_condition = "DATE($date_expr) = :today";
        $params = [':today' => date('Y-m-d')];
        break;
}

try {
    // 1. Get Aggregates
    $dash_stmt = $conn->prepare("SELECT COUNT(*) as total_books, SUM(Pax) as total_pax FROM tbl_booking WHERE $sql_condition");
    $dash_stmt->execute($params);
    $dash_data = $dash_stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Get the actual list of tours
    $list_stmt = $conn->prepare("SELECT * FROM tbl_booking WHERE $sql_condition ORDER BY $date_expr ASC");
    $list_stmt->execute($params);
    $active_tours = $list_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output pure JSON
    echo json_encode([
        "status" => "success",
        "total_books" => $dash_data['total_books'] ?? 0,
        "total_pax" => $dash_data['total_pax'] ?? 0,
        "recent_bookings" => $active_tours
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>