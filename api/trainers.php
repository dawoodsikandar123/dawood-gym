<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    $r = $conn->query("SELECT t.*, GROUP_CONCAT(a.name SEPARATOR ', ') AS specializations
      FROM trainers t
      LEFT JOIN trainer_activities ta ON t.id = ta.trainer_id
      LEFT JOIN activities a ON ta.activity_id = a.id
      GROUP BY t.id ORDER BY t.id");
    echo json_encode($r->fetch_all(MYSQLI_ASSOC));
    break;

  case 'POST':
    $d    = json_decode(file_get_contents('php://input'), true);
    $res  = $conn->query("SELECT MAX(CAST(SUBSTRING(trainer_code,2) AS UNSIGNED)) AS mx FROM trainers");
    $row  = $res->fetch_assoc();
    $next = 'T' . str_pad(($row['mx'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    $stmt = $conn->prepare("INSERT INTO trainers (trainer_code,full_name,phone,gender,experience_yrs,bio,join_date) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssiss', $next, $d['full_name'], $d['phone'], $d['gender'], $d['experience_yrs'], $d['bio'], $d['join_date']);
    $stmt->execute();
    $tid = $conn->insert_id;
    if (!empty($d['activity_ids'])) {
      foreach ($d['activity_ids'] as $aid) {
        $s2 = $conn->prepare("INSERT IGNORE INTO trainer_activities (trainer_id,activity_id) VALUES (?,?)");
        $s2->bind_param('ii', $tid, $aid);
        $s2->execute();
      }
    }
    echo json_encode(['success' => true, 'id' => $tid]);
    break;

  case 'PUT':
    $d  = json_decode(file_get_contents('php://input'), true);
    $id = (int)$d['id'];
    $stmt = $conn->prepare("UPDATE trainers SET full_name=?,phone=?,gender=?,experience_yrs=?,bio=?,join_date=? WHERE id=?");
    $stmt->bind_param('sssissi', $d['full_name'],$d['phone'],$d['gender'],$d['experience_yrs'],$d['bio'],$d['join_date'],$id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'DELETE':
    // Get id from URL or body
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
      $body = json_decode(file_get_contents('php://input'), true);
      $id   = (int)($body['id'] ?? 0);
    }
    if (!$id) { echo json_encode(['success'=>false,'error'=>'No ID']); break; }
    // Delete trainer_activities first
    $s1 = $conn->prepare("DELETE FROM trainer_activities WHERE trainer_id=?");
    $s1->bind_param('i', $id);
    $s1->execute();
    // Then delete trainer
    $s2 = $conn->prepare("DELETE FROM trainers WHERE id=?");
    $s2->bind_param('i', $id);
    $s2->execute();
    echo json_encode(['success' => true]);
    break;
}
