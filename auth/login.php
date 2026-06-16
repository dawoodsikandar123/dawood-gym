<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['error' => 'POST only']));
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) {
    die(json_encode(['success' => false, 'message' => 'Fields required']));
}

// Fetch user
$stmt = $conn->prepare("SELECT id, password_hash, full_name, session_token FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($password, $user['password_hash'])) {
    die(json_encode(['success' => false, 'message' => 'Invalid credentials']));
}

// Single session: generate new token, invalidate old
$token = bin2hex(random_bytes(32));
$stmt2 = $conn->prepare("UPDATE users SET session_token = ?, last_login = NOW() WHERE id = ?");
$stmt2->bind_param('si', $token, $user['id']);
$stmt2->execute();

// Set session
$_SESSION['user_id']   = $user['id'];
$_SESSION['username']  = $username;
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['token']     = $token;

echo json_encode(['success' => true, 'redirect' => '../dashboard.html']);
