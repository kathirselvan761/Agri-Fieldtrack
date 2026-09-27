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
        $unit_type = $_POST['unit_type'] ?? 'Hour';
        $rate = floatval($_POST['rate'] ?? 0.00);
        $description = trim($_POST['description'] ?? '');

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("UPDATE work_types SET name = :n, unit_type = :u, rate = :r, description = :d WHERE id = :id");
                $stmt->execute(['n' => $name, 'u' => $unit_type, 'r' => $rate, 'd' => $description, 'id' => $id]);
                setFlash('success', __('updated_success', 'Work type rate updated.'));
            } else {
                $stmt = $conn->prepare("INSERT INTO work_types (name, unit_type, rate, description) VALUES (:n, :u, :r, :d)");
                $stmt->execute(['n' => $name, 'u' => $unit_type, 'r' => $rate, 'd' => $description]);
                setFlash('success', __('saved_success', 'New agricultural work type created.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving work type:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM work_types WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Work type removed.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting work type:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('work_types', 'Work Types Management');
require_once __DIR__ . '/../includes/header.php';

$workTypes = $conn->query("SELECT * FROM work_types ORDER BY id ASC")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('work_types', 'Work Types & Rental Rates'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('work_types', 'Configure agricultural implement services (Rotavator, Plough, Land Levelling, Pitting, Haulage rates).'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#workTypeModal" onclick="resetForm()">
    <i class="bi bi-plus-lg"></i> <?php echo __('add', 'Create Work Type'); ?>
  </button>
</div>

<div class="card-erp p-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('work_types', 'Work Type Name'); ?></th>
          <th><?php echo __('billing_type', 'Billing Unit'); ?></th>
          <th><?php echo __('rate', 'Rental Rate'); ?></th>
          <th><?php echo __('description', 'Description'); ?></th>
          <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($workTypes as $wt): ?>
          <tr>
            <td class="fw-bold text-primary">
              <i class="bi bi-tools me-2 text-secondary"></i><?php echo htmlspecialchars($wt['name']); ?>
            </td>
            <td>
              <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($wt['unit_type']); ?></span>
            </td>
            <td class="fw-bold text-success"><?php echo formatCurrency($wt['rate']); ?> / <?php echo strtolower($wt['unit_type']); ?></td>
            <td class="text-muted small"><?php echo htmlspecialchars($wt['description'] ?: '-'); ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick='editWorkType(<?php echo json_encode($wt); ?>)'><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $wt['id']; ?>')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Work Type Modal -->
<div class="modal fade" id="workTypeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="index.php" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="wt_id">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-tools me-2 text-primary"></i><?php echo __('add', 'Create Work Type'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('work_types', 'Work Type Name'); ?> *</label>
            <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Rotavator / Banana Pit">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('billing_type', 'Billing Unit Type'); ?> *</label>
            <select name="unit_type" id="unit_type" class="form-select" required>
              <option value="Hour"><?php echo __('per_hour', 'Hour (Per Hour)'); ?></option>
              <option value="Acre"><?php echo __('per_acre', 'Acre (Per Acre)'); ?></option>
              <option value="Trip"><?php echo __('billing_type', 'Trip (Per Trip / Load)'); ?></option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('rate_per_unit', 'Rental Rate'); ?> *</label>
            <input type="number" step="0.01" name="rate" id="rate" class="form-control" required placeholder="1500.00">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('description', 'Description'); ?></label>
            <textarea name="description" id="description" class="form-control" rows="2" placeholder="Implementation specifications or field notes..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Work Type'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetForm() {
  document.getElementById('wt_id').value = '';
  document.getElementById('name').value = '';
  document.getElementById('unit_type').value = 'Hour';
  document.getElementById('rate').value = '1500.00';
  document.getElementById('description').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-tools me-2 text-primary"></i><?php echo __('add', 'Create Work Type'); ?>';
}

function editWorkType(data) {
  document.getElementById('wt_id').value = data.id;
  document.getElementById('name').value = data.name;
  document.getElementById('unit_type').value = data.unit_type;
  document.getElementById('rate').value = data.rate;
  document.getElementById('description').value = data.description || '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i><?php echo __('edit', 'Edit Work Type Rate'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('workTypeModal'));
  modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
