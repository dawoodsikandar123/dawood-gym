<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    // Clear token in DB
    $stmt = $conn->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
}

session_destroy();
header('Location: ../login.html');
exit;
