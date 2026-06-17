<?php
require_once 'db_connect.php';
include 'sidebar.php'; 

// 1. Pagination Settings
$records_per_page = 30;
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// 2. Capture Form Inputs
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search_code = isset($_GET['search_code']) ? trim($_GET['search_code']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Generate a query string to easily attach to pagination links
$url_query = "&filter=" . urlencode($filter) . "&search_code=" . urlencode($search_code) . "&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date);

$sql_condition = "1=1"; 
$params = [];

// 3. Build the SQL condition
if ($search_code !== '') {
    $sql_condition .= " AND BookingCode LIKE :search_code";
    $params[':search_code'] = '%' . $search_code . '%';
}

if ($start_date !== '' && $end_date !== '') {
    $sql_condition .= " AND STR_TO_DATE(BookingDate, '%c/%e/%Y') BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date; 
    $params[':end_date'] = $end_date;
} elseif ($start_date !== '') {
    $sql_condition .= " AND STR_TO_DATE(BookingDate, '%c/%e/%Y') >= :start_date";
    $params[':start_date'] = $start_date;
} elseif ($end_date !== '') {
    $sql_condition .= " AND STR_TO_DATE(BookingDate, '%c/%e/%Y') <= :end_date";
    $params[':end_date'] = $end_date;
} else {
    if ($filter == 'month') {
        $sql_condition .= " AND MONTH(STR_TO_DATE(BookingDate, '%c/%e/%Y')) = :month AND YEAR(STR_TO_DATE(BookingDate, '%c/%e/%Y')) = :year";
        $params[':month'] = date('n');
        $params[':year'] = date('Y');
    } elseif ($filter == 'year') {
        $sql_condition .= " AND YEAR(STR_TO_DATE(BookingDate, '%c/%e/%Y')) = :year";
        $params[':year'] = date('Y');
    }
}

// 4. UPDATED: Get TOTAL records AND TOTAL Pax for the current filter
$total_stmt = $conn->prepare("SELECT COUNT(*) as total_books, SUM(Pax) as total_pax FROM tbl_booking WHERE $sql_condition");
foreach($params as $key => $val) {
    $total_stmt->bindValue($key, $val);
}
$total_stmt->execute();
$total_data = $total_stmt->fetch(PDO::FETCH_ASSOC);

$total_records = $total_data['total_books'] ?? 0;
$total_pax = $total_data['total_pax'] ?? 0; // Default to 0 if null

$total_pages = ceil($total_records / $records_per_page);
if ($total_pages == 0) $total_pages = 1; 

// 5. Fetch the records for the table
$stmt = $conn->prepare("SELECT * FROM tbl_booking WHERE $sql_condition ORDER BY STR_TO_DATE(BookingDate, '%c/%e/%Y') DESC LIMIT :limit OFFSET :offset");
foreach($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Master List</h2>
    
    <div class="d-flex gap-2">
        <span class="badge bg-primary fs-6 py-2 shadow-sm" title="Filtered Bookings">
            <i class="fas fa-ticket-alt me-1"></i> Bookings: <?= number_format($total_records) ?>
        </span>
        <span class="badge bg-success fs-6 py-2 shadow-sm" title="Filtered Guests">
            <i class="fas fa-users me-1"></i> Total Pax: <?= number_format($total_pax) ?>
        </span>
        <span class="badge bg-secondary fs-6 py-2 shadow-sm" title="Total Pages">
            Pages: <?= $total_pages ?>
        </span>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3 bg-light">
    <div class="card-body py-2">
        <form method="GET" action="all_bookings.php" class="row gx-2 gy-2 align-items-center">
            
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" name="search_code" class="form-control" placeholder="Search Booking Code..." value="<?= htmlspecialchars($search_code) ?>">
                </div>
            </div>

            <div class="col-md-2">
                <input type="date" name="start_date" class="form-control form-control-sm" placeholder="From Date" value="<?= htmlspecialchars($start_date) ?>" title="Start Date">
            </div>
            
            <div class="col-md-2">
                <input type="date" name="end_date" class="form-control form-control-sm" placeholder="To Date" value="<?= htmlspecialchars($end_date) ?>" title="End Date">
            </div>

            <div class="col-md-2">
                <select name="filter" class="form-select form-select-sm">
                    <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="month" <?= $filter == 'month' ? 'selected' : '' ?>>This Month</option>
                    <option value="year" <?= $filter == 'year' ? 'selected' : '' ?>>This Year</option>
                </select>
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100 mb-1"><i class="fas fa-filter me-1"></i> Apply Filter</button>
                <a href="all_bookings.php" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Company</th>
                        <th>Code</th>
                        <th>Pax</th>
                        <th>Meal</th>
                        <th>Guide</th>
                        <th>Confirmed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['BookingID']) ?></td>
                            <td><?= htmlspecialchars($row['BookingDate']) ?></td>
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
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center p-4 text-muted">No bookings match your search or filter criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white py-3">
        <nav aria-label="Booking pagination">
            <ul class="pagination justify-content-center mb-0">
                
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=1<?= $url_query ?>" aria-label="First">
                        <span aria-hidden="true">&laquo; First</span>
                    </a>
                </li>
                
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page - 1 ?><?= $url_query ?>" aria-label="Previous">
                        <span aria-hidden="true">Previous</span>
                    </a>
                </li>

                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?p=<?= $i ?><?= $url_query ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page + 1 ?><?= $url_query ?>" aria-label="Next">
                        <span aria-hidden="true">Next</span>
                    </a>
                </li>
                
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $total_pages ?><?= $url_query ?>" aria-label="Last">
                        <span aria-hidden="true">Last &raquo;</span>
                    </a>
                </li>
                
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>