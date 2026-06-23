<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require_once 'db_connect.php';

// 1. Read the JSON package sent by the mobile app
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["status" => "error", "message" => "No data received."]);
    exit;
}

$new_code = trim($input['BookingCode']);

try {
    // 2. DUPLICATE CHECK: See if this Booking Code already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_booking WHERE BookingCode = :code");
    $check_stmt->execute([':code' => $new_code]);
    $duplicate_count = $check_stmt->fetchColumn();

    if ($duplicate_count > 0) {
        // Send JSON error back to mobile app
        echo json_encode(["status" => "error", "message" => "The Booking Code '{$new_code}' already exists!"]);
        exit;
    } 

    // 3. If unique, proceed to save the new booking
    $sql = "INSERT INTO tbl_booking (BookingDate, TourCompany, BookingCode, Pax, Meal, TourGuideName, TourGuideContact, Confirm) 
            VALUES (:bdate, :company, :code, :pax, :meal, :guide, :contact, :confirm)";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':bdate'   => $input['BookingDate'],
        ':company' => $input['TourCompany'],
        ':code'    => $new_code,
        ':pax'     => $input['Pax'],
        ':meal'    => $input['Meal'],
        ':guide'   => $input['TourGuideName'],
        ':contact' => $input['TourGuideContact'],
        ':confirm' => $input['Confirm']
    ]);
    
    // Send JSON success message back to mobile app
    echo json_encode(["status" => "success", "message" => "New booking successfully added!"]);

} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>