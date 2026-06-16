<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    $r = $conn->query("SELECT * FROM v_payments ORDER BY id ASC");
    echo json_encode($r->fetch_all(MYSQLI_ASSOC));
    break;

  case 'POST':
    $d    = json_decode(file_get_contents('php://input'), true);
    $res  = $conn->query("SELECT MAX(CAST(SUBSTRING(payment_code,2) AS UNSIGNED)) AS mx FROM payments");
    $row  = $res->fetch_assoc();
    $next = 'P' . str_pad(($row['mx'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);

    // Get activity_id from member
    $mstmt = $conn->prepare("SELECT activity_id FROM members WHERE id=?");
    $mstmt->bind_param('i', $d['member_id']);
    $mstmt->execute();
    $mrow = $mstmt->get_result()->fetch_assoc();
    $aid  = $mrow['activity_id'] ?? 1;

    $date = ($d['status']==='Paid' && $d['payment_date']) ? $d['payment_date'] : null;
    $stmt = $conn->prepare("INSERT INTO payments (payment_code,member_id,activity_id,amount,payment_date,status) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('siidss', $next, $d['member_id'], $aid, $d['amount'], $date, $d['status']);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'PUT':
    $d    = json_decode(file_get_contents('php://input'), true);
    $id   = (int)$d['id'];
    $date = ($d['status']==='Paid' && $d['payment_date']) ? $d['payment_date'] : null;
    $stmt = $conn->prepare("UPDATE payments SET payment_date=?, status=? WHERE id=?");
    $stmt->bind_param('ssi', $date, $d['status'], $id);
    $stmt->execute();

    // Sync member's payment_status to match this payment's status
    $pstmt = $conn->prepare("SELECT member_id FROM payments WHERE id=?");
    $pstmt->bind_param('i', $id);
    $pstmt->execute();
    $prow = $pstmt->get_result()->fetch_assoc();
    if ($prow) {
        $mstmt = $conn->prepare("UPDATE members SET payment_status=? WHERE id=?");
        $mstmt->bind_param('si', $d['status'], $prow['member_id']);
        $mstmt->execute();
    }

    echo json_encode(['success' => true]);
    break;
}
