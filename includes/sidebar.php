<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside id="sidebar">
  <div class="sidebar-header">
    <a href="<?php echo SITE_URL; ?>/dashboard.php" class="sidebar-brand">
      <i class="fa-solid fa-tractor"></i>
      <span><?php echo __('app_name', 'AgriTrak'); ?> <small style="font-size:0.65rem; display:block; font-weight:400; color:#94a3b8;"><?php echo __('enterprise_erp', 'ENTERPRISE ERP'); ?></small></span>
    </a>
  </div>
  
  <ul class="sidebar-menu">
    <li class="sidebar-heading"><?php echo __('nav_main_dashboard', 'MAIN DASHBOARD'); ?></li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/dashboard.php" class="sidebar-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="bi bi-grid-1x2-fill"></i>
        <span><?php echo __('dashboard', 'Dashboard'); ?></span>
      </a>
    </li>

    <li class="sidebar-heading"><?php echo __('nav_fleet_resources', 'FLEET & RESOURCES'); ?></li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/tractors/index.php" class="sidebar-link <?php echo ($current_dir == 'tractors') ? 'active' : ''; ?>">
        <i class="fa-solid fa-tractor"></i>
        <span><?php echo __('tractors', 'Tractors'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/customers/index.php" class="sidebar-link <?php echo ($current_dir == 'customers') ? 'active' : ''; ?>">
        <i class="bi bi-people-fill"></i>
        <span><?php echo __('customers', 'Customers'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/drivers/index.php" class="sidebar-link <?php echo ($current_dir == 'drivers' && $current_page != 'attendance.php') ? 'active' : ''; ?>">
        <i class="bi bi-person-vcard-fill"></i>
        <span><?php echo __('drivers', 'Drivers'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/drivers/attendance.php" class="sidebar-link <?php echo ($current_dir == 'drivers' && $current_page == 'attendance.php') ? 'active' : ''; ?>">
        <i class="bi bi-calendar-check-fill"></i>
        <span><?php echo __('attendance', 'Attendance'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/work_types/index.php" class="sidebar-link <?php echo ($current_dir == 'work_types') ? 'active' : ''; ?>">
        <i class="bi bi-tools"></i>
        <span><?php echo __('work_types', 'Work Types'); ?></span>
      </a>
    </li>

    <li class="sidebar-heading"><?php echo __('nav_operations', 'OPERATIONS'); ?></li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/work_entries/index.php" class="sidebar-link <?php echo ($current_dir == 'work_entries') ? 'active' : ''; ?>">
        <i class="bi bi-journal-plus"></i>
        <span><?php echo __('daily_work_entry', 'Daily Work Entry'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/bookings/index.php" class="sidebar-link <?php echo ($current_dir == 'bookings') ? 'active' : ''; ?>">
        <i class="bi bi-calendar-event-fill"></i>
        <span><?php echo __('bookings_calendar', 'Bookings & Calendar'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/payments/index.php" class="sidebar-link <?php echo ($current_dir == 'payments') ? 'active' : ''; ?>">
        <i class="bi bi-cash-stack"></i>
        <span><?php echo __('payment_receipts', 'Payment Receipts'); ?></span>
      </a>
    </li>

    <li class="sidebar-heading"><?php echo __('nav_maintenance_expenses', 'MAINTENANCE & EXPENSES'); ?></li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/diesel/index.php" class="sidebar-link <?php echo ($current_dir == 'diesel') ? 'active' : ''; ?>">
        <i class="bi bi-fuel-pump-fill"></i>
        <span><?php echo __('diesel_logs', 'Diesel Logs'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/services/index.php" class="sidebar-link <?php echo ($current_dir == 'services') ? 'active' : ''; ?>">
        <i class="bi bi-wrench-adjustable-circle-fill"></i>
        <span><?php echo __('service_repair', 'Service & Repair'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/expenses/index.php" class="sidebar-link <?php echo ($current_dir == 'expenses') ? 'active' : ''; ?>">
        <i class="bi bi-wallet2"></i>
        <span><?php echo __('expenses', 'Expenses'); ?></span>
      </a>
    </li>

    <li class="sidebar-heading"><?php echo __('nav_system_reports', 'SYSTEM & REPORTS'); ?></li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/reports/index.php" class="sidebar-link <?php echo ($current_dir == 'reports') ? 'active' : ''; ?>">
        <i class="bi bi-file-earmark-bar-graph-fill"></i>
        <span><?php echo __('reports_analytics', 'Reports & Analytics'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/search.php" class="sidebar-link <?php echo ($current_page == 'search.php') ? 'active' : ''; ?>">
        <i class="bi bi-search"></i>
        <span><?php echo __('global_search', 'Global Search'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/contact.php" class="sidebar-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
        <i class="bi bi-telephone-fill"></i>
        <span><?php echo __('contact_us', 'Contact Us'); ?></span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="<?php echo SITE_URL; ?>/settings/index.php" class="sidebar-link <?php echo ($current_dir == 'settings') ? 'active' : ''; ?>">
        <i class="bi bi-gear-fill"></i>
        <span><?php echo __('settings', 'Settings'); ?></span>
      </a>
    </li>
  </ul>
</aside>
