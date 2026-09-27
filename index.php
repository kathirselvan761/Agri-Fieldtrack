<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

checkAlreadyLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = __('enter_both_fields', 'Please enter both username and password.');
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1");
            $stmt->execute(['username' => $username, 'email' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error = __('invalid_credentials', 'Invalid username or password.');
            }
        } catch (Exception $e) {
            $error = 'Authentication error: ' . $e->getMessage();
        }
    }
}
$company = getCompanySettings();
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo __('login_title', 'Login - AgriTrak ERP System'); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 1.5rem;
    }
    .login-card {
      width: 100%;
      max-width: 440px;
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
      overflow: hidden;
      position: relative;
    }
    [data-bs-theme="dark"] .login-card {
      background: #151c2c;
      color: #ffffff;
    }
  </style>
</head>
<body>

<div class="login-card">
  <!-- Language Selector Top Bar -->
  <div class="d-flex justify-content-end p-3 pb-0">
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1 rounded-pill px-3" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-translate text-primary"></i>
        <span class="small fw-semibold">
          <?php 
            $curr = getCurrentLang();
            if ($curr === 'en') echo 'English';
            elseif ($curr === 'hi') echo 'हिन्दी';
            else echo 'தமிழ்';
          ?>
        </span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li><a class="dropdown-item <?php echo getCurrentLang() === 'ta' ? 'active' : ''; ?>" href="?lang=ta">🇮🇳 தமிழ் (Tamil)</a></li>
        <li><a class="dropdown-item <?php echo getCurrentLang() === 'en' ? 'active' : ''; ?>" href="?lang=en">🇬🇧 English</a></li>
        <li><a class="dropdown-item <?php echo getCurrentLang() === 'hi' ? 'active' : ''; ?>" href="?lang=hi">🇮🇳 हिन्दी (Hindi)</a></li>
      </ul>
    </div>
  </div>

  <div class="p-4 p-md-5 pt-2 text-center border-bottom border-light-subtle">
    <div class="bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow" style="width: 64px; height: 64px; font-size: 2rem;">
      <i class="fa-solid fa-tractor"></i>
    </div>
    <h3 class="fw-bold mb-1 brand-font"><?php echo htmlspecialchars($company['company_name']); ?></h3>
    <p class="text-muted small mb-0"><?php echo __('tractor_rental_erp', 'Tractor Rental Management ERP System'); ?></p>
  </div>

  <div class="p-4 p-md-5">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger d-flex align-items-center py-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
      </div>
    <?php endif; ?>

    <form action="" method="POST">
      <div class="mb-3">
        <label for="username" class="form-label fw-semibold"><?php echo __('username_or_email', 'Username or Email'); ?></label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" name="username" id="username" class="form-control" placeholder="<?php echo __('enter_username', 'Enter username'); ?>" required value="admin">
        </div>
      </div>

      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <label for="password" class="form-label fw-semibold"><?php echo __('password', 'Password'); ?></label>
          <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal" class="small text-primary text-decoration-none"><?php echo __('forgot_password', 'Forgot Password?'); ?></a>
        </div>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="password" id="password" class="form-control" placeholder="<?php echo __('enter_password', 'Enter password'); ?>" required value="admin123">
        </div>
      </div>

      <div class="d-grid mt-4">
        <button type="submit" class="btn btn-primary btn-lg shadow-sm">
          <i class="bi bi-box-arrow-in-right me-2"></i><?php echo __('sign_in_to_erp', 'Sign In to ERP'); ?>
        </button>
      </div>
    </form>

    <div class="mt-4 pt-3 border-top text-center">
      <div class="p-2 rounded bg-light-subtle small text-muted">
        <i class="bi bi-key-fill text-warning me-1"></i> <?php echo __('demo_credentials', 'Demo Credentials:'); ?> <strong>admin</strong> / <strong>admin123</strong>
      </div>
    </div>
  </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-shield-lock me-2 text-primary"></i><?php echo __('reset_admin_password', 'Reset Admin Password'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small"><?php echo __('reset_password_instruction', 'Enter your registered admin email address. Password reset instructions will be sent.'); ?></p>
        <div class="mb-3">
          <label class="form-label fw-semibold"><?php echo __('admin_email', 'Admin Email'); ?></label>
          <input type="email" class="form-control" placeholder="admin@agritrak.com" value="admin@agritrak.com">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
        <button type="button" class="btn btn-primary btn-sm" onclick="alert('Password reset link sent to admin@agritrak.com. Default login remains admin / admin123');" data-bs-dismiss="modal"><?php echo __('send_reset_link', 'Send Reset Link'); ?></button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
