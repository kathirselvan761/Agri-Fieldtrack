<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
checkLoggedIn();
$currentUser = getCurrentUser();
$company = getCompanySettings();
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . htmlspecialchars($company['company_name']) : htmlspecialchars($company['company_name']); ?></title>
  
  <!-- Favicon -->
  <link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons & FontAwesome 6 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  
  <!-- FullCalendar CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">

  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <!-- Custom ERP Style -->
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
<div id="wrapper">
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <div id="content-wrapper">
    <?php require_once __DIR__ . '/topbar.php'; ?>
    <main class="main-content">
      <?php displayFlash(); ?>
