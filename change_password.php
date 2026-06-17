<?php
require_once 'auth_check.php';
require_once 'db_connect.php';
include 'sidebar.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_pw = $_POST['current_password'];
    $new_pw = $_POST['new_password'];

    // 1. Verify current password
    $stmt = $conn->prepare("SELECT Password FROM tbl_users WHERE UserID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($current_pw, $user['Password'])) {
        // 2. Update to new password
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE tbl_users SET Password = ? WHERE UserID = ?");
        $update->execute([$hashed, $_SESSION['user_id']]);
        $success = "Password updated successfully!";
    } else {
        $error = "Current password is incorrect.";
    }
}
?>

<div class="card shadow-sm border-0" style="max-width: 400px;">
    <div class="card-body p-4">
        <h4><i class="fas fa-key me-2"></i> Change Password</h4>
        <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Update Password</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>