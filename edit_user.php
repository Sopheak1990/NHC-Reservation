<?php
require_once 'auth_check.php';
restrict_to_roles(['super_admin']);
require_once 'db_connect.php';
include 'sidebar.php';

$user_id = $_GET['id'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. If password field is NOT empty, we hash it and update it
    if (!empty($_POST['password'])) {
        $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE tbl_users SET FullName = ?, Role = ?, Password = ? WHERE UserID = ?");
        $stmt->execute([$_POST['fullname'], $_POST['role'], $new_pass, $user_id]);
    } else {
        // 2. If password field is empty, keep the old password
        $stmt = $conn->prepare("UPDATE tbl_users SET FullName = ?, Role = ? WHERE UserID = ?");
        $stmt->execute([$_POST['fullname'], $_POST['role'], $user_id]);
    }
    
    echo "<div class='alert alert-success'>User updated successfully! <a href='manage_users.php'>Back to list</a></div>";
}

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM tbl_users WHERE UserID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="card shadow-sm border-0" style="max-width: 500px;">
    <div class="card-body p-4">
        <h4>Edit User: <?= htmlspecialchars($user['Username']) ?></h4>
        <form method="POST">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['FullName']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label>Role</label>
                <select name="role" class="form-select">
                    <option value="normal_user" <?= $user['Role'] == 'normal_user' ? 'selected' : '' ?>>Normal User</option>
                    <option value="manager" <?= $user['Role'] == 'manager' ? 'selected' : '' ?>>Manager</option>
                    <option value="super_admin" <?= $user['Role'] == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                </select>
            </div>

            <div class="mb-3">
                <label>New Password <small class="text-muted">(Leave empty to keep current)</small></label>
                <input type="password" name="password" class="form-control" placeholder="Enter new password">
            </div>

            <button type="submit" class="btn btn-primary w-100">Update User</button>
            <a href="manage_users.php" class="btn btn-link w-100 mt-2 text-decoration-none">Cancel</a>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>