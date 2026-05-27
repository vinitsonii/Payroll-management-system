<?php
require_once '../includes/config.php';
requireLogin();
$page_title = 'Add Employee';
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $f=['first_name','last_name','email','phone','dob','gender','address',
        'department_id','designation_id','join_date','employment_type','status',
        'pan_number','bank_account','bank_name','ifsc_code'];
    $data=[];
    foreach($f as $k) $data[$k]=trim($_POST[$k]??'');
    if(empty($data['first_name'])) $errors[]='First name is required.';
    if(empty($data['last_name']))  $errors[]='Last name is required.';

    if(empty($errors)){
        $last=$db->query("SELECT emp_code FROM employees ORDER BY id DESC LIMIT 1")->fetch_assoc();
        $n   = $last ? intval(substr($last['emp_code'],3))+1 : 1;
        $code= 'EMP'.str_pad($n,4,'0',STR_PAD_LEFT);
        $stmt=$db->prepare("INSERT INTO employees (emp_code,first_name,last_name,email,phone,dob,gender,address,department_id,designation_id,join_date,employment_type,status,pan_number,bank_account,bank_name,ifsc_code) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $dept_id  = $data['department_id']  ?: null;
        $desig_id = $data['designation_id'] ?: null;
        $stmt->bind_param('ssssssssiisssssss',$code,$data['first_name'],$data['last_name'],$data['email'],$data['phone'],$data['dob'],$data['gender'],$data['address'],$dept_id,$desig_id,$data['join_date'],$data['employment_type'],$data['status'],$data['pan_number'],$data['bank_account'],$data['bank_name'],$data['ifsc_code']);
        if($stmt->execute()){
            $nid=$db->insert_id;
            if(!empty($_POST['basic_salary'])){
                $bs=floatval($_POST['basic_salary']); $hra=floatval($_POST['hra']??0);
                $da=floatval($_POST['da']??0); $ta=floatval($_POST['ta']??0);
                $ma=floatval($_POST['medical_allowance']??0); $oa=floatval($_POST['other_allowance']??0);
                $eff=$data['join_date']?:date('Y-m-d');
                $db->query("INSERT INTO salary_structures (employee_id,basic_salary,hra,da,ta,medical_allowance,other_allowance,effective_from) VALUES ($nid,$bs,$hra,$da,$ta,$ma,$oa,'$eff')");
            }
            setFlash('success',"Employee $code added successfully!");
            header('Location: list.php'); exit();
        }
        $errors[]='Database error: '.$db->error;
    }
}

$departments  = $db->query("SELECT * FROM departments ORDER BY name");
$designations = $db->query("SELECT * FROM designations ORDER BY title");
include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <h2>Add New Employee</h2>
  <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
</div>

<?php if($errors): ?>
<div class="alert alert-danger mb-4">
  <?php foreach($errors as $e) echo '<div><i class="bi bi-exclamation-circle me-1"></i>'.htmlspecialchars($e).'</div>'; ?>
</div>
<?php endif; ?>

<form method="POST">
<div class="row g-4">
  <div class="col-12 col-lg-8">

    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-person-fill me-2 text-accent"></i>Personal Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name']??'') ?>" required></div>
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name']??'') ?>" required></div>
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="">-- Select --</option>
              <?php foreach(['Male','Female','Other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($_POST['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">PAN Number</label><input type="text" name="pan_number" class="form-control mono" placeholder="ABCDE1234F" value="<?= htmlspecialchars($_POST['pan_number']??'') ?>"></div>
          <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($_POST['address']??'') ?></textarea></div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-briefcase-fill me-2 text-accent"></i>Job Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Department</label>
            <select name="department_id" id="department_id" class="form-select">
              <option value="">-- Select Department --</option>
              <?php while($d=$departments->fetch_assoc()): ?>
              <option value="<?= $d['id'] ?>" <?= ($_POST['department_id']??'')==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-6"><label class="form-label">Designation</label>
            <select name="designation_id" id="designation_id" class="form-select">
              <option value="">-- Select Designation --</option>
              <?php while($des=$designations->fetch_assoc()): ?>
              <option value="<?= $des['id'] ?>" <?= ($_POST['designation_id']??'')==$des['id']?'selected':'' ?>><?= htmlspecialchars($des['title']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Join Date</label><input type="date" name="join_date" class="form-control" value="<?= htmlspecialchars($_POST['join_date']??date('Y-m-d')) ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Employment Type</label>
            <select name="employment_type" class="form-select">
              <?php foreach(['Full-Time','Part-Time','Contract'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="Active">Active</option><option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-bank2 me-2 text-accent"></i>Bank Details</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Account No.</label><input type="text" name="bank_account" class="form-control mono" value="<?= htmlspecialchars($_POST['bank_account']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">Bank Name</label><input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($_POST['bank_name']??'') ?>"></div>
          <div class="col-12 col-sm-6 col-md-4"><label class="form-label">IFSC Code</label><input type="text" name="ifsc_code" class="form-control mono" placeholder="SBIN0001234" value="<?= htmlspecialchars($_POST['ifsc_code']??'') ?>"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card" style="position:sticky;top:80px">
      <div class="card-header"><i class="bi bi-cash-stack me-2 text-accent"></i>Salary Structure</div>
      <div class="card-body">
        <div class="mb-3"><label class="form-label">Basic Salary (₹)</label><input type="number" id="basic_salary" name="basic_salary" class="form-control mono" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($_POST['basic_salary']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">HRA (₹)</label><input type="number" id="hra" name="hra" class="form-control mono" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($_POST['hra']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">DA — Dearness Allowance (₹)</label><input type="number" id="da" name="da" class="form-control mono" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($_POST['da']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">Travel Allowance (₹)</label><input type="number" id="ta" name="ta" class="form-control mono" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($_POST['ta']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">Medical Allowance (₹)</label><input type="number" id="medical_allowance" name="medical_allowance" class="form-control mono" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($_POST['medical_allowance']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">Other Allowance (₹)</label><input type="number" id="other_allowance" name="other_allowance" class="form-control mono" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($_POST['other_allowance']??'') ?>"></div>

        <div class="gross-banner mb-4">
          <span class="fw-700 text-accent">Gross Salary</span>
          <span class="fw-900 mono text-accent" id="grossDisplay" style="font-size:20px">₹0.00</span>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-person-plus-fill me-2"></i>Add Employee</button>
        <a href="list.php" class="btn btn-outline-secondary w-100">Cancel</a>
      </div>
    </div>
  </div>
</div>
</form>

<?php include '../includes/footer.php'; ?>
