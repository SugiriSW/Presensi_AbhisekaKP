<?php
session_start();
require 'config.php';

// Catat log aktivitas sebelum logout
if (isset($_SESSION['id_user'])) {
    // Generate unique ID untuk log
    $log_id = uniqid('log_', true);
    $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_aktivitas, id_user, aktivitas) VALUES (?, ?, 'Logout dari sistem')");
    $stmt->execute([$log_id, $_SESSION['id_user']]);
}

// Hapus semua data session
$_SESSION = array();
session_destroy();

// Redirect ke halaman login
header('Location: login.php');
exit();
?>