<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
checkLoggedIn();

$pageTitle = __('global_search', 'Global Search');
require_once __DIR__ . '/includes/header.php';

$query = trim($_GET['q'] ?? '');

$foundTractors = [];
$foundCustomers = [];
$foundDrivers = [];
$foundEntries = [];

if (!empty($query)) {
    $searchKey = "%$query%";

    // Search Tractors
    $stmtT = $conn->prepare("SELECT * FROM tractors WHERE name LIKE ? OR reg_number LIKE ? OR brand LIKE ? OR model LIKE ?");
    $stmtT->execute([$searchKey, $searchKey, $searchKey, $searchKey]);
    $foundTractors = $stmtT->fetchAll();

    // Search Customers
    $stmtC = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR mobile LIKE ? OR village LIKE ? OR address LIKE ?");
    $stmtC->execute([$searchKey, $searchKey, $searchKey, $searchKey]);
    $foundCustomers = $stmtC->fetchAll();

    // Search Drivers
    $stmtD = $conn->prepare("SELECT * FROM drivers WHERE name LIKE ? OR phone LIKE ? OR license_number LIKE ?");
    $stmtD->execute([$searchKey, $searchKey, $searchKey]);
    $foundDrivers = $stmtD->fetchAll();

    // Search Work Entries
    $stmtE = $conn->prepare("
        SELECT w.*, c.name as customer_name, t.name as tractor_name, wt.name as work_type_name
        FROM work_entries w
        JOIN customers c ON w.customer_id = c.id
        JOIN tractors t ON w.tractor_id = t.id
        JOIN work_types wt ON w.work_type_id = wt.id
        WHERE c.name LIKE ? OR w.village LIKE ? OR t.name LIKE ? OR wt.name LIKE ? OR w.notes LIKE ?
        ORDER BY w.entry_date DESC
    ");
    $stmtE->execute([$searchKey, $searchKey, $searchKey, $searchKey, $searchKey]);
    $foundEntries = $stmtE->fetchAll();
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('global_search', 'Global Search'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('search_placeholder', 'Unified search engine across tractors, farmer customers, drivers, and daily work entries.'); ?></p>
  </div>
</div>

<!-- Search Form Bar -->
<div class="card-erp p-4 mb-4">
  <form action="search.php" method="GET">
    <div class="input-group input-group-lg">
      <span class="input-group-text"><i class="bi bi-search text-primary"></i></span>
      <input type="text" name="q" class="form-control" placeholder="<?php echo __('search_placeholder', 'Search tractors, customers, villages...'); ?>" value="<?php echo htmlspecialchars($query); ?>" autofocus>
      <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-search me-1"></i> <?php echo __('search', 'Search ERP'); ?></button>
    </div>
  </form>
</div>

<?php if (!empty($query)): ?>
  <div class="mb-3">
    <h5 class="fw-bold text-muted"><?php echo __('search_results', 'Search Results'); ?> " <span class="text-primary"><?php echo htmlspecialchars($query); ?></span> "</h5>
  </div>

  <!-- 1. Tractors Found -->
  <?php if (!empty($foundTractors)): ?>
    <div class="card-erp p-4 mb-4">
      <h5 class="fw-bold brand-font text-primary mb-3"><i class="fa-solid fa-tractor me-2"></i><?php echo __('matching_tractors', 'Matching Tractors'); ?> (<?php echo count($foundTractors); ?>)</h5>
      <div class="table-responsive">
        <table class="table table-custom align-middle">
          <thead>
            <tr>
              <th><?php echo __('tractor_name', 'Tractor Name'); ?></th>
              <th><?php echo __('registration_number', 'Registration'); ?></th>
              <th><?php echo __('model_year', 'Brand & Model'); ?></th>
              <th><?php echo __('status', 'Status'); ?></th>
              <th><?php echo __('actions', 'Action'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($foundTractors as $t): ?>
              <tr>
                <td class="fw-bold"><?php echo htmlspecialchars($t['name']); ?></td>
                <td class="fw-semibold text-primary"><?php echo htmlspecialchars($t['reg_number']); ?></td>
                <td><?php echo htmlspecialchars($t['brand'] . ' ' . $t['model']); ?></td>
                <td><span class="badge bg-light text-dark border"><?php echo __($t['status'], $t['status']); ?></span></td>
                <td><a href="<?php echo SITE_URL; ?>/tractors/index.php" class="btn btn-sm btn-outline-primary"><?php echo __('view', 'View Fleet'); ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- 2. Customers Found -->
  <?php if (!empty($foundCustomers)): ?>
    <div class="card-erp p-4 mb-4">
      <h5 class="fw-bold brand-font text-info mb-3"><i class="bi bi-people-fill me-2"></i><?php echo __('matching_customers', 'Matching Customers'); ?> (<?php echo count($foundCustomers); ?>)</h5>
      <div class="table-responsive">
        <table class="table table-custom align-middle">
          <thead>
            <tr>
              <th><?php echo __('customer_name', 'Customer Name'); ?></th>
              <th><?php echo __('phone', 'Mobile'); ?></th>
              <th><?php echo __('village', 'Village'); ?></th>
              <th><?php echo __('address', 'Address'); ?></th>
              <th><?php echo __('customer_ledger', 'Ledger'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($foundCustomers as $c): ?>
              <tr>
                <td class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></td>
                <td><?php echo htmlspecialchars($c['mobile']); ?></td>
                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['village']); ?></span></td>
                <td class="text-muted small"><?php echo htmlspecialchars($c['address']); ?></td>
                <td><a href="<?php echo SITE_URL; ?>/customers/index.php?view_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-info"><?php echo __('customer_ledger', 'Customer Ledger'); ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- 3. Drivers Found -->
  <?php if (!empty($foundDrivers)): ?>
    <div class="card-erp p-4 mb-4">
      <h5 class="fw-bold brand-font text-success mb-3"><i class="bi bi-person-vcard me-2"></i><?php echo __('matching_drivers', 'Matching Drivers'); ?> (<?php echo count($foundDrivers); ?>)</h5>
      <div class="table-responsive">
        <table class="table table-custom align-middle">
          <thead>
            <tr>
              <th><?php echo __('driver_name', 'Driver Name'); ?></th>
              <th><?php echo __('phone', 'Phone'); ?></th>
              <th><?php echo __('license_number', 'License'); ?></th>
              <th><?php echo __('status', 'Status'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($foundDrivers as $d): ?>
              <tr>
                <td class="fw-bold"><?php echo htmlspecialchars($d['name']); ?></td>
                <td><?php echo htmlspecialchars($d['phone']); ?></td>
                <td><code><?php echo htmlspecialchars($d['license_number']); ?></code></td>
                <td><span class="badge bg-success"><?php echo __($d['status'], $d['status']); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- 4. Work Entries Found -->
  <?php if (!empty($foundEntries)): ?>
    <div class="card-erp p-4 mb-4">
      <h5 class="fw-bold brand-font text-warning mb-3"><i class="bi bi-journal-plus me-2"></i><?php echo __('matching_work_entries', 'Matching Work Entries'); ?> (<?php echo count($foundEntries); ?>)</h5>
      <div class="table-responsive">
        <table class="table table-custom align-middle">
          <thead>
            <tr>
              <th><?php echo __('date', 'Date'); ?></th>
              <th><?php echo __('customers', 'Customer'); ?></th>
              <th><?php echo __('village', 'Village'); ?></th>
              <th><?php echo __('tractors', 'Tractor'); ?></th>
              <th><?php echo __('work_types', 'Work Type'); ?></th>
              <th><?php echo __('total', 'Total'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($foundEntries as $e): ?>
              <tr>
                <td><?php echo formatDate($e['entry_date']); ?></td>
                <td class="fw-bold"><?php echo htmlspecialchars($e['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($e['village']); ?></td>
                <td><?php echo htmlspecialchars($e['tractor_name']); ?></td>
                <td><?php echo htmlspecialchars($e['work_type_name']); ?></td>
                <td class="fw-bold text-success"><?php echo formatCurrency($e['total_amount']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if (empty($foundTractors) && empty($foundCustomers) && empty($foundDrivers) && empty($foundEntries)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-search-heart fs-1 d-block mb-2"></i>
      <h5><?php echo __('no_records_found', 'No matching records found.'); ?></h5>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
