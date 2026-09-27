<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Add / Edit / Delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $village = trim($_POST['village'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("UPDATE customers SET name = :n, mobile = :m, village = :v, address = :a, notes = :notes WHERE id = :id");
                $stmt->execute(['n' => $name, 'm' => $mobile, 'v' => $village, 'a' => $address, 'notes' => $notes, 'id' => $id]);
                setFlash('success', __('updated_success', 'Customer profile updated.'));
            } else {
                $stmt = $conn->prepare("INSERT INTO customers (name, mobile, village, address, notes) VALUES (:n, :r, :v, :a, :notes)");
                $stmt->execute(['n' => $name, 'r' => $mobile, 'v' => $village, 'a' => $address, 'notes' => $notes]);
                setFlash('success', __('saved_success', 'New Customer added.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving customer:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Customer profile deleted.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting customer:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('customer_management', 'Customer Management');
require_once __DIR__ . '/../includes/header.php';

// Fetch Customers List with calculated Total Billed & Pending Balance
$customers = $conn->query("
    SELECT c.*, 
           COALESCE(SUM(w.total_amount), 0) as total_billed,
           COALESCE(SUM(w.balance_amount), 0) as pending_balance,
           COUNT(w.id) as total_work_count
    FROM customers c
    LEFT JOIN work_entries w ON c.id = w.customer_id
    GROUP BY c.id
    ORDER BY c.id DESC
")->fetchAll();

// Handle Ledger Modal view via AJAX or URL
$viewCustomer = null;
$customerEntries = [];
if (isset($_GET['view_id'])) {
    $vId = intval($_GET['view_id']);
    $stmtC = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmtC->execute([$vId]);
    $viewCustomer = $stmtC->fetch();

    if ($viewCustomer) {
        $stmtE = $conn->prepare("
            SELECT w.*, t.name as tractor_name, wt.name as work_type_name
            FROM work_entries w
            JOIN tractors t ON w.tractor_id = t.id
            JOIN work_types wt ON w.work_type_id = wt.id
            WHERE w.customer_id = ?
            ORDER BY w.entry_date DESC
        ");
        $stmtE->execute([$vId]);
        $customerEntries = $stmtE->fetchAll();
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('customer_management', 'Customer Management'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('customer_list', 'Farmer directory, village-wise grouping, billing history, and pending ledgers.'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="resetForm()">
    <i class="bi bi-person-plus-fill"></i> <?php echo __('add_customer', 'Add New Customer'); ?>
  </button>
</div>

<!-- Customer Table -->
<div class="card-erp p-4 mb-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('customer_name', 'Customer Name'); ?></th>
          <th><?php echo __('phone', 'Mobile Number'); ?></th>
          <th><?php echo __('village', 'Village'); ?></th>
          <th><?php echo __('total_billed', 'Total Billed'); ?></th>
          <th><?php echo __('balance', 'Pending Balance'); ?></th>
          <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
          <tr>
            <td>
              <div class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars($c['address'] ?: ''); ?></small>
            </td>
            <td class="fw-semibold">
              <a href="tel:<?php echo htmlspecialchars($c['mobile']); ?>" class="text-decoration-none text-dark">
                <i class="bi bi-telephone text-success me-1"></i><?php echo htmlspecialchars($c['mobile']); ?>
              </a>
            </td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['village']); ?></span></td>
            <td class="fw-semibold"><?php echo formatCurrency($c['total_billed']); ?></td>
            <td>
              <?php if ($c['pending_balance'] > 0): ?>
                <span class="badge bg-danger-light text-danger fw-bold fs-7"><?php echo formatCurrency($c['pending_balance']); ?></span>
              <?php else: ?>
                <span class="badge bg-success-light text-success fw-bold fs-7"><?php echo __('paid', 'Settled'); ?></span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="index.php?view_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-info me-1"><i class="bi bi-journal-text"></i> <?php echo __('customer_ledger', 'Ledger'); ?></a>
              <button class="btn btn-sm btn-outline-primary me-1" onclick='editCustomer(<?php echo json_encode($c); ?>)'><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $c['id']; ?>')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="index.php" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="customer_id">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-person-plus me-2 text-primary"></i><?php echo __('add_customer', 'Add Customer Profile'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('customer_name', 'Customer Full Name'); ?> *</label>
            <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Ramasamy Gounder">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('phone', 'Mobile Number'); ?> *</label>
            <input type="text" name="mobile" id="mobile" class="form-control" required placeholder="e.g. 9842101234">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('village', 'Village / Location'); ?> *</label>
            <input type="text" name="village" id="village" class="form-control" required placeholder="e.g. Vadugapalayam">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('address', 'Full Address'); ?></label>
            <textarea name="address" id="address" class="form-control" rows="2" placeholder="Door number, street name, landmark..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('notes', 'Customer Notes'); ?></label>
            <input type="text" name="notes" id="notes" class="form-control" placeholder="Land details, crop type, special instructions...">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Customer'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Customer Ledger Detail Modal -->
<?php if ($viewCustomer): ?>
<div class="modal fade show" id="ledgerModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title fw-bold brand-font mb-0"><i class="bi bi-journal-text text-primary me-2"></i><?php echo __('customer_ledger', 'Customer Ledger'); ?>: <?php echo htmlspecialchars($viewCustomer['name']); ?></h5>
          <small class="text-muted"><?php echo __('village', 'Village'); ?>: <?php echo htmlspecialchars($viewCustomer['village']); ?> | <?php echo __('phone', 'Mobile'); ?>: <?php echo htmlspecialchars($viewCustomer['mobile']); ?></small>
        </div>
        <a href="index.php" class="btn-close"></a>
      </div>
      <div class="modal-body p-4">
        <div class="table-responsive">
          <table class="table table-custom align-middle">
            <thead>
              <tr>
                <th><?php echo __('date', 'Date'); ?></th>
                <th><?php echo __('tractors', 'Tractor'); ?></th>
                <th><?php echo __('work_types', 'Work Type'); ?></th>
                <th><?php echo __('total', 'Total'); ?></th>
                <th><?php echo __('advance', 'Advance'); ?></th>
                <th><?php echo __('balance', 'Balance'); ?></th>
                <th><?php echo __('status', 'Status'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($customerEntries)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3"><?php echo __('no_records_found', 'No work entry records found for this customer.'); ?></td></tr>
              <?php else: ?>
                <?php foreach ($customerEntries as $e): ?>
                  <tr>
                    <td><?php echo formatDate($e['entry_date']); ?></td>
                    <td><?php echo htmlspecialchars($e['tractor_name']); ?></td>
                    <td><?php echo htmlspecialchars($e['work_type_name']); ?></td>
                    <td class="fw-bold"><?php echo formatCurrency($e['total_amount']); ?></td>
                    <td><?php echo formatCurrency($e['advance_amount']); ?></td>
                    <td class="fw-bold text-danger"><?php echo formatCurrency($e['balance_amount']); ?></td>
                    <td>
                      <span class="badge-status <?php echo strtolower($e['payment_status']) === 'paid' ? 'badge-paid' : 'badge-pending'; ?>">
                        <?php echo __($e['payment_status'], $e['payment_status']); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button onclick="printReceipt('ledgerModal')" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i> <?php echo __('print', 'Print Ledger'); ?></button>
        <a href="index.php" class="btn btn-primary btn-sm"><?php echo __('close', 'Close'); ?></a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function resetForm() {
  document.getElementById('customer_id').value = '';
  document.getElementById('name').value = '';
  document.getElementById('mobile').value = '';
  document.getElementById('village').value = '';
  document.getElementById('address').value = '';
  document.getElementById('notes').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-person-plus me-2 text-primary"></i><?php echo __('add_customer', 'Add Customer Profile'); ?>';
}

function editCustomer(data) {
  document.getElementById('customer_id').value = data.id;
  document.getElementById('name').value = data.name;
  document.getElementById('mobile').value = data.mobile;
  document.getElementById('village').value = data.village;
  document.getElementById('address').value = data.address || '';
  document.getElementById('notes').value = data.notes || '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i><?php echo __('edit_customer', 'Edit Customer Profile'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('customerModal'));
  modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
