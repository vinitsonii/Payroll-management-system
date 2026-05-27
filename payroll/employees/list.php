<?php
require_once '../includes/config.php';
requireLogin();
$page_title = 'All Employees';
$db = getDB();

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM employees WHERE id=".intval($_GET['delete']));
    setFlash('success','Employee deleted.'); header('Location: list.php'); exit();
}

$search = trim($_GET['search'] ?? '');
$dept   = intval($_GET['dept'] ?? 0);
$status = $_GET['status'] ?? '';
$where  = ['1=1'];
if ($search) $where[]="(e.first_name LIKE '%".addslashes($search)."%' OR e.last_name LIKE '%".addslashes($search)."%' OR e.emp_code LIKE '%".addslashes($search)."%')";
if ($dept)   $where[]="e.department_id=$dept";
if ($status) $where[]="e.status='".addslashes($status)."'";

$employees   = $db->query("SELECT e.*,d.name dept_name,des.title designation FROM employees e LEFT JOIN departments d ON e.department_id=d.id LEFT JOIN designations des ON e.designation_id=des.id WHERE ".implode(' AND ',$where)." ORDER BY e.id DESC");
$departments = $db->query("SELECT * FROM departments ORDER BY name");
include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <h2>All Employees</h2>
  <a href="add.php" class="btn btn-primary"><i class="bi bi-person-plus-fill me-2"></i>Add Employee</a>
</div>

<div class="card mb-4">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-sm-6 col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Search name, code..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <select name="dept" class="form-select">
          <option value="">All Departments</option>
          <?php $departments->data_seek(0); while($d=$departments->fetch_assoc()): ?>
          <option value="<?= $d['id'] ?>" <?= $dept==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-md-2">
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <?php foreach(['Active','Inactive','Terminated'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button>
        <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-people-fill me-2 text-accent"></i>Employee Records</span>
    <span class="text-muted" style="font-size:12px"><?= $employees->num_rows ?> found</span>
  </div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr>
        <th>#</th><th>Employee</th><th>Department</th><th>Designation</th>
        <th>Phone</th><th>Join Date</th><th>Type</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if($employees->num_rows===0): ?>
        <tr><td colspan="9" class="text-center text-muted py-5">No employees found.</td></tr>
      <?php else: $n=1; while($e=$employees->fetch_assoc()): ?>
      <tr>
        <td class="text-muted"><?= $n++ ?></td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="user-avatar" style="width:34px;height:34px;font-size:13px;flex-shrink:0">
              <?= strtoupper(substr($e['first_name'],0,1)) ?>
            </div>
            <div>
              <div class="fw-700"><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?></div>
              <div class="mono text-accent" style="font-size:11px"><?= $e['emp_code'] ?></div>
            </div>
          </div>
        </td>
        <td><?= htmlspecialchars($e['dept_name']??'—') ?></td>
        <td class="text-muted"><?= htmlspecialchars($e['designation']??'—') ?></td>
        <td><?= htmlspecialchars($e['phone']??'—') ?></td>
        <td><?= $e['join_date']?date('d M Y',strtotime($e['join_date'])):'—' ?></td>
        <td><span class="badge-pill bp-<?= strtolower(str_replace('-','',str_replace(' ','',$e['employment_type']))) ?>"><?= $e['employment_type'] ?></span></td>
        <td><span class="badge-pill bp-<?= strtolower($e['status']) ?>"><?= $e['status'] ?></span></td>
        <td>
          <div class="d-flex gap-1">
            <a href="view.php?id=<?= $e['id'] ?>" class="btn btn-icon btn-sm btn-action-view" title="View"><i class="bi bi-eye-fill"></i></a>
            <a href="edit.php?id=<?= $e['id'] ?>" class="btn btn-icon btn-sm btn-action-edit" title="Edit"><i class="bi bi-pencil-fill"></i></a>
            <a href="?delete=<?= $e['id'] ?>" class="btn btn-icon btn-sm btn-action-delete"
               data-confirm="Delete this employee and all their records?" title="Delete"><i class="bi bi-trash-fill"></i></a>
          </div>
        </td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
