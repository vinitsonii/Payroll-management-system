<?php
require_once '../includes/config.php';
requireLogin();
$db=$getDB=getDB();
$id=intval($_GET['id']??0);
$emp=$db->query("SELECT * FROM employees WHERE id=$id LIMIT 1")->fetch_assoc();
if(!$emp){setFlash('error','Not found.');header('Location: list.php');exit();}
$page_title='Edit Employee';
$errors=[];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $f=['first_name','last_name','email','phone','dob','gender','address','department_id','designation_id','join_date','employment_type','status','pan_number','bank_account','bank_name','ifsc_code'];
    $data=[];foreach($f as $k)$data[$k]=trim($_POST[$k]??'');
    if(empty($data['first_name']))$errors[]='First name required.';
    if(empty($data['last_name'])) $errors[]='Last name required.';
    if(empty($errors)){
        $stmt=$db->prepare("UPDATE employees SET first_name=?,last_name=?,email=?,phone=?,dob=?,gender=?,address=?,department_id=?,designation_id=?,join_date=?,employment_type=?,status=?,pan_number=?,bank_account=?,bank_name=?,ifsc_code=? WHERE id=?");
        $stmt->bind_param('ssssssssisssssssi',$data['first_name'],$data['last_name'],$data['email'],$data['phone'],$data['dob'],$data['gender'],$data['address'],$data['department_id'],$data['designation_id'],$data['join_date'],$data['employment_type'],$data['status'],$data['pan_number'],$data['bank_account'],$data['bank_name'],$data['ifsc_code'],$id);
        if($stmt->execute()){setFlash('success','Employee updated.');header("Location: view.php?id=$id");exit();}
        $errors[]='DB error: '.$db->error;
    }
    $emp=array_merge($emp,$data);
}

$departments=$db->query("SELECT * FROM departments ORDER BY name");
$designations=$db->query("SELECT * FROM designations ORDER BY title");
include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <div>
    <h2>Edit Employee</h2>
    <div class="page-subtitle mono text-accent"><?= $emp['emp_code'] ?></div>
  </div>
  <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
</div>

<?php if($errors): ?>
<div class="alert alert-danger mb-4">
  <?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
</div>
<?php endif; ?>

<form method="POST">
<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-person-fill me-2 text-accent"></i>Personal Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($emp['first_name']) ?>" required></div>
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($emp['last_name']) ?>" required></div>
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($emp['email']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($emp['phone']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($emp['dob']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach(['Male','Female','Other'] as $g): ?>
              <option value="<?= $g ?>" <?= $emp['gender']===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">PAN Number</label><input type="text" name="pan_number" class="form-control mono" value="<?= htmlspecialchars($emp['pan_number']??'') ?>"></div>
          <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($emp['address']??'') ?></textarea></div>
        </div>
      </div>
    </div>
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-briefcase-fill me-2 text-accent"></i>Job Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Department</label>
            <select name="department_id" id="department_id" class="form-select">
              <option value="">-- Select --</option>
              <?php while($d=$departments->fetch_assoc()): ?>
              <option value="<?= $d['id'] ?>" <?= $emp['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Designation</label>
            <select name="designation_id" id="designation_id" class="form-select">
              <option value="">-- Select --</option>
              <?php while($des=$designations->fetch_assoc()): ?>
              <option value="<?= $des['id'] ?>" <?= $emp['designation_id']==$des['id']?'selected':'' ?>><?= htmlspecialchars($des['title']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Join Date</label><input type="date" name="join_date" class="form-control" value="<?= htmlspecialchars($emp['join_date']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Employment Type</label>
            <select name="employment_type" class="form-select">
              <?php foreach(['Full-Time','Part-Time','Contract'] as $t): ?>
              <option value="<?= $t ?>" <?= $emp['employment_type']===$t?'selected':'' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Status</label>
            <select name="status" class="form-select">
              <?php foreach(['Active','Inactive','Terminated'] as $s): ?>
              <option value="<?= $s ?>" <?= $emp['status']===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><i class="bi bi-bank2 me-2 text-accent"></i>Bank Details</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Account No.</label><input type="text" name="bank_account" class="form-control mono" value="<?= htmlspecialchars($emp['bank_account']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Bank Name</label><input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($emp['bank_name']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">IFSC Code</label><input type="text" name="ifsc_code" class="form-control mono" value="<?= htmlspecialchars($emp['ifsc_code']??'') ?>"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card" style="position:sticky;top:80px">
      <div class="card-body">
        <div class="info-box mb-3">
          <div class="info-box-label">Employee Code</div>
          <div class="mono fw-900 text-accent" style="font-size:22px"><?= $emp['emp_code'] ?></div>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
        <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary w-100">Cancel</a>
      </div>
    </div>
  </div>
</div>
</form>

<?php include '../includes/footer.php'; ?>
