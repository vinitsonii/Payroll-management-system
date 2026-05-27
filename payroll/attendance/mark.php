<?php
require_once '../includes/config.php';
requireLogin();
$page_title='Mark Attendance';
$db=getDB();
$sel_date=$_GET['date']??date('Y-m-d');
$sel_dept=intval($_GET['dept']??0);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $date=$db->real_escape_string($_POST['att_date']);
    foreach($_POST['attendance'] as $eid=>$status){
        $eid=intval($eid); $status=$db->real_escape_string($status);
        $ci=$db->real_escape_string($_POST['checkin'][$eid]??'');
        $co=$db->real_escape_string($_POST['checkout'][$eid]??'');
        $db->query("INSERT INTO attendance (employee_id,att_date,status,check_in,check_out) VALUES ($eid,'$date','$status',".($ci?"'$ci'":'NULL').",".($co?"'$co'":'NULL').") ON DUPLICATE KEY UPDATE status='$status',check_in=".($ci?"'$ci'":'NULL').",check_out=".($co?"'$co'":'NULL'));
    }
    setFlash('success','Attendance saved for '.date('d M Y',strtotime($date)));
    header("Location: mark.php?date=$date&dept=$sel_dept");exit();
}

$dfw=$sel_dept?"AND e.department_id=$sel_dept":'';
$employees=$db->query("SELECT e.id,e.emp_code,e.first_name,e.last_name,d.name dept_name,a.status att_status,a.check_in,a.check_out FROM employees e LEFT JOIN departments d ON e.department_id=d.id LEFT JOIN attendance a ON a.employee_id=e.id AND a.att_date='$sel_date' WHERE e.status='Active' $dfw ORDER BY e.first_name");
$departments=$db->query("SELECT * FROM departments ORDER BY name");
$summary=$db->query("SELECT status,COUNT(*) cnt FROM attendance WHERE att_date='$sel_date' GROUP BY status");
$asum=[];while($r=$summary->fetch_assoc())$asum[$r['status']]=$r['cnt'];
include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>
<div class="page-header">
  <h2>Mark Attendance</h2>
  <a href="report.php" class="btn btn-outline-secondary"><i class="bi bi-table me-2"></i>Attendance Report</a>
</div>
<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-sm-6 col-md-3"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="<?= $sel_date ?>" max="<?= date('Y-m-d') ?>"></div>
      <div class="col-12 col-sm-6 col-md-3"><label class="form-label">Department</label>
        <select name="dept" class="form-select">
          <option value="">All Departments</option>
          <?php while($d=$departments->fetch_assoc()): ?>
          <option value="<?= $d['id'] ?>" <?= $sel_dept==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Load</button></div>
    </form>
  </div>
</div>
<div class="d-flex flex-wrap gap-2 mb-4">
  <?php foreach(['Present'=>'active','Absent'=>'inactive','Half-Day'=>'pending','Leave'=>'processed','WFH'=>'paid'] as $s=>$c): ?>
  <div class="badge-pill bp-<?= $c ?>"><?= $s ?>: <strong><?= $asum[$s]??0 ?></strong></div>
  <?php endforeach; ?>
</div>
<form method="POST">
  <input type="hidden" name="att_date" value="<?= $sel_date ?>">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-calendar-check-fill me-2 text-accent"></i><?= date('d F Y',strtotime($sel_date)) ?></span>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="markAllAttendance('Present')">All Present</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="markAllAttendance('Absent')">All Absent</button>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>#</th><th>Code</th><th>Employee</th><th>Dept</th><th>Status</th><th>Check In</th><th>Check Out</th></tr></thead>
        <tbody>
        <?php if($employees->num_rows===0): ?>
          <tr><td colspan="7" class="text-center text-muted py-5">No employees found.</td></tr>
        <?php else: $n=1; while($e=$employees->fetch_assoc()): $cur=$e['att_status']??'Present'; ?>
        <tr>
          <td class="text-muted"><?= $n++ ?></td>
          <td><span class="mono text-accent"><?= $e['emp_code'] ?></span></td>
          <td class="fw-700"><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?></td>
          <td class="text-muted"><?= htmlspecialchars($e['dept_name']??'—') ?></td>
          <td>
            <select name="attendance[<?= $e['id'] ?>]" class="form-select form-select-sm att-select" style="min-width:120px">
              <?php foreach(['Present','Absent','Half-Day','Leave','Holiday','WFH'] as $s): ?>
              <option value="<?= $s ?>" <?= $cur===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="time" name="checkin[<?= $e['id'] ?>]" class="form-control form-control-sm" style="min-width:100px" value="<?= $e['check_in']??'09:00' ?>"></td>
          <td><input type="time" name="checkout[<?= $e['id'] ?>]" class="form-control form-control-sm" style="min-width:100px" value="<?= $e['check_out']??'18:00' ?>"></td>
        </tr>
        <?php endwhile;endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-end">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-2"></i>Save Attendance</button>
    </div>
  </div>
</form>
<?php include '../includes/footer.php'; ?>
