<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$id = intval($_GET['id'] ?? 0);
$ps = $db->query("
    SELECT p.*, CONCAT(e.first_name,' ',e.last_name) emp_name,
           e.emp_code, e.email, e.pan_number,
           e.bank_account, e.bank_name, e.ifsc_code,
           d.name dept_name, des.title designation
    FROM payroll p
    JOIN employees e ON p.employee_id=e.id
    LEFT JOIN departments d   ON e.department_id=d.id
    LEFT JOIN designations des ON e.designation_id=des.id
    WHERE p.id=$id LIMIT 1")->fetch_assoc();

if (!$ps) { setFlash('error','Payslip not found.'); header('Location: list.php'); exit(); }
$page_title = 'Payslip — '.monthName($ps['pay_month']).' '.$ps['pay_year'];
$company = getSetting('company_name');
$address = getSetting('company_address');
$phone   = getSetting('company_phone');
$cemail  = getSetting('company_email');

include '../includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header no-print">
  <h2>Payslip</h2>
  <div class="d-flex gap-2">
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer-fill me-2"></i>Print / PDF</button>
    <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
  </div>
</div>

<div class="row justify-content-center">
<div class="col-12 col-lg-9">
<div class="card" id="payslipDoc">
  <div class="card-body p-4">

    <!-- Company Header -->
    <div class="d-flex justify-content-between align-items-start mb-4" style="flex-wrap:wrap;gap:14px">
      <div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
          <div style="width:42px;height:42px;background:linear-gradient(135deg,#f5a623,#e67e00);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff">
            <i class="bi bi-briefcase-fill"></i>
          </div>
          <div>
            <div class="fw-900" style="font-size:18px;color:var(--tx-primary)"><?= htmlspecialchars($company) ?></div>
            <div class="text-muted" style="font-size:12px"><?= htmlspecialchars($address) ?></div>
          </div>
        </div>
        <div class="text-muted" style="font-size:12px">
          <i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($phone) ?>
          &nbsp;&nbsp;<i class="bi bi-envelope-fill me-1"></i><?= htmlspecialchars($cemail) ?>
        </div>
      </div>
      <div style="background:var(--accent-glow);border:1px solid rgba(245,166,35,.4);border-radius:12px;padding:14px 22px;text-align:center">
        <div class="text-accent fw-800" style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase">PAYSLIP</div>
        <div class="fw-900" style="font-size:20px;color:var(--tx-primary)"><?= monthName($ps['pay_month']).' '.$ps['pay_year'] ?></div>
        <div class="text-muted" style="font-size:11px">Generated <?= date('d M Y',strtotime($ps['generated_on'])) ?></div>
      </div>
    </div>

    <hr>

    <!-- Employee + Pay Details -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-md-6">
        <div class="info-box">
          <div class="info-box-label"><i class="bi bi-person-fill me-1 text-accent"></i>Employee Details</div>
          <?php $emp_info=['Name'=>$ps['emp_name'],'Emp Code'=>$ps['emp_code'],'Department'=>$ps['dept_name']??'—','Designation'=>$ps['designation']??'—','PAN'=>$ps['pan_number']??'—'];
          foreach ($emp_info as $k=>$v): ?>
          <div class="kv-row"><span class="kv-key"><?= $k ?></span><span class="kv-value mono"><?= htmlspecialchars($v) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-6">
        <div class="info-box">
          <div class="info-box-label"><i class="bi bi-calendar3 me-1 text-accent"></i>Pay Period Details</div>
          <?php $pay_info=['Pay Month'=>monthName($ps['pay_month']).' '.$ps['pay_year'],'Working Days'=>$ps['working_days'],'Days Present'=>$ps['present_days'],'Bank'=>$ps['bank_name']??'—','Account No.'=>$ps['bank_account']??'—','IFSC'=>$ps['ifsc_code']??'—'];
          foreach ($pay_info as $k=>$v): ?>
          <div class="kv-row"><span class="kv-key"><?= $k ?></span><span class="kv-value mono"><?= htmlspecialchars($v) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Earnings & Deductions -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-md-6">
        <div style="border:1px solid var(--bd);border-radius:var(--radius);overflow:hidden">
          <div style="background:var(--green-glow);padding:10px 16px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--green);border-bottom:1px solid var(--bd)">
            <i class="bi bi-arrow-up-circle-fill me-2"></i>Earnings
          </div>
          <table class="table table-sm mb-0" style="font-size:13px">
            <tbody>
            <?php
            $earnings=['Basic Salary'=>$ps['basic_salary'],'HRA'=>$ps['hra'],'Dearness Allowance'=>$ps['da'],'Travel Allowance'=>$ps['ta'],'Medical Allowance'=>$ps['medical_allowance'],'Other Allowance'=>$ps['other_allowance']];
            foreach ($earnings as $lbl=>$val): if($val<=0) continue; ?>
            <tr><td class="text-muted"><?= $lbl ?></td><td class="text-end mono fw-700"><?= formatCurrency($val) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr style="background:var(--bg-card2)">
              <td class="fw-800 text-green">Gross Salary</td>
              <td class="text-end mono fw-900 text-green"><?= formatCurrency($ps['gross_salary']) ?></td>
            </tr></tfoot>
          </table>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-6">
        <div style="border:1px solid var(--bd);border-radius:var(--radius);overflow:hidden">
          <div style="background:var(--red-glow);padding:10px 16px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--red);border-bottom:1px solid var(--bd)">
            <i class="bi bi-arrow-down-circle-fill me-2"></i>Deductions
          </div>
          <table class="table table-sm mb-0" style="font-size:13px">
            <tbody>
            <?php
            $deds=['PF (Employee)'=>$ps['pf_employee'],'ESI (Employee)'=>$ps['esi_employee'],'TDS'=>$ps['tds'],'Professional Tax'=>$ps['professional_tax'],'Other'=>$ps['other_deduction']??0];
            foreach ($deds as $lbl=>$val): if($val<=0) continue; ?>
            <tr><td class="text-muted"><?= $lbl ?></td><td class="text-end mono fw-700 text-red">-<?= formatCurrency($val) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr style="background:var(--bg-card2)">
              <td class="fw-800 text-red">Total Deductions</td>
              <td class="text-end mono fw-900 text-red">-<?= formatCurrency($ps['total_deduction']) ?></td>
            </tr></tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Net Pay Banner -->
    <div class="net-banner mb-4">
      <div class="text-muted fw-700 mb-1" style="font-size:12px;letter-spacing:1px;text-transform:uppercase">Net Salary Payable</div>
      <div class="fw-900 text-green" style="font-size:40px"><?= formatCurrency($ps['net_salary']) ?></div>
      <div class="text-muted mt-1" style="font-size:12px">
        Status: <span class="badge-pill bp-<?= strtolower($ps['status']) ?>"><?= $ps['status'] ?></span>
      </div>
    </div>

    <!-- Employer Contributions -->
    <div class="info-box mb-4">
      <div class="info-box-label">Employer Contributions (Informational)</div>
      <div class="d-flex flex-wrap gap-4" style="font-size:13px">
        <span>PF Employer: <strong class="text-accent mono"><?= formatCurrency($ps['pf_employer']) ?></strong></span>
        <span>ESI Employer: <strong class="text-accent mono"><?= formatCurrency($ps['esi_employer']) ?></strong></span>
      </div>
    </div>

    <!-- Signatures -->
    <div class="row text-center mt-4" style="font-size:12px;color:var(--tx-muted)">
      <div class="col-4"><div style="border-top:1px solid var(--bd);padding-top:8px">Employee Signature</div></div>
      <div class="col-4"><div style="border-top:1px solid var(--bd);padding-top:8px">HR Signature</div></div>
      <div class="col-4"><div style="border-top:1px solid var(--bd);padding-top:8px">Authorized Signatory</div></div>
    </div>
    <div class="text-center mt-3 text-muted" style="font-size:11px">
      This is a computer-generated payslip and does not require a physical signature.
    </div>

  </div>
</div>
</div>
</div>

<style>
@media print{
  .no-print,.sidebar,.topbar{display:none!important}
  .main-wrap{margin-left:0!important}
  body{background:#fff!important;color:#000!important}
  .card,.info-box{background:#fff!important;border:1px solid #ccc!important}
  .card-body{padding:16px!important}
  .text-green,.text-accent,.text-red,.text-muted,.kv-key,.kv-value,.fw-900,.fw-800,.fw-700{color:#000!important}
  .table>:not(caption)>*>*{color:#000!important;border-bottom-color:#ddd!important}
  .table thead th{background:#f0f0f0!important;color:#555!important}
  .net-banner{background:#f9fafb!important;border:2px solid #ccc!important}
  .kv-row{border-bottom-color:#ddd!important}
  .page-body{padding:0!important}
}
</style>

<?php include '../includes/footer.php'; ?>
