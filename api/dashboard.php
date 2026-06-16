<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$stats    = $conn->query("SELECT * FROM v_dashboard")->fetch_assoc();
$members  = $conn->query("SELECT * FROM v_members ORDER BY id ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$payments = $conn->query("SELECT * FROM v_payments ORDER BY id ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

echo json_encode(['stats' => $stats, 'members' => $members, 'payments' => $payments]);
