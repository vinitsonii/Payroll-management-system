<?php
require_once '../includes/config.php';
requireLogin();
$page_title = 'Reports & Analytics';
$db = getDB();

$sel_year  = intval($_GET['year']  ?? date('Y'));
$sel_month = intval($_GET['month'] ?? date('n'));

// Monthly data (full year)
$monthly = [];
for ($m=1;$m<=12;$m++) {
    $r = $db->query("SELECT COALESCE(SUM(gross_salary),0) g,COALESCE(SUM(net_salary),0) n,COALESCE(SUM(total_deduction),0) d,COUNT(*) c FROM payroll WHERE pay_month=$m AND pay_year=$sel_year")->fetch_assoc();
    $monthly[$m] = $r;
}

// Dept-wise net this month
$dept_q = $db->query("SELECT d.name,COALESCE(SUM(p.net_salary),0) total FROM departments d LEFT JOIN employees e ON e.department_id=d.id LEFT JOIN payroll p ON p.employee_id=e.id AND p.pay_month=$sel_month AND p.pay_year=$sel_year GROUP BY d.id ORDER BY total DESC");
$d_names=[]; $d_totals=[];
while ($r=$dept_q->fetch_assoc()){$d_names[]=$r['name'];$d_totals[]=(float)$r['total'];}

// YTD
$ytd = $db->query("SELECT COALESCE(SUM(gross_salary),0) g,COALESCE(SUM(net_salary),0) n,COALESCE(SUM(total_deduction),0) d,COALESCE(SUM(pf_employee+pf_employer),0) pf,COALESCE(SUM(tds),0) tds,COALESCE(SUM(esi_employee+esi_employer),0) esi FROM payroll WHERE pay_year=$sel_year")->fetch_assoc();

// Top earners
$top = $db->query("SELECT CONCAT(e.first_name,' ',e.last_name) emp_name,e.emp_code,d.name dept,p.gross_salary,p.net_salary FROM payroll p JOIN employees e ON p.employee_id=e.id LEFT JOIN departments d ON e.department_id=d.id WHERE p.pay_month=$sel_month AND p.pay_year=$sel_year ORDER BY p.net_salary DESC LIMIT 8");

// Attendance summary
$att_q = $db->query("SELECT status,COUNT(*) cnt FROM attendance WHERE MONTH(att_date)=$sel_month AND YEAR(att_date)=$sel_year GROUP BY status");
$att_d=[]; while($r=$att_q->fetch_assoc()) $att_d[$r['status']]=(int)$r['cnt'];

include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <h2>Reports &amp; Analytics</h2>
  <form method="GET" class="d-flex gap-2 align-items-center">
    <select name="month" class="form-select form-select-sm" style="width:auto">
      <?php for ($m=1;$m<=12;$m++): ?>
      <option value="<?= $m ?>" <?= $sel_month==$m?'selected':'' ?>><?= monthName($m) ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" class="form-select form-select-sm" style="width:auto">
      <?php for ($y=date('Y')-2;$y<=date('Y');$y++): ?>
      <option value="<?= $y ?>" <?= $sel_year==$y?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
  </form>
</div>

<!-- YTD Cards -->
<div class="row g-3 mb-4">
  <?php
  $cards=[
    ["YTD Gross ($sel_year)",    $ytd['g'],   'cash-stack',       'si-blue'],
    ["YTD Net Paid ($sel_year)", $ytd['n'],   'wallet2',          'si-green'],
    ["YTD Deductions",           $ytd['d'],   'dash-circle-fill', 'si-red'],
    ["YTD PF ($sel_year)",       $ytd['pf'],  'piggy-bank-fill',  'si-amber'],
    ["YTD TDS ($sel_year)",      $ytd['tds'], 'receipt-cutoff',   'si-purple'],
  ];
  foreach ($cards as [$lbl,$val,$icon,$cls]):
  ?>
  <div class="col-6 col-sm-4 col-12 col-sm-6 col-md-4 col-xl">
    <div class="stat-card">
      <div class="stat-icon <?= $cls ?>"><i class="bi bi-<?= $icon ?>"></i></div>
      <div><div class="stat-label"><?= $lbl ?></div><div class="stat-value mono" style="font-size:15px"><?= formatCurrency($val) ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts Row 1 -->
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-8">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-graph-up me-2 text-accent"></i>Monthly Payroll Trend — <?= $sel_year ?></div>
      <div class="card-body"><canvas id="trendChart" height="110"></canvas></div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart-fill me-2 text-accent"></i>Attendance — <?= monthName($sel_month) ?></div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <canvas id="attChart" height="220"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-accent"></i>Dept-wise Net — <?= monthName($sel_month).' '.$sel_year ?></div>
      <div class="card-body"><canvas id="deptChart" height="170"></canvas></div>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-trophy-fill me-2 text-accent"></i>Top Earners — <?= monthName($sel_month).' '.$sel_year ?></div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Employee</th><th>Dept</th><th>Gross</th><th>Net</th></tr></thead>
          <tbody>
          <?php if ($top->num_rows===0): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No data.</td></tr>
          <?php else: while ($r=$top->fetch_assoc()): ?>
          <tr>
            <td><div class="fw-700"><?= htmlspecialchars($r['emp_name']) ?></div><div class="mono text-accent" style="font-size:11px"><?= $r['emp_code'] ?></div></td>
            <td class="text-muted"><?= htmlspecialchars($r['dept']??'—') ?></td>
            <td class="mono"><?= formatCurrency($r['gross_salary']) ?></td>
            <td class="mono fw-800 text-green"><?= formatCurrency($r['net_salary']) ?></td>
          </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Monthly Detail Table -->
<div class="card">
  <div class="card-header"><i class="bi bi-table me-2 text-accent"></i>Month-by-Month — <?= $sel_year ?></div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Month</th><th>Employees</th><th>Gross</th><th>Deductions</th><th>Net Paid</th></tr></thead>
      <tbody>
      <?php foreach ($monthly as $m=>$row): ?>
      <tr>
        <td class="fw-700"><?= monthName($m) ?></td>
        <td><?= $row['c']?:'—' ?></td>
        <td class="mono"><?= $row['g']>0?formatCurrency($row['g']):'<span class="text-muted">—</span>' ?></td>
        <td class="mono text-red"><?= $row['d']>0?'-'.formatCurrency($row['d']):'<span class="text-muted">—</span>' ?></td>
        <td class="mono fw-700 text-green"><?= $row['n']>0?formatCurrency($row['n']):'<span class="text-muted">—</span>' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
Chart.defaults.color = '#8fa3be';
const gridColor = '#2a3f5f';
new Chart(document.getElementById('trendChart'),{
  type:'line',
  data:{
    labels:<?= json_encode(array_map('monthName',range(1,12))) ?>,
    datasets:[
      {label:'Gross',data:<?= json_encode(array_column($monthly,'g')) ?>,borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,.12)',fill:true,tension:.4,borderWidth:2,pointRadius:4,pointBackgroundColor:'#3b82f6'},
      {label:'Net',  data:<?= json_encode(array_column($monthly,'n')) ?>,borderColor:'#22c55e',backgroundColor:'rgba(34,197,94,.12)', fill:true,tension:.4,borderWidth:2,pointRadius:4,pointBackgroundColor:'#22c55e'},
    ]
  },
  options:{responsive:true,plugins:{legend:{labels:{color:'#8fa3be'}}},
    scales:{x:{ticks:{color:'#8fa3be'},grid:{color:gridColor}},y:{ticks:{color:'#8fa3be',callback:v=>'₹'+v.toLocaleString('en-IN')},grid:{color:gridColor}}}}
});
new Chart(document.getElementById('attChart'),{
  type:'doughnut',
  data:{labels:<?= json_encode(array_keys($att_d)) ?>,datasets:[{data:<?= json_encode(array_values($att_d)) ?>,backgroundColor:['#22c55e','#ef4444','#f5a623','#3b82f6','#06b6d4','#a78bfa'],borderWidth:2,borderColor:'#172033'}]},
  options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#8fa3be',boxWidth:12,padding:10}}}}
});
new Chart(document.getElementById('deptChart'),{
  type:'bar',
  data:{labels:<?= json_encode($d_names) ?>,datasets:[{label:'Net Payroll',data:<?= json_encode($d_totals) ?>,backgroundColor:'rgba(167,139,250,.3)',borderColor:'#a78bfa',borderWidth:2,borderRadius:6}]},
  options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},
    scales:{x:{ticks:{color:'#8fa3be',callback:v=>'₹'+v.toLocaleString('en-IN')},grid:{color:gridColor}},y:{ticks:{color:'#8fa3be'},grid:{color:gridColor}}}}
});
</script>

<?php include '../includes/footer.php'; ?>
