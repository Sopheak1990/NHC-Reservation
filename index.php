
<?php
require_once 'auth_check.php';
// All three roles are allowed on this page
restrict_to_roles(['super_admin', 'manager', 'normal_user']);
require_once 'db_connect.php';
include 'sidebar.php'; 

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';

$sql_condition = "";
$params = [];
$overview_title = "";
$card_label = "";

// SMART DATE EXPRESSION: Tells MySQL to understand both "MM/DD/YYYY" (from CSV) and "YYYY-MM-DD" (from forms)
$date_expr = "COALESCE(STR_TO_DATE(BookingDate, '%c/%e/%Y'), STR_TO_DATE(BookingDate, '%Y-%m-%d'), BookingDate)";

// Set queries based on timeframe filter using the smart date expression
switch ($filter) {
    case 'month':
        $overview_title = "This Month's Overview";
        $date_badge = date('F Y'); 
        $card_label = "This Month's";
        $sql_condition = "MONTH($date_expr) = :month AND YEAR($date_expr) = :year";
        $params = [':month' => date('n'), ':year' => date('Y')];
        break;
    
    case 'year':
        $overview_title = "This Year's Overview";
        $date_badge = date('Y'); 
        $card_label = "This Year's";
        $sql_condition = "YEAR($date_expr) = :year";
        $params = [':year' => date('Y')];
        break;

    case 'today':
    default:
        $overview_title = "Today's Overview";
        $date_badge = date('F j, Y'); // Formatted nicely for the dashboard header
        $card_label = "Today's";
        // Compare the actual calendar date, avoiding text-match errors
        $sql_condition = "DATE($date_expr) = :today";
        $params = [':today' => date('Y-m-d')];
        $filter = 'today'; 
        break;
}

// 1. Get Aggregates for the Top Cards
$dash_stmt = $conn->prepare("SELECT COUNT(*) as total_books, SUM(Pax) as total_pax FROM tbl_booking WHERE $sql_condition");
$dash_stmt->execute($params);
$dash_data = $dash_stmt->fetch(PDO::FETCH_ASSOC);

// 2. Get the actual list of tours for the selected timeframe (Sorted by the smart date)
$list_stmt = $conn->prepare("SELECT * FROM tbl_booking WHERE $sql_condition ORDER BY $date_expr ASC");
$list_stmt->execute($params);
$active_tours = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1"><?= $overview_title ?></h2>
        <span class="badge bg-secondary fs-6"><i class="far fa-calendar-alt me-2"></i><?= $date_badge ?></span>
    </div>
    
    <div class="btn-group shadow-sm" role="group">
        <a href="index.php?filter=today" class="btn <?= $filter == 'today' ? 'btn-primary' : 'btn-outline-primary' ?>">Today</a>
        <a href="index.php?filter=month" class="btn <?= $filter == 'month' ? 'btn-primary' : 'btn-outline-primary' ?>">Month</a>
        <a href="index.php?filter=year" class="btn <?= $filter == 'year' ? 'btn-primary' : 'btn-outline-primary' ?>">Year</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title"><?= $card_label ?> Bookings</h5>
                <h2><?= number_format($dash_data['total_books'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title"><?= $card_label ?> Guests (Pax)</h5>
                <h2><?= number_format($dash_data['total_pax'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-dark shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Quick Action</h5>
                <a href="add_booking.php" class="btn btn-dark mt-2"><i class="fas fa-plus"></i> Add New Tour</a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i> <?= $card_label ?> Tour Schedule</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>Date</th>
                        <th>Tour Company</th>
                        <th>Meal</th>
                        <th>Pax</th>
                        <th>Guide Info</th> 
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($active_tours) > 0): ?>
                        <?php foreach ($active_tours as $tour): ?>
                        <tr>
                            <td><?= date('l, d-m-Y', strtotime($tour['BookingDate'])) ?></td>
                            <td><strong class="text-primary"><?= htmlspecialchars($tour['TourCompany']) ?></strong></td>
                            <td>
                                <?php if($tour['Meal'] == 'Lunch'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-sun me-1"></i> Lunch</span>
                                <?php else: ?>
                                    <span class="badge bg-dark"><i class="fas fa-moon me-1"></i> Dinner</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($tour['Pax']) ?></strong></td>
                            
                            <td>
                                <?= htmlspecialchars($tour['TourGuideName']) ?><br>
                                <small class="text-muted"><i class="fas fa-phone-alt fa-sm me-1"></i> <?= htmlspecialchars($tour['TourGuideContact']) ?></small>
                            </td>

                            <td>
                                <?php if(trim($tour['Confirm']) == 'True'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> Confirmed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-4 text-muted">
                                No tours scheduled for this timeframe.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>