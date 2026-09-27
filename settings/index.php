<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkLoggedIn();

// Handle Database SQL Backup Download GET
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    try {
        $tables = ['users', 'company_settings', 'tractors', 'customers', 'drivers', 'work_types', 'bookings', 'work_entries', 'payments', 'diesel_logs', 'services', 'expenses', 'attendance'];
        $sqlDump = "-- AgriTrak ERP Database Backup\n-- Generated at " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $sqlDump .= "TRUNCATE TABLE `$table`;\n";
            $rows = $conn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $vals = array_map(function($v) use ($conn) {
                    return $v === null ? "NULL" : $conn->quote($v);
                }, array_values($row));
                $sqlDump .= "INSERT INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
            }
            $sqlDump .= "\n";
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=agritrak_db_backup_' . date('Ymd_His') . '.sql');
        echo $sqlDump;
        exit();
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error generating backup:') . ' ' . $e->getMessage());
    }
}

// Handle Database Restore POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_db'])) {
    if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
        $sqlContent = file_get_contents($_FILES['sql_file']['tmp_name']);
        try {
            $conn->exec($sqlContent);
            setFlash('success', __('saved_success', 'Database restored successfully from SQL file.'));
        } catch (Exception $e) {
            setFlash('danger', __('error_occurred', 'Restore error:') . ' ' . $e->getMessage());
        }
    } else {
        setFlash('danger', __('error_occurred', 'Please upload a valid SQL file.'));
    }
    header("Location: index.php");
    exit();
}

// Handle Company Details POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_company'])) {
    $c_name = trim($_POST['company_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currency = trim($_POST['currency_symbol'] ?? '₹');
    $tax = trim($_POST['tax_number'] ?? '');

    try {
        $stmt = $conn->prepare("UPDATE company_settings SET company_name = :cn, phone = :p, email = :e, address = :a, currency_symbol = :cs, tax_number = :t WHERE id = 1");
        $stmt->execute(['cn' => $c_name, 'p' => $phone, 'e' => $email, 'a' => $address, 'cs' => $currency, 't' => $tax]);
        setFlash('success', __('updated_success', 'Company profile details updated.'));
    } catch (Exception $e) {
        setFlash('danger', __('error_occurred', 'Error updating company:') . ' ' . $e->getMessage());
    }
    header("Location: index.php");
    exit();
}

// Handle Change Password POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    $stmtU = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmtU->execute([$currentUser['id']]);
    $userRow = $stmtU->fetch();

    if ($userRow && password_verify($currPass, $userRow['password'])) {
        if ($newPass === $confirmPass && strlen($newPass) >= 6) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmtUp = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUp->execute([$hashed, $currentUser['id']]);
            setFlash('success', __('updated_success', 'Admin password changed successfully.'));
        } else {
            setFlash('danger', __('error_occurred', 'New password must match and be at least 6 characters.'));
        }
    } else {
        setFlash('danger', __('error_occurred', 'Current password incorrect.'));
    }
    header("Location: index.php");
    exit();
}

$pageTitle = __('settings', 'System Settings');
require_once __DIR__ . '/../includes/header.php';

$company = getCompanySettings();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo __('settings', 'System Settings & Database'); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('system_settings', 'Configure company details, update admin credentials, and generate database backups.'); ?></p>
  </div>
</div>

<div class="row g-4">
  <!-- 1. Company Settings -->
  <div class="col-12 col-lg-6" id="company">
    <div class="card-erp p-4">
      <h5 class="fw-bold brand-font text-primary mb-3"><i class="bi bi-building me-2"></i><?php echo __('company_settings', 'Company Details & Branding'); ?></h5>
      <form action="index.php" method="POST">
        <input type="hidden" name="save_company" value="1">
        
        <div class="mb-3">
          <label class="form-label fw-semibold"><?php echo __('company_name', 'Company Name'); ?> *</label>
          <input type="text" name="company_name" class="form-control" required value="<?php echo htmlspecialchars($company['company_name']); ?>">
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold"><?php echo __('phone', 'Phone Number'); ?></label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($company['phone']); ?>">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold"><?php echo __('email', 'Email Address'); ?></label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($company['email']); ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold"><?php echo __('address', 'Business Address'); ?></label>
          <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($company['address']); ?></textarea>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold"><?php echo __('currency_symbol', 'Currency Symbol'); ?></label>
            <input type="text" name="currency_symbol" class="form-control" value="<?php echo htmlspecialchars($company['currency_symbol']); ?>">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">GSTIN / Tax ID</label>
            <input type="text" name="tax_number" class="form-control" value="<?php echo htmlspecialchars($company['tax_number']); ?>">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> <?php echo __('save', 'Save Company Profile'); ?></button>
      </form>
    </div>
  </div>

  <!-- 2. Account & Password Settings -->
  <div class="col-12 col-lg-6">
    <div class="card-erp p-4 mb-4">
      <h5 class="fw-bold brand-font text-info mb-3"><i class="bi bi-shield-lock me-2"></i><?php echo __('change_password', 'Change Admin Password'); ?></h5>
      <form action="index.php" method="POST">
        <input type="hidden" name="change_password" value="1">
        
        <div class="mb-3">
          <label class="form-label fw-semibold"><?php echo __('current_password', 'Current Password'); ?></label>
          <input type="password" name="current_password" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold"><?php echo __('new_password', 'New Password'); ?></label>
          <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold"><?php echo __('confirm_password', 'Confirm New Password'); ?></label>
          <input type="password" name="confirm_password" class="form-control" required minlength="6">
        </div>

        <button type="submit" class="btn btn-info text-white btn-sm"><i class="bi bi-key me-1"></i> <?php echo __('update_password', 'Update Password'); ?></button>
      </form>
    </div>

    <!-- 3. Database Backup & Restore -->
    <div class="card-erp p-4">
      <h5 class="fw-bold brand-font text-success mb-3"><i class="bi bi-database-gear me-2"></i><?php echo __('backup_restore', 'Database Backup & Restore'); ?></h5>
      
      <div class="mb-4 pb-3 border-bottom">
        <label class="fw-semibold d-block mb-1"><?php echo __('download_backup', 'Download Database Backup (.sql)'); ?></label>
        <small class="text-muted d-block mb-3">Generates an instant full MySQL SQL dump file containing schema and all records.</small>
        <a href="index.php?action=backup" class="btn btn-success btn-sm"><i class="bi bi-download me-1"></i> <?php echo __('download_backup', 'Export Database SQL Dump'); ?></a>
      </div>

      <form action="index.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="restore_db" value="1">
        <label class="fw-semibold d-block mb-1"><?php echo __('restore_db', 'Restore Database from SQL File'); ?></label>
        <div class="input-group input-group-sm mb-2">
          <input type="file" name="sql_file" class="form-control" accept=".sql" required>
          <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Warning: Restoring will overwrite existing data. Proceed?')"><i class="bi bi-upload me-1"></i> Restore SQL</button>
        </div>
        <small class="text-muted">Choose a valid .sql backup file to restore database tables.</small>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
