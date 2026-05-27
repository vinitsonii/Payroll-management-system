<?php
require_once '../includes/config.php';
requireLogin();
$page_title='Salary Structures';
$db=getDB();
$records=$db->query("SELECT ss.*,CONCAT(e.first_name,' ',e.last_name) emp_name,e.emp_code,d.name dept_name,(ss.basic_salary+ss.hra+ss.da+ss.ta+ss.medical_allowance+ss.other_allowance) gross FROM salary_structures ss JOIN employees e ON ss.employee_id=e.id LEFT JOIN departments d ON e.department_id=d.id ORDER BY ss.id DESC");
include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>
<div class="page-header">
  <h2>Salary Structures</h2>
  <a href="edit.php" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Assign Salary</a>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Employee</th><th>Dept</th><th>Basic</th><th>HRA</th><th>DA</th><th>TA</th><th>Medical</th><th>Other</th><th>Gross</th><th>Effective</th><th></th></tr></thead>
      <tbody>
      <?php if($records->num_rows===0): ?>
        <tr><td colspan="11" class="text-center text-muted py-5">No structures yet.</td></tr>
      <?php else: while($r=$records->fetch_assoc()): ?>
      <tr>
        <td><div class="fw-700"><?= htmlspecialchars($r['emp_name']) ?></div><div class="mono text-accent" style="font-size:11px"><?= $r['emp_code'] ?></div></td>
        <td class="text-muted"><?= htmlspecialchars($r['dept_name']??'—') ?></td>
        <td class="mono"><?= formatCurrency($r['basic_salary']) ?></td>
        <td class="mono"><?= formatCurrency($r['hra']) ?></td>
        <td class="mono"><?= formatCurrency($r['da']) ?></td>
        <td class="mono"><?= formatCurrency($r['ta']) ?></td>
        <td class="mono"><?= formatCurrency($r['medical_allowance']) ?></td>
        <td class="mono"><?= formatCurrency($r['other_allowance']) ?></td>
        <td><span class="fw-900 text-accent mono"><?= formatCurrency($r['gross']) ?></span></td>
        <td><?= $r['effective_from']?date('d M Y',strtotime($r['effective_from'])):'—' ?></td>
        <td><a href="edit.php?emp=<?= $r['employee_id'] ?>" class="btn btn-icon btn-sm btn-action-edit"><i class="bi bi-pencil-fill"></i></a></td>
      </tr>
      <?php endwhile;endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
