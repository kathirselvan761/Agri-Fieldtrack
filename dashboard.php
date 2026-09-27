<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
checkLoggedIn();

$pageTitle = __('dashboard', 'Dashboard');
require_once __DIR__ . '/includes/header.php';

// Fetch KPI Metrics from Database
try {
    // 1. Tractors Counts
    $totTractors = $conn->query("SELECT COUNT(*) FROM tractors")->fetchColumn();
    $activeTractors = $conn->query("SELECT COUNT(*) FROM tractors WHERE status = 'Working'")->fetchColumn();
    $availableTractors = $conn->query("SELECT COUNT(*) FROM tractors WHERE status = 'Available'")->fetchColumn();

    // 2. Bookings Count Today
    $todayBookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE booking_date = CURRENT_DATE()")->fetchColumn();

    // 3. Customers & Drivers Count
    $totCustomers = $conn->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $totDrivers = $conn->query("SELECT COUNT(*) FROM drivers WHERE status = 'Active'")->fetchColumn();

    // 4. Financial Calculations
    $todayIncome = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM work_entries WHERE entry_date = CURRENT_DATE()")->fetchColumn();
    $weeklyIncome = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM work_entries WHERE entry_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)")->fetchColumn();
    $monthlyIncome = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM work_entries WHERE MONTH(entry_date) = MONTH(CURRENT_DATE()) AND YEAR(entry_date) = YEAR(CURRENT_DATE())")->fetchColumn();
    $yearlyIncome = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM work_entries WHERE YEAR(entry_date) = YEAR(CURRENT_DATE())")->fetchColumn();
    $pendingPayments = $conn->query("SELECT COALESCE(SUM(balance_amount), 0) FROM work_entries WHERE payment_status != 'Paid'")->fetchColumn();

    // 5. Fetch Recent Work Entries (Top 5)
    $recentWork = $conn->query("
        SELECT w.*, c.name as customer_name, t.name as tractor_name, wt.name as work_type_name
        FROM work_entries w
        JOIN customers c ON w.customer_id = c.id
        JOIN tractors t ON w.tractor_id = t.id
        JOIN work_types wt ON w.work_type_id = wt.id
        ORDER BY w.entry_date DESC, w.id DESC LIMIT 5
    ")->fetchAll();

    // 6. Fetch Upcoming Bookings (Top 5)
    $recentBookings = $conn->query("
        SELECT b.*, c.name as customer_name, t.name as tractor_name, wt.name as work_type_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN tractors t ON b.tractor_id = t.id
        JOIN work_types wt ON b.work_type_id = wt.id
        ORDER BY b.booking_date ASC LIMIT 5
    ")->fetchAll();

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>" . __('error_occurred', 'Error fetching metrics:') . " " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('app_name', 'AgriTrak ERP'); ?> - <?php echo __('dashboard', 'Dashboard'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('dashboard_overview', 'Overview of fleet operations, daily bookings, and income analytics.'); ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo SITE_URL; ?>/work_entries/index.php?action=new" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
      <i class="bi bi-plus-lg"></i> <?php echo __('new_work_entry', 'Record Daily Work'); ?>
    </a>
    <a href="<?php echo SITE_URL; ?>/reports/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
      <i class="bi bi-printer"></i> <?php echo __('reports_analytics', 'Financial Report'); ?>
    </a>
  </div>
</div>

<!-- 1. KPI Cards Grid - Fleet & Operations -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card-erp p-3">
      <div class="d-flex align-items-center justify-content-between">
        <span class="stat-label"><?php echo __('total_tractors', 'Total Tractors'); ?></span>
        <div class="stat-icon-wrapper bg-primary-light"><i class="fa-solid fa-tractor"></i></div>
      </div>
      <div class="stat-value text-primary"><?php echo number_format($totTractors); ?></div>
      <small class="text-muted fs-7"><?php echo __('tractors', 'Fleet count'); ?></small>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-2">
    <div class="card-erp p-3">
      <div class="d-flex align-items-center justify-content-between">
        <span class="stat-label"><?php echo __('active', 'Active'); ?></span>
        <div class="stat-icon-wrapper bg-info-light"><i class="bi bi-gear-wide-connected"></i></div>
      </div>
      <div class="stat-value text-info"><?php echo number_format($activeTractors); ?></div>
      <small class="text-muted fs-7"><?php echo __('active', 'On field right now'); ?></small>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-2">
    <div class="card-erp p-3">
      <div class="d-flex align-items-center justify-content-between">
        <span class="stat-label"><?php echo __('status', 'Status'); ?></span>
        <div class="stat-icon-wrapper bg-success-light"><i class="bi bi-check-circle-fill"></i></div>
      </div>
      <div class="stat-value text-success"><?php echo number_format($availableTractors); ?></div>
      <small class="text-muted fs-7"><?php echo __('confirmed', 'Ready for booking'); ?></small>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-2">
    <div class="card-erp p-3">
      <div class="d-flex align-items-center justify-content-between">
        <span class="stat-label"><?php echo __('bookings_calendar', "Today's Bookings"); ?></span>
        <div class="stat-icon-wrapper bg-warning-light"><i class="bi bi-calendar-event"></i></div>
      </div>
      <div class="stat-value text-warning"><?php echo number_format($todayBookings); ?></div>
      <small class="text-muted fs-7"><?php echo __('bookings_calendar', 'Scheduled today'); ?></small>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-2">
    <div class="card-erp p-3">
      <div class="d-flex align-items-center justify-content-between">
        <span class="stat-label"><?php echo __('total_customers', 'Total Customers'); ?></span>
        <div class="stat-icon-wrapper bg-primary-light"><i class="bi bi-people-fill"></i></div>
      </div>
      <div class="stat-value"><?php echo number_format($totCustomers); ?></div>
      <small class="text-muted fs-7"><?php echo __('customers', 'Registered farmers'); ?></small>
    </div>
  </div>

  <div class="col-6 col-md-4 col-xl-2">
    <div class="card-erp p-3">
      <div class="d-flex align-items-center justify-content-between">
        <span class="stat-label"><?php echo __('active_drivers', 'Active Drivers'); ?></span>
        <div class="stat-icon-wrapper bg-secondary-light"><i class="bi bi-person-vcard"></i></div>
      </div>
      <div class="stat-value"><?php echo number_format($totDrivers); ?></div>
      <small class="text-muted fs-7"><?php echo __('drivers', 'On duty drivers'); ?></small>
    </div>
  </div>
</div>

<!-- 2. KPI Cards Grid - Financial Income Summaries -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card-erp p-3 border-start border-4 border-success">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-success"><?php echo __('total_earnings', "Today's Income"); ?></span>
          <div class="stat-value text-success"><?php echo formatCurrency($todayIncome); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-success-light"><i class="bi bi-cash-coin"></i></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card-erp p-3 border-start border-4 border-primary">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-primary"><?php echo __('monthly_revenue', 'Weekly Income'); ?></span>
          <div class="stat-value text-primary"><?php echo formatCurrency($weeklyIncome); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-primary-light"><i class="bi bi-graph-up-arrow"></i></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card-erp p-3 border-start border-4 border-info">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-info"><?php echo __('monthly_revenue', 'Monthly Income'); ?></span>
          <div class="stat-value text-info"><?php echo formatCurrency($monthlyIncome); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-info-light"><i class="bi bi-calendar3"></i></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card-erp p-3 border-start border-4 border-danger">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-danger"><?php echo __('pending_dues', 'Pending Payments'); ?></span>
          <div class="stat-value text-danger"><?php echo formatCurrency($pendingPayments); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-danger-light"><i class="bi bi-exclamation-octagon"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- 3. Dashboard Charts Section -->
<div class="row g-3 mb-4">
  <!-- Chart 1: Revenue Trends -->
  <div class="col-12 col-lg-8">
    <div class="card-erp p-4 h-100">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0 brand-font"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i><?php echo __('revenue_trend', 'Income Trends (Daily & Monthly)'); ?></h5>
        <span class="badge bg-primary-light text-primary"><?php echo __('app_name', 'AgriTrak'); ?></span>
      </div>
      <div style="height: 300px; position: relative;">
        <canvas id="incomeChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 2: Tractor Usage Breakdown -->
  <div class="col-12 col-lg-4">
    <div class="card-erp p-4 h-100">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0 brand-font"><i class="bi bi-pie-chart-fill text-warning me-2"></i><?php echo __('fleet_utilization', 'Tractor Fleet Usage'); ?></h5>
      </div>
      <div style="height: 300px; position: relative;" class="d-flex align-items-center justify-content-center">
        <canvas id="tractorUsageChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- 4. Recent Activities Tables -->
<div class="row g-3 mb-4">
  <!-- Recent Work Entries -->
  <div class="col-12 col-lg-7">
    <div class="card-erp p-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0 brand-font"><i class="bi bi-clock-history me-2 text-primary"></i><?php echo __('recent_work_entries', 'Recent Work Entries'); ?></h5>
        <a href="<?php echo SITE_URL; ?>/work_entries/index.php" class="btn btn-sm btn-light"><?php echo __('view_all', 'View All'); ?></a>
      </div>
      <div class="table-responsive">
        <table class="table table-custom align-middle">
          <thead>
            <tr>
              <th><?php echo __('date', 'Date'); ?></th>
              <th><?php echo __('customers', 'Customer'); ?></th>
              <th><?php echo __('tractors', 'Tractor'); ?></th>
              <th><?php echo __('work_types', 'Work Type'); ?></th>
              <th><?php echo __('total', 'Total'); ?></th>
              <th><?php echo __('status', 'Status'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentWork)): ?>
              <tr><td colspan="6" class="text-center text-muted py-3"><?php echo __('no_records_found', 'No recent work entries logged.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($recentWork as $w): ?>
                <tr>
                  <td class="fw-medium"><?php echo formatDate($w['entry_date'], 'd M'); ?></td>
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($w['customer_name']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars($w['village']); ?></small>
                  </td>
                  <td><?php echo htmlspecialchars($w['tractor_name']); ?></td>
                  <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($w['work_type_name']); ?></span></td>
                  <td class="fw-bold"><?php echo formatCurrency($w['total_amount']); ?></td>
                  <td>
                    <?php
                    $st = strtolower($w['payment_status']);
                    $badgeClass = $st === 'paid' ? 'badge-paid' : ($st === 'partial' ? 'badge-partial' : 'badge-pending');
                    $translatedStatus = $st === 'paid' ? __('paid', 'Paid') : ($st === 'partial' ? __('partial', 'Partial') : __('pending', 'Pending'));
                    ?>
                    <span class="badge-status <?php echo $badgeClass; ?>"><?php echo $translatedStatus; ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Upcoming Bookings -->
  <div class="col-12 col-lg-5">
    <div class="card-erp p-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0 brand-font"><i class="bi bi-calendar-check me-2 text-success"></i><?php echo __('bookings_calendar', 'Upcoming Bookings'); ?></h5>
        <a href="<?php echo SITE_URL; ?>/bookings/index.php" class="btn btn-sm btn-light"><?php echo __('view_all', 'View Calendar'); ?></a>
      </div>
      <div class="table-responsive">
        <table class="table table-custom align-middle">
          <thead>
            <tr>
              <th><?php echo __('date', 'Date'); ?></th>
              <th><?php echo __('customers', 'Customer'); ?></th>
              <th><?php echo __('tractors', 'Tractor'); ?></th>
              <th><?php echo __('status', 'Status'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentBookings)): ?>
              <tr><td colspan="4" class="text-center text-muted py-3"><?php echo __('no_records_found', 'No upcoming bookings.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($recentBookings as $b): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?php echo formatDate($b['booking_date'], 'd M Y'); ?></div>
                    <small class="text-muted"><?php echo date('h:i A', strtotime($b['start_time'])); ?></small>
                  </td>
                  <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                  <td><?php echo htmlspecialchars($b['tractor_name']); ?></td>
                  <td>
                    <span class="badge bg-success-light text-success"><?php echo __($b['status'], $b['status']); ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Dashboard Chart.js Scripts -->
<script src="<?php echo SITE_URL; ?>/assets/js/dashboard.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
