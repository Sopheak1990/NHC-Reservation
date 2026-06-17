<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
// Normal users are blocked from this page
restrict_to_roles(['super_admin', 'manager']);

// Handle Form Submission Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_booking'])) {
    
    // Trim spaces from the code to ensure accurate checking
    $new_code = trim($_POST['BookingCode']);
    
    try {
        // 1. DUPLICATE CHECK: See if this Booking Code already exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_booking WHERE BookingCode = :code");
        $check_stmt->execute([':code' => $new_code]);
        $duplicate_count = $check_stmt->fetchColumn();

        if ($duplicate_count > 0) {
            // If it exists, block the save and show an error
            $error_msg = "Error: The Booking Code '<strong>" . htmlspecialchars($new_code) . "</strong>' already exists in the system!";
        } else {
            // 2. If unique, proceed to save the new booking
            $sql = "INSERT INTO tbl_booking (BookingDate, TourCompany, BookingCode, Pax, Meal, TourGuideName, TourGuideContact, Confirm) 
                    VALUES (:bdate, :company, :code, :pax, :meal, :guide, :contact, :confirm)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':bdate'   => $_POST['BookingDate'],
                ':company' => $_POST['TourCompany'],
                ':code'    => $new_code,
                ':pax'     => $_POST['Pax'],
                ':meal'    => $_POST['Meal'],
                ':guide'   => $_POST['TourGuideName'],
                ':contact' => $_POST['TourGuideContact'],
                ':confirm' => $_POST['Confirm']
            ]);
            
            $success_msg = "New booking for " . htmlspecialchars($_POST['TourCompany']) . " successfully added!";
            
            // Optional: Clear POST data so refreshing the page doesn't resubmit the form
            $_POST = array(); 
        }
    } catch(PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

// Include the layout AFTER processing the database logic
include 'sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0">Add New Booking</h2>
    <a href="all_bookings.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Master List</a>
</div>

<?php if(isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="fas fa-check-circle me-2"></i><?= $success_msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="fas fa-exclamation-triangle me-2"></i><?= $error_msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header bg-primary text-white py-3">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Enter Booking Details</h5>
    </div>
    <div class="card-body p-4">
        
        <form method="POST" action="add_booking.php">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Booking Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="BookingDate" value="<?= isset($_POST['BookingDate']) ? htmlspecialchars($_POST['BookingDate']) : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Tour Company <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="TourCompany" placeholder="e.g., G-Adventure" value="<?= isset($_POST['TourCompany']) ? htmlspecialchars($_POST['TourCompany']) : '' ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Booking Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($error_msg) ? 'is-invalid' : '' ?>" name="BookingCode" placeholder="Must be unique" value="<?= isset($_POST['BookingCode']) ? htmlspecialchars($_POST['BookingCode']) : '' ?>" required>
                    <?php if(isset($error_msg)): ?>
                        <div class="invalid-feedback">Please enter a different code.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fw-bold">Total Pax <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="Pax" min="1" value="<?= isset($_POST['Pax']) ? htmlspecialchars($_POST['Pax']) : '' ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fw-bold">Meal Type</label>
                    <select class="form-select" name="Meal">
                        <option value="Lunch" <?= (isset($_POST['Meal']) && $_POST['Meal'] == 'Lunch') ? 'selected' : '' ?>>Lunch</option>
                        <option value="Dinner" <?= (isset($_POST['Meal']) && $_POST['Meal'] == 'Dinner') ? 'selected' : '' ?>>Dinner</option>
                    </select>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Tour Guide Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="TourGuideName" placeholder="Name of guide or CEO" value="<?= isset($_POST['TourGuideName']) ? htmlspecialchars($_POST['TourGuideName']) : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Guide Contact Info <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="TourGuideContact" placeholder="Phone number or email" value="<?= isset($_POST['TourGuideContact']) ? htmlspecialchars($_POST['TourGuideContact']) : '' ?>" required>
                </div>
            </div>

            <div class="mb-4 bg-light p-3 rounded border">
                <label class="form-label fw-bold text-dark">Initial Confirmation Status</label>
                <select class="form-select w-50" name="Confirm">
                    <option value="False" <?= (isset($_POST['Confirm']) && $_POST['Confirm'] == 'False') ? 'selected' : '' ?>>Pending</option>
                    <option value="True" <?= (isset($_POST['Confirm']) && $_POST['Confirm'] == 'True') ? 'selected' : '' ?>>Confirmed</option>
                </select>
            </div>
            
            <hr class="mb-4">
            
            <div class="d-flex justify-content-between">
                <button type="reset" class="btn btn-outline-secondary px-4">Clear Form</button>
                <button type="submit" name="submit_booking" class="btn btn-primary px-5 fw-bold"><i class="fas fa-save me-2"></i>Save New Booking</button>
            </div>
            
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>