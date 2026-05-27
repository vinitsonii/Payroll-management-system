<?php
require_once '../includes/config.php';
requireLogin();
$page_title = 'Generate Payroll';
$db = getDB();

$sel_month = intval($_GET['month'] ?? date('n'));
$sel_year  = intval($_GET['year']  ?? date('Y'));

// Mark as paid
if (isset($_GET['markpaid'])) {
    $pid = intval($_GET['markpaid']);
    $db->query("UPDATE payroll SET status='Paid' WHERE id=$pid");
    setFlash('success', 'Marked as Paid.');
    header("Location: generate.php?month=$sel_month&year=$sel_year"); exit();
}

// Process payroll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process'])) {
    $month   = intval($_POST['pay_month']);
    $year    = intval($_POST['pay_year']);
    $emp_ids = $_POST['emp_ids'] ?? [];

    $pf_er   = floatval(getSetting('pf_employee_rate'))  / 100;
    $pf_err  = floatval(getSetting('pf_employer_rate'))  / 100;
    $esi_er  = floatval(getSetting('esi_employee_rate')) / 100;
    $esi_err = floatval(getSetting('esi_employer_rate')) / 100;
    $pt      = floatval(getSetting('pt_slab'));
    $wdays   = intval(getSetting('working_days')) ?: 26;
    $done    = 0;

    foreach ($emp_ids as $eid) {
        $eid = intval($eid);
        $sal = $db->query("SELECT * FROM salary_structures WHERE employee_id=$eid ORDER BY id DESC LIMIT 1")->fetch_assoc();
        if (!$sal) continue;

        $pres = $db->query("SELECT COUNT(*) c FROM attendance WHERE employee_id=$eid AND MONTH(att_date)=$month AND YEAR(att_date)=$year AND status IN ('Present','WFH')")->fetch_assoc()['c'];
        $half = $db->query("SELECT COUNT(*) c FROM attendance WHERE employee_id=$eid AND MONTH(att_date)=$month AND YEAR(att_date)=$year AND status='Half-Day'")->fetch_assoc()['c'];
        $present = floatval($pres) + floatval($half) * 0.5;
        if ($present == 0) $present = $wdays;

        $ratio = min($present / $wdays, 1);
        $basic = round($sal['basic_salary'] * $ratio, 2);
        $hra   = round($sal['hra']   * $ratio, 2);
        $da    = round($sal['da']    * $ratio, 2);
        $ta    = round($sal['ta']    * $ratio, 2);
        $ma    = round($sal['medical_allowance'] * $ratio, 2);
        $oa    = round($sal['other_allowance']   * $ratio, 2);
        $gross = $basic + $hra + $da + $ta + $ma + $oa;

        $pf_e  = round($basic * $pf_er,   2);
        $pf_r  = round($basic * $pf_err,  2);
        $esi_e = ($gross <= 21000) ? round($gross * $esi_er,  2) : 0;
        $esi_r = ($gross <= 21000) ? round($gross * $esi_err, 2) : 0;

        $annual = $gross * 12;
        if      ($annual <= 300000)  $tds = 0;
        elseif  ($annual <= 600000)  $tds = round((($annual - 300000) * 0.05) / 12, 2);
        elseif  ($annual <= 900000)  $tds = round((15000 + ($annual - 600000) * 0.10) / 12, 2);
        elseif  ($annual <= 1200000) $tds = round((45000 + ($annual - 900000) * 0.15) / 12, 2);
        elseif  ($annual <= 1500000) $tds = round((90000 + ($annual - 1200000) * 0.20) / 12, 2);
        else                         $tds = round((150000 + ($annual - 1500000) * 0.30) / 12, 2);

        $total_ded = $pf_e + $esi_e + $tds + $pt;
        $net = round($gross - $total_ded, 2);

        $db->query("INSERT INTO payroll
            (employee_id,pay_month,pay_year,working_days,present_days,
             basic_salary,hra,da,ta,medical_allowance,other_allowance,gross_salary,
             pf_employee,pf_employer,esi_employee,esi_employer,tds,professional_tax,
             total_deduction,net_salary,status)
            VALUES ($eid,$month,$year,$wdays,$present,
             $basic,$hra,$da,$ta,$ma,$oa,$gross,
             $pf_e,$pf_r,$esi_e,$esi_r,$tds,$pt,
             $total_ded,$net,'Processed')
            ON DUPLICATE KEY UPDATE
             present_days=$present,basic_salary=$basic,hra=$hra,da=$da,ta=$ta,
             medical_allowance=$ma,other_allowance=$oa,gross_salary=$gross,
             pf_employee=$pf_e,pf_employer=$pf_r,esi_employee=$esi_e,esi_employer=$esi_r,
             tds=$tds,professional_tax=$pt,total_deduction=$total_ded,net_salary=$net,status='Processed'");
        $done++;
    }
    setFlash('success', "Payroll processed for $done employee(s).");
    header("Location: list.php?month=$month&year=$year"); exit();
}

$employees = $db->query("
    SELECT e.id,e.emp_code,e.first_name,e.last_name,d.name dept_name,
           ss.basic_salary,
           (ss.basic_salary+ss.hra+ss.da+ss.ta+ss.medical_allowance+ss.other_allowance) gross,
           p.id payroll_id,p.net_salary,p.status pay_status
    FROM employees e
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN salary_structures ss ON ss.employee_id=e.id
    LEFT JOIN payroll p ON p.employee_id=e.id AND p.pay_month=$sel_month AND p.pay_year=$sel_year
    WHERE e.status='Active' ORDER BY e.first_name");

include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <h2>Generate Payroll</h2>
  <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-list-ul me-2"></i>All Payslips</a>
</div>

<!-- Period selector -->
<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-sm-6 col-md-3">
        <label class="form-label">Month</label>
        <select name="month" class="form-select">
          <?php for ($m=1;$m<=12;$m++): ?>
          <option value="<?= $m ?>" <?= $sel_month==$m?'selected':'' ?>><?= monthName($m) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-md-2">
        <label class="form-label">Year</label>
        <select name="year" class="form-select">
          <?php for ($y=date('Y')-2;$y<=date('Y');$y++): ?>
          <option value="<?= $y ?>" <?= $sel_year==$y?'selected':'' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Load</button>
      </div>
    </form>
  </div>
</div>

<form method="POST">
  <input type="hidden" name="pay_month" value="<?= $sel_month ?>">
  <input type="hidden" name="pay_year"  value="<?= $sel_year ?>">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>
        <i class="bi bi-cpu-fill me-2 text-accent"></i>
        <strong class="text-accent"><?= monthName($sel_month).' '.$sel_year ?></strong>
        &nbsp;
        <label class="text-muted fw-600" style="font-size:12px;cursor:pointer">
          <input type="checkbox" id="selectAll" class="me-1">Select All
        </label>
      </span>
      <button type="submit" name="process" class="btn btn-primary btn-sm">
        <i class="bi bi-cpu-fill me-2"></i>Process Selected
      </button>
    </div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th></th><th>Code</th><th>Employee</th><th>Dept</th><th>Basic</th><th>Gross CTC</th><th>Net Pay</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($employees->num_rows===0): ?>
          <tr><td colspan="9" class="text-center text-muted py-5">No active employees.</td></tr>
        <?php else: while ($e=$employees->fetch_assoc()): ?>
        <tr>
          <td><input type="checkbox" name="emp_ids[]" value="<?= $e['id'] ?>" class="row-check" <?= !$e['basic_salary']?'disabled title="No salary structure"':'' ?>></td>
          <td><span class="mono text-accent"><?= $e['emp_code'] ?></span></td>
          <td class="fw-700"><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?></td>
          <td class="text-muted"><?= htmlspecialchars($e['dept_name']??'—') ?></td>
          <td class="mono"><?= $e['basic_salary']?formatCurrency($e['basic_salary']):'<span class="text-red" style="font-size:12px">No structure</span>' ?></td>
          <td class="mono"><?= $e['gross']?formatCurrency($e['gross']):'—' ?></td>
          <td class="mono fw-800 text-green"><?= $e['net_salary']?formatCurrency($e['net_salary']):'<span class="text-muted">—</span>' ?></td>
          <td>
            <?php if ($e['pay_status']): ?>
            <span class="badge-pill bp-<?= strtolower($e['pay_status']) ?>"><?= $e['pay_status'] ?></span>
            <?php else: ?>
            <span class="badge-pill bp-draft">Not Generated</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <?php if ($e['payroll_id']): ?>
              <a href="view.php?id=<?= $e['payroll_id'] ?>" class="btn btn-icon btn-sm btn-action-view" title="View"><i class="bi bi-eye-fill"></i></a>
              <?php if ($e['pay_status']!=='Paid'): ?>
              <a href="?markpaid=<?= $e['payroll_id'] ?>&month=<?= $sel_month ?>&year=<?= $sel_year ?>" class="btn btn-icon btn-sm btn-action-pay" title="Mark Paid"><i class="bi bi-check-circle-fill"></i></a>
              <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<?php include '../includes/footer.php'; ?>
