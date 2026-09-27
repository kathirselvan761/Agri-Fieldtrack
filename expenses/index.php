<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Add / Edit / Delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        $category = $_POST['category'] ?? 'Others';
        $title = trim($_POST['title'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0.00);
        $description = trim($_POST['description'] ?? '');

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("UPDATE expenses SET expense_date = :ed, category = :c, title = :t, amount = :a, description = :d WHERE id = :id");
                $stmt->execute(['ed' => $expense_date, 'c' => $category, 't' => $title, 'a' => $amount, 'd' => $description, 'id' => $id]);
                setFlash('success', __('updated_success', 'Expense record updated.'));
            } else {
                $stmt = $conn->prepare("INSERT INTO expenses (expense_date, category, title, amount, description) VALUES (:ed, :c, :t, :a, :d)");
                $stmt->execute(['ed' => $expense_date, 'c' => $category, 't' => $title, 'a' => $amount, 'd' => $description]);
                setFlash('success', __('saved_success', 'Expense recorded.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving expense:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Expense log deleted.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting expense:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('expenses', 'Expense Management');
require_once __DIR__ . '/../includes/header.php';

$expenses = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC, id DESC")->fetchAll();

$todayExpense = $conn->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = CURRENT_DATE()")->fetchColumn();
$monthlyExpense = $conn->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())")->fetchColumn();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('expenses', 'Business Expense Management'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('expenses', 'Record company operational costs including fuel, salaries, maintenance, and spares.'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#expenseModal" onclick="resetForm()">
    <i class="bi bi-wallet2"></i> <?php echo __('add', 'Add Expense Log'); ?>
  </button>
</div>

<!-- Expense Summaries -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-6">
    <div class="card-erp p-3 border-start border-4 border-danger">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-danger"><?php echo __('today_expenses', 'Today\'s Expenses'); ?></span>
          <div class="stat-value text-danger"><?php echo formatCurrency($todayExpense); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-danger-light"><i class="bi bi-cash"></i></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6">
    <div class="card-erp p-3 border-start border-4 border-warning">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-warning"><?php echo __('monthly_expenses', 'Monthly Expenses'); ?></span>
          <div class="stat-value text-warning"><?php echo formatCurrency($monthlyExpense); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-warning-light"><i class="bi bi-pie-chart"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- Expenses Table -->
<div class="card-erp p-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('date', 'Date'); ?></th>
          <th><?php echo __('category', 'Category'); ?></th>
          <th>Title / Purpose</th>
          <th><?php echo __('amount', 'Amount'); ?></th>
          <th><?php echo __('description', 'Description'); ?></th>
          <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($expenses as $ex): ?>
          <tr>
            <td class="fw-bold"><?php echo formatDate($ex['expense_date'], 'd M Y'); ?></td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ex['category']); ?></span></td>
            <td class="fw-semibold"><?php echo htmlspecialchars($ex['title']); ?></td>
            <td class="fw-bold text-danger"><?php echo formatCurrency($ex['amount']); ?></td>
            <td class="text-muted small"><?php echo htmlspecialchars($ex['description'] ?: '-'); ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick='editExpense(<?php echo json_encode($ex); ?>)'><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $ex['id']; ?>')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="index.php" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="expense_id">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-wallet2 me-2 text-primary"></i><?php echo __('add', 'Add Expense Entry'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('date', 'Expense Date'); ?> *</label>
            <input type="date" name="expense_date" id="expense_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('category', 'Expense Category'); ?> *</label>
            <select name="category" id="category" class="form-select" required>
              <option value="Diesel"><?php echo __('diesel', 'Diesel'); ?></option>
              <option value="Salary"><?php echo __('drivers', 'Driver / Worker Salary'); ?></option>
              <option value="Repair"><?php echo __('service_maintenance', 'Tractor Repair & Overhaul'); ?></option>
              <option value="Spare Parts"><?php echo __('service_category', 'Spare Parts Purchase'); ?></option>
              <option value="Others"><?php echo __('category', 'Others'); ?></option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Expense Title *</label>
            <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Hydraulic hose replacement">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('amount', 'Amount'); ?> *</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" required placeholder="2500.00">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('description', 'Description / Notes'); ?></label>
            <textarea name="description" id="description" class="form-control" rows="2" placeholder="Vendor name, bill number, notes..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Expense'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetForm() {
  document.getElementById('expense_id').value = '';
  document.getElementById('expense_date').value = '<?php echo date('Y-m-d'); ?>';
  document.getElementById('category').value = 'Diesel';
  document.getElementById('title').value = '';
  document.getElementById('amount').value = '';
  document.getElementById('description').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-wallet2 me-2 text-primary"></i><?php echo __('add', 'Add Expense Entry'); ?>';
}

function editExpense(data) {
  document.getElementById('expense_id').value = data.id;
  document.getElementById('expense_date').value = data.expense_date;
  document.getElementById('category').value = data.category;
  document.getElementById('title').value = data.title;
  document.getElementById('amount').value = data.amount;
  document.getElementById('description').value = data.description || '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i><?php echo __('edit', 'Edit Expense Entry'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('expenseModal'));
  modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
