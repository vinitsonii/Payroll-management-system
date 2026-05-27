<?php
require_once '../includes/config.php';
requireLogin();
$page_title = 'All Payslips';
$db = getDB();

$sel_month = intval($_GET['month'] ?? 0);
$sel_year  = intval($_GET['year']  ?? date('Y'));
$search    = trim($_GET['search'] ?? '');

$where = ['1=1'];
if ($sel_month) $where[] = "p.pay_month=$sel_month";
if ($sel_year)  $where[] = "p.pay_year=$sel_year";
if ($search)    $where[] = "(e.first_name LIKE '%".addslashes($search)."%' OR e.last_name LIKE '%".addslashes($search)."%' OR e.emp_code LIKE '%".addslashes($search)."%')";

$records = $db->query("
    SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.emp_code, d.name dept_name
    FROM payroll p
    JOIN employees e ON p.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    WHERE ".implode(' AND ',$where)."
    ORDER BY p.pay_year DESC, p.pay_month DESC, p.generated_on DESC");

include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <h2>All Payslips</h2>
  <a href="generate.php" class="btn btn-primary"><i class="bi bi-cpu-fill me-2"></i>Generate Payroll</a>
</div>

<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-sm-6 col-md-3"><input type="text" name="search" class="form-control" placeholder="Search employee..." value="<?= htmlspecialchars($search) ?>"></div>
      <div class="col-12 col-sm-4 col-md-2">
        <select name="month" class="form-select">
          <option value="">All Months</option>
          <?php for ($m=1;$m<=12;$m++): ?>
          <option value="<?= $m ?>" <?= $sel_month==$m?'selected':'' ?>><?= monthName($m) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-md-2">
        <select name="year" class="form-select">
          <?php for ($y=date('Y')-2;$y<=date('Y');$y++): ?>
          <option value="<?= $y ?>" <?= $sel_year==$y?'selected':'' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Filter</button>
        <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-receipt me-2 text-accent"></i>Payslip Records</span>
    <span class="text-muted" style="font-size:12px"><?= $records->num_rows ?> records</span>
  </div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>#</th><th>Employee</th><th>Dept</th><th>Period</th><th>Days</th><th>Gross</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($records->num_rows===0): ?>
        <tr><td colspan="10" class="text-center text-muted py-5">No payslips found.</td></tr>
      <?php else: $n=1; while ($r=$records->fetch_assoc()): ?>
      <tr>
        <td class="text-muted"><?= $n++ ?></td>
        <td><div class="fw-700"><?= htmlspecialchars($r['emp_name']) ?></div><div class="mono text-accent" style="font-size:11px"><?= $r['emp_code'] ?></div></td>
        <td class="text-muted"><?= htmlspecialchars($r['dept_name']??'—') ?></td>
        <td class="fw-600"><?= monthName($r['pay_month']).' '.$r['pay_year'] ?></td>
        <td><?= $r['present_days'] ?>/<?= $r['working_days'] ?></td>
        <td class="mono"><?= formatCurrency($r['gross_salary']) ?></td>
        <td class="mono text-red">-<?= formatCurrency($r['total_deduction']) ?></td>
        <td class="mono fw-800 text-green"><?= formatCurrency($r['net_salary']) ?></td>
        <td><span class="badge-pill bp-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
        <td>
          <div class="d-flex gap-1">
            <a href="view.php?id=<?= $r['id'] ?>" class="btn btn-icon btn-sm btn-action-view" title="View"><i class="bi bi-eye-fill"></i></a>
            <?php if ($r['status']!=='Paid'): ?>
            <a href="generate.php?markpaid=<?= $r['id'] ?>&month=<?= $r['pay_month'] ?>&year=<?= $r['pay_year'] ?>" class="btn btn-icon btn-sm btn-action-pay" title="Mark Paid"><i class="bi bi-check-circle-fill"></i></a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
