<?php
require_once 'includes/config.php';
requireLogin();
$page_title = 'Company Settings';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['company_name','company_address','company_phone','company_email','currency'];
    foreach ($keys as $k) {
        $val = $db->real_escape_string(trim($_POST[$k] ?? ''));
        $db->query("INSERT INTO settings (setting_key,setting_value) VALUES ('$k','$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
    }
    setFlash('success', 'Settings saved successfully.');
    header('Location: settings.php'); exit();
}

$s = [];
$all = $db->query("SELECT setting_key,setting_value FROM settings");
while ($r = $all->fetch_assoc()) $s[$r['setting_key']] = $r['setting_value'];

include 'includes/header.php';
?>
<script>var BASE_URL='<?= BASE_URL ?>';</script>

<div class="page-header">
  <h2>Company Settings</h2>
</div>

<div class="row justify-content-center">
<div class="col-12 col-lg-7">

  <div class="card mb-4">
    <div class="card-header"><i class="bi bi-building me-2 text-accent"></i>Company Information</div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Company Name</label>
          <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($s['company_name']??'') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Address</label>
          <textarea name="company_address" class="form-control" rows="2"><?= htmlspecialchars($s['company_address']??'') ?></textarea>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-12 col-sm-6 col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="company_phone" class="form-control" value="<?= htmlspecialchars($s['company_phone']??'') ?>">
          </div>
          <div class="col-12 col-sm-6 col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($s['company_email']??'') ?>">
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Currency Symbol</label>
          <input type="text" name="currency" class="form-control" style="max-width:80px" value="<?= htmlspecialchars($s['currency']??'₹') ?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-2"></i>Save Settings</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="bi bi-info-circle me-2 text-accent"></i>System Info</div>
    <div class="card-body">
      <div class="row g-3">
        <?php $info=[['Application','PayrollPro v1.0'],['PHP Version',phpversion()],['Database','MySQL / MariaDB'],['Logged in as',$_SESSION['user_name'].' ('.$_SESSION['user_role'].')'],['Server',php_uname('n')],['Timezone',date_default_timezone_get()]];
        foreach ($info as [$k,$v]): ?>
        <div class="col-12 col-sm-6 col-md-6">
          <div class="info-box">
            <div class="info-box-label"><?= $k ?></div>
            <div class="mono fw-700"><?= htmlspecialchars($v) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>
</div>

<?php include 'includes/footer.php'; ?>
