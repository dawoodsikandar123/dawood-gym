<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    $month = (int)($_GET['month'] ?? date('n'));
    $year  = (int)($_GET['year']  ?? date('Y'));
    $date  = $_GET['date'] ?? '';

    if ($date) {
      $stmt = $conn->prepare("SELECT * FROM v_attendance WHERE att_date=? ORDER BY id ASC");
      $stmt->bind_param('s', $date);
    } else {
      $stmt = $conn->prepare("SELECT * FROM v_attendance WHERE MONTH(att_date)=? AND YEAR(att_date)=? ORDER BY id ASC");
      $stmt->bind_param('ii', $month, $year);
    }
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    break;

  case 'POST':
    $d    = json_decode(file_get_contents('php://input'), true);
    $res  = $conn->query("SELECT MAX(CAST(SUBSTRING(attendance_code,2) AS UNSIGNED)) AS mx FROM attendance");
    $row  = $res->fetch_assoc();
    $next = 'A' . str_pad(($row['mx'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    $time = ($d['status'] === 'Present' && $d['check_in_time']) ? $d['check_in_time'] : null;
    $stmt = $conn->prepare("INSERT INTO attendance (attendance_code,member_id,att_date,check_in_time,status) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sisss', $next, $d['member_id'], $d['att_date'], $time, $d['status']);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'PUT':
    $d    = json_decode(file_get_contents('php://input'), true);
    $id   = (int)$d['id'];
    $time = ($d['status'] === 'Present' && $d['check_in_time']) ? $d['check_in_time'] : null;
    $stmt = $conn->prepare("UPDATE attendance SET att_date=?, check_in_time=?, status=? WHERE id=?");
    $stmt->bind_param('sssi', $d['att_date'], $time, $d['status'], $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'DELETE':
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;
}
