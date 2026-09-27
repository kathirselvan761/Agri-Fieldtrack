<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Add / Edit / Delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $tractor_id = intval($_POST['tractor_id'] ?? 0);
        $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
        $work_type_id = intval($_POST['work_type_id'] ?? 0);
        $booking_date = $_POST['booking_date'] ?? date('Y-m-d');
        $start_time = $_POST['start_time'] ?? '08:00:00';
        $end_time = $_POST['end_time'] ?? '17:00:00';
        $status = $_POST['status'] ?? 'Confirmed';
        $notes = trim($_POST['notes'] ?? '');

        try {
            if (!empty($id)) {
                $stmt = $conn->prepare("
                    UPDATE bookings 
                    SET customer_id = :c, tractor_id = :t, driver_id = :dr, work_type_id = :wt, 
                        booking_date = :bd, start_time = :st, end_time = :et, status = :s, notes = :n 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'c' => $customer_id, 't' => $tractor_id, 'dr' => $driver_id, 'wt' => $work_type_id,
                    'bd' => $booking_date, 'st' => $start_time, 'et' => $end_time, 's' => $status, 'n' => $notes, 'id' => $id
                ]);
                setFlash('success', __('updated_success', 'Booking details updated.'));
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO bookings (customer_id, tractor_id, driver_id, work_type_id, booking_date, start_time, end_time, status, notes)
                    VALUES (:c, :t, :dr, :wt, :bd, :st, :et, :s, :n)
                ");
                $stmt->execute([
                    'c' => $customer_id, 't' => $tractor_id, 'dr' => $driver_id, 'wt' => $work_type_id,
                    'bd' => $booking_date, 'st' => $start_time, 'et' => $end_time, 's' => $status, 'n' => $notes
                ]);
                setFlash('success', __('saved_success', 'New Tractor Booking created.'));
            }
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Error saving booking:') . ' ' . $e->getMessage());
        }
        header("Location: index.php");
        exit();
    }
}

// Handle Delete GET
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$delId]);
        setFlash('success', __('deleted_success', 'Booking cancelled and deleted.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error deleting booking:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('bookings_calendar', 'Booking Management & Calendar');
require_once __DIR__ . '/../includes/header.php';

// Fetch Dropdown Options
$customers = $conn->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
$tractors = $conn->query("SELECT * FROM tractors ORDER BY name ASC")->fetchAll();
$drivers = $conn->query("SELECT * FROM drivers WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
$workTypes = $conn->query("SELECT * FROM work_types ORDER BY name ASC")->fetchAll();

// Fetch Bookings List
$bookings = $conn->query("
    SELECT b.*, c.name as customer_name, c.mobile as customer_mobile, c.village, 
           t.name as tractor_name, d.name as driver_name, wt.name as work_type_name
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN tractors t ON b.tractor_id = t.id
    LEFT JOIN drivers d ON b.driver_id = d.id
    JOIN work_types wt ON b.work_type_id = wt.id
    ORDER BY b.booking_date ASC
")->fetchAll();

// Prepare Events Array for FullCalendar
$calendarEvents = [];
foreach ($bookings as $b) {
    $color = '#10b981'; // Confirmed green
    if ($b['status'] === 'In Progress') $color = '#2b7fff';
    if ($b['status'] === 'Completed') $color = '#64748b';
    if ($b['status'] === 'Cancelled') $color = '#ef4444';

    $calendarEvents[] = [
        'id' => $b['id'],
        'title' => $b['customer_name'] . ' - ' . $b['tractor_name'] . ' (' . $b['work_type_name'] . ')',
        'start' => $b['booking_date'] . 'T' . $b['start_time'],
        'end' => $b['booking_date'] . 'T' . $b['end_time'],
        'backgroundColor' => $color,
        'borderColor' => $color
    ];
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('bookings_calendar', 'Booking Management & Calendar'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('booking_management', 'Schedule tractor reservations, assign driver operators, and manage interactive calendar timelines.'); ?></p>
  </div>
  <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#bookingModal" onclick="resetForm()">
    <i class="bi bi-calendar-plus"></i> <?php echo __('new_booking', 'Add New Booking'); ?>
  </button>
</div>

<!-- Tabs for Calendar View & Table View -->
<ul class="nav nav-tabs mb-4" id="bookingTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-semibold" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" type="button" role="tab"><i class="bi bi-calendar-range me-2 text-primary"></i><?php echo __('bookings_calendar', 'Interactive Calendar'); ?></button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-semibold" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-view" type="button" role="tab"><i class="bi bi-list-task me-2 text-primary"></i><?php echo __('booking_list', 'Bookings List View'); ?></button>
  </li>
</ul>

<div class="tab-content" id="bookingTabContent">
  <!-- Calendar View Tab -->
  <div class="tab-pane fade show active" id="calendar-view" role="tabpanel">
    <div class="card-erp p-4">
      <div id="bookingCalendar" style="min-height: 600px;"></div>
    </div>
  </div>

  <!-- Table View Tab -->
  <div class="tab-pane fade" id="table-view" role="tabpanel">
    <div class="card-erp p-4">
      <div class="table-responsive">
        <table class="table table-custom align-middle dataTable">
          <thead>
            <tr>
              <th><?php echo __('booking_date', 'Booking Date & Time'); ?></th>
              <th><?php echo __('customers', 'Customer & Village'); ?></th>
              <th><?php echo __('tractors', 'Tractor'); ?></th>
              <th><?php echo __('drivers', 'Driver'); ?></th>
              <th><?php echo __('work_types', 'Work Type'); ?></th>
              <th><?php echo __('status', 'Status'); ?></th>
              <th class="text-end"><?php echo __('actions', 'Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td>
                  <div class="fw-bold"><?php echo formatDate($b['booking_date'], 'd M Y'); ?></div>
                  <small class="text-muted"><?php echo date('h:i A', strtotime($b['start_time'])); ?> - <?php echo date('h:i A', strtotime($b['end_time'])); ?></small>
                </td>
                <td>
                  <div class="fw-bold"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                  <small class="text-muted"><?php echo htmlspecialchars($b['village']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($b['tractor_name']); ?></td>
                <td><?php echo htmlspecialchars($b['driver_name'] ?: 'Unassigned'); ?></td>
                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($b['work_type_name']); ?></span></td>
                <td>
                  <span class="badge <?php echo $b['status'] === 'Confirmed' ? 'bg-success' : ($b['status'] === 'In Progress' ? 'bg-primary' : 'bg-secondary'); ?>">
                    <?php echo __($b['status'], $b['status']); ?>
                  </span>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary me-1" onclick='editBooking(<?php echo json_encode($b); ?>)'><i class="bi bi-pencil"></i></button>
                  <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('index.php?delete_id=<?php echo $b['id']; ?>')"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form action="index.php" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="booking_id">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-calendar-plus me-2 text-primary"></i><?php echo __('new_booking_title', 'Schedule Tractor Booking'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('select_customer', 'Customer'); ?> *</label>
              <select name="customer_id" id="customer_id" class="form-select" required>
                <option value=""><?php echo __('select_customer', 'Select Customer'); ?></option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['village']); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('select_tractor', 'Tractor'); ?> *</label>
              <select name="tractor_id" id="tractor_id" class="form-select" required>
                <option value=""><?php echo __('select_tractor', 'Select Tractor'); ?></option>
                <?php foreach ($tractors as $t): ?>
                  <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['status']); ?>)</option>
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

            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('select_work_type', 'Work Type Service'); ?> *</label>
              <select name="work_type_id" id="work_type_id" class="form-select" required>
                <option value=""><?php echo __('select_work_type', 'Select Work Type'); ?></option>
                <?php foreach ($workTypes as $wt): ?>
                  <option value="<?php echo $wt['id']; ?>"><?php echo htmlspecialchars($wt['name']); ?> (₹<?php echo number_format($wt['rate']); ?>/<?php echo $wt['unit_type']; ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('booking_date', 'Booking Date'); ?> *</label>
              <input type="date" name="booking_date" id="booking_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('start_time', 'Start Time'); ?></label>
              <input type="time" name="start_time" id="start_time" class="form-control" value="08:00">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold"><?php echo __('end_time', 'End Time'); ?></label>
              <input type="time" name="end_time" id="end_time" class="form-control" value="17:00">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('status', 'Booking Status'); ?></label>
              <select name="status" id="status" class="form-select">
                <option value="Confirmed"><?php echo __('confirmed', 'Confirmed'); ?></option>
                <option value="In Progress"><?php echo __('status', 'In Progress'); ?></option>
                <option value="Completed"><?php echo __('completed', 'Completed'); ?></option>
                <option value="Cancelled"><?php echo __('cancelled', 'Cancelled'); ?></option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold"><?php echo __('notes', 'Booking Notes'); ?></label>
              <input type="text" name="notes" id="notes" class="form-control" placeholder="Special requirements, field instructions...">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Booking'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('bookingCalendar');
  if (calendarEl) {
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listWeek'
      },
      events: <?php echo json_encode($calendarEvents); ?>,
      eventClick: function(info) {
        alert('Booking Event: ' + info.event.title + '\nStart: ' + info.event.start.toLocaleString());
      }
    });
    calendar.render();
  }
});

function resetForm() {
  document.getElementById('booking_id').value = '';
  document.getElementById('customer_id').value = '';
  document.getElementById('tractor_id').value = '';
  document.getElementById('driver_id').value = '';
  document.getElementById('work_type_id').value = '';
  document.getElementById('booking_date').value = '<?php echo date('Y-m-d'); ?>';
  document.getElementById('start_time').value = '08:00';
  document.getElementById('end_time').value = '17:00';
  document.getElementById('status').value = 'Confirmed';
  document.getElementById('notes').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-calendar-plus me-2 text-primary"></i><?php echo __('new_booking_title', 'Schedule Tractor Booking'); ?>';
}

function editBooking(data) {
  document.getElementById('booking_id').value = data.id;
  document.getElementById('customer_id').value = data.customer_id;
  document.getElementById('tractor_id').value = data.tractor_id;
  document.getElementById('driver_id').value = data.driver_id || '';
  document.getElementById('work_type_id').value = data.work_type_id;
  document.getElementById('booking_date').value = data.booking_date;
  document.getElementById('start_time').value = data.start_time;
  document.getElementById('end_time').value = data.end_time;
  document.getElementById('status').value = data.status;
  document.getElementById('notes').value = data.notes || '';
  document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i><?php echo __('edit', 'Edit Booking Details'); ?>';
  var modal = new bootstrap.Modal(document.getElementById('bookingModal'));
  modal.show();
}

// Auto open modal if action=new query param is passed
<?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
document.addEventListener('DOMContentLoaded', function() {
  var modal = new bootstrap.Modal(document.getElementById('bookingModal'));
  modal.show();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
