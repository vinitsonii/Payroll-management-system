<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$id = intval($_GET['id']??0);
$emp = $db->query("SELECT e.*,d.name dept_name,des.title designation FROM employees e LEFT JOIN departments d ON e.department_id=d.id LEFT JOIN designations des ON e.designation_id=des.id WHERE e.id=$id LIMIT 1")->fetch_assoc();
if(!$emp){setFlash('error','Not found.');header('Location: list.php');exit();}
$page_title = $emp['first_name'].' '.$emp['last_name'];
$salary  = $db->query("SELECT * FROM salary_structures WHERE employee_id=$id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$payslips= $db->query("SELECT * FROM payroll WHERE employee_id=$id ORDER BY pay_year DESC,pay_month DESC LIMIT 6");
$tm=date('Y-m');
$att_q=$db->query("SELECT status,COUNT(*) cnt FROM attendance WHERE employee_id=$id AND DATE_FORMAT(att_date,'%Y-%m')='$tm' GROUP BY status");
$att=[];while($r=$att_q->fetch_assoc())$att[$r['status']]=$r['cnt'];
include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <h2>Employee Profile</h2>
  <div class="d-flex gap-2">
    <a href="edit.php?id=<?= $id ?>" class="btn btn-primary"><i class="bi bi-pencil-fill me-2"></i>Edit</a>
    <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
  </div>
</div>

<div class="row g-4">
  <!-- Left column -->
  <div class="col-12 col-lg-4">
    <div class="card mb-3">
      <div class="card-body text-center py-4">
        <div class="user-avatar mx-auto mb-3" style="width:72px;height:72px;font-size:28px;font-weight:900">
          <?= strtoupper(substr($emp['first_name'],0,1)) ?>
        </div>
        <h5 class="fw-800 mb-1"><?= htmlspecialchars($emp['first_name'].' '.$emp['last_name']) ?></h5>
        <div class="text-muted mb-2" style="font-size:13px"><?= htmlspecialchars($emp['designation']??'N/A') ?></div>
        <span class="badge-pill bp-<?= strtolower($emp['status']) ?>"><?= $emp['status'] ?></span>
        <hr>
        <div class="text-start">
          <?php $info=[
            'Emp Code'=>['<span class="mono text-accent fw-700">'.$emp['emp_code'].'</span>',true],
            'Department'=>[htmlspecialchars($emp['dept_name']??'—'),false],
            'Type'=>[$emp['employment_type'],false],
            'Joined'=>[$emp['join_date']?date('d M Y',strtotime($emp['join_date'])):'—',false],
            'Gender'=>[$emp['gender']??'—',false],
            'DOB'=>[$emp['dob']?date('d M Y',strtotime($emp['dob'])):'—',false],
          ];
          foreach($info as $k=>[$v,$raw]): ?>
          <div class="kv-row"><span class="kv-key"><?= $k ?></span><span class="kv-value"><?= $raw?$v:htmlspecialchars($v) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-calendar-check-fill me-2 text-accent"></i>This Month</div>
      <div class="card-body">
        <?php foreach(['Present'=>'green','Absent'=>'red','Half-Day'=>'amber','Leave'=>'blue','WFH'=>'cyan'] as $s=>$c): ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted fw-600" style="font-size:13px"><?= $s ?></span>
          <span class="badge-pill bp-<?= $c==='amber'?'pending':($c==='blue'?'processed':($c==='cyan'?'paid':($c==='green'?'active':'inactive'))) ?>"><?= $att[$s]??0 ?> days</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Right column -->
  <div class="col-12 col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-envelope-fill me-2 text-accent"></i>Contact & Identity</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-md-6"><div class="info-box"><div class="info-box-label">Email</div><?= htmlspecialchars($emp['email']??'—') ?></div></div>
          <div class="col-12 col-sm-6 col-md-6"><div class="info-box"><div class="info-box-label">Phone</div><?= htmlspecialchars($emp['phone']??'—') ?></div></div>
          <div class="col-12 col-sm-6 col-md-6"><div class="info-box"><div class="info-box-label">PAN Number</div><span class="mono"><?= htmlspecialchars($emp['pan_number']??'—') ?></span></div></div>
          <div class="col-12"><div class="info-box"><div class="info-box-label">Address</div><?= nl2br(htmlspecialchars($emp['address']??'—')) ?></div></div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-bank2 me-2 text-accent"></i>Bank Details</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-md-4"><div class="info-box"><div class="info-box-label">Account No.</div><span class="mono"><?= htmlspecialchars($emp['bank_account']??'—') ?></span></div></div>
          <div class="col-12 col-sm-6 col-md-4"><div class="info-box"><div class="info-box-label">Bank Name</div><?= htmlspecialchars($emp['bank_name']??'—') ?></div></div>
          <div class="col-12 col-sm-6 col-md-4"><div class="info-box"><div class="info-box-label">IFSC Code</div><span class="mono"><?= htmlspecialchars($emp['ifsc_code']??'—') ?></span></div></div>
        </div>
      </div>
    </div>

    <?php if($salary): $gross=$salary['basic_salary']+$salary['hra']+$salary['da']+$salary['ta']+$salary['medical_allowance']+$salary['other_allowance']; ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-stack me-2 text-accent"></i>Salary Structure</span>
        <a href="../salary/edit.php?emp=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
      </div>
      <div class="card-body">
        <div class="row g-2 mb-3">
          <?php foreach(['Basic Salary'=>$salary['basic_salary'],'HRA'=>$salary['hra'],'DA'=>$salary['da'],'Travel'=>$salary['ta'],'Medical'=>$salary['medical_allowance'],'Other'=>$salary['other_allowance']] as $lbl=>$val): ?>
          <div class="col-md-4 col-6">
            <div class="info-box">
              <div class="info-box-label"><?= $lbl ?></div>
              <div class="mono fw-700"><?= formatCurrency($val) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="gross-banner">
          <span class="fw-700 text-accent">Gross Salary</span>
          <span class="fw-900 mono text-accent" style="font-size:20px"><?= formatCurrency($gross) ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt me-2 text-accent"></i>Recent Payslips</span>
        <a href="../payslip/list.php?search=<?= urlencode($emp['emp_code']) ?>" class="btn btn-sm btn-outline-secondary">All</a>
      </div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Period</th><th>Gross</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php if($payslips->num_rows===0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No payslips yet.</td></tr>
          <?php else: while($ps=$payslips->fetch_assoc()): ?>
          <tr>
            <td class="fw-600"><?= monthName($ps['pay_month']).' '.$ps['pay_year'] ?></td>
            <td class="mono"><?= formatCurrency($ps['gross_salary']) ?></td>
            <td class="mono text-red">-<?= formatCurrency($ps['total_deduction']) ?></td>
            <td class="mono fw-800 text-green"><?= formatCurrency($ps['net_salary']) ?></td>
            <td><span class="badge-pill bp-<?= strtolower($ps['status']) ?>"><?= $ps['status'] ?></span></td>
            <td><a href="../payslip/view.php?id=<?= $ps['id'] ?>" class="btn btn-icon btn-sm btn-action-view"><i class="bi bi-eye-fill"></i></a></td>
          </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
