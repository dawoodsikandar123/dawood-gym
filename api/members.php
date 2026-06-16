<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($method) {

  case 'GET':
    if ($action === 'list') {
        $r = $conn->query("SELECT * FROM v_members ORDER BY id ASC");
        echo json_encode($r->fetch_all(MYSQLI_ASSOC));
    } elseif ($action === 'single' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM v_members WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
    }
    break;

  case 'POST':
    $d = json_decode(file_get_contents('php://input'), true);
    $res  = $conn->query("SELECT MAX(CAST(SUBSTRING(member_code,2) AS UNSIGNED)) AS mx FROM members");
    $row  = $res->fetch_assoc();
    $next = 'M' . str_pad(($row['mx'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO members
      (member_code,full_name,phone,emergency_contact,gender,age,address,join_date,activity_id,trainer_id,monthly_fee,payment_status)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $payment_status = 'Unpaid';
    $stmt->bind_param('sssssissiids',
        $next, $d['full_name'], $d['phone'], $d['emergency_contact'],
        $d['gender'], $d['age'], $d['address'], $d['join_date'],
        $d['activity_id'], $d['trainer_id'], $d['monthly_fee'], $payment_status
    );
    $stmt->execute();
    $member_id = $conn->insert_id;

    // Create a corresponding payment record (Unpaid, no date)
    $pres = $conn->query("SELECT MAX(CAST(SUBSTRING(payment_code,2) AS UNSIGNED)) AS mx FROM payments");
    $prow = $pres->fetch_assoc();
    $pnext = 'P' . str_pad(($prow['mx'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    $pstatus = 'Unpaid';
    $pdate = null;
    $pstmt = $conn->prepare("INSERT INTO payments (payment_code,member_id,activity_id,amount,payment_date,status) VALUES (?,?,?,?,?,?)");
    $pstmt->bind_param('siidss', $pnext, $member_id, $d['activity_id'], $d['monthly_fee'], $pdate, $pstatus);
    $pstmt->execute();

    echo json_encode(['success' => true, 'member_code' => $next, 'id' => $member_id]);
    break;

  case 'PUT':
    $d  = json_decode(file_get_contents('php://input'), true);
    $id = (int)($d['id'] ?? 0);
    $stmt = $conn->prepare("UPDATE members SET
      full_name=?, phone=?, emergency_contact=?, gender=?, age=?,
      address=?, join_date=?, activity_id=?, trainer_id=?, monthly_fee=?
      WHERE id=?");
    $stmt->bind_param('ssssissiidi',
        $d['full_name'], $d['phone'], $d['emergency_contact'], $d['gender'],
        $d['age'], $d['address'], $d['join_date'], $d['activity_id'],
        $d['trainer_id'], $d['monthly_fee'], $id
    );
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'DELETE':
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;
}
