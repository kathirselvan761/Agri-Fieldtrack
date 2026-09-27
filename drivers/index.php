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
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $license_number = trim($_POST['license_number'] ?? '');
        $salary = floatval($_POST['salary'] ?? 0.00);
        $status = $_POST['status'] ?? 'Active';

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("UPDATE drivers SET name = :n, phone = :p, address = :a, license_number = :l, salary = :s, status = :st WHERE id = :id");
                $stmt->execute(['n' => $name, 'p' => $phone, 'a' => $address, 'l' => $license_number, 's' => $salary, 'st' => $status, 'id' => $id]);
                setFlash('success', __('updated_success', 'Driver details updated.'));
            } else {
                $stmt = $conn->prepare("INSERT INTO drivers (name, phone, address, license_number, salary, status) VALUES (:n, :p, :a, :l, :s, :st)");
                $stmt->execute(['n' => $name, 'p' => $phone, 'a' => $address, 'l' => $license_number, 's' => $salary, 'st' => $status]);
                setFlash('success', __('saved_success', 'New Driver registered.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving driver:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM drivers WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Driver record deleted.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting driver:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('driver_management', 'Driver Management');
require_once __DIR__ . '/../includes/header.php';

$drivers = $conn->query("SELECT * FROM drivers ORDER BY id DESC")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('driver_management', 'Driver Management'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('driver_list', 'Manage tractor operators, monthly salaries, licenses, and duty status.'); ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="attendance.php" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
      <i class="bi bi-calendar-check-fill"></i> <?php echo __('attendance', 'Attendance Register'); ?>
    </a>
    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#driverModal" onclick="resetForm()">
      <i class="bi bi-person-plus-fill"></i> <?php echo __('add', 'Add New Driver'); ?>
    </button>
  </div>
</div>

<div class="card-erp p-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('driver_name', 'Driver Name'); ?></th>
          <th><?php echo __('phone', 'Phone Number'); ?></th>
          <th><?php echo __('license_number', 'License Number'); ?></th>
          <th><?php echo __('daily_salary', 'Monthly Salary'); ?></th>
          <th><?php echo __('status', 'Status'); ?></th>
          <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($drivers as $d): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-3">
                <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 42px; height: 42px;">
                  <i class="bi bi-person-vcard fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold"><?php echo htmlspecialchars($d['name']); ?></div>
                  <small class="text-muted"><?php echo htmlspecialchars($d['address'] ?: ''); ?></small>
                </div>
              </div>
            </td>
            <td class="fw-semibold"><?php echo htmlspecialchars($d['phone']); ?></td>
            <td><code><?php echo htmlspecialchars($d['license_number'] ?: 'N/A'); ?></code></td>
            <td class="fw-bold text-success"><?php echo formatCurrency($d['salary']); ?></td>
            <td>
              <span class="badge <?php echo $d['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                <?php echo __($d['status'], $d['status']); ?>
              </span>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick='editDriver(<?php echo json_encode($d); ?>)'><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $d['id']; ?>')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Driver Add/Edit Modal -->
<div class="modal fade" id="driverModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="index.php" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="driver_id">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-person-vcard me-2 text-primary"></i><?php echo __('add', 'Add Driver Details'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('driver_name', 'Driver Name'); ?> *</label>
            <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Karthik Raja">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('phone', 'Phone Number'); ?> *</label>
            <input type="text" name="phone" id="phone" class="form-control" required placeholder="e.g. 9123456780">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('license_number', 'License Number'); ?></label>
            <input type="text" name="license_number" id="license_number" class="form-control" placeholder="e.g. TN3820190012345">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('daily_salary', 'Monthly Salary'); ?></label>
            <input type="number" step="0.01" name="salary" id="salary" class="form-control" placeholder="18000.00">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('address', 'Address'); ?></label>
            <textarea name="address" id="address" class="form-control" rows="2" placeholder="Driver address..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('status', 'Status'); ?></label>
            <select name="status" id="status" class="form-select">
              <option value="Active"><?php echo __('active', 'Active'); ?></option>
              <option value="Inactive"><?php echo __('inactive', 'Inactive'); ?></option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Driver'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetForm() {
  document.getElementById('driver_id').value = '';
  document.getElementById('name').value = '';
  document.getElementById('phone').value = '';
  document.getElementById('license_number').value = '';
  document.getElementById('salary').value = '18000.00';
  document.getElementById('address').value = '';
  document.getElementById('status').value = 'Active';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-person-vcard me-2 text-primary"></i><?php echo __('add', 'Add Driver Details'); ?>';
}

function editDriver(data) {
  document.getElementById('driver_id').value = data.id;
  document.getElementById('name').value = data.name;
  document.getElementById('phone').value = data.phone;
  document.getElementById('license_number').value = data.license_number || '';
  document.getElementById('salary').value = data.salary;
  document.getElementById('address').value = data.address || '';
  document.getElementById('status').value = data.status;
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i><?php echo __('edit', 'Edit Driver Profile'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('driverModal'));
  modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
