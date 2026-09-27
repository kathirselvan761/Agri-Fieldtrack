<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Add Payment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $work_entry_id = intval($_POST['work_entry_id'] ?? 0);
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $amount = floatval($_POST['amount'] ?? 0.00);
    $payment_mode = $_POST['payment_mode'] ?? 'Cash';
    $reference_no = trim($_POST['reference_no'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    try {
        if ($amount > 0 && $customer_id > 0) {
            $stmt = $conn->prepare("INSERT INTO payments (work_entry_id, customer_id, payment_date, amount, payment_mode, reference_no, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$work_entry_id ?: null, $customer_id, $payment_date, $amount, $payment_mode, $reference_no, $notes]);

            // Update Work Entry Balance if associated
            if ($work_entry_id > 0) {
                $stmtW = $conn->prepare("SELECT total_amount, advance_amount, balance_amount FROM work_entries WHERE id = ?");
                $stmtW->execute([$work_entry_id]);
                $we = $stmtW->fetch();
                if ($we) {
                    $newBal = max(0, $we['balance_amount'] - $amount);
                    $newAdv = $we['advance_amount'] + $amount;
                    $status = ($newBal <= 0) ? 'Paid' : 'Partial';

                    $stmtU = $conn->prepare("UPDATE work_entries SET advance_amount = ?, balance_amount = ?, payment_status = ? WHERE id = ?");
                    $stmtU->execute([$newAdv, $newBal, $status, $work_entry_id]);
                }
            }

            setFlash('success', __('saved_success', 'Payment recorded successfully.'));
        } else {
            setFlash('danger', __('error_occurred', 'Invalid payment amount or customer.'));
        }
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error recording payment:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('payment_receipts', 'Payment Receipts Management');
require_once __DIR__ . '/../includes/header.php';

// Fetch Customers & Unpaid Work Entries Dropdown
$customers = $conn->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
$pendingEntries = $conn->query("
    SELECT w.*, c.name as customer_name 
    FROM work_entries w 
    JOIN customers c ON w.customer_id = c.id 
    WHERE w.payment_status != 'Paid' 
    ORDER BY w.entry_date DESC
")->fetchAll();

// Fetch All Payment Transactions
$payments = $conn->query("
    SELECT p.*, c.name as customer_name, c.village, c.mobile
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    ORDER BY p.payment_date DESC, p.id DESC
")->fetchAll();

$totCollected = $conn->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('payment_receipts', 'Payment Receipts & Collections'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('payment_management', 'Record advance payments, customer balance settlements, UPI/Cash vouchers, and print receipts.'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
    <i class="bi bi-cash-stack"></i> <?php echo __('record_payment', 'Record New Payment'); ?>
  </button>
</div>

<!-- Payments KPI Card -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-6 col-lg-4">
    <div class="card-erp p-3 border-start border-4 border-success">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-success"><?php echo __('total_paid', 'Total Payment Collections'); ?></span>
          <div class="stat-value text-success"><?php echo formatCurrency($totCollected); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-success-light"><i class="bi bi-bank"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- Payments Transactions Table -->
<div class="card-erp p-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('date', 'Payment Date'); ?></th>
          <th><?php echo __('customer_name', 'Customer Name'); ?></th>
          <th><?php echo __('village', 'Village'); ?></th>
          <th><?php echo __('amount', 'Amount Received'); ?></th>
          <th><?php echo __('payment_mode', 'Payment Mode'); ?></th>
          <th>Ref / Transaction No</th>
          <th><?php echo __('notes', 'Notes'); ?></th>
          <th class="text-end"><?php echo __('print_receipt', 'Print Receipt'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td class="fw-bold"><?php echo formatDate($p['payment_date'], 'd M Y'); ?></td>
            <td>
              <div class="fw-bold"><?php echo htmlspecialchars($p['customer_name']); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars($p['mobile']); ?></small>
            </td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['village']); ?></span></td>
            <td class="fw-bold text-success fs-6"><?php echo formatCurrency($p['amount']); ?></td>
            <td>
              <?php
              $mode = $p['payment_mode'];
              $badgeClass = $mode === 'UPI' ? 'bg-info' : ($mode === 'Bank' ? 'bg-primary' : 'bg-success');
              ?>
              <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($mode); ?></span>
            </td>
            <td><code><?php echo htmlspecialchars($p['reference_no'] ?: 'N/A'); ?></code></td>
            <td class="text-muted small"><?php echo htmlspecialchars($p['notes'] ?: '-'); ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" onclick='printPaymentVoucher(<?php echo json_encode($p); ?>)'><i class="bi bi-printer me-1"></i> <?php echo __('print', 'Receipt'); ?></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="index.php" method="POST">
        <input type="hidden" name="record_payment" value="1">

        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2 text-success"></i><?php echo __('record_payment', 'Record Customer Payment'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Link to Outstanding Work Entry (Optional)</label>
            <select name="work_entry_id" id="work_entry_id" class="form-select" onchange="autoFillEntry(this)">
              <option value="">Direct Customer Payment (No Specific Entry)</option>
              <?php foreach ($pendingEntries as $pe): ?>
                <option value="<?php echo $pe['id']; ?>" data-customer="<?php echo $pe['customer_id']; ?>" data-balance="<?php echo $pe['balance_amount']; ?>">
                  #WE-<?php echo sprintf('%04d', $pe['id']); ?> - <?php echo htmlspecialchars($pe['customer_name']); ?> (Due: ₹<?php echo number_format($pe['balance_amount']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('select_customer', 'Customer'); ?> *</label>
            <select name="customer_id" id="pay_customer_id" class="form-select" required>
              <option value=""><?php echo __('select_customer', 'Select Customer'); ?></option>
              <?php foreach ($customers as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['village']); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('date', 'Payment Date'); ?> *</label>
            <input type="date" name="payment_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('amount', 'Payment Amount'); ?> *</label>
            <input type="number" step="0.01" name="amount" id="pay_amount" class="form-control" required placeholder="e.g. 2000.00">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('payment_mode', 'Payment Mode'); ?> *</label>
            <select name="payment_mode" class="form-select" required>
              <option value="Cash"><?php echo __('cash', 'Cash'); ?></option>
              <option value="UPI"><?php echo __('online_upi', 'UPI (GPay / PhonePe / Paytm)'); ?></option>
              <option value="Bank"><?php echo __('bank_transfer', 'Bank Transfer / Cheque'); ?></option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Reference / UTR Number</label>
            <input type="text" name="reference_no" class="form-control" placeholder="e.g. UPI/20260731/98712">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('notes', 'Notes'); ?></label>
            <input type="text" name="notes" class="form-control" placeholder="Payment receipt notes...">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle me-1"></i> <?php echo __('save', 'Save Payment Receipt'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function autoFillEntry(selectEl) {
  const selectedOption = selectEl.options[selectEl.selectedIndex];
  const customerId = selectedOption.getAttribute('data-customer');
  const balance = selectedOption.getAttribute('data-balance');
  
  if (customerId) document.getElementById('pay_customer_id').value = customerId;
  if (balance) document.getElementById('pay_amount').value = balance;
}

function printPaymentVoucher(data) {
  const windowContent = `
    <div style="font-family:sans-serif; padding:30px; border:2px solid #0f172a; border-radius:10px; max-width:600px; margin:auto;">
      <h2 style="color:#2b7fff; margin-bottom:5px; text-align:center;"><?php echo htmlspecialchars($company['company_name']); ?></h2>
      <p style="text-align:center; color:#64748b; font-size:14px; margin-top:0;"><?php echo htmlspecialchars($company['address']); ?> | Tel: <?php echo htmlspecialchars($company['phone']); ?></p>
      <hr style="border-top:1px solid #e2e8f0; margin:20px 0;">
      <h3 style="text-align:center; letter-spacing:1px;"><?php echo __('payment_receipt', 'PAYMENT RECEIPT VOUCHER'); ?></h3>
      <table style="width:100%; font-size:15px; margin-top:20px; border-collapse:collapse;">
        <tr><td style="padding:6px; color:#64748b;"><?php echo __('receipt_no', 'Receipt No'); ?>:</td><td style="padding:6px; font-weight:bold;">#PAY-${data.id}</td></tr>
        <tr><td style="padding:6px; color:#64748b;"><?php echo __('date', 'Date'); ?>:</td><td style="padding:6px; font-weight:bold;">${data.payment_date}</td></tr>
        <tr><td style="padding:6px; color:#64748b;"><?php echo __('customer_name', 'Received From'); ?>:</td><td style="padding:6px; font-weight:bold; font-size:17px; color:#0f172a;">${data.customer_name} (${data.village})</td></tr>
        <tr><td style="padding:6px; color:#64748b;"><?php echo __('amount', 'Amount Received'); ?>:</td><td style="padding:6px; font-weight:bold; font-size:20px; color:#10b981;">₹${parseFloat(data.amount).toFixed(2)}</td></tr>
        <tr><td style="padding:6px; color:#64748b;"><?php echo __('payment_mode', 'Payment Mode'); ?>:</td><td style="padding:6px; font-weight:bold;">${data.payment_mode}</td></tr>
        <tr><td style="padding:6px; color:#64748b;">Ref / UTR No:</td><td style="padding:6px;">${data.reference_no || 'N/A'}</td></tr>
        <tr><td style="padding:6px; color:#64748b;"><?php echo __('notes', 'Notes'); ?>:</td><td style="padding:6px;">${data.notes || 'Payment settlement'}</td></tr>
      </table>
      <div style="margin-top:40px; display:flex; justify-content:space-between; align-items:flex-end;">
        <div>Received by: ____________</div>
        <div>Authorized Stamp & Signature</div>
      </div>
    </div>
  `;

  const printWin = window.open('', '', 'width=700,height=600');
  printWin.document.write('<html><head><title>Payment Receipt</title></head><body>' + windowContent + '</body></html>');
  printWin.document.close();
  printWin.focus();
  printWin.print();
  printWin.close();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
