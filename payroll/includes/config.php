<?php
// ── Database ──────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'payroll_db');

// ── App ───────────────────────────────────────────────────────
// Change to your local path if needed e.g. http://localhost/payroll/
define('BASE_URL', 'http://localhost/payroll/');
define('APP_NAME', 'PayrollPro');

// ── Timezone ──────────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ── Session ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

// ── DB Connection (singleton) ─────────────────────────────────
function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) die("DB Connection failed: " . $conn->connect_error);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ── Auth ──────────────────────────────────────────────────────
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// ── Settings ──────────────────────────────────────────────────
function getSetting(string $key): string {
    $db  = getDB();
    $key = $db->real_escape_string($key);
    $res = $db->query("SELECT setting_value FROM settings WHERE setting_key='$key' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) return (string)$row['setting_value'];
    return '';
}

// ── Flash messages ────────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── Helpers ───────────────────────────────────────────────────
function formatCurrency(float $amount): string {
    $symbol = getSetting('currency') ?: '₹';
    return $symbol . number_format($amount, 2);
}

function monthName(int $m): string {
    return date('F', mktime(0, 0, 0, $m, 1));
}
