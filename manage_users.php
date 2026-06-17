<?php
require_once 'auth_check.php';
restrict_to_roles(['super_admin']); // Only Super Admin can see this page
require_once 'db_connect.php';
include 'sidebar.php';

// Handle Delete User
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM tbl_users WHERE UserID = :id AND Username != 'admin'");
    $stmt->execute([':id' => $_GET['delete']]);
    $msg = "User deleted successfully.";
}

// Fetch all users
$users = $conn->query("SELECT * FROM tbl_users")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users-cog me-2"></i> User Management</h2>
    <a href="add_user.php" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> Add New User</a>
</div>

<div class="card shadow-sm border-0">
    <table class="table align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Role</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['Username']) ?></td>
                <td><?= htmlspecialchars($user['FullName']) ?></td>
                <td><span class="badge bg-info text-dark"><?= ucfirst(str_replace('_', ' ', $user['Role'])) ?></span></td>
                <td class="text-center" style="white-space: nowrap;">
                    <?php 
                    // Only show buttons if the row being displayed is NOT the current logged-in user
                    // (Assuming the super admin is the one logged in)
                    if ($user['UserID'] !== $_SESSION['user_id']): 
                    ?>
                        <a href="edit_user.php?id=<?= $user['UserID'] ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="setDeleteId(<?= $user['UserID'] ?>, '<?= htmlspecialchars($user['FullName']) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    <?php else: ?>
                        <span class="badge bg-secondary">Current User</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to permanently delete <strong><span id="userNameDisplay"></span></strong>? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Delete User</a>
      </div>
    </div>
  </div>
</div>

<script>
function setDeleteId(id, name) {
    document.getElementById('userNameDisplay').innerText = name;
    document.getElementById('confirmDeleteBtn').href = 'manage_users.php?delete=' + id;
    var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    myModal.show();
}
</script>

<?php include 'footer.php'; ?>