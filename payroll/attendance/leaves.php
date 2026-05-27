<?php
require_once '../includes/config.php';
requireLogin();
$page_title='Leave Management';
$db=getDB();
if(isset($_GET['action'],$_GET['id'])){
    $lid=intval($_GET['id']); $by=intval($_SESSION['user_id']);
    $action=$_GET['action']==='approve'?'Approved':'Rejected';
    $db->query("UPDATE leave_applications SET status='$action',approved_by=$by WHERE id=$lid");
    setFlash('success',"Leave $action."); header('Location: leaves.php'); exit();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $eid=intval($_POST['employee_id']); $tid=intval($_POST['leave_type_id']);
    $from=$db->real_escape_string($_POST['from_date']); $to=$db->real_escape_string($_POST['to_date']);
    $reason=$db->real_escape_string(trim($_POST['reason']));
    $days=(int)((strtotime($to)-strtotime($from))/86400)+1;
    $db->query("INSERT INTO leave_applications (employee_id,leave_type_id,from_date,to_date,total_days,reason) VALUES ($eid,$tid,'$from','$to',$days,'$reason')");
    setFlash('success','Leave application submitted.'); header('Location: leaves.php'); exit();
}
$applications=$db->query("SELECT la.*,CONCAT(e.first_name,' ',e.last_name) emp_name,e.emp_code,lt.name leave_type FROM leave_applications la JOIN employees e ON la.employee_id=e.id JOIN leave_types lt ON la.leave_type_id=lt.id ORDER BY la.applied_on DESC");
$employees  =$db->query("SELECT id,emp_code,first_name,last_name FROM employees WHERE status='Active' ORDER BY first_name");
$leave_types=$db->query("SELECT * FROM leave_types");
include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>
<div class="page-header">
  <h2>Leave Management</h2>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyModal"><i class="bi bi-plus-lg me-2"></i>Apply Leave</button>
</div>
<div class="card">
  <div class="card-header"><i class="bi bi-calendar2-x-fill me-2 text-accent"></i>Leave Applications</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>#</th><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if($applications->num_rows===0): ?>
        <tr><td colspan="9" class="text-center text-muted py-5">No leave applications.</td></tr>
      <?php else: $n=1; while($r=$applications->fetch_assoc()): ?>
      <tr>
        <td class="text-muted"><?= $n++ ?></td>
        <td><div class="fw-700"><?= htmlspecialchars($r['emp_name']) ?></div><div class="mono text-accent" style="font-size:11px"><?= $r['emp_code'] ?></div></td>
        <td><?= htmlspecialchars($r['leave_type']) ?></td>
        <td><?= date('d M Y',strtotime($r['from_date'])) ?></td>
        <td><?= date('d M Y',strtotime($r['to_date'])) ?></td>
        <td><span class="badge-pill bp-pending"><?= $r['total_days'] ?> day(s)</span></td>
        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['reason']) ?></td>
        <td><span class="badge-pill bp-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
        <td>
          <?php if($r['status']==='Pending'): ?>
          <div class="d-flex gap-1">
            <a href="?action=approve&id=<?= $r['id'] ?>" class="btn btn-icon btn-sm btn-action-pay" title="Approve"><i class="bi bi-check-lg"></i></a>
            <a href="?action=reject&id=<?= $r['id'] ?>"  class="btn btn-icon btn-sm btn-action-delete" title="Reject"><i class="bi bi-x-lg"></i></a>
          </div>
          <?php else: ?><span class="text-muted" style="font-size:12px"><?= $r['status'] ?></span><?php endif; ?>
        </td>
      </tr>
      <?php endwhile;endif; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Modal -->
<div class="modal fade" id="applyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Apply for Leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST">
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Employee *</label>
          <select name="employee_id" class="form-select" required>
            <option value="">-- Select Employee --</option>
            <?php while($e=$employees->fetch_assoc()): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?> (<?= $e['emp_code'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Leave Type *</label>
          <select name="leave_type_id" class="form-select" required>
            <option value="">-- Select Type --</option>
            <?php while($lt=$leave_types->fetch_assoc()): ?>
            <option value="<?= $lt['id'] ?>"><?= htmlspecialchars($lt['name']) ?> (<?= $lt['days_allowed'] ?> days/year)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-6"><label class="form-label">From *</label><input type="date" name="from_date" class="form-control" required></div>
          <div class="col-6"><label class="form-label">To *</label><input type="date" name="to_date" class="form-control" required></div>
        </div>
        <div class="mb-3"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Application</button>
      </div>
      </form>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
