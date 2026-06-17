<?php
require_once 'auth_check.php';
restrict_to_roles(['super_admin']);
require_once 'db_connect.php';
include 'sidebar.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO tbl_users (Username, Password, FullName, Role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['username'], $pass, $_POST['fullname'], $_POST['role']]);
    echo "<div class='alert alert-success'>User created successfully!</div>";
}
?>

<div class="card shadow-sm border-0" style="max-width: 500px;">
    <div class="card-body p-4">
        <h4>Create New Staff Account</h4>
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Role</label>
                <select name="role" class="form-select">
                    <option value="normal_user">Normal User</option>
                    <option value="manager">Manager</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create User</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>