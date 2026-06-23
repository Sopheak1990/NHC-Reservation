<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST');

require_once 'db_connect.php';

// Prepare data from the request
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? $input['action'] : '';

try {
    // SCENARIO 1: DELETE USER
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM tbl_users WHERE UserID = :id");
        $stmt->execute([':id' => $input['id']]);
        echo json_encode(["status" => "success", "message" => "User deleted."]);
        exit;
    }

    // SCENARIO 2: EDIT USER
    if ($action === 'edit') {
        if (!empty($input['password'])) {
            $stmt = $conn->prepare("UPDATE tbl_users SET FullName = :fn, Role = :role, Password = :pw WHERE UserID = :id");
            $stmt->execute([':fn' => $input['fullname'], ':role' => $input['role'], ':pw' => password_hash($input['password'], PASSWORD_DEFAULT), ':id' => $input['id']]);
        } else {
            $stmt = $conn->prepare("UPDATE tbl_users SET FullName = :fn, Role = :role WHERE UserID = :id");
            $stmt->execute([':fn' => $input['fullname'], ':role' => $input['role'], ':id' => $input['id']]);
        }
        echo json_encode(["status" => "success", "message" => "User updated."]);
        exit;
    }

    // SCENARIO 3: FETCH LIST (Default)
    $stmt = $conn->query("SELECT UserID, Username, FullName, Role FROM tbl_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["status" => "success", "data" => $users]);

} catch (Exception $e) {
    // This catches database errors and sends them as JSON so your app doesn't crash
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>