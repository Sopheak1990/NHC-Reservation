<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require_once 'db_connect.php';

// 1. Read the JSON package sent by the mobile app
$input = json_decode(file_get_contents('php://input'), true);

// Check if data exists and contains the delete_id
if (!$input || !isset($input['delete_id'])) {
    echo json_encode(["status" => "error", "message" => "No Booking ID received."]);
    exit;
}

$booking_id = $input['delete_id'];

try {
    // 2. Delete the booking from the database
    $sql = "DELETE FROM tbl_booking WHERE BookingID = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $booking_id]);

    // 3. Check if a row was actually deleted
    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Booking successfully deleted!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Booking not found. It may have already been deleted."]);
    }

} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>