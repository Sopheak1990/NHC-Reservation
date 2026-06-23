<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
// All three roles are allowed on this page
restrict_to_roles(['super_admin', 'manager', 'normal_user']);

// Determine if the current user is allowed to edit or delete
$can_edit_delete = in_array($_SESSION['role'], ['super_admin', 'manager']);

// --- HANDLE DELETE ACTION ---
if ($can_edit_delete && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    try {
        $del_stmt = $conn->prepare("DELETE FROM tbl_booking WHERE BookingID = :id");
        $del_stmt->execute([':id' => $_POST['delete_id']]);
        $success_msg = "Booking #" . htmlspecialchars($_POST['delete_id']) . " has been securely deleted.";
    } catch(PDOException $e) {
        $error_msg = "Error deleting booking: " . $e->getMessage();
    }
}

// --- HANDLE UPDATE ACTION ---
if ($can_edit_delete && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_booking'])) {
    $new_code = trim($_POST['BookingCode']);
    $booking_id = $_POST['booking_id'];

    try {
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_booking WHERE BookingCode = :code AND BookingID != :id");
        $check_stmt->execute([':code' => $new_code, ':id' => $booking_id]);
        $duplicate_count = $check_stmt->fetchColumn();

        if ($duplicate_count > 0) {
            $error_msg = "Update Failed: The Booking Code '" . htmlspecialchars($new_code) . "' is already being used by another tour.";
        } else {
            $sql = "UPDATE tbl_booking SET 
                    BookingDate = :bdate, 
                    TourCompany = :company, 
                    BookingCode = :code, 
                    Pax = :pax, 
                    Meal = :meal, 
                    TourGuideName = :guide, 
                    TourGuideContact = :contact, 
                    Confirm = :confirm 
                    WHERE BookingID = :id";
                    
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':bdate'   => $_POST['BookingDate'],
                ':company' => $_POST['TourCompany'],
                ':code'    => $new_code,
                ':pax'     => $_POST['Pax'],
                ':meal'    => $_POST['Meal'],
                ':guide'   => $_POST['TourGuideName'],
                ':contact' => $_POST['TourGuideContact'],
                ':confirm' => $_POST['Confirm'],
                ':id'      => $booking_id 
            ]);
            $success_msg = "Booking #" . htmlspecialchars($booking_id) . " successfully updated!";
        }
    } catch(PDOException $e) {
        $error_msg = "Error updating booking: " . $e->getMessage();
    }
}

include 'sidebar.php'; 

// 1. Pagination Settings
$records_per_page = 30;
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// 2. Capture Form Inputs & Sort Input
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search_code = isset($_GET['search_code']) ? trim($_GET['search_code']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$sort_order = isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'ASC' : 'DESC'; 
$sort_val = isset($_GET['sort']) ? $_GET['sort'] : 'desc';

// Build URL query string to preserve all filters
$url_query = "&filter=" . urlencode($filter) . "&search_code=" . urlencode($search_code) . 
             "&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . 
             "&sort=" . urlencode($sort_val);

$sql_condition = "1=1"; 
$params = [];

// SMART DATE EXPRESSION
$date_expr = "COALESCE(STR_TO_DATE(BookingDate, '%c/%e/%Y'), STR_TO_DATE(BookingDate, '%Y-%m-%d'), BookingDate)";

// 3. Build the SQL condition
if ($search_code !== '') {
    $sql_condition .= " AND BookingCode LIKE :search_code";
    $params[':search_code'] = '%' . $search_code . '%';
}
if ($start_date !== '' && $end_date !== '') {
    $sql_condition .= " AND $date_expr BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date; 
    $params[':end_date'] = $end_date;
} elseif ($start_date !== '') {
    $sql_condition .= " AND $date_expr >= :start_date";
    $params[':start_date'] = $start_date;
} elseif ($end_date !== '') {
    $sql_condition .= " AND $date_expr <= :end_date";
    $params[':end_date'] = $end_date;
} else {
    if ($filter == 'month') {
        $sql_condition .= " AND MONTH($date_expr) = :month AND YEAR($date_expr) = :year";
        $params[':month'] = date('n');
        $params[':year'] = date('Y');
    } elseif ($filter == 'year') {
        $sql_condition .= " AND YEAR($date_expr) = :year";
        $params[':year'] = date('Y');
    }
}

// 4. Get TOTAL records
$total_stmt = $conn->prepare("SELECT COUNT(*) as total_books, SUM(Pax) as total_pax FROM tbl_booking WHERE $sql_condition");
foreach($params as $key => $val) { $total_stmt->bindValue($key, $val); }
$total_stmt->execute();
$total_data = $total_stmt->fetch(PDO::FETCH_ASSOC);

$total_records = $total_data['total_books'] ?? 0;
$total_pax = $total_data['total_pax'] ?? 0;
$total_pages = ceil($total_records / $records_per_page);
if ($total_pages == 0) $total_pages = 1; 

// 5. Fetch the records
$stmt = $conn->prepare("SELECT * FROM tbl_booking WHERE $sql_condition ORDER BY $date_expr $sort_order, BookingID $sort_order LIMIT :limit OFFSET :offset");
foreach($params as $key => $val) { $stmt->bindValue($key, $val); }
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Master List</h2>
    
    <div class="d-flex align-items-center gap-3">
        <div class="btn-group shadow-sm">
            <button onclick="exportExcel()" class="btn btn-sm btn-success fw-bold"><i class="fas fa-file-excel me-1"></i> Excel</button>
            <button onclick="exportPDF()" class="btn btn-sm btn-danger fw-bold"><i class="fas fa-file-pdf me-1"></i> PDF</button>
        </div>

        <div class="d-flex gap-2">
            <span class="badge bg-primary fs-6 py-2 shadow-sm"><i class="fas fa-ticket-alt me-1"></i> Bookings: <?= number_format($total_records) ?></span>
            <span class="badge bg-success fs-6 py-2 shadow-sm"><i class="fas fa-users me-1"></i> Pax: <?= number_format($total_pax) ?></span>
            <span class="badge bg-secondary fs-6 py-2 shadow-sm">Pages: <?= $total_pages ?></span>
        </div>
    </div>
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

<div class="card shadow-sm border-0 mb-3 bg-light">
    <div class="card-body py-2">
        <form method="GET" action="all_bookings.php" class="row gx-2 gy-2 align-items-center">
            <div class="col-md-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" name="search_code" class="form-control" placeholder="Search Code..." value="<?= htmlspecialchars($search_code) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <input type="date" name="start_date" class="form-control form-control-sm" placeholder="From Date" value="<?= htmlspecialchars($start_date) ?>" title="Start Date">
            </div>
            <div class="col-md-2">
                <input type="date" name="end_date" class="form-control form-control-sm" placeholder="To Date" value="<?= htmlspecialchars($end_date) ?>" title="End Date">
            </div>
            <div class="col-md-2">
                <select name="filter" class="form-select form-select-sm" title="Quick Filter">
                    <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="month" <?= $filter == 'month' ? 'selected' : '' ?>>This Month</option>
                    <option value="year" <?= $filter == 'year' ? 'selected' : '' ?>>This Year</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select form-select-sm" title="Sort by Date">
                    <option value="desc" <?= $sort_val == 'desc' ? 'selected' : '' ?>>Newest First</option>
                    <option value="asc" <?= $sort_val == 'asc' ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-50">Apply</button>
                <a href="all_bookings.php" class="btn btn-outline-secondary btn-sm w-50" title="Clear All">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="bookingTable" class="table table-hover table-striped mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Company</th>
                        <th>Code</th>
                        <th>Pax</th>
                        <th>Meal</th>
                        <th>Guide</th>
                        <th>Status</th>
                        <?php if ($can_edit_delete): ?>
                            <th class="text-center">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['BookingID']) ?></td>
                            <td><?= date('l, d-m-Y', strtotime($row['BookingDate'])) ?></td>
                            <td><strong><?= htmlspecialchars($row['TourCompany']) ?></strong></td>
                            <td><?= htmlspecialchars($row['BookingCode']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['Pax']) ?></span></td>
                            <td>
                                <?php if($row['Meal'] == 'Lunch'): ?>
                                    <span class="badge bg-light text-dark border"><i class="fas fa-sun text-warning me-1"></i> Lunch</span>
                                <?php else: ?>
                                    <span class="badge bg-dark"><i class="fas fa-moon text-light me-1"></i> Dinner</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['TourGuideName']) ?><br>
                                <small class="text-muted"><i class="fas fa-phone-alt fa-sm me-1"></i> <?= htmlspecialchars($row['TourGuideContact']) ?></small>
                            </td>
                            <td>
                                <?php if(trim($row['Confirm']) == 'True'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> Confirmed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            
                            <?php if ($can_edit_delete): ?>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['BookingID'] ?>" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['BookingID'] ?>" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>

                        <?php if ($can_edit_delete): ?>
                        <div class="modal fade" id="editModal<?= $row['BookingID'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Booking #<?= $row['BookingID'] ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="all_bookings.php?p=<?= $page ?><?= $url_query ?>">
                                            <input type="hidden" name="booking_id" value="<?= $row['BookingID'] ?>">
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted fw-bold">Booking Date</label>
                                                    <input type="date" class="form-control" name="BookingDate" value="<?= date('Y-m-d', strtotime($row['BookingDate'])) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted fw-bold">Tour Company</label>
                                                    <input type="text" class="form-control" name="TourCompany" value="<?= htmlspecialchars($row['TourCompany']) ?>" required>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted fw-bold">Booking Code</label>
                                                    <input type="text" class="form-control" name="BookingCode" value="<?= htmlspecialchars($row['BookingCode']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label text-muted fw-bold">Total Pax</label>
                                                    <input type="number" class="form-control" name="Pax" value="<?= htmlspecialchars($row['Pax']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label text-muted fw-bold">Meal Type</label>
                                                    <select class="form-select" name="Meal">
                                                        <option value="Lunch" <?= $row['Meal'] == 'Lunch' ? 'selected' : '' ?>>Lunch</option>
                                                        <option value="Dinner" <?= $row['Meal'] == 'Dinner' ? 'selected' : '' ?>>Dinner</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted fw-bold">Tour Guide Name</label>
                                                    <input type="text" class="form-control" name="TourGuideName" value="<?= htmlspecialchars($row['TourGuideName']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted fw-bold">Guide Contact Info</label>
                                                    <input type="text" class="form-control" name="TourGuideContact" value="<?= htmlspecialchars($row['TourGuideContact']) ?>" required>
                                                </div>
                                            </div>

                                            <div class="mb-4 bg-light p-3 rounded">
                                                <label class="form-label fw-bold text-dark">Confirmation Status</label>
                                                <select class="form-select w-50" name="Confirm">
                                                    <option value="False" <?= trim($row['Confirm']) == 'False' ? 'selected' : '' ?>>Pending (False)</option>
                                                    <option value="True" <?= trim($row['Confirm']) == 'True' ? 'selected' : '' ?>>Confirmed (True)</option>
                                                </select>
                                            </div>
                                            
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_booking" class="btn btn-success"><i class="fas fa-save me-2"></i>Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="deleteModal<?= $row['BookingID'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center py-4">
                                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                                        <p class="fs-5 mb-1">Are you sure you want to delete Booking <strong>#<?= htmlspecialchars($row['BookingID']) ?></strong>?</p>
                                        <p class="text-muted mb-0"><strong>Code:</strong> <?= htmlspecialchars($row['BookingCode']) ?></p>
                                        <p class="text-muted"><strong>Date:</strong> <?= date('d-m-Y', strtotime($row['BookingDate'])) ?></p>
                                        <p class="text-danger small mt-3 mb-0"><i class="fas fa-info-circle me-1"></i>This action cannot be undone.</p>
                                    </div>
                                    <div class="modal-footer justify-content-center">
                                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                                        <form method="POST" action="all_bookings.php?p=<?= $page ?><?= $url_query ?>">
                                            <input type="hidden" name="delete_id" value="<?= $row['BookingID'] ?>">
                                            <button type="submit" class="btn btn-danger px-4">Yes, Delete It</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center p-4 text-muted">No bookings match your search.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white py-3">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page - 1 ?><?= $url_query ?>">Previous</a>
                </li>
                <?php 
                $start = max(1, $page - 2); $end = min($total_pages, $page + 2);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?p=<?= $i ?><?= $url_query ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page + 1 ?><?= $url_query ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
function getCleanTable() {
    let table = document.getElementById("bookingTable");
    let cloneTable = table.cloneNode(true);
    
    <?php if ($can_edit_delete): ?>
    // Only delete the last column if the user actually has the Actions column!
    for(let i = 0; i < cloneTable.rows.length; i++) {
        cloneTable.rows[i].deleteCell(-1); 
    }
    <?php endif; ?>
    
    return cloneTable;
}

function exportExcel() {
    let wb = XLSX.utils.table_to_book(getCleanTable(), {sheet: "Bookings"});
    XLSX.writeFile(wb, "NHC_Filtered_Bookings.xlsx");
}

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape'); 
    
    doc.setFontSize(16);
    doc.setTextColor(40);
    doc.text("NHC Reservation - Filtered Master List", 14, 20);
    
    doc.setFontSize(11);
    doc.setTextColor(100); 
    doc.text("Total Bookings: <?= number_format($total_records) ?>   |   Total Pax: <?= number_format($total_pax) ?>", 14, 28);
    
    doc.autoTable({
        html: getCleanTable(),
        startY: 34, 
        theme: 'grid',
        styles: { fontSize: 9 },
        headStyles: { fillColor: [13, 110, 253] } 
    });
    
    doc.save('NHC_Filtered_Bookings.pdf');
}
</script>

<?php include 'footer.php'; ?>