<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['peran'] != 'superadmin') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id_karyawan = $_GET['id'];
    
    // Jangan biarkan nonaktifkan diri sendiri
    if ($id_karyawan != $_SESSION['id_user']) {
        try {
            $stmt = $pdo->prepare("UPDATE pengguna SET status = 0 WHERE id_user = ?");
            $stmt->execute([$id_karyawan]);
            
            // Catat log
            $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_pengguna, aktivitas) 
                VALUES (?, 'Menonaktifkan karyawan ID: $id_karyawan')");
            $stmt->execute([$_SESSION['id_user']]);
            
            $_SESSION['success'] = "Karyawan berhasil dinonaktifkan!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal menonaktifkan karyawan: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Anda tidak dapat menonaktifkan akun sendiri!";
    }
}

header('Location: kelola_karyawan.php');
exit();
?>