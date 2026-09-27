<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Add / Edit / Delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $log_date = $_POST['log_date'] ?? date('Y-m-d');
        $tractor_id = intval($_POST['tractor_id'] ?? 0);
        $litres = floatval($_POST['litres'] ?? 0.0);
        $rate_per_litre = floatval($_POST['rate_per_litre'] ?? 0.0);
        $total_cost = $litres * $rate_per_litre;
        $fuel_station = trim($_POST['fuel_station'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Upload Bill Photo
        $bill_photo = $_POST['existing_photo'] ?? '';
        if (isset($_FILES['bill_photo']) && $_FILES['bill_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['bill_photo']['name'], PATHINFO_EXTENSION);
            $fileName = 'diesel_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            if (move_uploaded_file($_FILES['bill_photo']['tmp_name'], $uploadDir . $fileName)) {
                $bill_photo = 'assets/uploads/' . $fileName;
            }
        }

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("
                    UPDATE diesel_logs 
                    SET log_date = :ld, tractor_id = :t, litres = :l, rate_per_litre = :r, 
                        total_cost = :tot, fuel_station = :fs, bill_photo = :bp, notes = :n 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'ld' => $log_date, 't' => $tractor_id, 'l' => $litres, 'r' => $rate_per_litre,
                    'tot' => $total_cost, 'fs' => $fuel_station, 'bp' => $bill_photo, 'n' => $notes, 'id' => $id
                ]);
                setFlash('success', __('updated_success', 'Diesel log updated.'));
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO diesel_logs (log_date, tractor_id, litres, rate_per_litre, total_cost, fuel_station, bill_photo, notes)
                    VALUES (:ld, :t, :l, :r, :tot, :fs, :bp, :n)
                ");
                $stmt->execute([
                    'ld' => $log_date, 't' => $tractor_id, 'l' => $litres, 'r' => $rate_per_litre,
                    'tot' => $total_cost, 'fs' => $fuel_station, 'bp' => $bill_photo, 'n' => $notes
                ]);

                // Automatically log to expenses table as well for financial accounting
                $stmtExp = $conn->prepare("INSERT INTO expenses (expense_date, category, title, amount, description) VALUES (?, 'Diesel', ?, ?, ?)");
                $stmtExp->execute([$log_date, "Diesel refill: {$litres} Ltrs", $total_cost, "Refill at {$fuel_station}"]);

                setFlash('success', __('saved_success', 'Diesel refill logged successfully.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving diesel log:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM diesel_logs WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Diesel log removed.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting diesel log:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('diesel_management', 'Diesel Management');
require_once __DIR__ . '/../includes/header.php';

// Fetch Dropdown Options & Summaries
$tractors = $conn->query("SELECT * FROM tractors ORDER BY name ASC")->fetchAll();

$todayFuelCost = $conn->query("SELECT COALESCE(SUM(total_cost), 0) FROM diesel_logs WHERE log_date = CURRENT_DATE()")->fetchColumn();
$monthlyFuelCost = $conn->query("SELECT COALESCE(SUM(total_cost), 0) FROM diesel_logs WHERE MONTH(log_date) = MONTH(CURRENT_DATE()) AND YEAR(log_date) = YEAR(CURRENT_DATE())")->fetchColumn();
$monthlyLitres = $conn->query("SELECT COALESCE(SUM(litres), 0) FROM diesel_logs WHERE MONTH(log_date) = MONTH(CURRENT_DATE()) AND YEAR(log_date) = YEAR(CURRENT_DATE())")->fetchColumn();

// Fetch Diesel Logs List
$dieselLogs = $conn->query("
    SELECT d.*, t.name as tractor_name, t.reg_number
    FROM diesel_logs d
    JOIN tractors t ON d.tractor_id = t.id
    ORDER BY d.log_date DESC, d.id DESC
")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('diesel_management', 'Diesel & Fuel Management'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('diesel_logs', 'Track tractor fuel consumption, refilling bills, and fuel station expenses.'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#dieselModal" onclick="resetForm()">
    <i class="bi bi-fuel-pump-fill"></i> <?php echo __('log_fuel', 'Log Fuel Refill'); ?>
  </button>
</div>

<!-- Diesel KPI Metrics -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="card-erp p-3 border-start border-4 border-warning">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-warning"><?php echo __('today_fuel', 'Today\'s Fuel Cost'); ?></span>
          <div class="stat-value text-warning"><?php echo formatCurrency($todayFuelCost); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-warning-light"><i class="bi bi-fuel-pump"></i></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card-erp p-3 border-start border-4 border-primary">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-primary"><?php echo __('monthly_fuel', 'Monthly Fuel Cost'); ?></span>
          <div class="stat-value text-primary"><?php echo formatCurrency($monthlyFuelCost); ?></div>
        </div>
        <div class="stat-icon-wrapper bg-primary-light"><i class="bi bi-currency-rupee"></i></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card-erp p-3 border-start border-4 border-info">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="stat-label text-info"><?php echo __('total_litres', 'Monthly Consumption'); ?></span>
          <div class="stat-value text-info"><?php echo number_format($monthlyLitres, 2); ?> Ltrs</div>
        </div>
        <div class="stat-icon-wrapper bg-info-light"><i class="bi bi-droplet-fill"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- Diesel Logs Data Table -->
<div class="card-erp p-4">
  <div class="table-responsive">
    <table class="table table-custom align-middle dataTable">
      <thead>
        <tr>
          <th><?php echo __('date', 'Refill Date'); ?></th>
          <th><?php echo __('tractors', 'Tractor'); ?></th>
          <th><?php echo __('litres', 'Litres Filled'); ?></th>
          <th><?php echo __('rate_per_unit', 'Rate / Litre'); ?></th>
          <th><?php echo __('total_cost', 'Total Cost'); ?></th>
          <th><?php echo __('fuel_station', 'Fuel Station'); ?></th>
          <th>Bill Photo</th>
          <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dieselLogs as $d): ?>
          <tr>
            <td class="fw-bold"><?php echo formatDate($d['log_date'], 'd M Y'); ?></td>
            <td>
              <div class="fw-bold"><?php echo htmlspecialchars($d['tractor_name']); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars($d['reg_number']); ?></small>
            </td>
            <td class="fw-semibold text-primary"><?php echo number_format($d['litres'], 2); ?> Litres</td>
            <td>₹<?php echo number_format($d['rate_per_litre'], 2); ?></td>
            <td class="fw-bold text-danger"><?php echo formatCurrency($d['total_cost']); ?></td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($d['fuel_station'] ?: 'N/A'); ?></span></td>
            <td>
              <?php if (!empty($d['bill_photo']) && file_exists(__DIR__ . '/../' . $d['bill_photo'])): ?>
                <a href="<?php echo SITE_URL . '/' . ltrim($d['bill_photo'], '/'); ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-paperclip"></i> View Bill</a>
              <?php else: ?>
                <span class="text-muted small">No photo</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick='editDiesel(<?php echo json_encode($d); ?>)'><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $d['id']; ?>')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Diesel Modal -->
<div class="modal fade" id="dieselModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="index.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="diesel_id">
        <input type="hidden" name="existing_photo" id="existing_photo">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-fuel-pump-fill me-2 text-warning"></i><?php echo __('log_fuel', 'Log Fuel Refill'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('date', 'Refill Date'); ?> *</label>
            <input type="date" name="log_date" id="log_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
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

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold"><?php echo __('litres', 'Litres Filled'); ?> *</label>
              <input type="number" step="0.1" name="litres" id="dLitres" class="form-control" required placeholder="e.g. 40.0" oninput="calcDieselTotal()">
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold"><?php echo __('rate_per_unit', 'Rate / Litre'); ?> *</label>
              <input type="number" step="0.01" name="rate_per_litre" id="dRate" class="form-control" required value="94.50" oninput="calcDieselTotal()">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-danger"><?php echo __('total_cost', 'Total Cost'); ?></label>
            <input type="number" step="0.01" name="total_cost" id="dTotal" class="form-control fw-bold text-danger bg-light" readonly value="0.00">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('fuel_station', 'Fuel Station Bunk'); ?></label>
            <input type="text" name="fuel_station" id="fuel_station" class="form-control" placeholder="e.g. Indian Oil / HP Pump">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Bill Receipt Photo</label>
            <input type="file" name="bill_photo" class="form-control" accept="image/*">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?php echo __('notes', 'Notes'); ?></label>
            <input type="text" name="notes" id="notes" class="form-control" placeholder="Driver notes or fuel level...">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Diesel Log'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function calcDieselTotal() {
  const l = parseFloat(document.getElementById('dLitres').value) || 0;
  const r = parseFloat(document.getElementById('dRate').value) || 0;
  document.getElementById('dTotal').value = (l * r).toFixed(2);
}

function resetForm() {
  document.getElementById('diesel_id').value = '';
  document.getElementById('existing_photo').value = '';
  document.getElementById('log_date').value = '<?php echo date('Y-m-d'); ?>';
  document.getElementById('tractor_id').value = '';
  document.getElementById('dLitres').value = '';
  document.getElementById('dRate').value = '94.50';
  document.getElementById('dTotal').value = '0.00';
  document.getElementById('fuel_station').value = '';
  document.getElementById('notes').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-fuel-pump-fill me-2 text-warning"></i><?php echo __('log_fuel', 'Log Fuel Refill'); ?>';
}

function editDiesel(data) {
  document.getElementById('diesel_id').value = data.id;
  document.getElementById('existing_photo').value = data.bill_photo || '';
  document.getElementById('log_date').value = data.log_date;
  document.getElementById('tractor_id').value = data.tractor_id;
  document.getElementById('dLitres').value = data.litres;
  document.getElementById('dRate').value = data.rate_per_litre;
  document.getElementById('dTotal').value = data.total_cost;
  document.getElementById('fuel_station').value = data.fuel_station || '';
  document.getElementById('notes').value = data.notes || '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-warning"></i><?php echo __('edit', 'Edit Diesel Refill Log'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('dieselModal'));
  modal.show();
}

// Auto open modal if action=new query param is passed
<?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
document.addEventListener('DOMContentLoaded', function() {
  var modal = new bootstrap.Modal(document.getElementById('dieselModal'));
  modal.show();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
