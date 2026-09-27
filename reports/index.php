<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $from_date = $_GET['from_date'] ?? date('Y-m-01');
    $to_date = $_GET['to_date'] ?? date('Y-m-d');
    $tractor_id = $_GET['tractor_id'] ?? '';
    $customer_id = $_GET['customer_id'] ?? '';
    $driver_id = $_GET['driver_id'] ?? '';
    $work_type_id = $_GET['work_type_id'] ?? '';

    $where = ["w.entry_date BETWEEN '$from_date' AND '$to_date'"];
    if (!empty($tractor_id)) $where[] = "w.tractor_id = " . intval($tractor_id);
    if (!empty($customer_id)) $where[] = "w.customer_id = " . intval($customer_id);
    if (!empty($driver_id)) $where[] = "w.driver_id = " . intval($driver_id);
    if (!empty($work_type_id)) $where[] = "w.work_type_id = " . intval($work_type_id);

    $sql = "
        SELECT w.entry_date, c.name as customer_name, w.village, t.name as tractor_name, 
               d.name as driver_name, wt.name as work_type_name, w.quantity, w.rate, 
               w.total_amount, w.advance_amount, w.balance_amount, w.payment_status
        FROM work_entries w
        JOIN customers c ON w.customer_id = c.id
        JOIN tractors t ON w.tractor_id = t.id
        LEFT JOIN drivers d ON w.driver_id = d.id
        JOIN work_types wt ON w.work_type_id = wt.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY w.entry_date ASC
    ";

    $rows = $conn->query($sql)->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tractor_erp_report_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [__('work_date', 'Entry Date'), __('customers', 'Customer'), __('village', 'Village'), __('tractors', 'Tractor'), __('drivers', 'Driver'), __('work_types', 'Work Type'), __('quantity', 'Quantity'), __('rate', 'Rate'), __('total', 'Total (INR)'), __('advance', 'Advance (INR)'), __('balance', 'Balance (INR)'), __('status', 'Payment Status')]);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['entry_date'], $r['customer_name'], $r['village'], $r['tractor_name'],
            $r['driver_name'] ?: 'Unassigned', $r['work_type_name'], $r['quantity'],
            $r['rate'], $r['total_amount'], $r['advance_amount'], $r['balance_amount'], $r['payment_status']
        ]);
    }
    fclose($output);
    exit();
}

$pageTitle = __('reports_analytics', 'Reports & Analytics');
require_once __DIR__ . '/../includes/header.php';

// Default Filter Parameters
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');
$filterTractor = $_GET['tractor_id'] ?? '';
$filterCustomer = $_GET['customer_id'] ?? '';
$filterDriver = $_GET['driver_id'] ?? '';
$filterWorkType = $_GET['work_type_id'] ?? '';

// Build Query
$whereClause = ["w.entry_date BETWEEN " . $conn->quote($fromDate) . " AND " . $conn->quote($toDate)];
if (!empty($filterTractor)) $whereClause[] = "w.tractor_id = " . intval($filterTractor);
if (!empty($filterCustomer)) $whereClause[] = "w.customer_id = " . intval($filterCustomer);
if (!empty($filterDriver)) $whereClause[] = "w.driver_id = " . intval($filterDriver);
if (!empty($filterWorkType)) $whereClause[] = "w.work_type_id = " . intval($filterWorkType);

$sqlReport = "
    SELECT w.*, c.name as customer_name, c.village, t.name as tractor_name, 
           d.name as driver_name, wt.name as work_type_name, wt.unit_type
    FROM work_entries w
    JOIN customers c ON w.customer_id = c.id
    JOIN tractors t ON w.tractor_id = t.id
    LEFT JOIN drivers d ON w.driver_id = d.id
    JOIN work_types wt ON w.work_type_id = wt.id
    WHERE " . implode(" AND ", $whereClause) . "
    ORDER BY w.entry_date DESC
";

$reportData = $conn->query($sqlReport)->fetchAll();

// Calculated Metrics for Filter Range
$totalRevenue = 0;
$totalAdvance = 0;
$totalBalance = 0;
foreach ($reportData as $r) {
    $totalRevenue += $r['total_amount'];
    $totalAdvance += $r['advance_amount'];
    $totalBalance += $r['balance_amount'];
}

// Fetch Dropdown Select Options
$tractors = $conn->query("SELECT * FROM tractors ORDER BY name ASC")->fetchAll();
$customers = $conn->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
$drivers = $conn->query("SELECT * FROM drivers WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
$workTypes = $conn->query("SELECT * FROM work_types ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('reports_analytics', 'Reports & Financial Analytics'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('reports_management', 'Generate date range, entity-wise tractor work statements, CSV exports, and print reports.'); ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="index.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
      <i class="bi bi-file-earmark-excel"></i> Export Excel CSV
    </a>
    <button onclick="window.print()" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
      <i class="bi bi-printer"></i> <?php echo __('print', 'Print Statement'); ?>
    </button>
  </div>
</div>

<!-- Multi-Filter Control Panel -->
<div class="card-erp p-4 mb-4 no-print">
  <form method="GET" action="index.php" class="row g-3">
    <div class="col-md-3">
      <label class="form-label fw-semibold"><?php echo __('from_date', 'From Date'); ?></label>
      <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($fromDate); ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold"><?php echo __('to_date', 'To Date'); ?></label>
      <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($toDate); ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold"><?php echo __('select_tractor', 'Tractor Filter'); ?></label>
      <select name="tractor_id" class="form-select form-select-sm">
        <option value=""><?php echo __('all', 'All Tractors'); ?></option>
        <?php foreach ($tractors as $t): ?>
          <option value="<?php echo $t['id']; ?>" <?php echo $filterTractor == $t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold"><?php echo __('select_customer', 'Customer Filter'); ?></label>
      <select name="customer_id" class="form-select form-select-sm">
        <option value=""><?php echo __('all', 'All Customers'); ?></option>
        <?php foreach ($customers as $c): ?>
          <option value="<?php echo $c['id']; ?>" <?php echo $filterCustomer == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label fw-semibold"><?php echo __('select_driver', 'Driver Filter'); ?></label>
      <select name="driver_id" class="form-select form-select-sm">
        <option value=""><?php echo __('all', 'All Drivers'); ?></option>
        <?php foreach ($drivers as $d): ?>
          <option value="<?php echo $d['id']; ?>" <?php echo $filterDriver == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label fw-semibold"><?php echo __('select_work_type', 'Work Type Filter'); ?></label>
      <select name="work_type_id" class="form-select form-select-sm">
        <option value=""><?php echo __('all', 'All Work Types'); ?></option>
        <?php foreach ($workTypes as $wt): ?>
          <option value="<?php echo $wt['id']; ?>" <?php echo $filterWorkType == $wt['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($wt['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6 d-flex align-items-end gap-2">
      <button type="submit" class="btn btn-primary btn-sm w-50"><i class="bi bi-filter me-1"></i> <?php echo __('apply_filter', 'Apply Filters'); ?></button>
      <a href="index.php" class="btn btn-outline-secondary btn-sm w-50"><?php echo __('cancel', 'Reset Filters'); ?></a>
    </div>
  </form>
</div>

<!-- Report Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="card-erp p-3 border-start border-4 border-primary">
      <span class="stat-label text-primary"><?php echo __('total_billed', 'Statement Total Billed'); ?></span>
      <div class="stat-value text-primary"><?php echo formatCurrency($totalRevenue); ?></div>
      <small class="text-muted"><?php echo count($reportData); ?> <?php echo __('work_entries', 'Work Entries'); ?></small>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card-erp p-3 border-start border-4 border-success">
      <span class="stat-label text-success"><?php echo __('advance', 'Advance Collected'); ?></span>
      <div class="stat-value text-success"><?php echo formatCurrency($totalAdvance); ?></div>
      <small class="text-muted"><?php echo __('payments', 'Total payments received'); ?></small>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card-erp p-3 border-start border-4 border-danger">
      <span class="stat-label text-danger"><?php echo __('balance', 'Outstanding Balance'); ?></span>
      <div class="stat-value text-danger"><?php echo formatCurrency($totalBalance); ?></div>
      <small class="text-muted"><?php echo __('pending', 'Pending customer dues'); ?></small>
    </div>
  </div>
</div>

<!-- Report Data Table -->
<div class="card-erp p-4" id="printableArea">
  <div class="d-none d-print-block mb-4 text-center">
    <h2 class="fw-bold"><?php echo htmlspecialchars($company['company_name']); ?></h2>
    <h4>OPERATIONAL FINANCIAL STATEMENT</h4>
    <p>Period: <?php echo formatDate($fromDate); ?> to <?php echo formatDate($toDate); ?></p>
    <hr>
  </div>

  <div class="table-responsive">
    <table class="table table-custom align-middle">
      <thead>
        <tr>
          <th><?php echo __('date', 'Date'); ?></th>
          <th><?php echo __('customers', 'Customer'); ?></th>
          <th><?php echo __('village', 'Village'); ?></th>
          <th><?php echo __('tractors', 'Tractor'); ?></th>
          <th><?php echo __('drivers', 'Driver'); ?></th>
          <th><?php echo __('work_types', 'Work Type'); ?></th>
          <th><?php echo __('quantity', 'Qty'); ?></th>
          <th><?php echo __('rate', 'Rate'); ?></th>
          <th><?php echo __('total', 'Total'); ?></th>
          <th><?php echo __('balance', 'Balance'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reportData)): ?>
          <tr><td colspan="10" class="text-center text-muted py-4"><?php echo __('no_records', 'No records found matching the specified filters.'); ?></td></tr>
        <?php else: ?>
          <?php foreach ($reportData as $r): ?>
            <tr>
              <td><?php echo formatDate($r['entry_date'], 'd M Y'); ?></td>
              <td class="fw-bold"><?php echo htmlspecialchars($r['customer_name']); ?></td>
              <td><?php echo htmlspecialchars($r['village']); ?></td>
              <td><?php echo htmlspecialchars($r['tractor_name']); ?></td>
              <td><?php echo htmlspecialchars($r['driver_name'] ?: '-'); ?></td>
              <td><?php echo htmlspecialchars($r['work_type_name']); ?></td>
              <td><?php echo $r['quantity'] . ' ' . $r['unit_type']; ?></td>
              <td>₹<?php echo number_format($r['rate']); ?></td>
              <td class="fw-bold"><?php echo formatCurrency($r['total_amount']); ?></td>
              <td class="fw-bold text-danger"><?php echo formatCurrency($r['balance_amount']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
