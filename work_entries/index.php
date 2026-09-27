<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Add / Edit / Delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $entry_date = $_POST['entry_date'] ?? date('Y-m-d');
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $village = trim($_POST['village'] ?? '');
        $tractor_id = intval($_POST['tractor_id'] ?? 0);
        $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
        $work_type_id = intval($_POST['work_type_id'] ?? 0);
        $quantity = floatval($_POST['quantity'] ?? 1.0);
        $rate = floatval($_POST['rate'] ?? 0.0);
        $total_amount = $quantity * $rate;
        $advance_amount = floatval($_POST['advance_amount'] ?? 0.0);
        $balance_amount = $total_amount - $advance_amount;
        $payment_status = $_POST['payment_status'] ?? 'Pending';
        $notes = trim($_POST['notes'] ?? '');

        // Auto update village from customer if empty
        if (empty($village) && $customer_id > 0) {
            $stmtC = $conn->prepare("SELECT village FROM customers WHERE id = ?");
            $stmtC->execute([$customer_id]);
            $village = $stmtC->fetchColumn() ?: '';
        }

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("
                    UPDATE work_entries 
                    SET entry_date = :d, customer_id = :c, village = :v, tractor_id = :t, driver_id = :dr, 
                        work_type_id = :wt, quantity = :q, rate = :r, total_amount = :tot, advance_amount = :adv, 
                        balance_amount = :bal, payment_status = :ps, notes = :notes 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'd' => $entry_date, 'c' => $customer_id, 'v' => $village, 't' => $tractor_id, 'dr' => $driver_id,
                    'wt' => $work_type_id, 'q' => $quantity, 'r' => $rate, 'tot' => $total_amount, 'adv' => $advance_amount,
                    'bal' => $balance_amount, 'ps' => $payment_status, 'notes' => $notes, 'id' => $id
                ]);
                setFlash('success', __('updated_success', 'Work entry updated successfully.'));
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO work_entries (entry_date, customer_id, village, tractor_id, driver_id, work_type_id, quantity, rate, total_amount, advance_amount, balance_amount, payment_status, notes)
                    VALUES (:d, :c, :v, :t, :dr, :wt, :q, :r, :tot, :adv, :bal, :ps, :notes)
                ");
                $stmt->execute([
                    'd' => $entry_date, 'c' => $customer_id, 'v' => $village, 't' => $tractor_id, 'dr' => $driver_id,
                    'wt' => $work_type_id, 'q' => $quantity, 'r' => $rate, 'tot' => $total_amount, 'adv' => $advance_amount,
                    'bal' => $balance_amount, 'ps' => $payment_status, 'notes' => $notes
                ]);
                $newWorkId = $conn->lastInsertId();

                // If advance paid, log initial payment transaction automatically
                if ($advance_amount > 0) {
                    $stmtP = $conn->prepare("INSERT INTO payments (work_entry_id, customer_id, payment_date, amount, payment_mode, notes) VALUES (?, ?, ?, ?, 'Cash', 'Advance received at work entry')");
                    $stmtP->execute([$newWorkId, $customer_id, $entry_date, $advance_amount]);
                }

                setFlash('success', __('saved_success', 'Daily Work Entry recorded successfully.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving work entry:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM work_entries WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Work entry record deleted.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting work entry:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('daily_work_entry', 'Daily Work Entries');
require_once __DIR__ . '/../includes/header.php';

// Fetch Dropdown Options
$customers = $conn->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
$tractors = $conn->query("SELECT * FROM tractors ORDER BY name ASC")->fetchAll();
$drivers = $conn->query("SELECT * FROM drivers WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
$workTypes = $conn->query("SELECT * FROM work_types ORDER BY name ASC")->fetchAll();

// Fetch All Work Entries
$workEntries = $conn->query("
    SELECT w.*, c.name as customer_name, c.mobile as customer_mobile, t.name as tractor_name, 
           d.name as driver_name, wt.name as work_type_name, wt.unit_type
    FROM work_entries w
    JOIN customers c ON w.customer_id = c.id
    JOIN tractors t ON w.tractor_id = t.id
    LEFT JOIN drivers d ON w.driver_id = d.id
    JOIN work_types wt ON w.work_type_id = wt.id
    ORDER BY w.entry_date DESC, w.id DESC
")->fetchAll();

// Receipt Printable View Modal trigger
$printRecord = null;
if (isset($_GET['receipt_id'])) {
    $rId = intval($_GET['receipt_id']);
    $stmtR = $conn->prepare("
        SELECT w.*, c.name as customer_name, c.mobile as customer_mobile, c.address as customer_address, 
               t.name as tractor_name, t.reg_number, d.name as driver_name, wt.name as work_type_name, wt.unit_type
        FROM work_entries w
        JOIN customers c ON w.customer_id = c.id
        JOIN tractors t ON w.tractor_id = t.id
        LEFT JOIN drivers d ON w.driver_id = d.id
        JOIN work_types wt ON w.work_type_id = wt.id
        WHERE w.id = ?
    ");
    $stmtR->execute([$rId]);
    $printRecord = $stmtR->fetch();
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('daily_work_entry', 'Daily Work Entries'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('work_entry_management', 'Record daily agricultural work operations, auto-calculate totals, advance, and balances.'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#workEntryModal" onclick="resetForm()">
    <i class="bi bi-journal-plus"></i> <?php echo __('new_work_entry', 'New Work Entry'); ?>
  </button>
</div>

<!-- Work Entries Data Table -->
<div class="card-erp p-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('work_date', 'Entry Date'); ?></th>
          <th><?php echo __('customers', 'Customer & Village'); ?></th>
          <th><?php echo __('tractors', 'Tractor & Driver'); ?></th>
          <th><?php echo __('work_types', 'Work Type & Qty'); ?></th>
          <th><?php echo __('total', 'Total'); ?></th>
          <th><?php echo __('advance', 'Advance / Balance'); ?></th>
          <th><?php echo __('status', 'Payment Status'); ?></th>
          <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($workEntries as $w): ?>
          <tr>
            <td>
              <div class="fw-bold"><?php echo formatDate($w['entry_date'], 'd M Y'); ?></div>
              <small class="text-muted">ID: #WE-<?php echo sprintf('%04d', $w['id']); ?></small>
            </td>
            <td>
              <div class="fw-bold"><?php echo htmlspecialchars($w['customer_name']); ?></div>
              <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($w['village']); ?></small>
            </td>
            <td>
              <div class="fw-semibold"><?php echo htmlspecialchars($w['tractor_name']); ?></div>
              <small class="text-muted"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($w['driver_name'] ?: 'Unassigned'); ?></small>
            </td>
            <td>
              <div class="fw-bold text-primary"><?php echo htmlspecialchars($w['work_type_name']); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars($w['quantity'] . ' ' . $w['unit_type'] . '(s) @ ₹' . number_format($w['rate'])); ?></small>
            </td>
            <td class="fw-bold fs-6"><?php echo formatCurrency($w['total_amount']); ?></td>
            <td>
              <small class="d-block text-success"><?php echo __('advance', 'Adv'); ?>: <?php echo formatCurrency($w['advance_amount']); ?></small>
              <small class="d-block text-danger fw-semibold"><?php echo __('balance', 'Bal'); ?>: <?php echo formatCurrency($w['balance_amount']); ?></small>
            </td>
            <td>
              <?php
              $st = strtolower($w['payment_status']);
              $badgeClass = $st === 'paid' ? 'badge-paid' : ($st === 'partial' ? 'badge-partial' : 'badge-pending');
              ?>
              <span class="badge-status <?php echo $badgeClass; ?>"><?php echo __($w['payment_status'], $w['payment_status']); ?></span>
            </td>
            <td class="text-end">
              <a href="index.php?receipt_id=<?php echo $w['id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="<?php echo __('print_receipt', 'View & Print Invoice Receipt'); ?>"><i class="bi bi-receipt"></i></a>
              <button class="btn btn-sm btn-outline-primary me-1" onclick='editWorkEntry(<?php echo json_encode($w); ?>)'><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $w['id']; ?>')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Work Entry Modal -->
<div class="modal fade" id="workEntryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form action="index.php" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="we_id">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-journal-plus me-2 text-primary"></i><?php echo __('new_work_entry_title', 'Record Daily Work Entry'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('work_date', 'Entry Date'); ?> *</label>
              <input type="date" name="entry_date" id="entry_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('select_customer', 'Customer'); ?> *</label>
              <select name="customer_id" id="customer_id" class="form-select" required>
                <option value=""><?php echo __('select_customer', 'Select Customer'); ?></option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?php echo $c['id']; ?>" data-village="<?php echo htmlspecialchars($c['village']); ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['village']); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('village', 'Village / Field Location'); ?> *</label>
              <input type="text" name="village" id="village" class="form-control" required placeholder="Village name">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('select_tractor', 'Tractor'); ?> *</label>
              <select name="tractor_id" id="tractor_id" class="form-select" required>
                <option value=""><?php echo __('select_tractor', 'Select Tractor'); ?></option>
                <?php foreach ($tractors as $t): ?>
                  <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['reg_number']); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('select_driver', 'Driver Operator'); ?></label>
              <select name="driver_id" id="driver_id" class="form-select">
                <option value=""><?php echo __('select_driver', 'Select Driver'); ?></option>
                <?php foreach ($drivers as $d): ?>
                  <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('select_work_type', 'Work Type'); ?> *</label>
              <select name="work_type_id" id="workTypeSelect" class="form-select" required>
                <option value=""><?php echo __('select_work_type', 'Select Work Type'); ?></option>
                <?php foreach ($workTypes as $wt): ?>
                  <option value="<?php echo $wt['id']; ?>" data-rate="<?php echo $wt['rate']; ?>"><?php echo htmlspecialchars($wt['name']); ?> (₹<?php echo number_format($wt['rate']); ?>/<?php echo $wt['unit_type']; ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('quantity', 'Quantity (Hours/Acres/Trips)'); ?> *</label>
              <input type="number" step="0.25" name="quantity" id="calcQuantity" class="form-control" required value="1.00">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('rate_per_unit', 'Rate'); ?> *</label>
              <input type="number" step="0.01" name="rate" id="calcRate" class="form-control" required value="0.00">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-primary"><?php echo __('total_amount', 'Total Amount'); ?></label>
              <input type="number" step="0.01" name="total_amount" id="calcTotal" class="form-control fw-bold text-primary bg-light" readonly value="0.00">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-success"><?php echo __('advance', 'Advance Paid'); ?></label>
              <input type="number" step="0.01" name="advance_amount" id="calcAdvance" class="form-control" value="0.00">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold text-danger"><?php echo __('balance', 'Balance Due'); ?></label>
              <input type="number" step="0.01" name="balance_amount" id="calcBalance" class="form-control fw-bold text-danger bg-light" readonly value="0.00">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('status', 'Payment Status'); ?></label>
              <select name="payment_status" id="calcStatus" class="form-select">
                <option value="Pending"><?php echo __('pending', 'Pending'); ?></option>
                <option value="Partial"><?php echo __('partial', 'Partial'); ?></option>
                <option value="Paid"><?php echo __('paid', 'Paid'); ?></option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('notes', 'Work Notes'); ?></label>
              <input type="text" name="notes" id="notes" class="form-control" placeholder="Field details, crop condition...">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Work Entry'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Receipt Invoice Modal View -->
<?php if ($printRecord): ?>
<div class="modal fade show" id="receiptModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header no-print">
        <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-2 text-primary"></i><?php echo __('work_receipt', 'Work Receipt Invoice'); ?> #WE-<?php echo sprintf('%04d', $printRecord['id']); ?></h5>
        <a href="index.php" class="btn-close"></a>
      </div>
      <div class="modal-body p-4" id="printableArea">
        <div class="invoice-card">
          <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
              <h3 class="fw-bold text-primary brand-font mb-0"><?php echo htmlspecialchars($company['company_name']); ?></h3>
              <small class="text-muted"><?php echo htmlspecialchars($company['address']); ?> | <?php echo __('phone', 'Phone'); ?>: <?php echo htmlspecialchars($company['phone']); ?></small>
            </div>
            <div class="text-end">
              <h5 class="fw-bold text-dark mb-0"><?php echo __('work_receipt', 'WORK INVOICE'); ?></h5>
              <small class="text-muted"><?php echo __('date', 'Date'); ?>: <?php echo formatDate($printRecord['entry_date']); ?></small>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-6">
              <h6 class="fw-bold text-uppercase text-muted small"><?php echo __('customer_management', 'CUSTOMER DETAILS'); ?></h6>
              <div class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($printRecord['customer_name']); ?></div>
              <div><?php echo __('village', 'Village'); ?>: <strong><?php echo htmlspecialchars($printRecord['village']); ?></strong></div>
              <div><?php echo __('phone', 'Phone'); ?>: <?php echo htmlspecialchars($printRecord['customer_mobile']); ?></div>
            </div>
            <div class="col-6 text-end">
              <h6 class="fw-bold text-uppercase text-muted small"><?php echo __('nav_operations', 'OPERATION DETAILS'); ?></h6>
              <div><?php echo __('tractors', 'Tractor'); ?>: <strong><?php echo htmlspecialchars($printRecord['tractor_name']); ?> (<?php echo htmlspecialchars($printRecord['reg_number']); ?>)</strong></div>
              <div><?php echo __('drivers', 'Driver'); ?>: <?php echo htmlspecialchars($printRecord['driver_name'] ?: 'Unassigned'); ?></div>
            </div>
          </div>

          <table class="table table-bordered mb-4">
            <thead class="table-light">
              <tr>
                <th><?php echo __('description', 'Service Work Description'); ?></th>
                <th class="text-center"><?php echo __('billing_type', 'Billing Unit'); ?></th>
                <th class="text-center"><?php echo __('rate', 'Rate'); ?></th>
                <th class="text-end"><?php echo __('total_amount', 'Total Amount'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong><?php echo htmlspecialchars($printRecord['work_type_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($printRecord['notes']); ?></small></td>
                <td class="text-center"><?php echo $printRecord['quantity'] . ' ' . $printRecord['unit_type'] . '(s)'; ?></td>
                <td class="text-center">₹<?php echo number_format($printRecord['rate'], 2); ?></td>
                <td class="text-end fw-bold">₹<?php echo number_format($printRecord['total_amount'], 2); ?></td>
              </tr>
            </tbody>
          </table>

          <div class="row justify-content-end">
            <div class="col-6">
              <table class="table table-borderless">
                <tr>
                  <td><?php echo __('total_billed', 'Total Billed'); ?>:</td>
                  <td class="text-end fw-bold">₹<?php echo number_format($printRecord['total_amount'], 2); ?></td>
                </tr>
                <tr>
                  <td class="text-success"><?php echo __('advance', 'Advance Paid'); ?>:</td>
                  <td class="text-end fw-bold text-success">₹<?php echo number_format($printRecord['advance_amount'], 2); ?></td>
                </tr>
                <tr class="border-top">
                  <td class="fs-5 fw-bold text-danger"><?php echo __('balance', 'Balance Due'); ?>:</td>
                  <td class="text-end fs-5 fw-bold text-danger">₹<?php echo number_format($printRecord['balance_amount'], 2); ?></td>
                </tr>
              </table>
            </div>
          </div>

          <div class="pt-4 border-top mt-4 text-center text-muted small">
            Thank you for your business! For queries contact <?php echo htmlspecialchars($company['phone']); ?>
          </div>
        </div>
      </div>
      <div class="modal-footer no-print">
        <button onclick="printReceipt('printableArea')" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i> <?php echo __('print_invoice', 'Print Invoice'); ?></button>
        <a href="index.php" class="btn btn-secondary btn-sm"><?php echo __('close', 'Close'); ?></a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
// Auto fill village on customer change
document.getElementById('customer_id').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const vil = opt.getAttribute('data-village');
  if (vil) document.getElementById('village').value = vil;
});

function resetForm() {
  document.getElementById('we_id').value = '';
  document.getElementById('entry_date').value = '<?php echo date('Y-m-d'); ?>';
  document.getElementById('customer_id').value = '';
  document.getElementById('village').value = '';
  document.getElementById('tractor_id').value = '';
  document.getElementById('driver_id').value = '';
  document.getElementById('workTypeSelect').value = '';
  document.getElementById('calcQuantity').value = '1.00';
  document.getElementById('calcRate').value = '0.00';
  document.getElementById('calcTotal').value = '0.00';
  document.getElementById('calcAdvance').value = '0.00';
  document.getElementById('calcBalance').value = '0.00';
  document.getElementById('calcStatus').value = 'Pending';
  document.getElementById('notes').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-journal-plus me-2 text-primary"></i><?php echo __('new_work_entry_title', 'Record Daily Work Entry'); ?>';
}

function editWorkEntry(data) {
  document.getElementById('we_id').value = data.id;
  document.getElementById('entry_date').value = data.entry_date;
  document.getElementById('customer_id').value = data.customer_id;
  document.getElementById('village').value = data.village;
  document.getElementById('tractor_id').value = data.tractor_id;
  document.getElementById('driver_id').value = data.driver_id || '';
  document.getElementById('workTypeSelect').value = data.work_type_id;
  document.getElementById('calcQuantity').value = data.quantity;
  document.getElementById('calcRate').value = data.rate;
  document.getElementById('calcTotal').value = data.total_amount;
  document.getElementById('calcAdvance').value = data.advance_amount;
  document.getElementById('calcBalance').value = data.balance_amount;
  document.getElementById('calcStatus').value = data.payment_status;
  document.getElementById('notes').value = data.notes || '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i><?php echo __('edit', 'Edit Work Entry'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('workEntryModal'));
  modal.show();
}

// Auto open modal if action=new query param is passed
<?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
document.addEventListener('DOMContentLoaded', function() {
  var modal = new bootstrap.Modal(document.getElementById('workEntryModal'));
  modal.show();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
