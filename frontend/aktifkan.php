<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['peran'] != 'superadmin') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id_karyawan = $_GET['id'];
    
    try {
        // Update status ke aktif
        $stmt = $pdo->prepare("UPDATE pengguna SET status = 'aktif' WHERE id_user = ?");
        $stmt->execute([$id_karyawan]);
        
        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_pengguna, aktivitas) VALUES (?, ?)");
        $aktivitas = "Mengaktifkan kembali karyawan ID: $id_karyawan";
        $stmt->execute([$_SESSION['id_user'], $aktivitas]);
        
        $_SESSION['success'] = "Karyawan berhasil diaktifkan kembali!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal mengaktifkan karyawan: " . $e->getMessage();
    }
    
    header('Location: kelola_karyawan.php');
    exit();
}
?>