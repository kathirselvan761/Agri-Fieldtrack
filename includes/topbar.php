<header class="top-navbar">
  <div class="d-flex align-items-center gap-3">
    <button class="toggle-sidebar-btn" id="sidebarToggleBtn" title="<?php echo __('toggle_navigation', 'Toggle Navigation'); ?>">
      <i class="bi bi-list"></i>
    </button>
    <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="header-search d-none d-md-block">
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="<?php echo __('search_placeholder', 'Search tractors, customers, villages...'); ?>" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
      </div>
    </form>
  </div>

  <div class="d-flex align-items-center gap-2 gap-md-3">
    <!-- Language Switcher Dropdown -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-1 shadow-sm" type="button" data-bs-toggle="dropdown" id="languageDropdown" title="<?php echo __('select_language', 'Language'); ?>">
        <i class="bi bi-translate text-primary"></i>
        <span class="d-none d-md-inline ms-1 fw-medium">
          <?php 
            $curr = getCurrentLang();
            if ($curr === 'en') echo 'English';
            elseif ($curr === 'hi') echo 'हिन्दी';
            else echo 'தமிழ்';
          ?>
        </span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li><h6 class="dropdown-header"><i class="bi bi-globe me-1"></i><?php echo __('select_language', 'Language'); ?></h6></li>
        <li>
          <a class="dropdown-item d-flex align-items-center justify-content-between <?php echo getCurrentLang() === 'ta' ? 'active fw-bold' : ''; ?>" href="?lang=ta">
            <span>🇮🇳 தமிழ் (Tamil)</span>
            <?php if (getCurrentLang() === 'ta'): ?><i class="bi bi-check2"></i><?php endif; ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center justify-content-between <?php echo getCurrentLang() === 'en' ? 'active fw-bold' : ''; ?>" href="?lang=en">
            <span>🇬🇧 English</span>
            <?php if (getCurrentLang() === 'en'): ?><i class="bi bi-check2"></i><?php endif; ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center justify-content-between <?php echo getCurrentLang() === 'hi' ? 'active fw-bold' : ''; ?>" href="?lang=hi">
            <span>🇮🇳 हिन्दी (Hindi)</span>
            <?php if (getCurrentLang() === 'hi'): ?><i class="bi bi-check2"></i><?php endif; ?>
          </a>
        </li>
      </ul>
    </div>

    <!-- Quick Add Button -->
    <div class="dropdown">
      <button class="btn btn-primary btn-sm dropdown-toggle d-flex align-items-center gap-1 shadow-sm" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline"><?php echo __('quick_action', 'Quick Action'); ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/work_entries/index.php?action=new"><i class="bi bi-journal-plus text-primary me-2"></i><?php echo __('new_work_entry', 'New Work Entry'); ?></a></li>
        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/bookings/index.php?action=new"><i class="bi bi-calendar-plus text-success me-2"></i><?php echo __('new_booking', 'New Booking'); ?></a></li>
        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/customers/index.php?action=new"><i class="bi bi-person-plus text-info me-2"></i><?php echo __('add_customer', 'Add Customer'); ?></a></li>
        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/diesel/index.php?action=new"><i class="bi bi-fuel-pump text-warning me-2"></i><?php echo __('log_diesel', 'Log Diesel'); ?></a></li>
      </ul>
    </div>

    <!-- Theme Toggle -->
    <button class="btn btn-outline-secondary btn-sm rounded-circle p-2" id="themeToggleBtn" title="<?php echo __('toggle_theme', 'Toggle Theme'); ?>" style="width:36px; height:36px; display:flex; align-items:center; justify-content:center;">
      <i class="bi bi-moon-stars-fill"></i>
    </button>

    <!-- User Profile Dropdown -->
    <div class="dropdown ms-1">
      <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-main" data-bs-toggle="dropdown">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width:36px; height:36px; font-size: 0.9rem;">
          <?php echo strtoupper(substr($currentUser['full_name'] ?? 'A', 0, 1)); ?>
        </div>
        <div class="ms-2 d-none d-md-block text-start" style="line-height: 1.2;">
          <span class="d-block fw-semibold" style="font-size:0.88rem;"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Admin'); ?></span>
          <small class="text-muted" style="font-size:0.75rem;"><?php echo ucfirst(htmlspecialchars($currentUser['role'] ?? 'admin')); ?></small>
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li class="dropdown-header"><?php echo __('logged_in_as', 'Logged in as'); ?> <strong><?php echo htmlspecialchars($currentUser['username'] ?? 'admin'); ?></strong></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/settings/index.php"><i class="bi bi-person-gear me-2"></i><?php echo __('account_settings', 'Account Settings'); ?></a></li>
        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/settings/index.php#company"><i class="bi bi-building me-2"></i><?php echo __('company_details', 'Company Details'); ?></a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i><?php echo __('logout', 'Logout'); ?></a></li>
      </ul>
    </div>
  </div>
</header>
