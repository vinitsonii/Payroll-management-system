<?php
require_once 'includes/config.php';
if (isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'index.php'); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = ucfirst($user['role']);
            header('Location: ' . BASE_URL . 'index.php'); exit();
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Login — PayrollPro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body style="display:block">

<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-icon"><i class="bi bi-briefcase-fill"></i></div>
      <h1>PayrollPro</h1>
      <p>Sign in to your workspace</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger mb-3">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control"
               placeholder="admin@payroll.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:15px">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <div class="text-center mt-4">
      <small class="text-muted">Default: <strong>admin@payroll.com</strong> / <span class="mono">password</span></small>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
