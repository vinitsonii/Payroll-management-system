<?php
require_once '../includes/config.php';
requireLogin();
$db=getDB();
$emp_id=intval($_GET['emp']??0);
$emp=$salary=null;
if($emp_id){
    $emp   =$db->query("SELECT id,first_name,last_name,emp_code FROM employees WHERE id=$emp_id LIMIT 1")->fetch_assoc();
    $salary=$db->query("SELECT * FROM salary_structures WHERE employee_id=$emp_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
}
$page_title=$salary?'Edit Salary Structure':'Assign Salary';
$errors=[];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $eid =intval($_POST['employee_id']);
    $bs  =floatval($_POST['basic_salary']);
    $hra =floatval($_POST['hra']);
    $da  =floatval($_POST['da']);
    $ta  =floatval($_POST['ta']);
    $ma  =floatval($_POST['medical_allowance']);
    $oa  =floatval($_POST['other_allowance']);
    $eff =$db->real_escape_string(trim($_POST['effective_from']));
    if(!$eid)  $errors[]='Select an employee.';
    if($bs<=0) $errors[]='Basic salary must be > 0.';
    if(empty($errors)){
        $ex=$db->query("SELECT id FROM salary_structures WHERE employee_id=$eid ORDER BY id DESC LIMIT 1")->fetch_assoc();
        if($ex) $db->query("UPDATE salary_structures SET basic_salary=$bs,hra=$hra,da=$da,ta=$ta,medical_allowance=$ma,other_allowance=$oa,effective_from='$eff' WHERE id={$ex['id']}");
        else    $db->query("INSERT INTO salary_structures (employee_id,basic_salary,hra,da,ta,medical_allowance,other_allowance,effective_from) VALUES ($eid,$bs,$hra,$da,$ta,$ma,$oa,'$eff')");
        setFlash('success','Salary structure saved.');
        header('Location: list.php');exit();
    }
}

$employees=$db->query("SELECT id,emp_code,first_name,last_name FROM employees WHERE status='Active' ORDER BY first_name");
include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>
<div class="page-header">
  <h2><?= $page_title ?></h2>
  <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
</div>
<?php if($errors): ?><div class="alert alert-danger mb-4"><?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>
<div class="row justify-content-center"><div class="col-12 col-lg-7">
<div class="card">
  <div class="card-header"><i class="bi bi-cash-stack me-2 text-accent"></i>Salary Components</div>
  <div class="card-body">
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Employee *</label>
        <?php if($emp): ?>
          <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
          <div class="form-control" style="cursor:default;background:var(--bg-card2)!important">
            <?= htmlspecialchars($emp['first_name'].' '.$emp['last_name']) ?>
            <span class="mono text-accent ms-2">(<?= $emp['emp_code'] ?>)</span>
          </div>
        <?php else: ?>
          <select name="employee_id" class="form-select" required>
            <option value="">-- Select Employee --</option>
            <?php while($e=$employees->fetch_assoc()): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?> (<?= $e['emp_code'] ?>)</option>
            <?php endwhile; ?>
          </select>
        <?php endif; ?>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Basic Salary (₹) *</label><input type="number" id="basic_salary" name="basic_salary" class="form-control mono" step="0.01" min="0" value="<?= $salary['basic_salary']??'' ?>" required></div>
        <div class="col-12 col-sm-6 col-md-6"><label class="form-label">HRA (₹)</label><input type="number" id="hra" name="hra" class="form-control mono" step="0.01" min="0" value="<?= $salary['hra']??'' ?>"></div>
        <div class="col-12 col-sm-6 col-md-6"><label class="form-label">DA — Dearness Allowance (₹)</label><input type="number" id="da" name="da" class="form-control mono" step="0.01" min="0" value="<?= $salary['da']??'' ?>"></div>
        <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Travel Allowance (₹)</label><input type="number" id="ta" name="ta" class="form-control mono" step="0.01" min="0" value="<?= $salary['ta']??'' ?>"></div>
        <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Medical Allowance (₹)</label><input type="number" id="medical_allowance" name="medical_allowance" class="form-control mono" step="0.01" min="0" value="<?= $salary['medical_allowance']??'' ?>"></div>
        <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Other Allowance (₹)</label><input type="number" id="other_allowance" name="other_allowance" class="form-control mono" step="0.01" min="0" value="<?= $salary['other_allowance']??'' ?>"></div>
        <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Effective From</label><input type="date" name="effective_from" class="form-control" value="<?= $salary['effective_from']??date('Y-m-d') ?>"></div>
      </div>
      <div class="gross-banner mb-4">
        <span class="fw-700 text-accent">Gross Salary</span>
        <span class="fw-900 mono text-accent" id="grossDisplay" style="font-size:22px">₹0.00</span>
      </div>
      <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-2"></i>Save Salary Structure</button>
    </form>
  </div>
</div>
</div></div>
<?php include '../includes/footer.php'; ?>
