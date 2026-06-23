<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = isset($input['user_id']) ? $input['user_id'] : '';
    $current_pw = isset($input['current_password']) ? $input['current_password'] : '';
    $new_pw = isset($input['new_password']) ? $input['new_password'] : '';

    if (empty($user_id) || empty($current_pw) || empty($new_pw)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    try {
        // 1. Verify current password
        $stmt = $conn->prepare("SELECT Password FROM tbl_users WHERE UserID = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_pw, $user['Password'])) {
            // 2. Update to new password
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE tbl_users SET Password = ? WHERE UserID = ?");
            $update->execute([$hashed, $user_id]);
            
            echo json_encode(["status" => "success", "message" => "Password updated successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Current password is incorrect."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
}
?>