<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$sort_order = isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'ASC' : 'DESC'; 
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$sql_condition = "1=1"; 
$params = [];
$date_expr = "COALESCE(STR_TO_DATE(BookingDate, '%c/%e/%Y'), STR_TO_DATE(BookingDate, '%Y-%m-%d'), BookingDate)";

if ($start_date !== '' && $end_date !== '') {
    $sql_condition .= " AND $date_expr BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date; 
    $params[':end_date'] = $end_date;
}

try {
    $stmt = $conn->prepare("SELECT * FROM tbl_booking WHERE $sql_condition ORDER BY $date_expr $sort_order, BookingID $sort_order LIMIT 100");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $bookings
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>