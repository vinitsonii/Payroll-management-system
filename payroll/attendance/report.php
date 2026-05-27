<?php
require_once '../includes/config.php';
requireLogin();
$page_title = 'Attendance Report';
$db = getDB();

$sel_month = intval($_GET['month'] ?? date('n'));
$sel_year  = intval($_GET['year']  ?? date('Y'));
$sel_dept  = intval($_GET['dept']  ?? 0);
$sel_emp   = intval($_GET['emp']   ?? 0);

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $sel_month, $sel_year);

$emp_where = "e.status='Active'";
if ($sel_dept) $emp_where .= " AND e.department_id=$sel_dept";
if ($sel_emp)  $emp_where .= " AND e.id=$sel_emp";

$att_raw = $db->query("SELECT employee_id, DAY(att_date) day, status FROM attendance WHERE MONTH(att_date)=$sel_month AND YEAR(att_date)=$sel_year ".($sel_emp?"AND employee_id=$sel_emp":""));
$att_map = [];
while ($r = $att_raw->fetch_assoc()) $att_map[$r['employee_id']][$r['day']] = $r['status'];

$emp_q = $db->query("SELECT e.id,e.emp_code,e.first_name,e.last_name,d.name dept_name FROM employees e LEFT JOIN departments d ON e.department_id=d.id WHERE $emp_where ORDER BY e.first_name");
$emp_list = [];
while ($e = $emp_q->fetch_assoc()) {
    $p=$ab=$h=$l=$wfh=$ho=0;
    for ($d=1;$d<=$days_in_month;$d++) {
        $st = $att_map[$e['id']][$d] ?? null;
        if ($st==='Present')  $p++;
        elseif ($st==='Absent')   $ab++;
        elseif ($st==='Half-Day') $h++;
        elseif ($st==='Leave')    $l++;
        elseif ($st==='WFH')      $wfh++;
        elseif ($st==='Holiday')  $ho++;
    }
    $emp_list[] = array_merge($e, ['P'=>$p,'A'=>$ab,'H'=>$h,'L'=>$l,'W'=>$wfh,'Ho'=>$ho,'eff'=>$p+$wfh+($h*0.5)]);
}

$overall = ['P'=>0,'A'=>0,'H'=>0,'L'=>0,'W'=>0,'Ho'=>0];
foreach ($emp_list as $e) foreach ($overall as $k=>$_) $overall[$k]+=$e[$k];

$status_map = [
    'Present' =>['P', '#22c55e','rgba(34,197,94,.18)'],
    'Absent'  =>['A', '#ef4444','rgba(239,68,68,.18)'],
    'Half-Day'=>['H', '#f5a623','rgba(245,166,35,.18)'],
    'Leave'   =>['L', '#3b82f6','rgba(59,130,246,.18)'],
    'WFH'     =>['W', '#06b6d4','rgba(6,182,212,.18)'],
    'Holiday' =>['Ho','#a78bfa','rgba(167,139,250,.18)'],
];

$departments = $db->query("SELECT * FROM departments ORDER BY name");
$all_emps    = $db->query("SELECT id,emp_code,first_name,last_name FROM employees WHERE status='Active' ORDER BY first_name");

include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header no-print">
  <h2>Attendance Report</h2>
  <div class="d-flex gap-2">
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer-fill me-1"></i>Print</button>
    <a href="mark.php" class="btn btn-primary btn-sm"><i class="bi bi-calendar-check-fill me-1"></i>Mark Attendance</a>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4 no-print">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-sm-4 col-md-2">
        <label class="form-label">Month</label>
        <select name="month" class="form-select">
          <?php for($m=1;$m<=12;$m++): ?>
          <option value="<?= $m ?>" <?= $sel_month==$m?'selected':'' ?>><?= monthName($m) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-md-2">
        <label class="form-label">Year</label>
        <select name="year" class="form-select">
          <?php for($y=date('Y')-2;$y<=date('Y');$y++): ?>
          <option value="<?= $y ?>" <?= $sel_year==$y?'selected':'' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <label class="form-label">Department</label>
        <select name="dept" class="form-select">
          <option value="">All Departments</option>
          <?php while($d=$departments->fetch_assoc()): ?>
          <option value="<?= $d['id'] ?>" <?= $sel_dept==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <label class="form-label">Employee</label>
        <select name="emp" class="form-select">
          <option value="">All Employees</option>
          <?php while($e=$all_emps->fetch_assoc()): ?>
          <option value="<?= $e['id'] ?>" <?= $sel_emp==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?> (<?= $e['emp_code'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Apply</button>
        <a href="report.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
  <?php
  $sum_defs=[
    ['Present',  $overall['P'],  'person-check-fill','si-green', '#22c55e'],
    ['Absent',   $overall['A'],  'person-x-fill',   'si-red',   '#ef4444'],
    ['Half-Day', $overall['H'],  'hourglass-split',  'si-amber', '#f5a623'],
    ['Leave',    $overall['L'],  'calendar2-x-fill', 'si-blue',  '#3b82f6'],
    ['WFH',      $overall['W'],  'house-fill',       'si-cyan',  '#06b6d4'],
    ['Holiday',  $overall['Ho'],'star-fill',         'si-purple','#a78bfa'],
  ];
  foreach($sum_defs as [$lbl,$val,$icon,$cls,$_]):
  ?>
  <div class="col-6 col-sm-4 col-12 col-sm-6 col-md-4 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon <?= $cls ?>"><i class="bi bi-<?= $icon ?>"></i></div>
      <div><div class="stat-label"><?= $lbl ?></div><div class="stat-value"><?= $val ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Legend -->
<div class="d-flex flex-wrap gap-2 mb-3 no-print">
  <?php foreach ($status_map as $st=>[$code,$col,$bg]): ?>
  <span style="background:<?= $bg ?>;color:<?= $col ?>;border:1px solid <?= $col ?>40;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700">
    <?= $code ?> = <?= $st ?>
  </span>
  <?php endforeach; ?>
  <span style="background:var(--bg-card2);color:var(--tx-muted);border:1px solid var(--bd);padding:3px 12px;border-radius:20px;font-size:12px">· = No Record</span>
</div>

<!-- Calendar Grid -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-table me-2 text-accent"></i>Daily Grid — <strong class="text-accent"><?= monthName($sel_month).' '.$sel_year ?></strong></span>
    <span class="text-muted" style="font-size:12px"><?= count($emp_list) ?> employee(s)</span>
  </div>
  <div style="overflow-x:auto">
    <?php if(empty($emp_list)): ?>
      <div class="text-center text-muted py-5">No employees found.</div>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse;min-width:900px;font-size:12px" id="attGrid">
      <thead>
        <tr style="background:var(--bg-card2)">
          <th style="min-width:160px;padding:10px 14px;text-align:left;color:var(--tx-muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;position:sticky;left:0;z-index:2;background:var(--bg-card2);border-bottom:1px solid var(--bd2)" rowspan="2">Employee</th>
          <th style="min-width:80px;padding:10px 14px;text-align:left;color:var(--tx-muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;border-bottom:1px solid var(--bd2)" rowspan="2">Dept</th>
          <?php for($d=1;$d<=$days_in_month;$d++):
            $dow=date('N',mktime(0,0,0,$sel_month,$d,$sel_year));
            $isW=($dow>=6);
          ?>
          <th style="min-width:34px;padding:4px 2px;text-align:center;font-size:11px;font-weight:700;border-bottom:1px solid var(--bd2);<?= $isW?'color:var(--accent)':'color:var(--tx-second)' ?>;border-left:1px solid var(--bd)"><?= $d ?></th>
          <?php endfor; ?>
          <th style="min-width:34px;padding:6px 4px;text-align:center;color:var(--green);font-size:11px;font-weight:800;border-left:2px solid var(--bd2);border-bottom:1px solid var(--bd2)" rowspan="2">P</th>
          <th style="min-width:34px;padding:6px 4px;text-align:center;color:var(--red);font-size:11px;font-weight:800;border-bottom:1px solid var(--bd2)" rowspan="2">A</th>
          <th style="min-width:34px;padding:6px 4px;text-align:center;color:var(--accent);font-size:11px;font-weight:800;border-bottom:1px solid var(--bd2)" rowspan="2">H</th>
          <th style="min-width:34px;padding:6px 4px;text-align:center;color:var(--blue);font-size:11px;font-weight:800;border-bottom:1px solid var(--bd2)" rowspan="2">L</th>
          <th style="min-width:42px;padding:6px 4px;text-align:center;color:var(--green);font-size:11px;font-weight:800;border-bottom:1px solid var(--bd2)" rowspan="2">Eff.</th>
        </tr>
        <tr style="background:var(--bg-card2)">
          <?php for($d=1;$d<=$days_in_month;$d++):
            $dow=date('N',mktime(0,0,0,$sel_month,$d,$sel_year));
            $isW=($dow>=6);
          ?>
          <th style="padding:2px 1px;text-align:center;font-size:9px;font-weight:600;letter-spacing:.2px;border-bottom:1px solid var(--bd2);border-left:1px solid var(--bd);<?= $isW?'color:var(--accent)':'color:var(--tx-muted)' ?>"><?= date('D',mktime(0,0,0,$sel_month,$d,$sel_year))[0] ?></th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach($emp_list as $emp): ?>
      <tr style="border-bottom:1px solid var(--bd)">
        <td style="padding:8px 14px;position:sticky;left:0;z-index:1;background:var(--bg-card);border-right:1px solid var(--bd)">
          <div class="fw-700" style="font-size:12px;color:var(--tx-primary)"><?= htmlspecialchars($emp['first_name'].' '.$emp['last_name']) ?></div>
          <div class="mono text-accent" style="font-size:10px"><?= $emp['emp_code'] ?></div>
        </td>
        <td style="padding:8px 14px;color:var(--tx-muted);font-size:12px;border-right:1px solid var(--bd)"><?= htmlspecialchars($emp['dept_name']??'—') ?></td>
        <?php for($d=1;$d<=$days_in_month;$d++):
          $dow=date('N',mktime(0,0,0,$sel_month,$d,$sel_year));
          $isW=($dow>=6);
          $st=$att_map[$emp['id']][$d]??null;
          $cfg=$st?($status_map[$st]??null):null;
        ?>
        <td style="padding:3px 2px;text-align:center;border-left:1px solid var(--bd);<?= $isW?'background:rgba(245,166,35,.04)':'' ?>">
          <?php if($cfg): [$code,$col,$bg]=$cfg; ?>
          <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:7px;background:<?= $bg ?>;color:<?= $col ?>;font-size:10px;font-weight:800"><?= $code ?></span>
          <?php elseif($isW): ?>
          <span style="color:var(--tx-muted);font-size:11px">—</span>
          <?php else: ?>
          <span style="color:var(--bd2);font-size:14px">·</span>
          <?php endif; ?>
        </td>
        <?php endfor; ?>
        <td style="text-align:center;font-weight:800;color:var(--green);border-left:2px solid var(--bd2)"><?= $emp['P'] ?></td>
        <td style="text-align:center;font-weight:800;color:var(--red)"><?= $emp['A'] ?></td>
        <td style="text-align:center;font-weight:800;color:var(--accent)"><?= $emp['H'] ?></td>
        <td style="text-align:center;font-weight:800;color:var(--blue)"><?= $emp['L'] ?></td>
        <td style="text-align:center;font-weight:900;color:var(--green)"><?= $emp['eff'] ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Single employee detail -->
<?php if($sel_emp && count($emp_list)===1):
  $e=$emp_list[0];
  $pct=$days_in_month>0?round(($e['eff']/$days_in_month)*100):0;
?>
<div class="row g-4">
  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-person-fill me-2 text-accent"></i>Employee Summary</div>
      <div class="card-body text-center">
        <div class="user-avatar mx-auto mb-2" style="width:60px;height:60px;font-size:24px;font-weight:900"><?= strtoupper(substr($e['first_name'],0,1)) ?></div>
        <div class="fw-800 mb-1"><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?></div>
        <div class="mono text-accent mb-3"><?= $e['emp_code'] ?></div>
        <canvas id="attRing" width="120" height="120" style="margin:0 auto 6px;display:block"></canvas>
        <div class="fw-900 text-green" style="font-size:22px"><?= $pct ?>%</div>
        <div class="text-muted mb-4" style="font-size:12px">Attendance Rate</div>
        <div class="row g-2">
          <?php foreach([['Present',$e['P'],'#22c55e'],['Absent',$e['A'],'#ef4444'],['Half-Day',$e['H'],'#f5a623'],['Leave',$e['L'],'#3b82f6'],['WFH',$e['W'],'#06b6d4'],['Holiday',$e['Ho'],'#a78bfa']] as [$lbl,$v,$col]): ?>
          <div class="col-4">
            <div style="background:<?= $col ?>15;border:1px solid <?= $col ?>30;border-radius:8px;padding:8px 4px;text-align:center">
              <div style="font-size:20px;font-weight:900;color:<?= $col ?>"><?= $v ?></div>
              <div style="font-size:10px;color:var(--tx-muted)"><?= $lbl ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-accent"></i>Daily Pattern — <?= monthName($sel_month).' '.$sel_year ?></div>
      <div class="card-body"><canvas id="dailyChart" height="130"></canvas></div>
    </div>
  </div>
</div>
<script>
new Chart(document.getElementById('attRing'),{type:'doughnut',data:{datasets:[{data:[<?= $pct ?>,<?= 100-$pct ?>],backgroundColor:['#22c55e','#2a3f5f'],borderWidth:0}]},options:{cutout:'78%',plugins:{legend:{display:false}},responsive:false}});
const dLabels=[<?php for($d=1;$d<=$days_in_month;$d++) echo "'$d',"; ?>];
const dColors=[<?php for($d=1;$d<=$days_in_month;$d++){$st=$att_map[$sel_emp][$d]??null;$c=match($st){'Present'=>"'#22c55e'",'WFH'=>"'#06b6d4'",'Half-Day'=>"'#f5a623'",'Leave'=>"'#3b82f6'",'Holiday'=>"'#a78bfa'",'Absent'=>"'#ef4444'",default=>"'#2a3f5f'"};echo $c.',';}?>];
const dVals=[<?php for($d=1;$d<=$days_in_month;$d++){$st=$att_map[$sel_emp][$d]??null;$v=match($st){'Present'=>1,'WFH'=>1,'Half-Day'=>0.5,'Leave'=>0.4,'Holiday'=>0.7,'Absent'=>0.15,default=>0};echo $v.',';}?>];
const dTips=[<?php for($d=1;$d<=$days_in_month;$d++) echo "'".($att_map[$sel_emp][$d]??'No Record')."',"; ?>];
new Chart(document.getElementById('dailyChart'),{type:'bar',data:{labels:dLabels,datasets:[{data:dVals,backgroundColor:dColors,borderRadius:5,borderWidth:0}]},options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>dTips[ctx.dataIndex]}}},scales:{x:{ticks:{color:'#8fa3be',font:{size:10}},grid:{display:false}},y:{display:false,max:1.3}}}});
</script>
<?php endif; ?>

<style>
@media print{
  .no-print{display:none!important}
  .sidebar,.topbar{display:none!important}
  .main-wrap{margin-left:0!important}
  body{background:#fff!important;color:#000!important}
  .card{background:#fff!important;border:1px solid #ccc!important}
  .card-header{background:#f0f0f0!important;color:#000!important}
  #attGrid td,#attGrid th{color:#000!important;border-color:#ccc!important}
  .stat-card{background:#f9f9f9!important;border:1px solid #ccc!important}
  .stat-label,.stat-value{color:#000!important}
}
</style>

<?php include '../includes/footer.php'; ?>
