<?php
require_once 'includes/config.php';
requireLogin();
$page_title = 'Dashboard';
$db = getDB();

$total_emp      = $db->query("SELECT COUNT(*) c FROM employees WHERE status='Active'")->fetch_assoc()['c'];
$total_dept     = $db->query("SELECT COUNT(*) c FROM departments")->fetch_assoc()['c'];
$m = date('n'); $y = date('Y');
$payroll_sum    = $db->query("SELECT COALESCE(SUM(net_salary),0) s FROM payroll WHERE pay_month=$m AND pay_year=$y AND status='Paid'")->fetch_assoc()['s'];
$pending_leaves = $db->query("SELECT COUNT(*) c FROM leave_applications WHERE status='Pending'")->fetch_assoc()['c'];
$today_present  = $db->query("SELECT COUNT(*) c FROM attendance WHERE att_date=CURDATE() AND status IN ('Present','WFH')")->fetch_assoc()['c'];

$recent_payroll = $db->query("
    SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name, e.emp_code
    FROM payroll p JOIN employees e ON p.employee_id=e.id
    ORDER BY p.generated_on DESC LIMIT 7");

// Chart: last 6 months salary
$c_labels=[]; $c_gross=[]; $c_net=[];
for ($i=5;$i>=0;$i--) {
    $ts=strtotime("-$i months");
    $cm=date('n',$ts); $cy=date('Y',$ts);
    $c_labels[] = date('M y',$ts);
    $r=$db->query("SELECT COALESCE(SUM(gross_salary),0) g,COALESCE(SUM(net_salary),0) n FROM payroll WHERE pay_month=$cm AND pay_year=$cy");
    $row=$r->fetch_assoc();
    $c_gross[]=(float)$row['g']; $c_net[]=(float)$row['n'];
}

// Dept headcount
$dept_q=$db->query("SELECT d.name,COUNT(e.id) cnt FROM departments d LEFT JOIN employees e ON e.department_id=d.id AND e.status='Active' GROUP BY d.id");
$d_names=[]; $d_counts=[];
while($row=$dept_q->fetch_assoc()){$d_names[]=$row['name'];$d_counts[]=(int)$row['cnt'];}

include 'includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <div>
    <h2>Dashboard</h2>
    <div class="page-subtitle"><i class="bi bi-calendar3 me-1"></i><?= date('l, d F Y') ?></div>
  </div>
  <span class="badge-pill bp-active"><i class="bi bi-circle-fill me-1" style="font-size:7px"></i>System Online</span>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php
  $stats = [
    ['Active Employees', $total_emp,                  'people-fill',       'si-blue',   ''],
    ['Departments',      $total_dept,                  'building',          'si-purple', ''],
    ['Net Paid (Month)', formatCurrency($payroll_sum), 'wallet2',           'si-amber',  ''],
    ['Pending Leaves',   $pending_leaves,              'calendar2-x-fill',  'si-red',    ''],
    ['Present Today',    $today_present,               'person-check-fill', 'si-green',  ''],
  ];
  foreach ($stats as [$label,$val,$icon,$cls,$_]):
  ?>
  <div class="col-6 col-sm-4 col-md-4 col-xl">
    <div class="stat-card">
      <div class="stat-icon <?= $cls ?>"><i class="bi bi-<?= $icon ?>"></i></div>
      <div>
        <div class="stat-label"><?= $label ?></div>
        <div class="stat-value"><?= $val ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-8">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-accent"></i>Salary Trend — Last 6 Months</div>
      <div class="card-body"><canvas id="salaryChart" height="110"></canvas></div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart-fill me-2 text-accent"></i>Headcount by Dept</div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <canvas id="deptChart" height="220"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Recent Payroll -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-receipt me-2 text-accent"></i>Recent Payroll</span>
    <a href="<?= BASE_URL ?>payslip/list.php" class="btn btn-sm btn-outline-secondary">View All</a>
  </div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr>
        <th>Employee</th><th>Code</th><th>Period</th>
        <th>Gross</th><th>Deductions</th><th>Net Pay</th><th>Status</th>
      </tr></thead>
      <tbody>
      <?php if ($recent_payroll->num_rows===0): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No payroll records yet.</td></tr>
      <?php else: while ($r=$recent_payroll->fetch_assoc()): ?>
      <tr>
        <td class="fw-700"><?= htmlspecialchars($r['emp_name']) ?></td>
        <td><span class="mono text-accent"><?= $r['emp_code'] ?></span></td>
        <td><?= monthName($r['pay_month']).' '.$r['pay_year'] ?></td>
        <td class="mono"><?= formatCurrency($r['gross_salary']) ?></td>
        <td class="mono text-red">-<?= formatCurrency($r['total_deduction']) ?></td>
        <td class="mono fw-800 text-green"><?= formatCurrency($r['net_salary']) ?></td>
        <td><span class="badge-pill bp-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
Chart.defaults.color = '#8fa3be';
new Chart(document.getElementById('salaryChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($c_labels) ?>,
    datasets: [
      { label:'Gross', data: <?= json_encode($c_gross) ?>, backgroundColor:'rgba(59,130,246,.3)', borderColor:'#3b82f6', borderWidth:2, borderRadius:6 },
      { label:'Net',   data: <?= json_encode($c_net)   ?>, backgroundColor:'rgba(34,197,94,.3)',  borderColor:'#22c55e', borderWidth:2, borderRadius:6 }
    ]
  },
  options:{ responsive:true, plugins:{legend:{labels:{color:'#8fa3be'}}},
    scales:{ x:{ticks:{color:'#8fa3be'},grid:{color:'#2a3f5f'}},
             y:{ticks:{color:'#8fa3be',callback:v=>'₹'+v.toLocaleString('en-IN')},grid:{color:'#2a3f5f'}} } }
});
new Chart(document.getElementById('deptChart'), {
  type:'doughnut',
  data:{ labels:<?= json_encode($d_names) ?>, datasets:[{ data:<?= json_encode($d_counts) ?>,
    backgroundColor:['#f5a623','#3b82f6','#22c55e','#ef4444','#a78bfa','#06b6d4'],
    borderWidth:2, borderColor:'#172033' }] },
  options:{ responsive:true, plugins:{legend:{position:'bottom',labels:{color:'#8fa3be',boxWidth:12,padding:10}}} }
});
</script>

<?php include 'includes/footer.php'; ?>
