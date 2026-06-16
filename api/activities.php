<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$r = $conn->query("SELECT * FROM activities ORDER BY id");
echo json_encode($r->fetch_all(MYSQLI_ASSOC));
