<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Handle Attendance Submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attDate = $_POST['attendance_date'] ?? date('Y-m-d');
    $statuses = $_POST['status'] ?? [];
    $notes = $_POST['notes'] ?? [];

    try {
        $stmt = $conn->prepare("
            INSERT INTO attendance (attendance_date, driver_id, status, notes) 
            VALUES (:d, :driver_id, :status, :notes)
            ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)
        ");

        foreach ($statuses as $driverId => $status) {
            $note = $notes[$driverId] ?? '';
            $stmt->execute([
                'd' => $attDate,
                'driver_id' => $driverId,
                'status' => $status,
                'notes' => $note
            ]);
        }
        setFlash('success', __('saved_success', 'Attendance marked successfully.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error updating attendance:') . ' ' . $e->getMessage());
    }
    header("Location: attendance.php?date=" . $attDate);
    exit();
}

$pageTitle = __('attendance', 'Driver Attendance Register');
require_once __DIR__ . '/../includes/header.php';

// Fetch Active Drivers
$drivers = $conn->query("SELECT * FROM drivers WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

// Fetch existing attendance for selected date
$stmtAtt = $conn->prepare("SELECT * FROM attendance WHERE attendance_date = ?");
$stmtAtt->execute([$selectedDate]);
$existingAtt = [];
foreach ($stmtAtt->fetchAll() as $row) {
    $existingAtt[$row['driver_id']] = $row;
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('attendance', 'Driver Attendance Register'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('mark_attendance', 'Record daily driver presence, absences, and leaves.'); ?></p>
  </div>
  <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> <?php echo __('back', 'Drivers List'); ?></a>
</div>

<!-- Date Picker Row -->
<div class="card-erp p-3 mb-4">
  <form method="GET" class="row align-items-center g-3">
    <div class="col-auto">
      <label class="fw-semibold"><?php echo __('date', 'Select Register Date:'); ?></label>
    </div>
    <div class="col-auto">
      <input type="date" name="date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($selectedDate); ?>" onchange="this.form.submit()">
    </div>
    <div class="col-auto">
      <span class="badge bg-primary fs-7"><?php echo __('date', 'Marking for:'); ?> <?php echo formatDate($selectedDate, 'l, d F Y'); ?></span>
    </div>
  </form>
</div>

<!-- Attendance Form -->
<form action="" method="POST">
  <input type="hidden" name="save_attendance" value="1">
  <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selectedDate); ?>">

  <div class="card-erp p-4 mb-4">
    <div class="table-responsive">
      <table class="table table-custom align-middle">
        <thead>
          <tr>
            <th><?php echo __('driver_name', 'Driver Name'); ?></th>
            <th><?php echo __('phone', 'Phone'); ?></th>
            <th class="text-center"><?php echo __('attendance', 'Attendance Status'); ?></th>
            <th><?php echo __('notes', 'Notes / Duty Location'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($drivers as $d): 
            $currStatus = $existingAtt[$d['id']]['status'] ?? 'Present';
            $currNote = $existingAtt[$d['id']]['notes'] ?? '';
          ?>
            <tr>
              <td>
                <div class="fw-bold"><?php echo htmlspecialchars($d['name']); ?></div>
              </td>
              <td><?php echo htmlspecialchars($d['phone']); ?></td>
              <td class="text-center">
                <div class="btn-group" role="group">
                  <input type="radio" class="btn-check" name="status[<?php echo $d['id']; ?>]" id="pres_<?php echo $d['id']; ?>" value="Present" <?php echo $currStatus === 'Present' ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-success btn-sm" for="pres_<?php echo $d['id']; ?>"><i class="bi bi-check-lg me-1"></i><?php echo __('present', 'Present'); ?></label>

                  <input type="radio" class="btn-check" name="status[<?php echo $d['id']; ?>]" id="abs_<?php echo $d['id']; ?>" value="Absent" <?php echo $currStatus === 'Absent' ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-danger btn-sm" for="abs_<?php echo $d['id']; ?>"><i class="bi bi-x-lg me-1"></i><?php echo __('absent', 'Absent'); ?></label>

                  <input type="radio" class="btn-check" name="status[<?php echo $d['id']; ?>]" id="leave_<?php echo $d['id']; ?>" value="Leave" <?php echo $currStatus === 'Leave' ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-warning btn-sm" for="leave_<?php echo $d['id']; ?>"><i class="bi bi-person-slash me-1"></i><?php echo __('leave', 'Leave'); ?></label>
                </div>
              </td>
              <td>
                <input type="text" name="notes[<?php echo $d['id']; ?>]" class="form-control form-control-sm" placeholder="e.g. Field duty at Vadugapalayam" value="<?php echo htmlspecialchars($currNote); ?>">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="text-end mt-3">
      <button type="submit" class="btn btn-primary btn-sm shadow-sm"><i class="bi bi-check-circle me-1"></i> <?php echo __('save', 'Save Attendance Register'); ?></button>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
