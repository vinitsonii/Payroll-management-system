<?php
requireLogin();
$company  = getSetting('company_name') ?: 'PayrollPro';
$flash    = getFlash();
$cur_page = basename($_SERVER['PHP_SELF'], '.php');
function isActive($seg) { return strpos($_SERVER['REQUEST_URI'], $seg) !== false ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0f1623">
<title><?= htmlspecialchars($page_title ?? 'Dashboard') ?> — PayrollPro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ═══════════════════ SIDEBAR ═══════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <div class="sb-logo"><i class="bi bi-briefcase-fill"></i></div>
    <div>
      <div class="sb-brand-name">PayrollPro</div>
      <div class="sb-brand-sub">Management System</div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-section">Main</div>
    <a href="<?= BASE_URL ?>index.php" class="sb-link <?= $cur_page==='index'?'active':'' ?>">
      <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>

    <div class="sb-section">Employees</div>
    <a href="<?= BASE_URL ?>employees/list.php" class="sb-link <?= isActive('/employees/') ?>">
      <i class="bi bi-people-fill"></i><span>All Employees</span>
    </a>
    <a href="<?= BASE_URL ?>employees/add.php" class="sb-link">
      <i class="bi bi-person-plus-fill"></i><span>Add Employee</span>
    </a>

    <div class="sb-section">Salary</div>
    <a href="<?= BASE_URL ?>salary/list.php" class="sb-link <?= isActive('/salary/') ?>">
      <i class="bi bi-cash-stack"></i><span>Salary Structures</span>
    </a>
    <a href="<?= BASE_URL ?>payslip/generate.php" class="sb-link <?= isActive('/payslip/generate') ?>">
      <i class="bi bi-cpu-fill"></i><span>Generate Payroll</span>
    </a>
    <a href="<?= BASE_URL ?>payslip/list.php" class="sb-link <?= isActive('/payslip/list')||isActive('/payslip/view') ?>">
      <i class="bi bi-receipt"></i><span>Payslips</span>
    </a>

    <div class="sb-section">Attendance</div>
    <a href="<?= BASE_URL ?>attendance/mark.php" class="sb-link <?= isActive('/attendance/mark') ?>">
      <i class="bi bi-calendar-check-fill"></i><span>Mark Attendance</span>
    </a>
    <a href="<?= BASE_URL ?>attendance/report.php" class="sb-link <?= isActive('/attendance/report') ?>">
      <i class="bi bi-table"></i><span>Attendance Report</span>
    </a>
    <a href="<?= BASE_URL ?>attendance/leaves.php" class="sb-link <?= isActive('/attendance/leaves') ?>">
      <i class="bi bi-calendar2-x-fill"></i><span>Leave Management</span>
    </a>

    <div class="sb-section">Finance</div>
    <a href="<?= BASE_URL ?>tax/index.php" class="sb-link <?= isActive('/tax/') ?>">
      <i class="bi bi-percent"></i><span>Tax &amp; Deductions</span>
    </a>
    <a href="<?= BASE_URL ?>reports/index.php" class="sb-link <?= isActive('/reports/') ?>">
      <i class="bi bi-bar-chart-fill"></i><span>Reports</span>
    </a>

    <div class="sb-section">System</div>
    <a href="<?= BASE_URL ?>settings.php" class="sb-link <?= $cur_page==='settings'?'active':'' ?>">
      <i class="bi bi-gear-fill"></i><span>Settings</span>
    </a>
  </nav>
</aside>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<div class="main-wrap" id="mainWrap">

  <!-- Topbar -->
  <header class="topbar">
    <button class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
      <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></div>
    <div class="d-flex align-items-center gap-2">
      <span class="topbar-company d-none d-lg-block"><?= htmlspecialchars($company) ?></span>
      <div class="dropdown">
        <button class="user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
          <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><span class="dropdown-item-text"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Admin') ?></span></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- Flash message -->
  <?php if ($flash): ?>
  <div class="px-3 px-md-4 pt-3">
    <div class="alert alert-<?= $flash['type']==='success'?'success':($flash['type']==='error'?'danger':'info') ?> alert-dismissible fade show auto-dismiss mb-0" role="alert">
      <i class="bi bi-<?= $flash['type']==='success'?'check-circle-fill':'exclamation-triangle-fill' ?> me-2"></i>
      <?= htmlspecialchars($flash['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
  <?php endif; ?>

  <div class="page-body">
