<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Read the incoming JSON data from the mobile app
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$method = $_SERVER['REQUEST_METHOD'];

try {
    // 1. GET: Fetch all users
    if ($method === 'GET') {
        $stmt = $conn->query("SELECT UserID, Username, FullName, Role FROM tbl_users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $users]);
        exit;
    }

    // 2. POST: Add, Edit, or Delete
    if ($method === 'POST') {
        $action = isset($input['action']) ? $input['action'] : '';

        // ADD USER
        if ($action === 'add') {
            $pass = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO tbl_users (Username, Password, FullName, Role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['username'], $pass, $input['fullname'], $input['role']]);
            echo json_encode(["status" => "success", "message" => "User created successfully!"]);
        } 
        
        // EDIT USER
        elseif ($action === 'edit') {
            if (!empty($input['password'])) {
                $new_pass = password_hash($input['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE tbl_users SET FullName = ?, Role = ?, Password = ? WHERE UserID = ?");
                $stmt->execute([$input['fullname'], $input['role'], $new_pass, $input['id']]);
            } else {
                $stmt = $conn->prepare("UPDATE tbl_users SET FullName = ?, Role = ? WHERE UserID = ?");
                $stmt->execute([$input['fullname'], $input['role'], $input['id']]);
            }
            echo json_encode(["status" => "success", "message" => "User updated successfully!"]);
        } 
        
        // DELETE USER
        elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM tbl_users WHERE UserID = ? AND Username != 'admin'");
            $stmt->execute([$input['id']]);
            echo json_encode(["status" => "success", "message" => "User deleted."]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
?>