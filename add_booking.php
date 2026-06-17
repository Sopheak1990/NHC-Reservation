<?php
require_once 'db_connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_booking'])) {
    try {
        $sql = "INSERT INTO tbl_booking (BookingDate, TourCompany, BookingCode, Pax, Meal, TourGuideName, TourGuideContact, Confirm) 
                VALUES (:bdate, :company, :code, :pax, :meal, :guide, :contact, :confirm)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':bdate'   => $_POST['BookingDate'],
            ':company' => $_POST['TourCompany'],
            ':code'    => $_POST['BookingCode'],
            ':pax'     => $_POST['Pax'],
            ':meal'    => $_POST['Meal'],
            ':guide'   => $_POST['TourGuideName'],
            ':contact' => $_POST['TourGuideContact'],
            ':confirm' => $_POST['Confirm']
        ]);
        $success_msg = "Booking successfully added!";
    } catch(PDOException $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

include 'sidebar.php'; // Include layout AFTER database processing
?>

<?php if(isset($success_msg)): ?>
    <div class="alert alert-success"><?= $success_msg ?></div>
<?php endif; ?>
<?php if(isset($error_msg)): ?>
    <div class="alert alert-danger"><?= $error_msg ?></div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header bg-white">
        <h5 class="mb-0">Enter Tour Booking Details</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="add_booking.php">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Booking Date</label>
                    <input type="date" class="form-control" name="BookingDate" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tour Company</label>
                    <input type="text" class="form-control" name="TourCompany" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Booking Code</label>
                    <input type="text" class="form-control" name="BookingCode" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total Pax</label>
                    <input type="number" class="form-control" name="Pax" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Meal Type</label>
                    <select class="form-select" name="Meal">
                        <option value="Lunch">Lunch</option>
                        <option value="Dinner">Dinner</option>
                    </select>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Tour Guide Name</label>
                    <input type="text" class="form-control" name="TourGuideName" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Guide Contact Info</label>
                    <input type="text" class="form-control" name="TourGuideContact" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Status</label>
                <select class="form-select w-25" name="Confirm">
                    <option value="False">Pending</option>
                    <option value="True">Confirmed</option>
                </select>
            </div>
            <button type="submit" name="submit_booking" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Save Booking</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>