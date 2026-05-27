<?php
require_once '../includes/config.php';
requireLogin();
$page_title = 'Tax & Deductions';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['pf_employee_rate','pf_employer_rate','esi_employee_rate','esi_employer_rate','pt_slab','working_days'];
    foreach ($keys as $k) {
        $val = $db->real_escape_string(trim($_POST[$k] ?? ''));
        $db->query("UPDATE settings SET setting_value='$val' WHERE setting_key='$k'");
    }
    setFlash('success', 'Tax settings saved successfully.');
    header('Location: index.php'); exit();
}

$pf_emp  = getSetting('pf_employee_rate');
$pf_er   = getSetting('pf_employer_rate');
$esi_emp = getSetting('esi_employee_rate');
$esi_er  = getSetting('esi_employer_rate');
$pt      = getSetting('pt_slab');
$wd      = getSetting('working_days');

$deductions = $db->query("
    SELECT CONCAT(e.first_name,' ',e.last_name) emp_name, e.emp_code,
           p.pay_month, p.pay_year, p.gross_salary,
           p.pf_employee, p.esi_employee, p.tds, p.professional_tax, p.total_deduction
    FROM payroll p JOIN employees e ON p.employee_id=e.id
    ORDER BY p.pay_year DESC, p.pay_month DESC LIMIT 15");

include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <h2>Tax &amp; Deductions</h2>
</div>

<div class="row g-4">
  <div class="col-12 col-lg-5">
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-gear-fill me-2 text-accent"></i>Deduction Rate Settings</div>
      <div class="card-body">
        <form method="POST">
          <!-- PF -->
          <div class="info-box mb-3">
            <div class="info-box-label"><i class="bi bi-piggy-bank-fill me-1 text-accent"></i>Provident Fund (PF)</div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Employee %</label>
                <div class="input-group">
                  <input type="number" name="pf_employee_rate" class="form-control mono" value="<?= htmlspecialchars($pf_emp) ?>" step="0.01" min="0" max="100">
                  <span class="input-group-text">%</span>
                </div>
              </div>
              <div class="col-6">
                <label class="form-label">Employer %</label>
                <div class="input-group">
                  <input type="number" name="pf_employer_rate" class="form-control mono" value="<?= htmlspecialchars($pf_er) ?>" step="0.01" min="0" max="100">
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
            <div class="form-text mt-2">On Basic Salary. Standard: 12% each side.</div>
          </div>
          <!-- ESI -->
          <div class="info-box mb-3">
            <div class="info-box-label"><i class="bi bi-heart-pulse-fill me-1 text-accent"></i>ESI (Employee State Insurance)</div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Employee %</label>
                <div class="input-group">
                  <input type="number" name="esi_employee_rate" class="form-control mono" value="<?= htmlspecialchars($esi_emp) ?>" step="0.01" min="0" max="100">
                  <span class="input-group-text">%</span>
                </div>
              </div>
              <div class="col-6">
                <label class="form-label">Employer %</label>
                <div class="input-group">
                  <input type="number" name="esi_employer_rate" class="form-control mono" value="<?= htmlspecialchars($esi_er) ?>" step="0.01" min="0" max="100">
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
            <div class="form-text mt-2">On Gross. Applicable if gross ≤ ₹21,000/mo.</div>
          </div>
          <!-- PT -->
          <div class="info-box mb-3">
            <div class="info-box-label"><i class="bi bi-building-fill me-1 text-accent"></i>Professional Tax (Monthly)</div>
            <div class="input-group">
              <span class="input-group-text">₹</span>
              <input type="number" name="pt_slab" class="form-control mono" value="<?= htmlspecialchars($pt) ?>" step="1" min="0">
            </div>
            <div class="form-text mt-2">Standard ₹200/month (varies by state).</div>
          </div>
          <!-- Working Days -->
          <div class="info-box mb-4">
            <div class="info-box-label"><i class="bi bi-calendar3 me-1 text-accent"></i>Working Days per Month</div>
            <input type="number" name="working_days" class="form-control mono" value="<?= htmlspecialchars($wd) ?>" min="1" max="31">
            <div class="form-text mt-2">Used for pro-rata LOP deductions.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-save-fill me-2"></i>Save Settings
          </button>
        </form>
      </div>
    </div>

    <!-- TDS Slabs -->
    <div class="card">
      <div class="card-header"><i class="bi bi-receipt-cutoff me-2 text-accent"></i>TDS Income Tax Slabs (New Regime)</div>
      <div class="table-responsive">
        <table class="table mb-0" style="font-size:13px">
          <thead><tr><th>Annual Income</th><th>Tax Rate</th></tr></thead>
          <tbody>
            <tr><td>Up to ₹3,00,000</td><td><span class="badge-pill bp-active">Nil</span></td></tr>
            <tr><td>₹3L – ₹6L</td><td class="mono fw-700">5%</td></tr>
            <tr><td>₹6L – ₹9L</td><td class="mono fw-700">10%</td></tr>
            <tr><td>₹9L – ₹12L</td><td class="mono fw-700">15%</td></tr>
            <tr><td>₹12L – ₹15L</td><td class="mono fw-700">20%</td></tr>
            <tr><td>Above ₹15L</td><td><span class="badge-pill bp-inactive">30%</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-table me-2 text-accent"></i>Recent Deduction Records</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Employee</th><th>Period</th><th>Gross</th><th>PF</th><th>ESI</th><th>PT</th><th>TDS</th><th>Total</th></tr></thead>
          <tbody>
          <?php if ($deductions->num_rows === 0): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">No payroll records yet.</td></tr>
          <?php else: while ($r = $deductions->fetch_assoc()): ?>
          <tr>
            <td><div class="fw-700"><?= htmlspecialchars($r['emp_name']) ?></div><div class="mono text-accent" style="font-size:11px"><?= $r['emp_code'] ?></div></td>
            <td><?= monthName($r['pay_month']).' '.$r['pay_year'] ?></td>
            <td class="mono"><?= formatCurrency($r['gross_salary']) ?></td>
            <td class="mono text-red">-<?= formatCurrency($r['pf_employee']) ?></td>
            <td class="mono text-red">-<?= formatCurrency($r['esi_employee']) ?></td>
            <td class="mono text-red">-<?= formatCurrency($r['professional_tax']) ?></td>
            <td class="mono text-red">-<?= formatCurrency($r['tds']) ?></td>
            <td class="mono fw-800 text-red">-<?= formatCurrency($r['total_deduction']) ?></td>
          </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
