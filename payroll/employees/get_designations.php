<?php
require_once '../includes/config.php';
$db   = getDB();
$dept = intval($_GET['dept'] ?? 0);
$res  = $db->query("SELECT id, title FROM designations WHERE department_id=$dept ORDER BY title");
$out  = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
header('Content-Type: application/json');
echo json_encode($out);
