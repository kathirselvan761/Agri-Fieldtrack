<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Add / Edit / Delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $service_date = $_POST['service_date'] ?? date('Y-m-d');
        $tractor_id = intval($_POST['tractor_id'] ?? 0);
        $category = $_POST['category'] ?? 'Engine Oil';
        $cost = floatval($_POST['cost'] ?? 0.00);
        $vendor = trim($_POST['vendor'] ?? '');
        $next_service_due = !empty($_POST['next_service_due']) ? $_POST['next_service_due'] : null;
        $notes = trim($_POST['notes'] ?? '');

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("
                    UPDATE services 
                    SET service_date = :sd, tractor_id = :t, category = :c, cost = :cost, 
                        vendor = :v, next_service_due = :nsd, notes = :n 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'sd' => $service_date, 't' => $tractor_id, 'c' => $category, 'cost' => $cost,
                    'v' => $vendor, 'nsd' => $next_service_due, 'n' => $notes, 'id' => $id
                ]);
                setFlash('success', __('updated_success', 'Maintenance service log updated.'));
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO services (service_date, tractor_id, category, cost, vendor, next_service_due, notes)
                    VALUES (:sd, :t, :c, :cost, :v, :nsd, :n)
                ");
                $stmt->execute([
                    'sd' => $service_date, 't' => $tractor_id, 'c' => $category, 'cost' => $cost,
                    'v' => $vendor, 'nsd' => $next_service_due, 'n' => $notes
                ]);

                // Also log to financial expenses
                $stmtExp = $conn->prepare("INSERT INTO expenses (expense_date, category, title, amount, description) VALUES (?, 'Repair', ?, ?, ?)");
                $stmtExp->execute([$service_date, "Tractor Service: {$category}", $cost, "Serviced at {$vendor}"]);

                setFlash('success', __('saved_success', 'Service & Repair log recorded successfully.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving service log:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Service log deleted.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting service log:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('service_maintenance', 'Service & Repair Management');
require_once __DIR__ . '/../includes/header.php';

// Fetch Tractors Dropdown
$tractors = $conn->query("SELECT * FROM tractors ORDER BY name ASC")->fetchAll();

// Fetch Services List
$services = $conn->query("
    SELECT s.*, t.name as tractor_name, t.reg_number
    FROM services s
    JOIN tractors t ON s.tractor_id = t.id
    ORDER BY s.service_date DESC, s.id DESC
")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('service_maintenance', 'Service & Repair Management'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('service_logs', 'Track engine oil, filters, tyre replacements, greasing, and spare part repairs with reminders.'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="resetForm()">
    <i class="bi bi-wrench-adjustable-circle-fill"></i> <?php echo __('log_service', 'Log Service / Repair'); ?>
  </button>
</div>

<!-- Services Data Table -->
<div class="card-erp p-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('date', 'Service Date'); ?></th>
          <th><?php echo __('tractors', 'Tractor'); ?></th>
          <th><?php echo __('service_category', 'Service Category'); ?></th>
          <th>Workshop / Vendor</th>
          <th><?php echo __('cost', 'Cost'); ?></th>
          <th><?php echo __('next_service', 'Next Service Due'); ?></th>
          <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td class="fw-bold"><?php echo formatDate($s['service_date'], 'd M Y'); ?></td>
            <td>
              <div class="fw-bold"><?php echo htmlspecialchars($s['tractor_name']); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars($s['reg_number']); ?></small>
            </td>
            <td><span class="badge bg-warning-light text-warning fw-bold fs-7"><?php echo htmlspecialchars($s['category']); ?></span></td>
            <td><?php echo htmlspecialchars($s['vendor'] ?: 'In-house Workshop'); ?></td>
            <td class="fw-bold text-danger"><?php echo formatCurrency($s['cost']); ?></td>
            <td>
              <?php if (!empty($s['next_service_due'])): ?>
                <?php 
                $dueDate = strtotime($s['next_service_due']);
                $isOverdue = $dueDate < time();
                ?>
                <span class="badge <?php echo $isOverdue ? 'bg-danger' : 'bg-info'; ?>">
                  <?php echo formatDate($s['next_service_due'], 'd M Y'); ?>
                  <?php if ($isOverdue) echo ' (Overdue)'; ?>
                </span>
              <?php else: ?>
                <span class="text-muted small">Not set</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick='editService(<?php echo json_encode($s); ?>)'><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $s['id']; ?>')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Service Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="index.php" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="service_id">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-wrench-adjustable me-2 text-primary"></i><?php echo __('log_service', 'Log Tractor Maintenance'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('date', 'Service Date'); ?> *</label>
            <input type="date" name="service_date" id="service_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('select_tractor', 'Tractor'); ?> *</label>
            <select name="tractor_id" id="tractor_id" class="form-select" required>
              <option value=""><?php echo __('select_tractor', 'Select Tractor'); ?></option>
              <?php foreach ($tractors as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['reg_number']); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('service_category', 'Service Category'); ?> *</label>
            <select name="category" id="category" class="form-select" required>
              <option value="Engine Oil">Engine Oil</option>
              <option value="Filter">Filter Replacement</option>
              <option value="Battery">Battery Service</option>
              <option value="Tyre">Tyre Repair / Replacement</option>
              <option value="Greasing">Greasing & Lubrication</option>
              <option value="Repair">General Overhaul / Repair</option>
              <option value="Spare Parts">Spare Parts Replacement</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('cost', 'Service Cost'); ?> *</label>
            <input type="number" step="0.01" name="cost" id="cost" class="form-control" required placeholder="4500.00">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Vendor / Workshop Name</label>
            <input type="text" name="vendor" id="vendor" class="form-control" placeholder="e.g. Mahindra Authorized Service Center">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('next_service', 'Next Service Due Date'); ?></label>
            <input type="date" name="next_service_due" id="next_service_due" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('notes', 'Service Notes'); ?></label>
            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Parts changed, warranty details..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Service Log'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetForm() {
  document.getElementById('service_id').value = '';
  document.getElementById('service_date').value = '<?php echo date('Y-m-d'); ?>';
  document.getElementById('tractor_id').value = '';
  document.getElementById('category').value = 'Engine Oil';
  document.getElementById('cost').value = '';
  document.getElementById('vendor').value = '';
  document.getElementById('next_service_due').value = '';
  document.getElementById('notes').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-wrench-adjustable me-2 text-primary"></i><?php echo __('log_service', 'Log Tractor Maintenance'); ?>';
}

function editService(data) {
  document.getElementById('service_id').value = data.id;
  document.getElementById('service_date').value = data.service_date;
  document.getElementById('tractor_id').value = data.tractor_id;
  document.getElementById('category').value = data.category;
  document.getElementById('cost').value = data.cost;
  document.getElementById('vendor').value = data.vendor || '';
  document.getElementById('next_service_due').value = data.next_service_due || '';
  document.getElementById('notes').value = data.notes || '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i><?php echo __('edit', 'Edit Maintenance Record'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('serviceModal'));
  modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
