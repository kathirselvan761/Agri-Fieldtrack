<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Add / Edit / Delete POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $reg_number = trim($_POST['reg_number'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $year = intval($_POST['year'] ?? date('Y'));
        $status = $_POST['status'] ?? 'Available';
        $notes = trim($_POST['notes'] ?? '');

        // Photo Upload Handling
        $photo = $_POST['existing_photo'] ?? '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = 'tractor_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
                $photo = 'assets/uploads/' . $fileName;
            }
        }

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("UPDATE tractors SET name = :n, reg_number = :r, brand = :b, model = :m, year = :y, status = :s, photo = :p, notes = :notes WHERE id = :id");
                $stmt->execute(['n' => $name, 'r' => $reg_number, 'b' => $brand, 'm' => $model, 'y' => $year, 's' => $status, 'p' => $photo, 'notes' => $notes, 'id' => $id]);
                setFlash('success', __('updated_success', 'Tractor updated successfully.'));
            } else {
                $stmt = $conn->prepare("INSERT INTO tractors (name, reg_number, brand, model, year, status, photo, notes) VALUES (:n, :r, :b, :m, :y, :s, :p, :notes)");
                $stmt->execute(['n' => $name, 'r' => $reg_number, 'b' => $brand, 'm' => $model, 'y' => $year, 's' => $status, 'p' => $photo, 'notes' => $notes]);
                setFlash('success', __('saved_success', 'New Tractor added to fleet.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving tractor:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM tractors WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Tractor deleted from system.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting tractor:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('tractor_management', 'Tractor Management');
require_once __DIR__ . '/../includes/header.php';

// Fetch Tractors List with Filter
$filterStatus = $_GET['status'] ?? 'All';
$sql = "SELECT * FROM tractors";
if ($filterStatus !== 'All') {
    $sql .= " WHERE status = " . $conn->quote($filterStatus);
}
$sql .= " ORDER BY id DESC";
$tractors = $conn->query($sql)->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('tractor_management', 'Tractor Management'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('tractor_list', 'Manage fleet specifications, active field statuses, and maintenance records.'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#tractorModal" onclick="resetForm()">
    <i class="bi bi-plus-lg"></i> <?php echo __('add', 'Add New Tractor'); ?>
  </button>
</div>

<!-- Status Filter Tabs -->
<div class="d-flex gap-2 mb-4 overflow-auto pb-2">
  <a href="index.php?status=All" class="btn btn-sm <?php echo $filterStatus === 'All' ? 'btn-primary' : 'btn-outline-secondary'; ?>"><?php echo __('all', 'All Fleet'); ?></a>
  <a href="index.php?status=Available" class="btn btn-sm <?php echo $filterStatus === 'Available' ? 'btn-success' : 'btn-outline-success'; ?>"><?php echo __('active', 'Available'); ?></a>
  <a href="index.php?status=Working" class="btn btn-sm <?php echo $filterStatus === 'Working' ? 'btn-info' : 'btn-outline-info'; ?>"><?php echo __('status', 'Working'); ?></a>
  <a href="index.php?status=Service" class="btn btn-sm <?php echo $filterStatus === 'Service' ? 'btn-warning' : 'btn-outline-warning'; ?>"><?php echo __('under_service', 'Service'); ?></a>
  <a href="index.php?status=Breakdown" class="btn btn-sm <?php echo $filterStatus === 'Breakdown' ? 'btn-danger' : 'btn-outline-danger'; ?>"><?php echo __('status', 'Breakdown'); ?></a>
</div>

<!-- Tractors Grid / Table -->
<div class="card-erp p-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('tractor_name', 'Tractor Name'); ?></th>
          <th><?php echo __('registration_number', 'Reg. Number'); ?></th>
          <th><?php echo __('model_year', 'Brand & Model'); ?></th>
          <th><?php echo __('model_year', 'Year'); ?></th>
          <th><?php echo __('status', 'Status'); ?></th>
          <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tractors as $t): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-3">
                <div class="bg-light rounded p-2 text-center" style="width: 48px; height: 48px; display:flex; align-items:center; justify-content:center;">
                  <?php if (!empty($t['photo']) && file_exists(__DIR__ . '/../' . $t['photo'])): ?>
                    <img src="<?php echo SITE_URL . '/' . ltrim($t['photo'], '/'); ?>" class="img-fluid rounded" alt="Tractor">
                  <?php else: ?>
                    <i class="fa-solid fa-tractor fs-4 text-primary"></i>
                  <?php endif; ?>
                </div>
                <div>
                  <div class="fw-bold"><?php echo htmlspecialchars($t['name']); ?></div>
                  <small class="text-muted"><?php echo htmlspecialchars($t['notes'] ?: 'No notes'); ?></small>
                </div>
              </div>
            </td>
            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($t['reg_number']); ?></td>
            <td><?php echo htmlspecialchars($t['brand'] . ' ' . $t['model']); ?></td>
            <td><?php echo htmlspecialchars($t['year']); ?></td>
            <td>
              <?php
              $st = strtolower($t['status']);
              $badgeClass = $st === 'available' ? 'badge-available' : ($st === 'working' ? 'badge-working' : ($st === 'service' ? 'badge-service' : 'badge-breakdown'));
              ?>
              <span class="badge-status <?php echo $badgeClass; ?>"><?php echo __($t['status'], $t['status']); ?></span>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick='editTractor(<?php echo json_encode($t); ?>)'><i class="bi bi-pencil"></i> <?php echo __('edit', 'Edit'); ?></button>
              <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $t['id']; ?>')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Tractor Add/Edit Modal -->
<div class="modal fade" id="tractorModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form action="index.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="tractor_id">
        <input type="hidden" name="existing_photo" id="existing_photo">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="fa-solid fa-tractor me-2 text-primary"></i><?php echo __('add', 'Add New Tractor'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('tractor_name', 'Tractor Name'); ?> *</label>
              <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Mahindra 575 DI">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('registration_number', 'Registration Number'); ?> *</label>
              <input type="text" name="reg_number" id="reg_number" class="form-control" required placeholder="e.g. TN-38-AX-1001">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Brand *</label>
              <input type="text" name="brand" id="brand" class="form-control" required placeholder="e.g. Mahindra">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('model_year', 'Model'); ?> *</label>
              <input type="text" name="model" id="model" class="form-control" required placeholder="e.g. 575 DI SP Plus">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('model_year', 'Manufacturing Year'); ?></label>
              <input type="number" name="year" id="year" class="form-control" value="<?php echo date('Y'); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('status', 'Operational Status'); ?></label>
              <select name="status" id="status" class="form-select">
                <option value="Available"><?php echo __('active', 'Available'); ?></option>
                <option value="Working"><?php echo __('status', 'Working'); ?></option>
                <option value="Service"><?php echo __('under_service', 'Service'); ?></option>
                <option value="Breakdown"><?php echo __('status', 'Breakdown'); ?></option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tractor Photo</label>
              <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold"><?php echo __('notes', 'Notes / Maintenance Info'); ?></label>
              <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Engine details, attachment info, or notes..."></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Tractor'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetForm() {
  document.getElementById('tractor_id').value = '';
  document.getElementById('existing_photo').value = '';
  document.getElementById('name').value = '';
  document.getElementById('reg_number').value = '';
  document.getElementById('brand').value = '';
  document.getElementById('model').value = '';
  document.getElementById('year').value = '<?php echo date('Y'); ?>';
  document.getElementById('status').value = 'Available';
  document.getElementById('notes').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-tractor me-2 text-primary"></i><?php echo __('add', 'Add New Tractor'); ?>';
}

function editTractor(data) {
  document.getElementById('tractor_id').value = data.id;
  document.getElementById('existing_photo').value = data.photo || '';
  document.getElementById('name').value = data.name;
  document.getElementById('reg_number').value = data.reg_number;
  document.getElementById('brand').value = data.brand;
  document.getElementById('model').value = data.model;
  document.getElementById('year').value = data.year;
  document.getElementById('status').value = data.status;
  document.getElementById('notes').value = data.notes || '';
  document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-tractor me-2 text-primary"></i><?php echo __('edit', 'Edit Tractor Details'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('tractorModal'));
  modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
