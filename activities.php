<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$r = $conn->query("SELECT * FROM activities ORDER BY id");
echo json_encode($r->fetch_all(MYSQLI_ASSOC));
