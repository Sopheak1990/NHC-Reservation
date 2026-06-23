<?php
// api_update_booking.php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Get the raw POST data from your mobile app
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['BookingID'])) {
    echo json_encode(["status" => "error", "message" => "Invalid data received."]);
    exit;
}

try {
    $sql = "UPDATE tbl_booking SET 
            TourCompany = :company, 
            BookingCode = :code, 
            Pax = :pax, 
            Meal = :meal, 
            TourGuideName = :guide 
            WHERE BookingID = :id";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':company' => $input['TourCompany'],
        ':code'    => $input['BookingCode'],
        ':pax'     => $input['Pax'],
        ':meal'    => $input['Meal'],
        ':guide'   => $input['TourGuideName'],
        ':id'      => $input['BookingID']
    ]);

    echo json_encode(["status" => "success", "message" => "Booking updated."]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>